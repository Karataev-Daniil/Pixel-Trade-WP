<?php
function delete_product_and_attachments($post_id, $nonce = '') {
    $post_id = intval($post_id);
    if (!$post_id || get_post_type($post_id) !== 'products') return false;

    if ($nonce && !wp_verify_nonce($nonce, 'delete_product_' . $post_id)) {
        wp_die('Ошибка безопасности.'); 
    }

    $current_user_id = get_current_user_id();
    $post_author_id = (int) get_post_field('post_author', $post_id);

    if ($current_user_id !== $post_author_id && !current_user_can('manage_options')) {
        wp_die('У вас нет прав для удаления этого поста.');
    }

    $attachments = get_post_meta($post_id, 'product_gallery', true);
    $attachments = is_array($attachments) ? $attachments : [];

    $thumbnail_id = get_post_thumbnail_id($post_id);
    if ($thumbnail_id) $attachments[] = $thumbnail_id;

    foreach ($attachments as $attachment_id) {
        wp_delete_attachment((int)$attachment_id, true);
    }

    wp_delete_post($post_id, true);

    return true;
}
add_action('wp_ajax_delete_product', function() {
    $post_id = intval($_POST['product_id'] ?? 0);
    $nonce   = $_POST['nonce'] ?? '';

    if (!$post_id || !wp_verify_nonce($nonce, 'delete_product_' . $post_id)) {
        wp_send_json_error(['message' => t('Ошибка безопасности.', 'Security error.', 'Eroare de securitate.')]);
    }

    $post_author_id = (int) get_post_field('post_author', $post_id);
    if (get_current_user_id() !== $post_author_id && !current_user_can('manage_options')) {
        wp_send_json_error(['message' => t('У вас нет прав для удаления этого поста.', 'You do not have permission to delete this post.', 'Nu aveți permisiunea de a șterge această postare.')]);
    }

    $result = delete_product_and_attachments($post_id);
    if ($result) {
        wp_send_json_success(['message' => t('Товар был удалён.', 'Product has been deleted.', 'Produsul a fost șters.')]);
    } else {
        wp_send_json_error(['message' => t('Не удалось удалить товар.', 'Failed to delete product.', 'Nu s-a putut șterge produsul.')]);
    }
});

add_action('admin_post_nopriv_delete_product', function() {
    wp_die('Только авторизованные пользователи могут удалять продукты.');
});
