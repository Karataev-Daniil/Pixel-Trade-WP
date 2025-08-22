<?php
function handle_create_product() {
    if (
        !isset($_POST['submit_product']) ||
        !isset($_POST['product_form_nonce']) ||
        !wp_verify_nonce($_POST['product_form_nonce'], 'create_product_form') ||
        !current_user_can('edit_posts')
    ) {
        wp_die('Ошибка безопасности или нет прав');
    }

    $post_title    = sanitize_text_field($_POST['product_title'] ?? '');
    $post_content  = sanitize_textarea_field($_POST['product_content'] ?? '');
    $post_status   = sanitize_text_field($_POST['product_status'] ?? 'draft');
    $product_price = sanitize_text_field($_POST['product_price'] ?? '');

    $post_id = wp_insert_post([
        'post_title'   => $post_title,
        'post_content' => $post_content,
        'post_status'  => $post_status,
        'post_type'    => 'product',
        'post_author'  => get_current_user_id(),
    ]);

    if (is_wp_error($post_id)) {
        wp_die('Ошибка создания продукта');
    }

    update_post_meta($post_id, 'product_price', $product_price);

    if (!empty($_POST['product_categories']) && is_array($_POST['product_categories'])) {
        $category_ids = array_map('intval', $_POST['product_categories']);
        wp_set_post_terms($post_id, $category_ids, 'product_cat');
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
