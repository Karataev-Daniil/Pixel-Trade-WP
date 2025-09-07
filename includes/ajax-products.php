<?php
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