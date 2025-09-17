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

function get_recommended_products_for_user($limit = 36, $offset = 0) {
    global $wpdb;

    $user_id = is_user_logged_in() ? get_current_user_id() : null;
    $ip = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'];

    $where = $user_id
        ? $wpdb->prepare('user_id = %d', $user_id)
        : $wpdb->prepare('ip_address = %s', $ip);

    $viewed = $wpdb->get_results("
        SELECT product_id
        FROM {$wpdb->prefix}product_views
        WHERE $where
        ORDER BY viewed_at DESC
        LIMIT 50
    ");
    $viewed_ids = wp_list_pluck($viewed, 'product_id');
    $exclude_ids = array_slice($viewed_ids, 0, 10 + $offset);

    $cats = [];
    foreach ($viewed_ids as $pid) {
        $terms = wp_get_post_terms($pid, 'product_cat');
        if (is_array($terms)) {
            foreach ($terms as $t) {
                $cats[$t->term_id] = ($cats[$t->term_id] ?? 0) + 1;
            }
        }
    }
    arsort($cats);
    $cat_ids = array_keys($cats);

    $recommended = [];
    if ($cat_ids) {
        $query = new WP_Query([
            'post_type' => 'products',
            'posts_per_page' => $limit,
            'offset' => $offset,
            'post__not_in' => $exclude_ids,
            'tax_query' => [[
                'taxonomy' => 'product_cat',
                'field' => 'term_id',
                'terms' => $cat_ids,
            ]],
            'orderby' => 'date',
            'order' => 'DESC'
        ]);
        if ($query->have_posts()) {
            $recommended = wp_list_pluck($query->posts, 'ID');
        }
    }

    $remaining = $limit - count($recommended);
    if ($remaining > 0) {
        $exclude_ids = array_merge($exclude_ids, $recommended);

        $popular_ids = $wpdb->get_col($wpdb->prepare("
            SELECT product_id
            FROM {$wpdb->prefix}product_daily_views
            WHERE view_date >= DATE_SUB(NOW(), INTERVAL 7 DAY)
            AND product_id NOT IN (" . implode(',', array_map('intval', $exclude_ids ?: [0])) . ")
            GROUP BY product_id
            ORDER BY SUM(views) DESC
            LIMIT %d
        ", $remaining * 3));

        shuffle($popular_ids);
        $recommended = array_merge($recommended, array_slice($popular_ids, 0, $remaining));
    }

    $remaining = $limit - count($recommended);
    if ($remaining > 0) {
        $exclude_ids = array_merge($exclude_ids, $recommended);

        $random_query = new WP_Query([
            'post_type' => 'products',
            'posts_per_page' => $remaining,
            'post__not_in' => $exclude_ids,
            'orderby' => 'rand'
        ]);

        if ($random_query->have_posts()) {
            $recommended = array_merge($recommended, wp_list_pluck($random_query->posts, 'ID'));
        }
    }

    return new WP_Query([
        'post_type' => 'products',
        'post__in' => $recommended,
        'orderby' => 'post__in',
        'posts_per_page' => $limit
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
