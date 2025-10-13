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

    $results = [
        'categories'    => [],
        'users'         => [],
        'popular_queries'=> [],
        'products_html' => ''
    ];

    $cat_terms = get_terms([
        'taxonomy'   => 'product_cat',
        'hide_empty' => false,
        'name__like' => $query,
        'number'     => 5
    ]);

    if (!is_wp_error($cat_terms) && $cat_terms) {
        foreach ($cat_terms as $term) {
            $results['categories'][] = [
                'id'   => $term->term_id,
                'name' => $term->name,
                'link' => get_term_link($term)
            ];
        }
    }

    $user_ids = get_users([
        'search'         => '*' . $query . '*',
        'search_columns' => ['user_login', 'display_name'],
        'number'         => 5
    ]);

    foreach ($user_ids as $user) {
        $results['users'][] = [
            'id'   => $user->ID,
            'name' => $user->display_name,
            'link' => '/user/' . $user->user_login . '/'
        ];
    }

    $results['popular_queries'] = [];

    $product_ids = get_posts([
        'post_type'      => 'products',
        's'              => $query,
        'posts_per_page' => 5,
        'post_status'    => 'publish',
        'fields'         => 'ids'
    ]);

    if ($product_ids) {
        $args = [
            'post_type'      => 'products',
            'post__in'       => $product_ids,
            'posts_per_page' => 5,
            'orderby'        => 'post__in'
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


add_action('wp_ajax_load_more_products', 'load_more_products_ajax');
add_action('wp_ajax_nopriv_load_more_products', 'load_more_products_ajax');

function load_more_products_ajax() {
    $offset = intval($_POST['offset'] ?? 0);
    $per_page = wp_is_mobile() ? 12 : 35;
    $cat_id = intval($_POST['cat_id'] ?? 0);

    $args = [
        'post_type' => 'products',
        'posts_per_page' => $per_page,
        'offset' => $offset,
    ];

    if ($cat_id) {
        $args['tax_query'] = [
            [
                'taxonomy' => 'product_cat',
                'field' => 'term_id',
                'terms' => $cat_id,
            ]
        ];
    }

    $query = new WP_Query($args);

    if ($query->have_posts()) {
        while ($query->have_posts()) : $query->the_post();
            get_template_part('template-parts/product/card');
        endwhile;
    }

    wp_reset_postdata();
    wp_die();
}
