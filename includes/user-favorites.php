<?php
// Файл: includes/favorites.php
function favorites_add($user_id, $object_id, $object_type = 'product') {
    global $wpdb;
    $table = $wpdb->prefix . 'favorites';

    $exists = $wpdb->get_var($wpdb->prepare(
        "SELECT id FROM $table WHERE user_id = %d AND object_id = %d AND object_type = %s",
        $user_id, $object_id, $object_type
    ));

    if (!$exists) {
        $wpdb->insert($table, [
            'user_id' => $user_id,
            'object_id' => $object_id,
            'object_type' => $object_type,
            'date_added' => current_time('mysql'),
        ]);

    }
}
function favorites_remove($user_id, $object_id, $object_type = 'product') {
    global $wpdb;
    $table = $wpdb->prefix . 'favorites';

    $wpdb->delete($table, [
        'user_id' => $user_id,
        'object_id' => $object_id,
        'object_type' => $object_type,
    ]);
}
function favorites_get($user_id, $object_type = 'product') {
    global $wpdb;
    $table = $wpdb->prefix . 'favorites';

    return $wpdb->get_col($wpdb->prepare(
        "SELECT object_id FROM $table WHERE user_id = %d AND object_type = %s ORDER BY date_added DESC",
        $user_id, $object_type
    ));
}

function add_to_favorites() {
    check_ajax_referer('favorites_nonce', 'nonce');

    if (!is_user_logged_in()) {
        wp_send_json_error([
            'message' => 'Чтобы добавить товар в избранное, войдите в аккаунт.',
            'login_button' => '<a href="/user/login/" class="btn-login">Войти</a>'
        ]);
    }

    $user_id = get_current_user_id();
    $product_id = intval($_POST['product_id']);

    if (!$product_id) {
        wp_send_json_error(['message' => 'Некорректный ID товара']);
    }

    favorites_add($user_id, $product_id, 'product');

    wp_send_json_success(['message' => 'Товар добавлен в избранное']);
}
add_action('wp_ajax_add_to_favorites', 'add_to_favorites');
add_action('wp_ajax_nopriv_add_to_favorites', 'add_to_favorites');

function remove_from_favorites() {
    check_ajax_referer('favorites_nonce', 'nonce');

    if (!is_user_logged_in()) {
        wp_send_json_error([
            'message' => 'Чтобы удалить товар из избранного, войдите в аккаунт.',
            'login_button' => '<a href="/user/login/" class="btn-login">Войти</a>'
        ]);
    }

    $user_id = get_current_user_id();
    $product_id = intval($_POST['product_id']);

    if (!$product_id) {
        wp_send_json_error(['message' => 'Некорректный ID товара']);
    }

    favorites_remove($user_id, $product_id, 'product');

    wp_send_json_success(['message' => 'Товар удален из избранного']);
}
add_action('wp_ajax_remove_from_favorites', 'remove_from_favorites');
add_action('wp_ajax_nopriv_remove_from_favorites', 'remove_from_favorites');

function favorites_scripts() {
    wp_enqueue_script(
        'favorites-js',
        get_template_directory_uri() . '/assets/js/favorites.js',
        ['jquery'],
        null,
        true
    );
    wp_localize_script('favorites-js', 'favorites_ajax', [
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce'    => wp_create_nonce('favorites_nonce')
    ]);
}
add_action('wp_enqueue_scripts', 'favorites_scripts');
