<?php
add_action('rest_api_init', function () {
    register_rest_route('dm/v1', '/threads/(?P<id>\d+)/block', [
        [
            'methods'  => 'POST',
            'callback' => 'simple_dm_block_thread',
            'permission_callback' => 'is_user_logged_in',
        ],
        [
            'methods'  => 'DELETE',
            'callback' => 'simple_dm_unblock_thread',
            'permission_callback' => 'is_user_logged_in',
        ],
    ]);
});

function simple_dm_block_thread(WP_REST_Request $req) {
    $thread_id = intval($req['id']);
    $user_id   = get_current_user_id();

    if (!$thread_id || !$user_id) {
        return new WP_Error('invalid_request', 'Неверный запрос', ['status' => 400]);
    }

    $participants = dm_thread_participants($thread_id);
    if (!in_array($user_id, $participants)) {
        return new WP_Error('forbidden', 'Нет доступа к этому чату', ['status' => 403]);
    }

    // Сохраняем глобальную блокировку потока
    update_post_meta($thread_id, '_dm_blocked', 1);
    update_post_meta($thread_id, '_dm_blocked_by', $user_id);

    // Добавляем системное сообщение
    $msg_id = wp_insert_post([
        'post_type'   => 'dm_message',
        'post_status' => 'publish',
        'post_parent' => $thread_id,
        'post_author' => $user_id,
        'post_title'  => '',
        'post_content'=> sprintf('Пользователь %s заблокировал чат.', wp_get_current_user()->display_name),
        'meta_input'  => [
            '_system' => 1,
            '_event'  => 'blocked'
        ]
    ]);

    return [
        'success'        => true,
        'thread_id'      => $thread_id,
        'blocked'        => true,
        'blocked_by'     => $user_id,
        'system_message' => [
            'id'      => $msg_id,
            'date'    => current_time('mysql'),
            'content' => 'Вы заблокировали этого пользователя — переписка остановлена.',
            'system'  => true,
            'event'   => 'blocked',
        ]
    ];
}

function simple_dm_unblock_thread(WP_REST_Request $req) {
    $thread_id = intval($req['id']);
    $user_id   = get_current_user_id();

    if (!$thread_id || !$user_id) {
        return new WP_Error('invalid_request', 'Неверный запрос', ['status' => 400]);
    }

    $participants = dm_thread_participants($thread_id);
    if (!in_array($user_id, $participants)) {
        return new WP_Error('forbidden', 'Нет доступа к этому чату', ['status' => 403]);
    }

    $blocked_by = get_post_meta($thread_id, '_dm_blocked_by', true);
    if (!$blocked_by || $blocked_by != $user_id) {
        return new WP_Error('forbidden', 'Чат не заблокирован вами', ['status' => 403]);
    }

    // Убираем блокировку
    delete_post_meta($thread_id, '_dm_blocked');
    delete_post_meta($thread_id, '_dm_blocked_by');

    // Системное сообщение о разблокировке
    $msg_id = wp_insert_post([
        'post_type'   => 'dm_message',
        'post_status' => 'publish',
        'post_parent' => $thread_id,
        'post_author' => $user_id,
        'post_title'  => '',
        'post_content'=> sprintf('Пользователь %s разблокировал чат.', wp_get_current_user()->display_name),
        'meta_input'  => [
            '_system' => 1,
            '_event'  => 'unblocked'
        ]
    ]);

    return [
        'success'        => true,
        'thread_id'      => $thread_id,
        'blocked'        => false,
        'blocked_by'     => null,
        'system_message' => [
            'id'      => $msg_id,
            'date'    => current_time('mysql'),
            'content' => 'Вы разблокировали чат — переписка восстановлена.',
            'system'  => true,
            'event'   => 'unblocked',
        ]
    ];
}