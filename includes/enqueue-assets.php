<?php
function custom_enqueue_assets() {
    $theme_dir  = get_stylesheet_directory_uri();
    $theme_path = get_stylesheet_directory();
    $lang       = $GLOBALS['language'] ?? 'ru';

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
        'test-ui-page'     => '/assets/css/template/test-ui-page.css',
        'slick-css'        => '/includes/slick/slick.css',
    ];

    foreach ( $styles as $handle => $path ) {
        wp_enqueue_style(
            $handle,
            $theme_dir . $path,
            [],
            filemtime( $theme_path . $path )
        );
    }

    wp_enqueue_script( 'jquery' );
    wp_enqueue_script(
        'scripts',
        $theme_dir . '/assets/js/scripts.js',
        [ 'jquery' ],
        filemtime( $theme_path . '/assets/js/scripts.js' ),
        true
    );

    wp_enqueue_script(
        'popup', 
        $theme_dir . '/assets/js/popup.js',
        ['jquery'],
        filemtime( $theme_path . '/assets/js/popup.js' ),
        true
    );

    wp_localize_script('popup', 'themeVars', [
        'ajaxurl' => admin_url('admin-ajax.php'),
        'language' => $GLOBALS['language'] ?? 'ru',
        'imgPath' => get_template_directory_uri() . '/images/'
    ]);

    wp_enqueue_script(
        'catalog-menu',
        get_template_directory_uri() . '/assets/js/dropdown-catalog.js',
        ['jquery'],
        filemtime(get_template_directory() . '/assets/js/dropdown-catalog.js'),
        true
    );


    wp_enqueue_script(
        'slick-js',
        $theme_dir . '/includes/slick/slick.min.js',
        [ 'jquery' ],
        filemtime( $theme_path . '/includes/slick/slick.min.js' ),
        true
    );

    if ( get_query_var('user_profile') ) {
        wp_enqueue_style(
            'page-user-profile',
            $theme_dir . '/assets/css/template/page-user-profile.css',
            [],
            filemtime($theme_path . '/assets/css/template/page-user-profile.css')
        );

        wp_enqueue_script(
            'user-profile-js',
            $theme_dir . '/assets/js/user-profile.js',
            ['jquery'],
            filemtime($theme_path . '/assets/js/user-profile.js'),
            true
        );

        wp_localize_script('user-profile-js', 'USER_PROFILE_VARS', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'language'=> $lang,
            'imgPath' => get_template_directory_uri() . '/images/'
        ]);
    }

    if ( is_page('my-products') ) {
        wp_enqueue_style(
            'page-user-products-style',
            $theme_dir . '/assets/css/template/page-user-products.css',
            [],
            filemtime($theme_path . '/assets/css/template/page-user-products.css')
        );

        wp_enqueue_script(
            'chart-js',
            'https://cdn.jsdelivr.net/npm/chart.js',
            [],
            null,
            true
        );
    
        wp_enqueue_script(
            'my-products',
            $theme_dir . '/assets/js/my-products.js',
            ['jquery'],
            filemtime($theme_path . '/assets/js/my-products.js'),
            true
        );
    
        wp_localize_script('my-products', 'MY_PRODUCTS_AJAX', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce'   => wp_create_nonce('my_products_nonce'),
            'language'=> $lang,
        ]);
    
        wp_localize_script('my-products', 'MY_PRODUCTS_TEXT', [
            'loading'   => t('Загрузка товаров...','Loading products...','Se încarcă produsele...'),
            'not_found' => t('Товары не найдены.','Products not found.','Produse negăsite.'),
        ]);
    }


    if ( is_page('favorites') ) {
        wp_enqueue_style(
            'page-favorites-style',
            $theme_dir . '/assets/css/template/page-favorites.css',
            [],
            filemtime($theme_path . '/assets/css/template/page-favorites.css')
        );
    }

    if ( is_front_page() || is_page('ru') || is_page('ro') || is_page('en') ) {
        wp_enqueue_style(
            'front-page-style',
            $theme_dir . '/assets/css/template/front-page.css',
            [],
            filemtime($theme_path . '/assets/css/template/front-page.css')
        );
    }

    if ( is_tax('product_cat') ) {
        wp_enqueue_style(
            'taxonomy-product-cat-style',
            $theme_dir . '/assets/css/template/taxonomy-product-cat.css',
            [],
            filemtime($theme_path . '/assets/css/template/taxonomy-product-cat.css')
        );
    }

    if ( is_singular('products') || is_page('add-product') ) {
        wp_enqueue_style(
            'single-product-style',
            $theme_dir . '/assets/css/template/single-product.css',
            [],
            filemtime($theme_path . '/assets/css/template/single-product.css')
        );

        wp_enqueue_script(
            'single-product-scripts',
            $theme_dir . '/assets/js/single-product.js',
            ['jquery', 'sortablejs'],
            filemtime($theme_path . '/assets/js/single-product.js'),
            true
        );

        wp_enqueue_script(
            'category-selector',
            $theme_dir . '/assets/js/category-selector.js',
            ['jquery'],
            filemtime($theme_path . '/assets/js/category-selector.js'),
            true
        );

        wp_localize_script('category-selector', 'categorySelectorVars', [
            'ajaxUrl'          => admin_url('admin-ajax.php'),
            'language'         => $lang,
            // 'categoryFeatures' => function_exists('get_product_category_features') ? get_product_category_features() : []
        ]);

        wp_enqueue_script(
            'product-translations',
            $theme_dir . '/assets/js/product-translations.js',
            [],
            filemtime($theme_path . '/assets/js/product-translations.js'),
            true
        );

        wp_localize_script('product-translations', 'translationVars', [
            'ajaxUrl'   => admin_url('admin-ajax.php'),
            'nonce'     => wp_create_nonce('generate_translations_nonce'),
            'activeTab' => $lang,
        ]);

        wp_enqueue_script(
            'sortablejs',
            'https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js',
            [],
            null,
            true
        );
        
        wp_localize_script('single-product-scripts', 'singleProductData', [
            'translations' => [
                'selectCategory' => function_exists('t') ? t('Выберите категорию', 'Select category', 'Selectați categoria') : 'Select category',
                'labelLevel0'    => function_exists('t') ? t('Категория', 'Category', 'Categorie') : 'Category',
                'labelLevel1'    => function_exists('t') ? t('Подкатегория', 'Subcategory', 'Subcategorie') : 'Subcategory',
                'labelLevel2'    => function_exists('t') ? t('Под-подкатегория', 'Sub-subcategory', 'Sub-subcategorie') : 'Sub-subcategory',
            ],
            'language' => $lang,
        ]);
    }

    if ( is_page('login') || is_page('register') ) {
        wp_enqueue_style(
            'page-auth',
            $theme_dir . '/assets/css/template/page-auth.css',
            [],
            filemtime($theme_path . '/assets/css/template/page-auth.css')
        );
    }

    if ( is_page('settings') ) {
        wp_enqueue_style(
            'page-user-settings',
            $theme_dir . '/assets/css/template/page-user-settings.css',
            [],
            filemtime($theme_path . '/assets/css/template/page-user-settings.css')
        );
    }
}
add_action('wp_enqueue_scripts', 'custom_enqueue_assets');

add_action('admin_enqueue_scripts', function ($hook) {
    // Только для страниц категорий WooCommerce
    if ('edit-tags.php' === $hook || 'term.php' === $hook) {
        $screen = get_current_screen();
        if ($screen && $screen->taxonomy === 'product_cat') {
            wp_enqueue_media();
            wp_enqueue_script(
                'product-cat-media',
                get_template_directory_uri() . '/assets/js/product-cat-media.js',
                ['jquery'],
                null,
                true
            );
        }
    }
});