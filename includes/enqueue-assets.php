<?php
function custom_enqueue_assets() {
    $theme_dir  = get_stylesheet_directory_uri();
    $theme_path = get_stylesheet_directory();

    $styles = [
        'style'            => '/style.css',
        'fonts'            => '/assets/css/fonts.css',
        'reset'            => '/assets/css/reset.css',
        'buttons-ui'       => '/assets/css/ui-kit/buttons.css',
        'pallete-colors'   => '/assets/css/ui-kit/pallete-collors.css',
        'inputs-ui'        => '/assets/css/ui-kit/inputs.css',
        'typography'       => '/assets/css/ui-kit/typography.css',
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

    if ( is_page( 'my-products' ) ) {
        wp_enqueue_style(
            'page-user-products-style',
            $theme_dir . '/assets/css/template/page-user-products.css',
            [],
            filemtime( $theme_path . '/assets/css/template/page-user-products.css' )
        );
    }

    if ( is_front_page() || is_tax( 'product_cat' )) {
        wp_enqueue_style(
            'front-page-style',
            $theme_dir . '/assets/css/template/front-page.css',
            [],
            filemtime( $theme_path . '/assets/css/template/front-page.css' )
        );
    }

    if ( is_singular( 'product' ) || is_page( 'add-product' )) {
        wp_enqueue_style(
            'single-product-style',
            $theme_dir . '/assets/css/template/single-product.css',
            [],
            filemtime( $theme_path . '/assets/css/template/single-product.css' )
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

    if ( is_front_page() ) {
        wp_enqueue_script(
            'ajax-filter-script',
            $theme_dir . '/assets/js/ajax-filter.js',
            ['jquery'],
            filemtime( $theme_path . '/assets/js/ajax-filter.js' ),
            true
        );

        wp_localize_script( 'ajax-filter-script', 'ajaxFilterData', [
            'ajaxurl' => admin_url( 'admin-ajax.php' ),
        ]);
    }

    if ( is_page( 'login' ) || is_page( 'register' ) ) {
        wp_enqueue_style(
            'page-auth',
            get_stylesheet_directory_uri() . '/assets/css/template/page-auth.css',
            array(),
            filemtime( get_stylesheet_directory() . '/assets/css/template/page-auth.css' )
        );
    }

    if ( is_page( 'user-settings' ) ) {
        wp_enqueue_style(
            'page-user-settings',
            get_stylesheet_directory_uri() . '/assets/css/template/page-user-settings.css',
            array(),
            filemtime( get_stylesheet_directory() . '/assets/css/template/page-user-settings.css' )
        );
    }


    wp_enqueue_style(
        'page-test-style',
        $theme_dir . '/assets/css/template/page-test.css',
        [],
        filemtime( $theme_path . '/assets/css/template/page-test.css' )
    );
    


    if ( is_singular( 'product' ) || is_page( 'add-product' ) ) {
        global $language;
        wp_enqueue_script(
            'category-selector',
            $theme_dir . '/assets/js/category-selector.js',
            [],
            filemtime( $theme_path . '/assets/js/category-selector.js' ),
            true
        );
        wp_localize_script( 'category-selector', 'categorySelectorVars', [
            'ajaxUrl' => admin_url( 'admin-ajax.php' ),
            'language' => $language,
        ] );

        wp_enqueue_script(
            'translation-generator',
            $theme_dir . '/assets/js/translation-generator.js',
            [],
            filemtime( $theme_path . '/assets/js/translation-generator.js' ),
            true
        );
        wp_localize_script( 'translation-generator', 'translationVars', [
            'ajaxUrl' => admin_url( 'admin-ajax.php' ),
            'nonce'   => wp_create_nonce( 'generate_translations_nonce' ),
        ] );

        wp_enqueue_script(
            'sortablejs',
            'https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js',
            [],
            null,
            true
        );

        wp_enqueue_script(
            'gallery-manager',
            $theme_dir . '/assets/js/gallery-manager.js',
            [ 'sortablejs' ],
            filemtime( $theme_path . '/assets/js/gallery-manager.js' ),
            true
        );
    }

    function enqueue_chat_assets() {
        wp_enqueue_script('chat-js', get_template_directory_uri() . '/assets/js/chat.js', ['jquery'], null, true);
        wp_localize_script('chat-js', 'ajaxurl', admin_url('admin-ajax.php'));
    }
    add_action('wp_enqueue_scripts', 'enqueue_chat_assets');


    wp_enqueue_script(
        'slick-js',
        $theme_dir . '/includes/slick/slick.min.js',
        [ 'jquery' ],
        filemtime( $theme_path . '/includes/slick/slick.min.js' ),
        true
    );
}
add_action( 'wp_enqueue_scripts', 'custom_enqueue_assets' );

function favorites_enqueue_assets() {
    wp_enqueue_script(
        'favorites-js',
        get_template_directory_uri() . '/assets/js/favorites.js',
        ['jquery'],
        null,
        true
    );
    wp_localize_script('favorites-js', 'favorites_ajax', [
        'url' => admin_url('admin-ajax.php'),
    ]);
}
add_action('wp_enqueue_scripts', 'favorites_enqueue_assets');

