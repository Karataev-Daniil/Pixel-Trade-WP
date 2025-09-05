<?php

require_once get_template_directory() . '/includes/global/settings.php';
require_once get_template_directory() . '/includes/helpers.php';

require_once get_template_directory() . '/includes/enqueue-assets.php';

require_once get_template_directory() . '/includes/custom-post-types.php';
require_once get_template_directory() . '/includes/user-roles.php';
require_once get_template_directory() . '/includes/product-helpers.php';

require_once get_template_directory() . '/includes/user-registration.php';
require_once get_template_directory() . '/includes/user-login.php';
require_once get_template_directory() . '/includes/user-create-product.php';
require_once get_template_directory() . '/includes/user-edit-product.php';
require_once get_template_directory() . '/includes/user-settings.php';
require_once get_template_directory() . '/includes/user-favorites.php';
require_once get_template_directory() . '/includes/user-messenger/user-messenger.php';

require_once get_template_directory() . '/includes/admin-approval.php';

require_once get_template_directory() . '/includes/translation-product-ai.php';


add_action('wp_ajax_get_subcategories', 'get_subcategories_ajax');
add_action('wp_ajax_nopriv_get_subcategories', 'get_subcategories_ajax');

function get_subcategories_ajax() {
    $parent_id = isset($_GET['parent']) ? intval($_GET['parent']) : 0;

    $terms = get_terms([
        'taxonomy'   => 'product_cat',
        'hide_empty' => false,
        'parent'     => $parent_id,
    ]);

    $result = [];
    foreach ($terms as $term) {
        $result[] = [
            'term_id' => $term->term_id,
            'name'    => [
                'ru' => $term->name,
                'en' => get_term_meta($term->term_id, 'translation_en', true) ?: $term->name,
                'ro' => get_term_meta($term->term_id, 'translation_ro', true) ?: $term->name,
            ],
        ];
    }

    wp_send_json($result);
}


function sort_categories_by_hierarchy($categories) {
    if (empty($categories)) return [];

    $categories_by_id = [];
    foreach ($categories as $term) {
        $categories_by_id[$term->term_id] = $term;
    }

    $sorted = [];

    $leaf = null;
    foreach ($categories as $term) {
        if (!array_filter($categories, fn($t) => $t->parent === $term->term_id)) {
            $leaf = $term;
            break;
        }
    }

    while ($leaf) {
        $sorted[] = $leaf->term_id;
        $leaf = isset($categories_by_id[$leaf->parent]) ? $categories_by_id[$leaf->parent] : null;
    }

    return array_reverse($sorted);
}

add_action('wp_ajax_load_more_products', 'load_more_products_ajax');
add_action('wp_ajax_nopriv_load_more_products', 'load_more_products_ajax');

function load_more_products_ajax() {
    $paged = isset($_GET['paged']) ? intval($_GET['paged']) : 1;
    $cat_id = isset($_GET['cat_id']) ? intval($_GET['cat_id']) : 0;

    $args = [
        'post_type' => 'product',
        'posts_per_page' => 24,
        'paged' => $paged,
        'tax_query' => [
            [
                'taxonomy' => 'product_cat',
                'field' => 'term_id',
                'terms' => $cat_id,
            ]
        ]
    ];

    $products_query = new WP_Query($args);

    if ($products_query->have_posts()) :
        while ($products_query->have_posts()) : $products_query->the_post();
            get_template_part('template-parts/product/card');
        endwhile;
    endif;
    wp_reset_postdata();
    wp_die();
}

function resize_image_url($image, $width = 150, $height = 150) {
    if (is_numeric($image)) {
        $image = wp_get_attachment_url($image);
    }

    if (!$image) return '';

    $path_parts = pathinfo($image);
    return $path_parts['dirname'] . '/' . $path_parts['filename'] . '-' . $width . 'x' . $height . '.' . $path_parts['extension'];
}

add_image_size('small-thumb', 50, 50, true);
add_image_size('medium-thumb', 270, 200, true);