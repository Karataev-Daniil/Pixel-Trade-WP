<?php
// user-roles.php
function kayo_register_custom_roles() {
    add_role('seller', 'Seller', [
        'read' => true,
        'edit_posts' => true,
        'edit_products' => true,
        'edit_published_products' => true,
        'delete_products' => true,
        'publish_products' => true,
        'upload_files' => true,
    ]);

    add_role('buyer', 'Buyer', [
        'read' => true,
    ]);
}
add_action('init', 'kayo_register_custom_roles');

function kayo_block_seller_admin_access() {
    if (
        is_admin() &&
        !defined('DOING_AJAX') &&
        current_user_can('seller') &&
        !current_user_can('manage_options')
    ) {
        wp_redirect(home_url());
        exit;
    }
}
add_action('admin_init', 'kayo_block_seller_admin_access');

function kayo_hide_admin_bar_for_sellers() {
    if (current_user_can('seller') && !current_user_can('manage_options')) {
        show_admin_bar(false);
    }
}
add_action('after_setup_theme', 'kayo_hide_admin_bar_for_sellers');

function kayo_remove_custom_roles() {
    remove_role('seller');
    remove_role('buyer');
}
register_deactivation_hook(__FILE__, 'kayo_remove_custom_roles');

function add_product_caps() {
    $role = get_role('administrator');
    if ($role && !$role->has_cap('edit_product')) {
        $role->add_cap('edit_product');
        $role->add_cap('read_product');
        $role->add_cap('delete_product');
        $role->add_cap('edit_products');
        $role->add_cap('edit_others_products');
        $role->add_cap('publish_products');
        $role->add_cap('read_private_products');
        $role->add_cap('delete_products');
        $role->add_cap('delete_private_products');
        $role->add_cap('delete_published_products');
        $role->add_cap('delete_others_products');
        $role->add_cap('edit_private_products');
        $role->add_cap('edit_published_products');
        $role->add_cap('manage_product_categories');
    }
}
add_action('admin_init', 'add_product_caps');
