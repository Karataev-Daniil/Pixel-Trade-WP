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

    $lang = $_POST['product_lang'] ?? 'ru';
    $title_field   = $lang === 'ru' ? 'product_title' : ($lang === 'en' ? 'title_en' : 'title_ro');
    $content_field = $lang === 'ru' ? 'product_content' : ($lang === 'en' ? 'description_en' : 'description_ro');

    $title   = trim($_POST[$title_field] ?? '');
    $content = trim($_POST[$content_field] ?? '');
    $price   = floatval($_POST['product_price'] ?? 0) ?: floatval($_POST['product_old_price'] ?? 0);

    $categories = !empty($_POST['selected_categories'])
        ? array_map('intval', explode(',', $_POST['selected_categories']))
        : [];

    $status   = sanitize_text_field($_POST['product_status'] ?? 'draft');
    $currency = sanitize_text_field($_POST['product_currency'] ?? 'lei');
    $type     = sanitize_text_field($_POST['product_type'] ?? '');

    error_log('POST data: ' . print_r($_POST, true));

    if (!$title) {
        wp_die('Please fill the title. Current value: ' . var_export($title, true));
    }

    if (!$content) {
        wp_die('Please fill the content. Current value: ' . var_export($content, true));
    }

    if ($price <= 0) {
        wp_die('Please fill a valid price. Current value: ' . var_export($price, true));
    }

    if (empty($categories)) {
        wp_die('Please select at least one category. Current value: ' . var_export($categories, true));
    }

    $current_user = wp_get_current_user();

    $post_id = wp_insert_post([
        'post_type'    => 'products',
        'post_title'   => sanitize_text_field($title),
        'post_content' => sanitize_textarea_field($content),
        'post_status'  => $status,
        'post_author'  => $current_user->ID,
    ]);

    if (!$post_id) {
        wp_die('Failed to create product');
    }

    wp_set_post_terms($post_id, $categories, 'product_cat');

    update_post_meta($post_id, 'product_price', $price);
    update_post_meta($post_id, 'product_currency', $currency);
    update_post_meta($post_id, 'product_type', $type);

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

            $file_array = [
                'name'     => $files['name'][$i],
                'type'     => $files['type'][$i],
                'tmp_name' => $files['tmp_name'][$i],
                'error'    => $files['error'][$i],
                'size'     => $files['size'][$i],
            ];

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
