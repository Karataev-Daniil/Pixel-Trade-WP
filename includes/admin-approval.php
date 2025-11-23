<?php
// admin-approval.php
add_action('admin_menu', function () {
    add_users_page(
        'Ожидают подтверждения',
        'Ожидают подтверждения',
        'manage_options',
        'pending-user-approvals',
        'render_pending_users_page'
    );
});

add_action('admin_notices', function () {
    $pending_users = get_users(['meta_key' => 'pending_role', 'meta_compare' => 'EXISTS']);
    if (!empty($pending_users)) {
        echo '<div class="notice notice-warning"><p>Есть пользователи, ожидающие подтверждения роли. <a href="' . esc_url(admin_url('users.php?page=pending-user-approvals')) . '">Посмотреть</a></p></div>';
    }
});

function handle_approve_user_role() {
    if (
        isset($_POST['approve_user_role'], $_POST['user_id'], $_POST['new_role']) &&
        current_user_can('administrator') &&
        check_admin_referer('approve_user_role_' . intval($_POST['user_id']))
    ) {
        $user_id = intval($_POST['user_id']);
        $new_role = sanitize_text_field($_POST['new_role']);

        if (in_array($new_role, ['buyer', 'seller'])) {
            wp_update_user([
                'ID' => $user_id,
                'role' => $new_role,
            ]);
            delete_user_meta($user_id, 'pending_role');

            wp_redirect(admin_url('users.php?page=pending-user-approvals&role_approved=1'));
            exit;
        }
    }
}
add_action('admin_init', 'handle_approve_user_role');

add_action('admin_notices', function () {
    if (isset($_GET['role_approved']) && $_GET['role_approved'] == 1) {
        echo '<div class="notice notice-success is-dismissible"><p>Роль пользователя успешно подтверждена.</p></div>';
    }
});

function render_pending_users_page() {
    if (!current_user_can('administrator')) {
        wp_die('У вас нет прав для доступа к этой странице.');
    }

    $pending_users = get_users(['meta_key' => 'pending_role', 'meta_compare' => 'EXISTS']);

    echo '<div class="wrap"><h1>Пользователи, ожидающие подтверждения</h1>';

    if (empty($pending_users)) {
        echo '<p>Нет пользователей, ожидающих подтверждения.</p>';
    } else {
        echo '<table class="widefat fixed striped">';
        echo '<thead><tr><th>Имя</th><th>Email</th><th>Ожидаемая роль</th><th>Действия</th></tr></thead>';
        echo '<tbody>';

        foreach ($pending_users as $user) {
            $pending_role = get_user_meta($user->ID, 'pending_role', true);

            echo '<tr>';
            echo '<td>' . esc_html($user->user_login) . '</td>';
            echo '<td>' . esc_html($user->user_email) . '</td>';
            echo '<td>' . esc_html($pending_role) . '</td>';
            echo '<td>';
            echo '<form method="post" style="display:inline;">';
            echo '<input type="hidden" name="user_id" value="' . esc_attr($user->ID) . '">';
            echo '<select name="new_role">';
            echo '<option value="buyer" ' . selected($pending_role, 'buyer', false) . '>Покупатель</option>';
            echo '<option value="seller" ' . selected($pending_role, 'seller', false) . '>Продавец</option>';
            echo '</select>';
            wp_nonce_field('approve_user_role_' . $user->ID);
            echo '<input type="submit" name="approve_user_role" class="button button-primary" value="Присвоить роль">';
            echo '</form>';
            echo '</td>';
            echo '</tr>';
        }

        echo '</tbody></table>';
    }

    echo '</div>';
}

function add_seller_caps_after_approval($user_id, $role) {
    if ($role === 'seller') {
        $user = new WP_User($user_id);

        $user->add_cap('edit_products');
        $user->add_cap('edit_published_products');
        $user->add_cap('publish_products');
        $user->add_cap('delete_products');
        $user->add_cap('upload_files');
    }
}

add_action('set_user_role', 'add_seller_caps_after_approval', 10, 2);