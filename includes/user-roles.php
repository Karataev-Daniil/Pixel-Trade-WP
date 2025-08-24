<?php
// user-roles.php
function remove_custom_roles() {
    remove_role('seller');
    remove_role('buyer');
}
register_deactivation_hook(__FILE__, 'remove_custom_roles');

function register_custom_roles() {
    // Seller
    if (!get_role('seller')) {
        add_role('seller', 'Seller', [
            'read' => true,
            'edit_posts' => true,
            'publish_posts' => true,
            'upload_files' => true,
        ]);
    }

    if (!get_role('buyer')) {
        add_role('buyer', 'Buyer', [
            'read' => true,
        ]);
    }
}
add_action('init', 'register_custom_roles');

function add_product_caps_to_admin() {
    $role = get_role('administrator');
    if ($role) {
        $caps = [
            'edit_product', 'read_product', 'delete_product',
            'edit_products', 'edit_others_products', 'publish_products',
            'read_private_products', 'delete_products', 'delete_private_products',
            'delete_published_products', 'delete_others_products',
            'edit_private_products', 'edit_published_products',
            'manage_product_categories'
        ];
        foreach ($caps as $cap) {
            $role->add_cap($cap);
        }
    }
}
add_action('admin_init', 'add_product_caps_to_admin');

function add_product_caps_to_seller() {
    $role = get_role('seller');
    if ($role) {
        $caps = [
            'edit_product', 'read_product', 'delete_product',
            'edit_products', 'edit_others_products', 'publish_products',
            'read_private_products', 'delete_products', 'delete_private_products',
            'delete_published_products', 'delete_others_products',
            'edit_private_products', 'edit_published_products',
            'manage_product_categories'
        ];
        foreach ($caps as $cap) {
            $role->add_cap($cap);
        }
    }
}
add_action('init', 'add_product_caps_to_seller', 20);

function hide_admin_bar_for_sellers() {
    if (current_user_can('seller') && !current_user_can('manage_options')) {
        show_admin_bar(false);
    }
}
add_action('after_setup_theme', 'hide_admin_bar_for_sellers');
