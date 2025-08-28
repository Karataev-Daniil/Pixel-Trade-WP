<?php
add_action('rest_api_init', function() {

    register_rest_route('simple-dm/v1', '/threads/(?P<id>\d+)/block', [
        'methods' => 'POST',
        'callback' => 'dm_block_thread_user',
        'permission_callback' => function() { return is_user_logged_in(); }
    ]);

    register_rest_route('simple-dm/v1', '/threads/(?P<id>\d+)/unblock', [
        'methods' => 'POST',
        'callback' => 'dm_unblock_thread_user',
        'permission_callback' => function() { return is_user_logged_in(); }
    ]);

});

function dm_block_thread_user($request) {
    $thread_id = intval($request['id']);
    $current_user = get_current_user_id();

    // Проверяем, существует ли пост
    if(!get_post($thread_id)) {
        return new WP_Error('invalid_thread', 'Тред не найден', ['status' => 404]);
    }

    update_post_meta($thread_id, '_dm_blocked_' . $current_user, 1);
    return ['success' => true];
}

function dm_unblock_thread_user($request) {
    $thread_id = intval($request['id']);
    $current_user = get_current_user_id();

    if(!get_post($thread_id)) {
        return new WP_Error('invalid_thread', 'Тред не найден', ['status' => 404]);
    }

    delete_post_meta($thread_id, '_dm_blocked_' . $current_user);
    return ['success' => true];
}
