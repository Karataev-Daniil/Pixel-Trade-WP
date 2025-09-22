<?php
// Helpers / Global
require_once get_template_directory() . '/includes/global/settings.php';
require_once get_template_directory() . '/includes/helpers.php';
require_once get_template_directory() . '/includes/custom-seo.php';
require_once get_template_directory() . '/includes/enqueue-assets.php';
require_once get_template_directory() . '/includes/image-sizes.php';

// Post Types & Roles
require_once get_template_directory() . '/includes/custom-post-types.php';
require_once get_template_directory() . '/includes/user-roles.php';
require_once get_template_directory() . '/includes/product-helpers.php';

// User Actions
require_once get_template_directory() . '/includes/user-registration.php';
require_once get_template_directory() . '/includes/user-login.php';
require_once get_template_directory() . '/includes/user-create-product.php';
require_once get_template_directory() . '/includes/user-edit-product.php';
require_once get_template_directory() . '/includes/user-delete-product.php';
require_once get_template_directory() . '/includes/user-settings.php';
require_once get_template_directory() . '/includes/user-favorites.php';
require_once get_template_directory() . '/includes/user-messenger/user-messenger.php';
require_once get_template_directory() . '/includes/user-products-dashboard.php';

// Admin / Moderation
require_once get_template_directory() . '/includes/admin-approval.php';

// AI / Translation
require_once get_template_directory() . '/includes/translation-product-ai.php';

// Language Redirect / Handling
require_once get_template_directory() . '/includes/language-redirect.php';

// AJAX Handlers
require_once get_template_directory() . '/includes/ajax-products.php';

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
    $post_type = 'products'; // совпадает с вашим кодом

    // кешируем список id рекомендаций (массив int)
    $user_key = is_user_logged_in() ? 'user_' . get_current_user_id() : 'ip_' . md5( ($_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR']) );
    $cache_key = "recommended_products_{$user_key}_{$post_type}_{$limit}_{$offset}";
    $cached_ids = get_transient($cache_key);
    if (is_array($cached_ids) && count($cached_ids) >= 1) {
        // Возвращаем WP_Query, сохраняя порядок
        return new WP_Query([
            'post_type' => $post_type,
            'post__in' => array_slice($cached_ids, 0, $limit),
            'orderby' => 'post__in',
            'posts_per_page' => $limit,
            'post_status' => 'publish',
            'no_found_rows' => true,
        ]);
    }

    // ==== 1) собираем просмотренные продукты для этого пользователя/по IP ====
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

    // 2) считаем веса категорий по просмотрам (чтобы понять интересы)
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

    // Параметры весов 
    $W_CAT = 1000;   // вес за совпадение по категории
    $W_POP = 200;    // вес за популярность
    $W_RANDOM = 5;   // низкий вес для рандомных

    $candidates = [];

    // 3) кандидаты из релевантных категорий
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
                    if (isset($cats[$tid])) {
                        $score_cat += $cats[$tid];
                    }
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

    // 4) популярные товары (за последние 7 дней) — заполняет оставшееся
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
        foreach ($popular_rows as $r) {
            $max_views = max($max_views, (int)$r->total_views);
        }
        $rank = 0;
        foreach ($popular_rows as $r) {
            $rank++;
            $pid = (int)$r->product_id;
            if ($pid <= 0) continue;
            if (get_post_status($pid) !== 'publish') continue;
            $pop_score = $max_views ? (int) ( ($r->total_views / $max_views) * $W_POP ) : $W_POP;
            $candidates[$pid] = max($candidates[$pid] ?? 0, $candidates[$pid] ?? 0);
            $candidates[$pid] = ($candidates[$pid] ?? 0) + $pop_score;
        }
    }

    // 5) если всё ещё мало — добавляем случайные товары (low-priority)
    $remaining = $limit - count($candidates);
    if ($remaining > 0) {
        $exclude_for_random = array_unique(array_merge($exclude_ids, array_keys($candidates)));
        $rand_pool = min( $remaining * 8, 200 );
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

    // 6) сортируем кандидатов по score и берём top $limit 
    if (empty($candidates)) {
        return new WP_Query([
            'post_type' => $post_type,
            'posts_per_page' => 0,
            'no_found_rows' => true,
        ]);
    }

    arsort($candidates);
    $recommended_ids = array_map('intval', array_keys($candidates));

    $recommended_ids = array_slice($recommended_ids, 0, $limit);

    set_transient($cache_key, $recommended_ids, MINUTE_IN_SECONDS * 5);

    return new WP_Query([
        'post_type' => $post_type,
        'post__in' => $recommended_ids,
        'orderby' => 'post__in',
        'posts_per_page' => $limit,
        'post_status' => 'publish',
        'no_found_rows' => true,
    ]);
}
add_action('wp_ajax_load_more_products', 'load_more_products_ajax');
add_action('wp_ajax_nopriv_load_more_products', 'load_more_products_ajax');

function load_more_products_ajax() {
    $offset = intval($_POST['offset']);
    $limit = 36;

    $query = get_recommended_products_for_user($limit, $offset);

    if ($query->have_posts()) {
        while ($query->have_posts()) : $query->the_post();
            echo '<div class="product-item">' . get_the_title() . '</div>';
        endwhile;
    }

    wp_die();
}

add_action('save_post_products', function($post_id, $post, $update){
    $author_id = $post->post_author;
    if ($author_id) {
        my_products_clear_cache($author_id);
    }
}, 10, 3);

function get_current_category_features() {
    $term = get_queried_object();
    if (!$term) return [];
    $all_features = get_product_category_features();
    return $all_features[$term->term_id] ?? [];
}

add_action('wp_ajax_load_more_products', 'load_more_products');
add_action('wp_ajax_nopriv_load_more_products', 'load_more_products');

function load_more_products() {
    $cat_id = intval($_POST['cat_id'] ?? 0);
    $paged  = intval($_POST['paged'] ?? 1);

    if (!$cat_id) {
        echo '<p>Категория не указана</p>';
        wp_die();
    }

    $args = [
        'post_type'      => 'products',
        'posts_per_page' => 24,
        'paged'          => $paged,
        'tax_query'      => [
            [
                'taxonomy' => 'product_cat',
                'field'    => 'term_id',
                'terms'    => $cat_id,
            ]
        ],
    ];

    // Получаем фильтры из POST-запроса
    $features = get_product_category_features()[$cat_id] ?? [];
    $meta_query = [];

    if (!empty($features) && is_array($features)) {
        foreach ($features as $key => $feature) {
            $value = trim($_POST[$key] ?? '');
            if ($value !== '') { // добавляем только выбранные фильтры
                $meta_query[] = [
                    'key'     => '_' . $key,
                    'value'   => sanitize_text_field($value),
                    'compare' => 'LIKE',
                ];
            }
        }
    }

    if (!empty($meta_query)) {
        $meta_query['relation'] = 'AND';
        $args['meta_query'] = $meta_query;
    }

    $query = new WP_Query($args);

    if ($query->have_posts()) {
        while ($query->have_posts()) : $query->the_post();
            get_template_part('template-parts/product/card');
        endwhile;
    } else {
        echo '<p>Ничего не найдено</p>';
    }

    echo '<span style="display:none" data-max="' . $query->max_num_pages . '"></span>';

    wp_reset_postdata();
    wp_die();
}


// Получаем динамические поля по категориям
// add_action('wp_ajax_get_category_features', 'ajax_get_category_features');
// add_action('wp_ajax_nopriv_get_category_features', 'ajax_get_category_features');

// function ajax_get_category_features() {
//     global $wpdb;
//     $category_ids = isset($_GET['categories']) ? explode(',', $_GET['categories']) : [];
//     $category_ids = array_map('intval', $category_ids);
//     $result = [];

//     if ($category_ids) {
//         $features_table = $wpdb->prefix . 'features';
//         $options_table  = $wpdb->prefix . 'feature_options';

//         foreach ($category_ids as $cat_id) {
//             $fields = $wpdb->get_results($wpdb->prepare(
//                 "SELECT id, label_ru, label_en, label_ro, type FROM $features_table WHERE category_id = %d",
//                 $cat_id
//             ), ARRAY_A);

//             if (!$fields) continue;

//             foreach ($fields as $field) {
//                 $fieldData = [
//                     'label' => [
//                         'ru' => $field['label_ru'],
//                         'en' => $field['label_en'],
//                         'ro' => $field['label_ro']
//                     ]
//                 ];

//                 if ($field['type'] === 'select') {
//                     $options = $wpdb->get_results($wpdb->prepare(
//                         "SELECT value_ru, value_en, value_ro FROM $options_table WHERE feature_id = %d ORDER BY id ASC",
//                         $field['id']
//                     ), ARRAY_A);

//                     $fieldData['options'] = array_map(function($opt) {
//                         return [
//                             'ru' => $opt['value_ru'],
//                             'en' => $opt['value_en'],
//                             'ro' => $opt['value_ro']
//                         ];
//                     }, $options);
//                 }

//                 $result[$cat_id]['_' . sanitize_title($field['label_ru'])] = $fieldData;
//             }
//         }
//     }

//     wp_send_json($result);
// }


// AJAX для загрузки характеристик категории
add_action('wp_ajax_get_category_features', 'ajax_get_category_features');
add_action('wp_ajax_nopriv_get_category_features', 'ajax_get_category_features');

function ajax_get_category_features() {
    global $wpdb;

    $category_id = intval($_GET['category_id'] ?? 0);
    if (!$category_id) {
        wp_send_json_error(['message' => 'No category_id']);
    }

    // Загружаем все фичи по категории
    $features = $wpdb->get_results($wpdb->prepare("
        SELECT id, `key`, label_ru, label_en, label_ro
        FROM wp_features
        WHERE category_id = %d
        ORDER BY id ASC
    ", $category_id), ARRAY_A);

    $result = [];

    foreach ($features as $feature) {
        $options = $wpdb->get_results($wpdb->prepare("
            SELECT value_ru, value_en, value_ro
            FROM wp_feature_options
            WHERE feature_id = %d
            ORDER BY id ASC
        ", $feature['id']), ARRAY_A);

        $result[$feature['key']] = [
            'label' => [
                'ru' => $feature['label_ru'],
                'en' => $feature['label_en'],
                'ro' => $feature['label_ro'],
            ],
            'options' => $options
        ];
    }

    wp_send_json_success($result);
}

function my_user_profile_rewrite() {
    add_rewrite_rule(
    '^([a-z]{2})/account/([^/]+)/?$',
    'index.php?lang=$matches[1]&user_profile=$matches[2]',
    'top'
);

}
add_action('init', 'my_user_profile_rewrite', 10);

function my_user_profile_query_vars($vars) {
    $vars[] = 'user_profile';
    $vars[] = 'lang';
    return $vars;
}
add_filter('query_vars', 'my_user_profile_query_vars');

function my_user_profile_template($template) {
    $user_nicename = get_query_var('user_profile');
    if ($user_nicename) {
        $user = get_user_by('slug', $user_nicename);
        if ($user) {
            $new_template = locate_template(['page-template/page-user-profile.php']);
            if ($new_template) return $new_template;
        } else {
            global $wp_query;
            $wp_query->set_404();
            status_header(404);
            return get_404_template();
        }
    }
    return $template;
}
add_filter('template_include', 'my_user_profile_template');