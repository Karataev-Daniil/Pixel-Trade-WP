<?php
$current_user_id = $args['current_user_id'] ?? get_current_user_id();

add_filter('wp_handle_upload_prefilter', function($file) {
    $file['name'] = generate_random_filename($file['name']);
    return $file;
});

function handle_create_product() {
    if (!isset($_POST['product_form_nonce']) || !wp_verify_nonce($_POST['product_form_nonce'], 'create_product_form')) {
        wp_die('Invalid nonce');
    }

    if (!is_user_logged_in()) {
        wp_die('You must be logged in');
    }

    $lang = sanitize_text_field($_POST['product_lang'] ?? 'ru');
    $title_field   = $lang === 'ru' ? 'product_title' : ($lang === 'en' ? 'title_en' : 'title_ro');
    $content_field = $lang === 'ru' ? 'product_content' : ($lang === 'en' ? 'description_en' : 'description_ro');

    $title   = sanitize_text_field(trim($_POST[$title_field] ?? ''));
    $content = sanitize_textarea_field(trim($_POST[$content_field] ?? ''));
    $price   = floatval($_POST['product_price'] ?? 0) ?: floatval($_POST['product_old_price'] ?? 0);

    $categories = !empty($_POST['selected_categories'])
        ? array_map('intval', explode(',', $_POST['selected_categories']))
        : [];

    $status   = sanitize_text_field($_POST['product_status'] ?? 'draft');
    $currency = sanitize_text_field($_POST['product_currency'] ?? 'lei');
    $type     = sanitize_text_field($_POST['product_type'] ?? '');

    if (!$title) wp_die('Please fill the title.');
    if (!$content) wp_die('Please fill the content.');
    if ($price <= 0) wp_die('Please fill a valid price.');
    if (empty($categories)) wp_die('Please select at least one category.');

    $current_user = wp_get_current_user();

    $post_id = wp_insert_post([
        'post_type'    => 'products',
        'post_title'   => $title,
        'post_content' => $content,
        'post_status'  => $status,
        'post_author'  => $current_user->ID,
        'post_name'    => generate_product_slug(),
    ]);

    if (!$post_id) wp_die('Failed to create product');

    wp_set_post_terms($post_id, $categories, 'product_cat');
    update_post_meta($post_id, 'product_price', $price);
    update_post_meta($post_id, 'product_currency', $currency);
    update_post_meta($post_id, 'product_type', $type);

if (!empty($_POST['dynamic_fields'])) {
    $dynamic_fields = json_decode(stripslashes($_POST['dynamic_fields']), true);
    if (is_array($dynamic_fields)) {
        // Сохраняем каждое поле отдельным meta_key:
        foreach ($dynamic_fields as $meta_key => $meta_value) {
            update_post_meta($post_id, $meta_key, sanitize_text_field($meta_value));
        }
        // И при желании — общим массивом:
        update_post_meta($post_id, 'dynamic_features', array_map('sanitize_text_field', $dynamic_fields));
    }
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

            $random_name = generate_random_filename($files['name'][$i]);
            $file_array = [
                'name'     => $random_name,
                'type'     => $files['type'][$i],
                'tmp_name' => $files['tmp_name'][$i],
                'error'    => $files['error'][$i],
                'size'     => $files['size'][$i],
            ];

            if ($file_array['size'] > 200 * 1024) {
                $mime = $file_array['type'];
                $image = wp_get_image_editor($file_array['tmp_name']);
                if (!is_wp_error($image)) {
                    if ($mime === 'image/jpeg' || $mime === 'image/jpg' || $mime === 'image/webp') {
                        $image->set_quality(80);
                    }
                    $image->save($file_array['tmp_name']);
                }
            }

            $attachment_id = media_handle_sideload($file_array, $post_id);
            if (!is_wp_error($attachment_id)) {
                $attachment_ids[] = $attachment_id;
            } else {
                error_log('Attachment error: ' . $attachment_id->get_error_message());
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
