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





// add_action('init', function () {
//     if (is_admin() || current_user_can('manage_options')) {
//         return;
//     }

//     // Пропускаем AJAX и REST API
//     if (defined('DOING_AJAX') && DOING_AJAX) return;
//     if (defined('REST_REQUEST') && REST_REQUEST) return;

//     $ip = $_SERVER['REMOTE_ADDR'];

//     // Проверяем transient, чтобы не дергать API каждый раз
//     $country = get_transient('user_country_' . md5($ip));
//     if (!$country) {
//         $response = wp_remote_get("https://ipapi.co/{$ip}/country/");
//         if (is_wp_error($response)) return;

//         $country = trim(wp_remote_retrieve_body($response));
//         // Кэшируем на 12 часов
//         set_transient('user_country_' . md5($ip), $country, 12 * HOUR_IN_SECONDS);
//     }

//     if ($country !== 'MD') {
//         wp_die(
//             __('Доступ запрещён. Сайт доступен только из Молдовы.', 'pixeltrade'),
//             __('Доступ запрещён', 'pixeltrade'),
//             ['response' => 403]
//         );
//     }
// });
