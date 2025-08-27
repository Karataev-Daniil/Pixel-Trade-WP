<?php
require_once get_template_directory() . '/includes/global/settings.php';
require_once get_template_directory() . '/includes/helpers.php';

require_once get_template_directory() . '/includes/enqueue-assets.php';

require_once get_template_directory() . '/includes/custom-post-types.php';
require_once get_template_directory() . '/includes/user-roles.php';

require_once get_template_directory() . '/includes/user-registration.php';
require_once get_template_directory() . '/includes/user-login.php';
require_once get_template_directory() . '/includes/user-create-product.php';
require_once get_template_directory() . '/includes/user-edit-product.php';
require_once get_template_directory() . '/includes/user-settings.php';
require_once get_template_directory() . '/includes/user-favorites.php';
require_once get_template_directory() . '/includes/user-messenger.php';

require_once get_template_directory() . '/includes/ajax/filter-products.php';

require_once get_template_directory() . '/includes/admin-approval.php';

require_once get_template_directory() . '/includes/translation-product-ai.php';
require_once get_template_directory() . '/includes/translation-messenger-ai.php';


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