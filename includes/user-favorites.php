<?php
// Файл: includes/favorites.php
function create_favorites_page() {
    $page_check = get_page_by_path('favorites');
    if (!$page_check) {
        wp_insert_post([
            'post_title'   => 'Избранное',
            'post_name'    => 'favorites',
            'post_status'  => 'publish',
            'post_type'    => 'page',
            'post_content' => '[user_favorites]',
        ]);
    }
}
add_action('after_switch_theme', 'create_favorites_page');

function user_can_manage_favorites($user_id = null) {
    if (!$user_id) {
        $user_id = get_current_user_id();
    }
    $user = get_userdata($user_id);
    if (!$user) return false;

    return true;
}

function add_to_favorites() {
    check_ajax_referer('favorites_nonce', 'nonce');

    if (!is_user_logged_in()) {
        wp_send_json_error([
            'message' => 'Чтобы добавить товар в избранное, войдите в аккаунт.',
            'login_button' => '<a href="/account/login/" class="btn-login">Войти</a>'
        ]);
    }

    if (!user_can_manage_favorites()) {
        wp_send_json_error(['message' => 'У вас нет прав для добавления в избранное']);
    }

    $user_id = get_current_user_id();
    $product_id = intval($_POST['product_id']);
    if (!$product_id) {
        wp_send_json_error(['message' => 'Некорректный ID товара']);
    }

    $favorites = get_user_meta($user_id, 'favorite_products', true);
    if (!is_array($favorites)) $favorites = [];

    if (!in_array($product_id, $favorites)) {
        $favorites[] = $product_id;
        update_user_meta($user_id, 'favorite_products', $favorites);
    }

    wp_send_json_success(['message' => 'Товар добавлен в избранное']);
}

function remove_from_favorites() {
    check_ajax_referer('favorites_nonce', 'nonce');

    if (!is_user_logged_in()) {
        wp_send_json_error([
            'message' => 'Чтобы удалить товар из избранного, войдите в аккаунт.',
            'login_button' => '<a href="/account/login/" class="btn-login">Войти</a>'
        ]);
    }

    if (!user_can_manage_favorites()) {
        wp_send_json_error(['message' => 'У вас нет прав для удаления из избранного']);
    }

    $user_id = get_current_user_id();
    $product_id = intval($_POST['product_id']);

    $favorites = get_user_meta($user_id, 'favorite_products', true);
    if (!is_array($favorites)) $favorites = [];

    if (($key = array_search($product_id, $favorites)) !== false) {
        unset($favorites[$key]);
        update_user_meta($user_id, 'favorite_products', $favorites);
    }

    wp_send_json_success(['message' => 'Товар удален из избранного']);
}

function get_user_favorites($user_id = null) {
    if (!$user_id) $user_id = get_current_user_id();
    $favorites = get_user_meta($user_id, 'favorite_products', true);
    return is_array($favorites) ? $favorites : [];
}

function favorites_shortcode() {
    if (!is_user_logged_in() || !user_can_manage_favorites()) {
        return '<p>Войдите, чтобы видеть избранное.</p>';
    }

    $favorites = get_user_favorites();
    if (empty($favorites)) {
        return '<p>У вас пока нет избранных товаров.</p>';
    }

    $query = new WP_Query([
        'post_type' => 'products',
        'post__in'  => $favorites,
        'posts_per_page' => -1,
    ]);

    ob_start();

    if ($query->have_posts()) {
        echo '<div class="favorites-grid">';
        while ($query->have_posts()) {
            $query->the_post();
            $price = get_post_meta(get_the_ID(), 'product_price', true);
            $thumbnail = get_the_post_thumbnail_url(get_the_ID(), 'medium');
            if (!$thumbnail) {
                $thumbnail = get_template_directory_uri() . '/images/product-placeholder.png';
            }
            ?>
            
            <?php
        }
        echo '</div>';
        wp_reset_postdata();
    }

    return ob_get_clean();
}
add_shortcode('user_favorites', 'favorites_shortcode');

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
add_action('wp_ajax_add_to_favorites', 'add_to_favorites');
add_action('wp_ajax_nopriv_add_to_favorites', 'add_to_favorites');
add_action('wp_ajax_remove_from_favorites', 'remove_from_favorites');
add_action('wp_ajax_nopriv_remove_from_favorites', 'remove_from_favorites');


