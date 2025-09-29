<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

// Rename uploaded files for product form only
add_filter('wp_handle_upload_prefilter', function($file) {
    $action = $_REQUEST['action'] ?? '';
    if ($action !== 'create_product') return $file;
    if (! empty($_FILES['product_gallery'])) {
        $file['name'] = generate_random_filename($file['name']);
    }
    return $file;
});

// Redirect with errors
function redirect_with_error(array $errors, array $old_values = [], $back_url = '') {
    $back = $back_url ?: wp_get_referer() ?: home_url('/');
    $current_user = get_current_user_id();
    $key = 'product_form_errors_' . $current_user;
    set_transient($key, [
        'errors' => $errors,
        'old' => $old_values,
    ], 60 * 5);
    wp_safe_redirect($back);
    exit;
}

// Get transient errors
function get_product_error_message_from_key($key) {
    if (! $key) return '';
    $msg = get_transient($key);
    if ($msg) delete_transient($key);
    return $msg ? wp_unslash($msg) : '';
}

// Strict meta key sanitization
function sanitize_meta_key_strict($key) {
    $key = (string) $key;
    $key = preg_replace('/[^a-zA-Z0-9_\-]/', '', $key);
    $key = ltrim($key, '_');
    if ($key === '') return false;
    return $key;
}

// Recursive sanitization for dynamic fields
function sanitize_dynamic_fields($fields) {
    if (!is_array($fields)) return [];
    foreach ($fields as $k => $v) {
        if (is_array($v)) $fields[$k] = sanitize_dynamic_fields($v);
        else $fields[$k] = sanitize_text_field($v);
    }
    return $fields;
}

// Check if image is valid
function is_image_file_safe($file_path) {
    $info = @getimagesize($file_path);
    return $info !== false;
}

// Handle product creation
add_action('admin_post_create_product', 'handle_create_product');
add_action('admin_post_nopriv_create_product', 'handle_create_product');
function handle_create_product() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        wp_safe_redirect(home_url('/'));
        exit;
    }

    $raw_post = wp_unslash($_POST);
    $current_user = wp_get_current_user();

    // Nonce & permissions
    if (empty($raw_post['product_form_nonce']) || ! wp_verify_nonce($raw_post['product_form_nonce'], 'create_product_form')) {
        wp_die('Invalid request');
    }
    if (!is_user_logged_in() || !current_user_can('edit_posts')) {
        wp_die('Insufficient permissions');
    }

    // Language-specific fields
    $lang = sanitize_text_field($raw_post['product_lang'] ?? 'ru');
    $title_field = $lang === 'ru' ? 'product_title' : ($lang === 'en' ? 'title_en' : 'title_ro');
    $content_field = $lang === 'ru' ? 'product_content' : ($lang === 'en' ? 'description_en' : 'description_ro');

    // Basic sanitization
    $title = sanitize_text_field(trim($raw_post[$title_field] ?? ''));
    $content = sanitize_textarea_field(trim($raw_post[$content_field] ?? ''));
    $price = floatval($raw_post['product_price'] ?? 0);
    $currency = strtolower(sanitize_text_field($raw_post['product_currency'] ?? 'lei'));
    $type = sanitize_text_field($raw_post['product_type'] ?? 'sell');
    $status = sanitize_text_field($raw_post['product_status'] ?? 'draft');

    // Categories
    $categories = [];
    if (!empty($raw_post['selected_categories'])) {
        $cats = explode(',', $raw_post['selected_categories']);
        $cats = array_map('intval', $cats);
        $cats = array_filter($cats);
        $categories = array_values($cats);
    }

    // Server-side validation
    $errors = [];
    if (!$title) {
        $errors[$title_field] = t('Заполните заголовок', 'Please fill the title', 'Completați titlul');
    }
    if (!$content) {
        $errors[$content_field] = t('Заполните описание', 'Please fill the content', 'Completați descrierea');
    }
    if ($price <= 0) {
        $errors['product_price'] = t('Укажите корректную цену', 'Please provide a valid price', 'Vă rugăm să furnizați un preț valid');
    }
    if (empty($categories)) {
        $errors['selected_categories'] = t('Выберите хотя бы одну категорию', 'Please select at least one category', 'Selectați cel puțin o categorie');
    }
    if (!in_array($currency, ['lei','usd','eur'], true)) {
        $currency = 'lei';
    }
    if (!empty($errors)) {
        redirect_with_error($errors, $raw_post);
    }

    // Insert product
    $post_args = [
        'post_type' => 'products',
        'post_title' => $title,
        'post_content' => $content,
        'post_status' => $status,
        'post_author' => $current_user->ID,
        'post_name' => generate_product_slug(),
    ];
    $post_id = wp_insert_post($post_args, true);
    if (is_wp_error($post_id) || !$post_id) {
        redirect_with_error(['general' => __('Failed to create product.', 'pixeltrade')], $raw_post);
    }

    // Taxonomy
    wp_set_post_terms($post_id, $categories, 'product_cat');

    // Core meta
    update_post_meta($post_id, 'product_price', $price);
    update_post_meta($post_id, 'product_currency', $currency);
    update_post_meta($post_id, 'product_type', $type);
    update_post_meta($post_id, '_title_en', sanitize_text_field($raw_post['title_en'] ?? ''));
    update_post_meta($post_id, '_title_ro', sanitize_text_field($raw_post['title_ro'] ?? ''));
    update_post_meta($post_id, '_description_en', sanitize_textarea_field($raw_post['description_en'] ?? ''));
    update_post_meta($post_id, '_description_ro', sanitize_textarea_field($raw_post['description_ro'] ?? ''));

    // Dynamic fields
    if (!empty($raw_post['dynamic_fields'])) {
        $decoded = json_decode($raw_post['dynamic_fields'], true);
        if (is_array($decoded)) {
            $dynamic_fields = sanitize_dynamic_fields($decoded);
            $new_features = [];
            foreach ($dynamic_fields as $key => $value) {
                $meta_key = '__' . ltrim($key, '_'); // always __key
                $new_features[$meta_key] = $value; // Save each field separately
                update_post_meta($post_id, $meta_key, $value);
            }
            // Save entire array
            update_post_meta($post_id, 'dynamic_features', $new_features);
        }
    }

    // Handle gallery
    if (!function_exists('media_handle_sideload')) {
        require_once ABSPATH . 'wp-admin/includes/image.php';
        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/media.php';
    }
    $attachment_ids = [];
    $files = $_FILES['product_gallery'] ?? null;
    if ($files && !empty($files['name'][0])) {
        $count = count($files['name']);
        for ($i = 0; $i < $count && $i < 10; $i++) {
            if ($files['error'][$i] !== UPLOAD_ERR_OK) continue;
            $tmp = $files['tmp_name'][$i];
            $orig = $files['name'][$i];
            $size = $files['size'][$i];
            if ($size <= 0 || $size > 5*1024*1024) continue;
            if (!is_image_file_safe($tmp)) continue;
            $check = wp_check_filetype_and_ext($tmp, $orig);
            if (!$check['type'] || !in_array($check['type'], ['image/jpeg','image/png','image/webp','image/gif'], true)) continue;
            $file_array = ['name'=>generate_random_filename($orig),'tmp_name'=>$tmp];
            if ($size>500*1024) {
                $img = wp_get_image_editor($tmp);
                if(!is_wp_error($img) && method_exists($img,'set_quality')) {
                    $img->set_quality(80);
                    $img->save($tmp);
                }
            }
            $attach_id = media_handle_sideload($file_array, $post_id);
            if(!is_wp_error($attach_id)) $attachment_ids[] = $attach_id;
        }
    }
    if (!empty($attachment_ids)) {
        update_post_meta($post_id, 'product_gallery', $attachment_ids);
        set_post_thumbnail($post_id, $attachment_ids[0]);
    }

    wp_safe_redirect(get_permalink($post_id));
    exit;
}
