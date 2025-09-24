<?php
// Helpers / Global
require_once get_template_directory() . '/includes/global/settings.php';
require_once get_template_directory() . '/includes/helpers.php';
require_once get_template_directory() . '/includes/custom-seo.php';
require_once get_template_directory() . '/includes/enqueue-assets.php';
require_once get_template_directory() . '/includes/image-sizes.php';

// Post Types & Roles
require_once get_template_directory() . '/includes/custom-post-types.php';
require_once get_template_directory() . '/includes/user-roles.php';
require_once get_template_directory() . '/includes/product-helpers.php';

// User Actions
require_once get_template_directory() . '/includes/user-registration.php';
require_once get_template_directory() . '/includes/user-login.php';
require_once get_template_directory() . '/includes/user-settings.php';
require_once get_template_directory() . '/includes/user-create-product.php';
require_once get_template_directory() . '/includes/user-edit-product.php';
require_once get_template_directory() . '/includes/user-delete-product.php';
require_once get_template_directory() . '/includes/user-favorites.php';
require_once get_template_directory() . '/includes/user-products-dashboard.php';
require_once get_template_directory() . '/includes/user-messenger/user-messenger.php';

// Admin / Moderation
require_once get_template_directory() . '/includes/admin-approval.php';

// AI / Translation
require_once get_template_directory() . '/includes/translation-product-ai.php';

// Language Redirect / Handling
require_once get_template_directory() . '/includes/language-redirect.php';

// AJAX Handlers
require_once get_template_directory() . '/includes/ajax-products.php';

// Recommended Products (Homepage)
require_once get_template_directory() . '/includes/recommended-products.php';

// User Public Profile
require_once get_template_directory() . '/includes/user-public-profile.php';

// Получаем динамические поля по категориям
// add_action('wp_ajax_get_category_features', 'ajax_get_category_features');
// add_action('wp_ajax_nopriv_get_category_features', 'ajax_get_category_features');

// function ajax_get_category_features() {
//     global $wpdb;
//     $category_ids = isset($_GET['categories']) ? explode(',', $_GET['categories']) : [];
//     $category_ids = array_map('intval', $category_ids);
//     $result = [];

//     if ($category_ids) {
//         $features_table = $wpdb->prefix . 'features';
//         $options_table  = $wpdb->prefix . 'feature_options';

//         foreach ($category_ids as $cat_id) {
//             $fields = $wpdb->get_results($wpdb->prepare(
//                 "SELECT id, label_ru, label_en, label_ro, type FROM $features_table WHERE category_id = %d",
//                 $cat_id
//             ), ARRAY_A);

//             if (!$fields) continue;

//             foreach ($fields as $field) {
//                 $fieldData = [
//                     'label' => [
//                         'ru' => $field['label_ru'],
//                         'en' => $field['label_en'],
//                         'ro' => $field['label_ro']
//                     ]
//                 ];

//                 if ($field['type'] === 'select') {
//                     $options = $wpdb->get_results($wpdb->prepare(
//                         "SELECT value_ru, value_en, value_ro FROM $options_table WHERE feature_id = %d ORDER BY id ASC",
//                         $field['id']
//                     ), ARRAY_A);

//                     $fieldData['options'] = array_map(function($opt) {
//                         return [
//                             'ru' => $opt['value_ru'],
//                             'en' => $opt['value_en'],
//                             'ro' => $opt['value_ro']
//                         ];
//                     }, $options);
//                 }

//                 $result[$cat_id]['_' . sanitize_title($field['label_ru'])] = $fieldData;
//             }
//         }
//     }

//     wp_send_json($result);
// }
