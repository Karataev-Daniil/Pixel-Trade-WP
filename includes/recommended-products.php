<?php
add_action('wp_ajax_load_more_favorites', 'load_more_favorites_callback');
add_action('wp_ajax_nopriv_load_more_favorites', 'load_more_favorites_callback');

/**
 * Улучшенные рекомендации продуктов для пользователя.
 *
 * Возвращает WP_Query с постами в порядке убывания score (первый — наиболее релевантный).
 *
 * @param int $limit  Кол-во возвращаемых постов (по умолчанию 36)
 * @param int $offset Пагинация/сдвиг (влияет на то, какие просмотренные товары исключаются)
 * @return WP_Query
 */
function get_recommended_products_for_user($limit = 36, $offset = 0) {
    global $wpdb;

    $limit = max(1, (int)$limit);
    $offset = max(0, (int)$offset);
    $post_type = 'products';

    $user_key = is_user_logged_in() 
        ? 'user_' . get_current_user_id() 
        : 'ip_' . md5( $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] );

    $cache_key = "recommended_products_{$user_key}_{$post_type}_{$limit}_{$offset}";
    $cached_ids = get_transient($cache_key);

    if (is_array($cached_ids) && count($cached_ids) >= 1) {
        return new WP_Query([
            'post_type' => $post_type,
            'post__in' => array_slice($cached_ids, 0, $limit),
            'orderby' => 'post__in',
            'posts_per_page' => $limit,
            'post_status' => 'publish',
            'no_found_rows' => true,
        ]);
    }

    // ===== 1) просмотренные продукты =====
    $user_id = is_user_logged_in() ? get_current_user_id() : 0;
    $ip_raw = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '';
    if (strpos($ip_raw, ',') !== false) {
        $ip_raw = trim(explode(',', $ip_raw)[0]);
    }
    $ip = filter_var($ip_raw, FILTER_VALIDATE_IP) ? $ip_raw : '';

    if ($user_id) {
        $viewed_ids = $wpdb->get_col($wpdb->prepare(
            "SELECT product_id FROM {$wpdb->prefix}product_views WHERE user_id = %d ORDER BY viewed_at DESC LIMIT %d",
            $user_id, 50
        ));
    } else {
        $viewed_ids = $ip
            ? $wpdb->get_col($wpdb->prepare(
                "SELECT product_id FROM {$wpdb->prefix}product_views WHERE ip_address = %s ORDER BY viewed_at DESC LIMIT %d",
                $ip, 50
            ))
            : [];
    }
    $viewed_ids = array_map('intval', array_filter((array)$viewed_ids));

    $exclude_ids = array_slice($viewed_ids, 0, 10 + $offset);
    $exclude_ids = array_map('intval', $exclude_ids);

    // ===== 2) веса категорий =====
    $cats = [];
    foreach ($viewed_ids as $pid) {
        $terms = wp_get_post_terms($pid, 'product_cat', ['fields' => 'ids']);
        if (is_array($terms)) {
            foreach ($terms as $t) {
                $cats[(int)$t] = ($cats[(int)$t] ?? 0) + 1;
            }
        }
    }

    arsort($cats);
    $cat_ids = array_keys($cats);

    $W_CAT = 1000;
    $W_POP = 200;
    $W_RANDOM = 5;

    $candidates = [];

    // ===== 3) кандидаты из категорий =====
    if (!empty($cat_ids)) {
        $cat_query_limit = min(300, max($limit * 4, 60));
        $cat_args = [
            'post_type' => $post_type,
            'posts_per_page' => $cat_query_limit,
            'post_status' => 'publish',
            'post__not_in' => $exclude_ids,
            'fields' => 'ids',
            'no_found_rows' => true,
            'tax_query' => [[
                'taxonomy' => 'product_cat',
                'field' => 'term_id',
                'terms' => $cat_ids,
                'operator' => 'IN'
            ]]
        ];
        $cat_q = new WP_Query($cat_args);
        $cat_ids_pool = $cat_q->have_posts() ? (array) $cat_q->posts : [];

        foreach ($cat_ids_pool as $candidate_id) {
            $tids = wp_get_post_terms($candidate_id, 'product_cat', ['fields' => 'ids']);
            $score_cat = 0;
            if (is_array($tids)) {
                foreach ($tids as $tid) {
                    if (isset($cats[$tid])) $score_cat += $cats[$tid];
                }
            }
            if ($score_cat > 0) {
                $post_date = get_post_time('U', true, $candidate_id);
                $age_days = max(1, floor( (time() - $post_date) / DAY_IN_SECONDS ));
                $freshness_bonus = max(0, 30 - $age_days);
                $score = ($score_cat * $W_CAT) + $freshness_bonus;
                $candidates[(int)$candidate_id] = max($candidates[(int)$candidate_id] ?? 0, (int)$score);
            }
        }
        wp_reset_postdata();
    }

    // ===== 4) популярные товары =====
    $desired_additional = max(0, $limit - count($candidates));
    if ($desired_additional > 0) {
        $limit_pop = max($desired_additional * 3, 20);
        $not_in_sql = $exclude_ids ? implode(',', array_map('intval', $exclude_ids)) : '0';
        $sql = "
            SELECT product_id, SUM(views) as total_views
            FROM {$wpdb->prefix}product_daily_views
            WHERE view_date >= DATE_SUB(NOW(), INTERVAL 7 DAY)
            AND product_id NOT IN ({$not_in_sql})
            GROUP BY product_id
            ORDER BY total_views DESC
            LIMIT %d
        ";
        $popular_rows = $wpdb->get_results( $wpdb->prepare($sql, $limit_pop) );
        $max_views = 0;
        foreach ($popular_rows as $r) $max_views = max($max_views, (int)$r->total_views);

        foreach ($popular_rows as $r) {
            $pid = (int)$r->product_id;
            if ($pid <= 0 || get_post_status($pid) !== 'publish') continue;
            $pop_score = $max_views ? (int)(($r->total_views / $max_views) * $W_POP) : $W_POP;
            $candidates[$pid] = max($candidates[$pid] ?? 0, $pop_score);
            $candidates[$pid] += $pop_score;
        }
    }

    // ===== 5) случайные товары =====
    $remaining = $limit - count($candidates);
    if ($remaining > 0) {
        $exclude_for_random = array_unique(array_merge($exclude_ids, array_keys($candidates)));
        $rand_pool = min($remaining * 8, 200);
        $rand_args = [
            'post_type' => $post_type,
            'posts_per_page' => $rand_pool,
            'post_status' => 'publish',
            'post__not_in' => $exclude_for_random,
            'orderby' => 'rand',
            'fields' => 'ids',
            'no_found_rows' => true,
        ];
        $rand_q = new WP_Query($rand_args);
        $rand_ids = $rand_q->have_posts() ? (array)$rand_q->posts : [];
        foreach ($rand_ids as $rid) {
            $candidates[(int)$rid] = ($candidates[(int)$rid] ?? 0) + rand(1, $W_RANDOM);
        }
        wp_reset_postdata();
    }

    // ===== 6) сортировка и возврат =====
    if (empty($candidates)) return new WP_Query(['post_type'=>$post_type,'posts_per_page'=>0,'no_found_rows'=>true]);

    arsort($candidates);
    $recommended_ids = array_slice(array_map('intval', array_keys($candidates)), 0, $limit);

    set_transient($cache_key, $recommended_ids, MINUTE_IN_SECONDS * 5);

    return new WP_Query([
        'post_type' => $post_type,
        'post__in' => $recommended_ids,
        'orderby' => 'post__in',
        'posts_per_page' => $limit,
        'post_status' => 'publish',
    ]);
}

add_action('wp_ajax_load_more_products', 'load_more_products_ajax');
add_action('wp_ajax_nopriv_load_more_products', 'load_more_products_ajax');

function load_more_products_ajax() {
    $offset = intval($_POST['offset']);
    $per_page = wp_is_mobile() ? 12 : 36;

    $query = get_recommended_products_for_user($per_page, $offset);

    if ($query->have_posts()) {
        while ($query->have_posts()) : $query->the_post();
            get_template_part('template-parts/product/card');
        endwhile;
    }

    wp_die();
}

add_action('save_post_products', function($post_id, $post, $update){
    $author_id = $post->post_author;
    if ($author_id) my_products_clear_cache($author_id);
}, 10, 3);
