<?php
// === Frontend scripts & styles ===
add_action('wp_enqueue_scripts', function() {
    $theme_dir  = get_stylesheet_directory_uri();
    $theme_path = get_stylesheet_directory();
    $lang       = $GLOBALS['language'] ?? 'ru';

    // React widgets
    wp_enqueue_style('dm-style', $theme_dir . '/assets/css/widgets/messenger.css');
    wp_enqueue_script('react', 'https://unpkg.com/react@18/umd/react.production.min.js', [], null, true);
    wp_enqueue_script('react-dom', 'https://unpkg.com/react-dom@18/umd/react-dom.production.min.js', ['react'], null, true);

    wp_enqueue_script('dm-bot-js', $theme_dir . '/bot.js', ['react', 'react-dom'], null, true);
    wp_enqueue_script('dm-messenger-js', $theme_dir . '/assets/js/messenger.js', ['react', 'react-dom'], null, true);

    wp_localize_script('dm-bot-js', 'ChatBotAjax', [
        'ajax_url' => admin_url('admin-ajax.php'),
    ]);

    wp_localize_script('dm-messenger-js', 'SIMPLE_DM', [
        'rest' => rest_url('dm/v1/'),
        'nonce' => wp_create_nonce('wp_rest'),
        'currentUser' => [
            'id' => get_current_user_id(),
            'name' => wp_get_current_user()->display_name,
            'avatar' => ($avatar_id = get_user_meta(get_current_user_id(), 'profile_avatar', true))
                ? wp_get_attachment_url($avatar_id)
                : get_avatar_url(get_current_user_id()),
            'language' => $lang
        ],
        'defaultAvatar' => $theme_dir . '/images/avatar-placeholder.jpg',
    ]);

    // Base styles
    $styles = [
        'style'            => '/style.css',
        'fonts'            => '/assets/css/fonts.css',
        'reset'            => '/assets/css/reset.css',
        'buttons-ui'       => '/assets/css/ui-kit/buttons.css',
        'pallete-colors'   => '/assets/css/ui-kit/pallete-collors.css',
        'inputs-ui'        => '/assets/css/ui-kit/inputs.css',
        'typography'       => '/assets/css/ui-kit/typography.css',
        'popup'            => '/assets/css/ui-kit/popup.css',
        'menu'             => '/assets/css/menu.css',
        'footer-menu'      => '/assets/css/footer-menu.css',
        'slick-css'        => '/includes/slick/slick.css',
    ];

    foreach ($styles as $handle => $path) {
        wp_enqueue_style($handle, $theme_dir . $path, [], filemtime($theme_path . $path));
    }

    // Global JS
    wp_enqueue_script('jquery');
    wp_enqueue_script('scripts', $theme_dir . '/assets/js/scripts.js', ['jquery'], filemtime($theme_path . '/assets/js/scripts.js'), true);
    wp_enqueue_script('popup', $theme_dir . '/assets/js/popup.js', ['jquery'], filemtime($theme_path . '/assets/js/popup.js'), true);
    wp_enqueue_script('main-menu', $theme_dir . '/assets/js/main-menu.js', ['jquery'], filemtime($theme_path . '/assets/js/main-menu.js'), true);
    wp_enqueue_script('slick-js', $theme_dir . '/includes/slick/slick.min.js', ['jquery'], filemtime($theme_path . '/includes/slick/slick.min.js'), true);

    wp_localize_script('popup', 'themeVars', [
        'ajaxurl' => admin_url('admin-ajax.php'),
        'language' => $lang,
        'imgPath' => $theme_dir . '/images/',
    ]);

    wp_localize_script('main-menu', 'ajax_object', [
        'ajaxurl' => admin_url('admin-ajax.php'),
        'icons' => [
            'category' => file_get_contents(get_template_directory() . '/images/icon-category.svg'),
            'user' => file_get_contents(get_template_directory() . '/images/icon-user.svg'),
        ],
    ]);

    // Conditional pages
    if (get_query_var('user_profile')) {
        wp_enqueue_style('page-user-profile', $theme_dir . '/assets/css/template/page-user-profile.css', [], filemtime($theme_path . '/assets/css/template/page-user-profile.css'));
        wp_enqueue_script('user-profile-js', $theme_dir . '/assets/js/user-profile.js', ['jquery'], filemtime($theme_path . '/assets/js/user-profile.js'), true);

        wp_localize_script('user-profile-js', 'USER_PROFILE_VARS', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'language'=> $lang,
            'imgPath' => $theme_dir . '/images/',
        ]);
    }

    if (is_page('products')) {
        wp_enqueue_style('page-user-products-style', $theme_dir . '/assets/css/template/page-user-products.css', [], filemtime($theme_path . '/assets/css/template/page-user-products.css'));
        wp_enqueue_script('chart-js', 'https://cdn.jsdelivr.net/npm/chart.js', [], null, true);
        wp_enqueue_script('user-products', $theme_dir . '/assets/js/user-products.js', ['jquery'], filemtime($theme_path . '/assets/js/user-products.js'), true);

        wp_localize_script('user-products', 'MY_PRODUCTS_AJAX', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce'   => wp_create_nonce('my_products_nonce'),
            'language'=> $lang,
        ]);

        wp_localize_script('user-products', 'MY_PRODUCTS_TEXT', [
            'loading'   => t('Загрузка товаров...', 'Loading products...', 'Se încarcă produsele...'),
            'not_found' => t('Товары не найдены.', 'Products not found.', 'Produse negăsite.'),
        ]);
    }

    if (is_page('favorites')) {
        wp_enqueue_style('page-favorites-style', $theme_dir . '/assets/css/template/page-favorites.css', [], filemtime($theme_path . '/assets/css/template/page-favorites.css'));
    }

    if (is_front_page() || is_page(['ru', 'ro', 'en'])) {
        wp_enqueue_style('front-page-style', $theme_dir . '/assets/css/template/front-page.css', [], filemtime($theme_path . '/assets/css/template/front-page.css'));
    }

    if (is_tax('product_cat')) {
        wp_enqueue_style('taxonomy-product-cat-style', $theme_dir . '/assets/css/template/taxonomy-product-cat.css', [], filemtime($theme_path . '/assets/css/template/taxonomy-product-cat.css'));
    }

    if (is_singular('products') || is_page('add-product')) {
        wp_enqueue_style('single-product-style', $theme_dir . '/assets/css/template/single-product.css', [], filemtime($theme_path . '/assets/css/template/single-product.css'));

        wp_enqueue_script('sortablejs', 'https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js', [], null, true);
        wp_enqueue_script('single-product-scripts', $theme_dir . '/assets/js/single-product.js', ['jquery', 'sortablejs'], filemtime($theme_path . '/assets/js/single-product.js'), true);
        wp_enqueue_script('category-selector', $theme_dir . '/assets/js/category-selector.js', ['jquery'], filemtime($theme_path . '/assets/js/category-selector.js'), true);
        wp_enqueue_script('product-translations', $theme_dir . '/assets/js/product-translations.js', [], filemtime($theme_path . '/assets/js/product-translations.js'), true);

        wp_localize_script('category-selector', 'categorySelectorVars', [
            'ajaxUrl'  => admin_url('admin-ajax.php'),
            'language' => $lang,
        ]);

        wp_localize_script('product-translations', 'translationVars', [
            'ajaxUrl'   => admin_url('admin-ajax.php'),
            'nonce'     => wp_create_nonce('generate_translations_nonce'),
            'activeTab' => $lang,
        ]);

        wp_localize_script('single-product-scripts', 'singleProductData', [
            'translations' => [
                'selectCategory' => t('Выберите категорию', 'Select category', 'Selectați categoria'),
                'labelLevel0'    => t('Категория', 'Category', 'Categorie'),
                'labelLevel1'    => t('Подкатегория', 'Subcategory', 'Subcategorie'),
                'labelLevel2'    => t('Под-подкатегория', 'Sub-subcategory', 'Sub-subcategorie'),
            ],
            'language' => $lang,
        ]);
    }

    if (is_page(['login', 'register'])) {
        wp_enqueue_style('page-auth', $theme_dir . '/assets/css/template/page-auth.css', [], filemtime($theme_path . '/assets/css/template/page-auth.css'));
    }

    if (is_page('settings')) {
        wp_enqueue_style('page-user-settings', $theme_dir . '/assets/css/template/page-user-settings.css', [], filemtime($theme_path . '/assets/css/template/page-user-settings.css'));
    }
});

// Admin scripts
add_action('admin_enqueue_scripts', function ($hook) {
    if (in_array($hook, ['edit-tags.php', 'term.php'])) {
        $screen = get_current_screen();
        if ($screen && $screen->taxonomy === 'product_cat') {
            wp_enqueue_media();
            wp_enqueue_script('product-cat-media', get_template_directory_uri() . '/assets/js/product-cat-media.js', ['jquery'], null, true);
        }
    }
});
