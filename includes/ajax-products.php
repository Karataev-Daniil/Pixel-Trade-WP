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