<?php
add_action('template_redirect', 'kayo_handle_product_submission');
function kayo_handle_product_submission() {
    if (!is_user_logged_in() || !isset($_POST['submit_product'])) return;

    if (!isset($_POST['product_form_nonce']) || !wp_verify_nonce($_POST['product_form_nonce'], 'save_product_form')) return;

    $current_user_id = get_current_user_id();
    $title = sanitize_text_field($_POST['product_title']);
    $content = sanitize_textarea_field($_POST['product_content']);
    $product_id = intval($_POST['product_id']);

    $post_data = [
        'post_title'   => $title,
        'post_content' => $content,
        'post_status'  => 'pending',
        'post_type'    => 'products',
        'post_author'  => $current_user_id,
    ];

    if ($product_id) {
        $post_data['ID'] = $product_id;
        wp_update_post($post_data);
    } else {
        wp_insert_post($post_data);
    }

    wp_redirect(remove_query_arg(['add_product', 'edit_product']));
    exit;
}

add_action('template_redirect', 'kayo_handle_product_deletion');
function kayo_handle_product_deletion() {
    if (!is_user_logged_in() || !isset($_GET['delete_product'])) return;

    $product_id = intval($_GET['delete_product']);
    if (!wp_verify_nonce($_GET['_wpnonce'], 'delete_product_' . $product_id)) return;

    $post = get_post($product_id);
    if ($post && $post->post_author == get_current_user_id()) {
        wp_trash_post($product_id);
    }

    wp_redirect(remove_query_arg(['delete_product', '_wpnonce']));
    exit;
}
