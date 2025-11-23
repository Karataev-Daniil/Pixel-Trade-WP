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
require_once get_template_directory() . '/includes/user-settings.php';
require_once get_template_directory() . '/includes/user-create-product.php';
require_once get_template_directory() . '/includes/user-edit-product.php';
require_once get_template_directory() . '/includes/user-delete-product.php';
require_once get_template_directory() . '/includes/user-favorites.php';
require_once get_template_directory() . '/includes/user-products-dashboard.php';
require_once get_template_directory() . '/includes/user-messenger/user-messenger.php';

// Admin / Moderation
require_once get_template_directory() . '/includes/admin-approval.php';

// AI / Translation
require_once get_template_directory() . '/includes/translation-product-ai.php';

// Language Redirect / Handling
require_once get_template_directory() . '/includes/language-redirect.php';

// AJAX Handlers
require_once get_template_directory() . '/includes/ajax-products.php';

// Recommended Products (Homepage)
require_once get_template_directory() . '/includes/recommended-products.php';

// User Public Profile
require_once get_template_directory() . '/includes/user-public-profile.php';

// ajax chatbot
require_once get_template_directory() . '/includes/ajax-chatbot.php';

add_action('wp_ajax_search_products', 'ajax_search_products');
add_action('wp_ajax_nopriv_search_products', 'ajax_search_products');

function ajax_search_products() {
    $query = trim(sanitize_text_field($_GET['q'] ?? ''));
    if (!$query) {
        wp_send_json_success([]);
    }

    global $wpdb;

    $results = [
        'categories'     => [],
        'users'          => [],
        'popular_queries'=> [],
        'products_html'  => ''
    ];

    $all_terms = get_terms([
        'taxonomy'   => 'product_cat',
        'hide_empty' => false,
        'number'     => 100,
    ]);

    $count_cats = 0;
    foreach ($all_terms as $term) {
        $meta_ro = get_term_meta($term->term_id, 'translation_ro', true);
        $meta_en = get_term_meta($term->term_id, 'translation_en', true);

        if (
            stripos($term->name, $query) !== false ||
            stripos((string)$meta_ro, $query) !== false ||
            stripos((string)$meta_en, $query) !== false
        ) {
            $results['categories'][] = [
                'id'   => $term->term_id,
                'name' => $term->name,
                'link' => get_term_link($term)
            ];
            $count_cats++;
            if ($count_cats >= 10) break;
        }
    }

    $user_ids = get_users([
        'search'         => '*' . $query . '*',
        'search_columns' => ['user_login', 'display_name'],
        'number'         => 20
    ]);

    $count_users = 0;
    foreach ($user_ids as $user) {
        if ($count_users >= 10) break;
        $results['users'][] = [
            'id'   => $user->ID,
            'name' => $user->display_name,
            'link' => '/user/' . $user->user_login . '/'
        ];
        $count_users++;
    }

    $like = '%' . $wpdb->esc_like($query) . '%';

    $sql = "
        SELECT DISTINCT p.ID
        FROM {$wpdb->posts} p
        LEFT JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
        WHERE p.post_type = 'products'
          AND p.post_status = 'publish'
          AND (
              p.post_title LIKE %s
              OR (pm.meta_key IN ('_title_ro','_title_en')
                  AND pm.meta_value LIKE %s)
          )
        LIMIT 10
    ";

    $product_ids = $wpdb->get_col($wpdb->prepare($sql, $like, $like));

    if ($product_ids) {
        $args = [
            'post_type'      => 'products',
            'post__in'       => $product_ids,
            'orderby'        => 'post__in',
            'posts_per_page' => 10,
        ];

        $query_posts = new WP_Query($args);

        ob_start();
        if ($query_posts->have_posts()): ?>
            <ul class="products-list-row">
                <?php while ($query_posts->have_posts()): $query_posts->the_post();
                    get_template_part('template-parts/product/card-row');
                endwhile; ?>
            </ul>
        <?php endif;
        wp_reset_postdata();

        $results['products_html'] = ob_get_clean();
    }

    wp_send_json_success($results);
}


add_action('wp_ajax_load_more_products', 'ajax_load_more_products');
add_action('wp_ajax_nopriv_load_more_products', 'ajax_load_more_products');

function ajax_load_more_products() {
    global $wpdb;

    $page = intval($_POST['page'] ?? 1);
    $cat_id = intval($_POST['cat_id'] ?? 0);
    $per_page = intval($_POST['per_page'] ?? 20);

    $meta_query = ['relation' => 'AND'];

    // Build meta_query dynamically based on selected filters
    foreach ($_POST as $key => $value) {
        if (in_array($key, ['action', 'page', 'cat_id', 'per_page']) || empty($value)) continue;
        $meta_query[] = [
            'key' => sanitize_text_field($key),
            'value' => sanitize_text_field($value),
            'compare' => '=',
        ];
    }

    $args = [
        'post_type' => 'products',
        'posts_per_page' => $per_page,
        'paged' => $page,
        'tax_query' => [
            [
                'taxonomy' => 'product_cat',
                'field' => 'term_id',
                'terms' => $cat_id,
            ]
        ],
        'meta_query' => count($meta_query) > 1 ? $meta_query : [],
    ];

    $query = new WP_Query($args);

    if ($query->have_posts()) {
        while ($query->have_posts()) : $query->the_post();
            get_template_part('template-parts/product/card');
        endwhile;
    }

    wp_reset_postdata();
    wp_die();
}
