<?php
add_filter('wp_handle_upload_prefilter', function($file) {
    $file['name'] = generate_random_filename($file['name']);
    return $file;
});

function handle_create_product() {
    if (!isset($_POST['product_form_nonce']) || !wp_verify_nonce($_POST['product_form_nonce'], 'create_product_form')) {
        return;
    }

    if (!is_user_logged_in()) {
        return;
    }

    $title = trim($_POST['product_title'] ?? '');
    $content = trim($_POST['product_content'] ?? '');
    $price = trim($_POST['product_price'] ?? '') ?: trim($_POST['product_old_price'] ?? '');
    $categories = json_decode(stripslashes($_POST['selected_categories'] ?? '[]'), true);

    if (!$title || !$content || !$price || empty($categories)) {
        return;
    }

    $current_user = wp_get_current_user();

    $post_id = wp_insert_post([
        'post_type'    => 'products',
        'post_title'   => sanitize_text_field($title),
        'post_content' => sanitize_textarea_field($content),
        'post_status'  => sanitize_text_field($_POST['product_status'] ?? 'draft'),
        'post_author'  => $current_user->ID,
    ]);

    if (!$post_id) {
        return;
    }

    wp_set_post_terms($post_id, array_map('intval', $categories), 'product_cat');

    update_post_meta($post_id, 'product_price', sanitize_text_field($price));
    if (!empty($_POST['product_currency'])) {
        update_post_meta($post_id, 'product_currency', sanitize_text_field($_POST['product_currency']));
    }
    if (!empty($_POST['product_type'])) {
        update_post_meta($post_id, 'product_type', sanitize_text_field($_POST['product_type']));
    }

    if (!function_exists('media_handle_upload')) {
        require_once ABSPATH . 'wp-admin/includes/image.php';
        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/media.php';
    }

    $attachment_ids = [];
    if (!empty($_FILES['product_gallery']['name'][0])) {
        $files = $_FILES['product_gallery'];
        foreach ($files['name'] as $i => $name) {
            if ($files['error'][$i] !== UPLOAD_ERR_OK) continue;

            $_FILES['single_file_upload'] = [
                'name'     => $files['name'][$i],
                'type'     => $files['type'][$i],
                'tmp_name' => $files['tmp_name'][$i],
                'error'    => $files['error'][$i],
                'size'     => $files['size'][$i],
            ];

            $attachment_id = media_handle_upload('single_file_upload', $post_id);
            if (!is_wp_error($attachment_id)) {
                $attachment_ids[] = $attachment_id;
            }
        }
    }

    if (!empty($attachment_ids)) {
        update_post_meta($post_id, 'product_gallery', $attachment_ids);
        set_post_thumbnail($post_id, $attachment_ids[0]);
    }

    wp_safe_redirect(get_permalink($post_id));
    exit;
}

add_action('admin_post_nopriv_create_product', 'handle_create_product');
add_action('admin_post_create_product', 'handle_create_product');
