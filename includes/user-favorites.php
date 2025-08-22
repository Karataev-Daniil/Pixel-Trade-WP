<?php
// includes/favorites.php

function create_favorites_page() {
    $page_check = get_page_by_path('favorites');
    if (!$page_check) {
        $page_id = wp_insert_post([
            'post_title'   => 'Избранное',
            'post_name'    => 'favorites',
            'post_status'  => 'publish',
            'post_type'    => 'page',
            'post_content' => '[user_favorites]',
        ]);
    }
}
add_action('after_switch_theme', 'create_favorites_page');

function add_to_favorites() {
    if (!is_user_logged_in()) {
        wp_send_json_error(['message' => 'Необходимо войти в систему']);
    }

    $user_id = get_current_user_id();
    $product_id = intval($_POST['product_id']);

    if (!$product_id) {
        wp_send_json_error(['message' => 'Некорректный ID товара']);
    }

    $favorites = get_user_meta($user_id, 'favorite_products', true);
    if (!is_array($favorites)) {
        $favorites = [];
    }

    if (!in_array($product_id, $favorites)) {
        $favorites[] = $product_id;
        update_user_meta($user_id, 'favorite_products', $favorites);
    }

    wp_send_json_success(['message' => 'Товар добавлен в избранное']);
}
add_action('wp_ajax_add_to_favorites', 'add_to_favorites');
add_action('wp_ajax_nopriv_add_to_favorites', 'add_to_favorites');

function remove_from_favorites() {
    if (!is_user_logged_in()) {
        wp_send_json_error(['message' => 'Необходимо войти в систему']);
    }

    $user_id = get_current_user_id();
    $product_id = intval($_POST['product_id']);

    $favorites = get_user_meta($user_id, 'favorite_products', true);
    if (!is_array($favorites)) {
        $favorites = [];
    }

    if (($key = array_search($product_id, $favorites)) !== false) {
        unset($favorites[$key]);
        update_user_meta($user_id, 'favorite_products', $favorites);
    }

    wp_send_json_success(['message' => 'Товар удален из избранного']);
}
add_action('wp_ajax_remove_from_favorites', 'remove_from_favorites');
add_action('wp_ajax_nopriv_remove_from_favorites', 'remove_from_favorites');

function get_user_favorites($user_id = null) {
    if (!$user_id) {
        $user_id = get_current_user_id();
    }
    $favorites = get_user_meta($user_id, 'favorite_products', true);
    return is_array($favorites) ? $favorites : [];
}

function favorites_shortcode() {
    if (!is_user_logged_in()) {
        return '<p>Войдите, чтобы видеть избранное.</p>';
    }

    $favorites = get_user_favorites();
    if (empty($favorites)) {
        return '<p>У вас пока нет избранных товаров.</p>';
    }

    $query = new WP_Query([
        'post_type' => 'product',
        'post__in'  => $favorites,
    ]);

    ob_start();
    if ($query->have_posts()) {
        echo '<div class="favorites-list">';
        while ($query->have_posts()) {
            $query->the_post();
            echo '<div class="favorite-item">';
            echo '<a href="' . get_permalink() . '">' . get_the_title() . '</a>';
            echo ' <button class="remove-from-favorites" data-id="' . get_the_ID() . '">Удалить</button>';
            echo '</div>';
        }
        echo '</div>';
    }
    wp_reset_postdata();

    return ob_get_clean();
}
add_shortcode('user_favorites', 'favorites_shortcode');