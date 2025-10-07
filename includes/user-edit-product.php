<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Handle product edit
function handle_product_edit_form_submission() {
    if (
        !isset($_POST['submit_product']) ||
        !isset($_POST['product_form_nonce']) ||
        !wp_verify_nonce($_POST['product_form_nonce'], 'save_product_form') ||
        !current_user_can('edit_posts')
    ) return;

    $product_id = intval($_POST['product_id'] ?? 0);
    if (!$product_id || get_post_type($product_id) !== 'products') return;

    if (get_current_user_id() !== (int)get_post_field('post_author', $product_id)) {
        wp_die('У вас нет прав для редактирования этого товара.');
    }

    $lang = sanitize_text_field($_POST['product_lang'] ?? 'ru');
    $title_field   = $lang === 'ru' ? 'product_title' : ($lang === 'en' ? 'title_en' : 'title_ro');
    $content_field = $lang === 'ru' ? 'product_content' : ($lang === 'en' ? 'description_en' : 'description_ro');

    $title   = sanitize_text_field(trim($_POST[$title_field] ?? ''));
    $content = sanitize_textarea_field(trim($_POST[$content_field] ?? ''));
    $price   = floatval($_POST['product_price'] ?? 0);
    $currency = strtolower(sanitize_text_field($_POST['product_currency'] ?? 'lei'));
    $type    = sanitize_text_field($_POST['product_type'] ?? '');
    $status  = sanitize_text_field($_POST['product_status'] ?? 'draft');
    $categories = $_POST['product_categories'] ?? [];
    if (!is_array($categories)) $categories = [];

    // Server-side validation
    $errors = [];
    if (!$title) $errors[$title_field] = t('Заполните заголовок', 'Please fill the title', 'Completați titlul');
    if (!$content) $errors[$content_field] = t('Заполните описание', 'Please fill the content', 'Completați descrierea');
    if ($price <= 0) $errors['product_price'] = t('Укажите корректную цену', 'Please provide a valid price', 'Vă rugăm să furnizați un preț valid');
    if (empty($categories)) $errors['selected_categories'] = t('Выберите хотя бы одну категорию', 'Please select at least one category', 'Selectați cel puțin o categorie');
    if (!in_array($currency, ['lei','usd','eur'], true)) $currency = 'lei';

    if (!empty($errors)) {
        redirect_with_error($errors, $_POST, wp_get_referer());
    }

    // Update post
    wp_update_post([
        'ID'           => $product_id,
        'post_title'   => $title,
        'post_content' => $content,
        'post_status'  => $status,
    ]);

    update_post_meta($product_id, 'product_price', $price);
    update_post_meta($product_id, 'product_currency', $currency);
    update_post_meta($product_id, 'product_type', $type);

    // Categories
    $category_ids = array_map('intval', $categories);
    wp_set_post_terms($product_id, $category_ids, 'product_cat');

    // Titles & Descriptions for other languages
    update_post_meta($product_id, '_title_en', sanitize_text_field($_POST['title_en'] ?? ''));
    update_post_meta($product_id, '_title_ro', sanitize_text_field($_POST['title_ro'] ?? ''));
    update_post_meta($product_id, '_description_en', sanitize_textarea_field($_POST['description_en'] ?? ''));
    update_post_meta($product_id, '_description_ro', sanitize_textarea_field($_POST['description_ro'] ?? ''));

    // Dynamic fields
    if (!empty($_POST['dynamic_fields'])) {
        $dynamic_fields = json_decode(wp_unslash($_POST['dynamic_fields']), true);
        if (is_array($dynamic_fields)) {
            $dynamic_fields = sanitize_dynamic_fields($dynamic_fields);

            $new_features = [];

            // Save individual fields with double underscore
            foreach ($dynamic_fields as $key => $value) {
                $meta_key = '__' . ltrim(sanitize_key($key), '_');

                if ($value === '' || $value === null) {
                    delete_post_meta($product_id, $meta_key);
                } else {
                    update_post_meta($product_id, $meta_key, $value);
                    $new_features[$meta_key] = $value;
                }
            }

            // Save the entire array with __ keys
            update_post_meta($product_id, 'dynamic_features', $new_features);
        }
    }

    // Gallery handling
    if (!function_exists('media_handle_upload')) {
        require_once ABSPATH . 'wp-admin/includes/image.php';
        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/media.php';
    }

    $remove_ids = array_filter(array_map('intval', explode(',', $_POST['remove_gallery_ids_input'] ?? '')));
    $current_gallery = get_post_meta($product_id, 'product_gallery', true);
    $current_gallery = is_array($current_gallery) ? $current_gallery : [];
    $current_gallery = array_diff($current_gallery, $remove_ids);

    foreach ($remove_ids as $remove_id) {
        wp_delete_attachment((int)$remove_id, true);
    }

    $new_attachment_ids = [];
    if (!empty($_FILES['product_gallery_input']['name'][0])) {
        add_filter('wp_handle_upload_prefilter', function($file) {
            $file['name'] = generate_random_filename($file['name']);
            return $file;
        });

        $files = $_FILES['product_gallery_input'];
        foreach ($files['name'] as $i => $name) {
            if ($files['error'][$i] !== UPLOAD_ERR_OK) continue;

            $file_array = [
                'name'     => $name,
                'type'     => $files['type'][$i],
                'tmp_name' => $files['tmp_name'][$i],
                'error'    => $files['error'][$i],
                'size'     => $files['size'][$i],
            ];

            if ($file_array['size'] > 200 * 1024) {
                $image = wp_get_image_editor($file_array['tmp_name']);
                if (!is_wp_error($image)) {
                    $mime = $file_array['type'];
                    if ($mime === 'image/jpeg' || $mime === 'image/jpg' || $mime === 'image/webp') {
                        $image->set_quality(80);
                    }
                    $image->save($file_array['tmp_name']);
                }
            }

            $attachment_id = media_handle_sideload($file_array, $product_id);
            if (!is_wp_error($attachment_id)) {
                $new_attachment_ids[] = $attachment_id;
            }
        }
        remove_all_filters('wp_handle_upload_prefilter');
    }

    $final_gallery = [];
    $gallery_order = explode(',', $_POST['gallery_order_input'] ?? '');
    foreach ($gallery_order as $order_id) {
        if (strpos($order_id, 'new-') === 0) {
            $index = (int) str_replace('new-', '', $order_id);
            if (isset($new_attachment_ids[$index])) $final_gallery[] = $new_attachment_ids[$index];
        } else {
            $id = (int)$order_id;
            if (in_array($id, $current_gallery)) $final_gallery[] = $id;
        }
    }

    update_post_meta($product_id, 'product_gallery', $final_gallery);

    if (!empty($final_gallery)) {
        set_post_thumbnail($product_id, $final_gallery[0]);
    } else {
        delete_post_thumbnail($product_id);
    }

    // Clear related products cache
    delete_transient('related_products_' . $product_id);

    wp_redirect(get_permalink($product_id));
    exit;
}
add_action('admin_post_edit_product', 'handle_product_edit_form_submission');
