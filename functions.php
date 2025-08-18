<?php
require_once get_template_directory() . '/includes/global/settings.php';
require_once get_template_directory() . '/includes/helpers.php';

require_once get_template_directory() . '/includes/enqueue-assets.php';

require_once get_template_directory() . '/includes/custom-post-types.php';
require_once get_template_directory() . '/includes/user-roles.php';

require_once get_template_directory() . '/includes/user-registration.php';
require_once get_template_directory() . '/includes/user-login.php';
require_once get_template_directory() . '/includes/user-edit-product.php';

require_once get_template_directory() . '/includes/ajax/filter-products.php';

require_once get_template_directory() . '/includes/admin-approval.php';

require_once get_template_directory() . '/includes/openai-api.php';


add_action('wp_ajax_get_subcategories', 'get_subcategories_ajax');
add_action('wp_ajax_nopriv_get_subcategories', 'get_subcategories_ajax');

function get_subcategories_ajax() {
    $parent_id = isset($_GET['parent']) ? intval($_GET['parent']) : 0;

    $terms = get_terms([
        'taxonomy'   => 'product_cat',
        'hide_empty' => false,
        'parent'     => $parent_id,
    ]);

    $result = [];
    foreach ($terms as $term) {
        $result[] = [
            'term_id' => $term->term_id,
            'name'    => [
                'ru' => $term->name,
                'en' => get_term_meta($term->term_id, 'translation_en', true) ?: $term->name,
                'ro' => get_term_meta($term->term_id, 'translation_ro', true) ?: $term->name,
            ],
        ];
    }

    wp_send_json($result);
}


function sort_categories_by_hierarchy($categories) {
    if (empty($categories)) return [];

    $categories_by_id = [];
    foreach ($categories as $term) {
        $categories_by_id[$term->term_id] = $term;
    }

    $sorted = [];

    $leaf = null;
    foreach ($categories as $term) {
        if (!array_filter($categories, fn($t) => $t->parent === $term->term_id)) {
            $leaf = $term;
            break;
        }
    }

    while ($leaf) {
        $sorted[] = $leaf->term_id;
        $leaf = isset($categories_by_id[$leaf->parent]) ? $categories_by_id[$leaf->parent] : null;
    }

    return array_reverse($sorted);
}


// // Создание таблицы при активации темы
// function create_messages_table() {
//     global $wpdb;
//     $table_name = $wpdb->prefix . 'private_messages';
//     $charset_collate = $wpdb->get_charset_collate();

//     $sql = "CREATE TABLE $table_name (
//         id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
//         sender_id BIGINT(20) UNSIGNED NOT NULL,
//         receiver_id BIGINT(20) UNSIGNED NOT NULL,
//         message TEXT NOT NULL,
//         created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
//         PRIMARY KEY (id),
//         INDEX sender_idx (sender_id),
//         INDEX receiver_idx (receiver_id)
//     ) $charset_collate;";

//     require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
//     dbDelta($sql);
// }
// add_action('after_switch_theme', 'create_messages_table');


// // Отправка приватного сообщения
// add_action('wp_ajax_send_private_message', function() {
//     if (!is_user_logged_in()) {
//         wp_send_json_error(['error' => 'Требуется авторизация']);
//     }

//     check_ajax_referer('private_messages_nonce', 'security');

//     global $wpdb;
//     $sender_id = get_current_user_id();
//     $receiver_id = intval($_POST['receiver_id'] ?? 0);
//     $message = sanitize_textarea_field($_POST['message'] ?? '');

//     if (!$receiver_id || !$message) {
//         wp_send_json_error(['error' => 'Пустое сообщение или получатель']);
//     }

//     $wpdb->insert("{$wpdb->prefix}private_messages", [
//         'sender_id'   => $sender_id,
//         'receiver_id' => $receiver_id,
//         'message'     => $message,
//     ]);

//     wp_send_json_success(['message' => 'Сообщение отправлено']);
// });


// // Загрузка приватных сообщений
// add_action('wp_ajax_load_private_messages', function() {
//     if (!is_user_logged_in()) {
//         wp_send_json_error(['error' => 'Требуется авторизация']);
//     }

//     check_ajax_referer('private_messages_nonce', 'security');

//     global $wpdb;
//     $current_user = get_current_user_id();
//     $receiver_id = intval($_GET['receiver_id'] ?? 0);

//     $messages = $wpdb->get_results($wpdb->prepare(
//         "SELECT * FROM {$wpdb->prefix}private_messages
//         WHERE ((sender_id = %d AND receiver_id = %d)
//            OR  (sender_id = %d AND receiver_id = %d))
//         ORDER BY created_at ASC",
//         $current_user, $receiver_id, $receiver_id, $current_user
//     ));

//     ob_start();
//     foreach ($messages as $msg) {
//         $from = ($msg->sender_id == $current_user) ? 'Вы' : 'Он/она';
//         echo '<p><strong>' . esc_html($from) . ':</strong> ' . nl2br(esc_html($msg->message)) . '</p>';
//     }
//     $output = ob_get_clean();

//     wp_send_json_success(['html' => $output]);
// });


// // Автосохранение черновика продукта
// add_action('wp_ajax_autosave_product_draft', function () {
//     if (!is_user_logged_in()) {
//         wp_send_json_error(['error' => 'Требуется авторизация']);
//     }

//     check_ajax_referer('autosave_product_nonce', 'security');

//     $current_user_id = get_current_user_id();
//     $post_data = [
//         'post_title'   => sanitize_text_field($_POST['product_title'] ?? ''),
//         'post_content' => sanitize_textarea_field($_POST['product_content'] ?? ''),
//         'post_status'  => 'draft',
//         'post_type'    => 'product',
//         'post_author'  => $current_user_id,
//     ];

//     $existing_draft_id = get_user_meta($current_user_id, '_autosave_product_id', true);

//     if ($existing_draft_id && get_post_status($existing_draft_id) === 'draft') {
//         $post_data['ID'] = $existing_draft_id;
//         wp_update_post($post_data);
//     } else {
//         $draft_id = wp_insert_post($post_data);
//         if ($draft_id) {
//             update_user_meta($current_user_id, '_autosave_product_id', $draft_id);
//         }
//     }

//     wp_send_json_success(['message' => 'Черновик сохранён']);
// });

// add_action('wp_enqueue_scripts', function () {
//     wp_enqueue_script('chat', get_template_directory_uri() . '/js/chat.js', ['jquery'], '1.0', true);

//     wp_localize_script('chat', 'pm_vars', [
//         'ajax_url'  => admin_url('admin-ajax.php'),
//         'nonce'     => wp_create_nonce('private_messages_nonce'),
//         'user_id'   => get_current_user_id(),
//     ]);
// });

