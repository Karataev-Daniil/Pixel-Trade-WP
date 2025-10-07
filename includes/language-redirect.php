<?php
add_action('init', function() {
    $langs = ['ru', 'ro', 'en'];
    $langs_regex = implode('|', $langs);

    // Products
    add_rewrite_rule(
        "^($langs_regex)/products/([^/]+)/?$",
        'index.php?post_type=products&name=$matches[2]',
        'top'
    );

    // Categories
    add_rewrite_rule(
        "^($langs_regex)/categories/(.+)/?$",
        'index.php?product_cat=$matches[2]',
        'top'
    );

    // Favorites (with pagination)
    add_rewrite_rule(
        "^($langs_regex)/user/favorites/page/([0-9]+)/?$",
        'index.php?pagename=user/favorites&paged=$matches[2]',
        'top'
    );

    // Favorites (main)
    add_rewrite_rule(
        "^($langs_regex)/user/favorites/?$",
        'index.php?pagename=user/favorites',
        'top'
    );

    // Pages
    add_rewrite_rule(
        "^($langs_regex)/(.*)/?$",
        'index.php?pagename=$matches[2]',
        'top'
    );

    // Front page
    $front_id = get_option('page_on_front');

    if ($front_id && $front_id != 0) {
        add_rewrite_rule(
            "^($langs_regex)/?$",
            'index.php?page_id=' . $front_id,
            'top'
        );
    } else {
        add_rewrite_rule(
            "^($langs_regex)/?$",
            'index.php?post_type=post',
            'top'
        );
    }
});

add_filter('post_type_link', function($post_link, $post) {
    if ($post->post_type === 'products') {
        $lang = $GLOBALS['language'] ?? 'ru';
        return home_url("/$lang/products/{$post->post_name}/");
    }
    return $post_link;
}, 10, 2);

add_filter('term_link', function($url, $term, $taxonomy) {
    if ($taxonomy === 'product_cat') {
        $lang = $GLOBALS['language'] ?? 'ru';
        $url = untrailingslashit($url);
        return home_url("/$lang" . parse_url($url, PHP_URL_PATH));
    }
    return $url;
}, 10, 3);

add_action('template_redirect', function() {
    if (is_admin() || (defined('DOING_AJAX') && DOING_AJAX) || (defined('REST_REQUEST') && REST_REQUEST)) {
        return;
    }

    $languages = ['ru','en','ro'];
    $uri = trim($_SERVER['REQUEST_URI'], '/');
    $uri_path = parse_url($uri, PHP_URL_PATH);
    $uri_parts = explode('/', $uri_path);
    $first_part = $uri_parts[0] ?? '';

    $search_page_slug = 'my-products';

    if (isset($_GET['s']) && !empty($_GET['s']) && in_array($search_page_slug, $uri_parts)) {
        return;
    }

    if ($uri_path === '') {
        $lang = $_COOKIE['language'] ?? 'ru';
        $lang = in_array($lang, $languages) ? $lang : 'ru';
        wp_redirect(home_url("/$lang/"), 301);
        exit;
    }

    if ($first_part && !in_array($first_part, $languages)) {
        wp_redirect(home_url('/ru/' . $uri_path), 301);
        exit;
    }

    $GLOBALS['language'] = $first_part ?: 'ru';
    setcookie('language', $GLOBALS['language'], time() + 30*DAY_IN_SECONDS, COOKIEPATH, COOKIE_DOMAIN);
});