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
require_once get_template_directory() . '/includes/user-create-product.php';
require_once get_template_directory() . '/includes/user-edit-product.php';
require_once get_template_directory() . '/includes/user-delete-product.php';
require_once get_template_directory() . '/includes/user-settings.php';
require_once get_template_directory() . '/includes/user-favorites.php';
require_once get_template_directory() . '/includes/user-messenger/user-messenger.php';

// Admin / Moderation
require_once get_template_directory() . '/includes/admin-approval.php';

// AI / Translation
require_once get_template_directory() . '/includes/translation-product-ai.php';

// Language Redirect / Handling
require_once get_template_directory() . '/includes/language-redirect.php';

// AJAX Handlers
require_once get_template_directory() . '/includes/ajax-products.php';