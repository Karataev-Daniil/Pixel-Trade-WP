<?php
function remove_custom_roles() {
    remove_role('seller');
    remove_role('buyer');
}
register_deactivation_hook(__FILE__, 'remove_custom_roles');

function register_custom_roles() {
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
            'edit_products', 'edit_others_products', 'publish_products', 'read_private_products',
            'delete_products', 'delete_private_products', 'delete_published_products', 'delete_others_products',
            'edit_private_products', 'edit_published_products', 'manage_product_categories'
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
            'edit_products', 'edit_others_products', 'publish_products', 'read_private_products',
            'delete_products', 'delete_private_products', 'delete_published_products', 'delete_others_products',
            'edit_private_products', 'edit_published_products', 'manage_product_categories'
        ];
        foreach ($caps as $cap) {
            $role->add_cap($cap);
        }
    }
}
add_action('init', 'add_product_caps_to_seller', 20);

add_action('after_setup_theme', function() {
    if (!current_user_can('manage_options')) {
        show_admin_bar(false);
    }
});

add_action('admin_init', function() {
    if ((current_user_can('seller') || current_user_can('buyer')) 
        && ! ( defined('DOING_AJAX') && DOING_AJAX ) 
        && ! ( defined('REST_REQUEST') && REST_REQUEST ) 
        && ! ( isset($_SERVER['PHP_SELF']) && strpos($_SERVER['PHP_SELF'], 'admin-post.php') !== false )
    ) {
        wp_redirect(home_url());
        exit;
    }
});

add_action('admin_menu', function() {
    if (current_user_can('seller') || current_user_can('buyer')) {
        global $menu, $submenu;
        $menu = [];
        $submenu = [];
    }
}, 999);
