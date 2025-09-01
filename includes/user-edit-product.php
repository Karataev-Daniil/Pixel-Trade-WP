<?php
function handle_product_edit_form_submission() {
    if (
        !isset($_POST['submit_product']) ||
        !isset($_POST['product_form_nonce']) ||
        !wp_verify_nonce($_POST['product_form_nonce'], 'save_product_form') ||
        !current_user_can('edit_posts')
    ) {
        return;
    }

    $product_id = intval($_POST['product_id'] ?? 0);
    if (!$product_id || get_post_type($product_id) !== 'products') return;

    if (get_current_user_id() !== (int)get_post_field('post_author', $product_id)) {
        wp_die('У вас нет прав для редактирования этого товара.');
    }

    wp_update_post([
        'ID'           => $product_id,
        'post_title'   => sanitize_text_field($_POST['product_title'] ?? ''),
        'post_content' => sanitize_textarea_field($_POST['product_content'] ?? ''),
        'post_status'  => sanitize_text_field($_POST['product_status'] ?? 'draft'),
    ]);

    update_post_meta($product_id, 'product_price', sanitize_text_field($_POST['product_price'] ?? ''));

    if (!empty($_POST['product_categories']) && is_array($_POST['product_categories'])) {
        $category_ids = array_map('intval', $_POST['product_categories']);
        wp_set_post_terms($product_id, $category_ids, 'product_cat');
    } else {
        wp_set_post_terms($product_id, [], 'product_cat');
    }

    update_post_meta($product_id, '_title_en', sanitize_text_field($_POST['title_en'] ?? ''));
    update_post_meta($product_id, '_title_ro', sanitize_text_field($_POST['title_ro'] ?? ''));
    update_post_meta($product_id, '_description_en', sanitize_textarea_field($_POST['description_en'] ?? ''));
    update_post_meta($product_id, '_description_ro', sanitize_textarea_field($_POST['description_ro'] ?? ''));

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
            $file['name'] = wp_generate_password(12, false, false) . '.' . pathinfo($file['name'], PATHINFO_EXTENSION);
            return $file;
        });

        $files = $_FILES['product_gallery_input'];
        foreach ($files['name'] as $i => $name) {
            if ($files['error'][$i] !== UPLOAD_ERR_OK) continue;

            $_FILES['single_file_upload'] = [
                'name'     => $files['name'][$i],
                'type'     => $files['type'][$i],
                'tmp_name' => $files['tmp_name'][$i],
                'error'    => $files['error'][$i],
                'size'     => $files['size'][$i],
            ];

            $attachment_id = media_handle_upload('single_file_upload', $product_id);
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
            if (isset($new_attachment_ids[$index])) {
                $final_gallery[] = $new_attachment_ids[$index];
            }
        } else {
            $id = (int)$order_id;
            if (in_array($id, $current_gallery)) {
                $final_gallery[] = $id;
            }
        }
    }

    update_post_meta($product_id, 'product_gallery', $final_gallery);

    if (!empty($final_gallery)) {
        set_post_thumbnail($product_id, $final_gallery[0]);
    } else {
        delete_post_thumbnail($product_id);
    }

    wp_redirect(get_permalink($product_id));
    exit;
}
add_action('init', 'handle_product_edit_form_submission');

