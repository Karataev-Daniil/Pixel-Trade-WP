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

    $current_user = wp_get_current_user();

    $lang = sanitize_text_field($_POST['product_lang'] ?? 'ru');

    $title_field   = $lang === 'ru' ? 'product_title' : ($lang === 'en' ? 'title_en' : 'title_ro');
    $content_field = $lang === 'ru' ? 'product_content' : ($lang === 'en' ? 'description_en' : 'description_ro');

    $title   = sanitize_text_field(trim($_POST[$title_field] ?? ''));
    $content = sanitize_textarea_field(trim($_POST[$content_field] ?? ''));
    $price   = floatval($_POST['product_price'] ?? 0);
    $currency = strtolower(sanitize_text_field($_POST['product_currency'] ?? 'lei'));
    $type     = sanitize_text_field($_POST['product_type'] ?? 'sell');
    $status   = sanitize_text_field($_POST['product_status'] ?? 'draft');

    $categories = !empty($_POST['selected_categories'])
        ? array_map('intval', explode(',', $_POST['selected_categories']))
        : [];

    if (!$title) wp_die('Please fill the title.');
    if (!$content) wp_die('Please fill the content.');
    if ($price <= 0) wp_die('Please fill a valid price.');
    if (empty($categories)) wp_die('Please select at least one category.');
    if (!in_array($currency, ['lei','usd','eur'])) $currency = 'lei';

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

    update_post_meta($post_id, '_title_en', sanitize_text_field($_POST['title_en'] ?? ''));
    update_post_meta($post_id, '_title_ro', sanitize_text_field($_POST['title_ro'] ?? ''));
    update_post_meta($post_id, '_description_en', sanitize_textarea_field($_POST['description_en'] ?? ''));
    update_post_meta($post_id, '_description_ro', sanitize_textarea_field($_POST['description_ro'] ?? ''));

    if (!empty($_POST['dynamic_fields'])) {
        $dynamic_fields = json_decode(stripslashes($_POST['dynamic_fields']), true);
        if (is_array($dynamic_fields)) {
            $dynamic_fields = sanitize_dynamic_fields($dynamic_fields);
        
            update_post_meta($post_id, 'dynamic_features', $dynamic_fields);

            foreach ($dynamic_fields as $meta_key => $meta_value) {
                update_post_meta($post_id, $meta_key, $meta_value);
            }
        }
    }


    if (!function_exists('media_handle_sideload')) {
        require_once ABSPATH . 'wp-admin/includes/image.php';
        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/media.php';
    }

    $attachment_ids = [];
    $files = $_FILES['product_gallery'] ?? null;
    if ($files && !empty($files['name'][0])) {
        foreach ($files['name'] as $i => $name) {
            if ($i >= 10) break;
            if ($files['error'][$i] !== UPLOAD_ERR_OK) continue;

            $file_array = [
                'name'     => generate_random_filename($name),
                'tmp_name' => $files['tmp_name'][$i],
                'type'     => $files['type'][$i],
                'size'     => $files['size'][$i],
                'error'    => $files['error'][$i],
            ];

            if ($file_array['size'] > 500 * 1024) {
                $image = wp_get_image_editor($file_array['tmp_name']);
                if (!is_wp_error($image)) {
                    $mime = $file_array['type'];
                    if (in_array($mime, ['image/jpeg','image/jpg','image/webp'])) {
                        $image->set_quality(80);
                    }
                    $image->save($file_array['tmp_name']);
                }
            }

            $attachment_id = media_handle_sideload($file_array, $post_id);
            if (!is_wp_error($attachment_id)) $attachment_ids[] = $attachment_id;
        }
    }

    if (!empty($attachment_ids)) {
        update_post_meta($post_id, 'product_gallery', $attachment_ids);
        set_post_thumbnail($post_id, $attachment_ids[0]);
    }

    wp_safe_redirect(get_permalink($post_id));
    exit;
}

function sanitize_dynamic_fields($fields) {
    foreach ($fields as $k => $v) {
        if (is_array($v)) {
            $fields[$k] = sanitize_dynamic_fields($v);
        } else {
            $fields[$k] = sanitize_text_field($v);
        }
    }
    return $fields;
}

add_action('admin_post_create_product', 'handle_create_product');
add_action('admin_post_nopriv_create_product', 'handle_create_product');
