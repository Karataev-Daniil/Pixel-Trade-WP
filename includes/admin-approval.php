<?php
// admin-approval.php

add_action('admin_menu', function () {
    add_users_page(
        'Ожидают подтверждения',
        'Ожидают подтверждения',
        'manage_options',
        'pending-user-approvals',
        'kayo_render_pending_users_page'
    );
});

function kayo_pending_roles_admin_notice() {
    $args = [
        'meta_key' => 'pending_role',
        'meta_compare' => 'EXISTS'
    ];
    $pending_users = get_users($args);

    if (!empty($pending_users)) {
        echo '<div class="notice notice-warning"><p>Есть пользователи, ожидающие подтверждения роли. <a href="' . esc_url(admin_url('users.php?page=pending-user-approvals')) . '">Посмотреть</a></p></div>';
    }
}
add_action('admin_notices', 'kayo_pending_roles_admin_notice');

function kayo_add_approve_button($actions, $user_object) {
    if (current_user_can('administrator') && get_user_meta($user_object->ID, 'pending_role', true)) {
        $approve_url = wp_nonce_url(
            add_query_arg([
                'action' => 'approve_user_role',
                'user_id' => $user_object->ID
            ], admin_url('users.php')),
            'approve_user_role_' . $user_object->ID
        );

        $actions['approve_role'] = '<a href="' . esc_url($approve_url) . '">Подтвердить роль</a>';
    }
    return $actions;
}
add_filter('user_row_actions', 'kayo_add_approve_button', 10, 2);

function kayo_handle_approve_user_role() {
    if (
        isset($_GET['action'], $_GET['user_id']) &&
        $_GET['action'] === 'approve_user_role' &&
        current_user_can('administrator') &&
        check_admin_referer('approve_user_role_' . intval($_GET['user_id']))
    ) {
        $user_id = intval($_GET['user_id']);
        $pending_role = get_user_meta($user_id, 'pending_role', true);

        if ($pending_role) {
            wp_update_user([
                'ID' => $user_id,
                'role' => sanitize_text_field($pending_role),
            ]);
            delete_user_meta($user_id, 'pending_role');
        }

        wp_redirect(admin_url('users.php?page=pending-user-approvals&role_approved=1'));
        exit;
    }
}
add_action('admin_init', 'kayo_handle_approve_user_role');

function kayo_show_user_role_approved_notice() {
    if (isset($_GET['role_approved']) && $_GET['role_approved'] == 1) {
        echo '<div class="notice notice-success is-dismissible"><p>Роль пользователя успешно подтверждена.</p></div>';
    }
}
add_action('admin_notices', 'kayo_show_user_role_approved_notice');

function kayo_render_pending_users_page() {
    if (!current_user_can('administrator')) {
        wp_die('У вас нет прав для доступа к этой странице.');
    }

    $args = [
        'meta_key' => 'pending_role',
        'meta_compare' => 'EXISTS',
    ];
    $pending_users = get_users($args);

    echo '<div class="wrap"><h1>Пользователи, ожидающие подтверждения</h1>';

    if (empty($pending_users)) {
        echo '<p>Нет пользователей, ожидающих подтверждения.</p>';
    } else {
        echo '<table class="widefat fixed striped">';
        echo '<thead><tr><th>Имя</th><th>Email</th><th>Ожидаемая роль</th><th>Действия</th></tr></thead>';
        echo '<tbody>';

        foreach ($pending_users as $user) {
            $pending_role = get_user_meta($user->ID, 'pending_role', true);
            $approve_url = wp_nonce_url(
                add_query_arg([
                    'action' => 'approve_user_role',
                    'user_id' => $user->ID
                ], admin_url('users.php?page=pending-user-approvals')),
                'approve_user_role_' . $user->ID
            );

            echo '<tr>';
            echo '<td>' . esc_html($user->user_login) . '</td>';
            echo '<td>' . esc_html($user->user_email) . '</td>';
            echo '<td>' . esc_html($pending_role) . '</td>';
            echo '<td><a class="button button-primary" href="' . esc_url($approve_url) . '">Подтвердить</a></td>';
            echo '</tr>';
        }

        echo '</tbody></table>';
    }

    echo '</div>';
}
