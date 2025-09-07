<?php
add_action('init', function() {
    add_rewrite_rule(
        '^(ru|en|ro)/products/([^/]+)/?$',
        'index.php?post_type=products&name=$matches[2]',
        'top'
    );

    add_rewrite_rule(
        '^(ru|en|ro)/categories/([^/]+)/?$',
        'index.php?product_cat=$matches[2]',
        'top'
    );

    add_rewrite_rule(
        '^(ru|en|ro)/(.*)/?$',
        'index.php?pagename=$matches[2]',
        'top'
    );

    $front_id = get_option('page_on_front');

    if ($front_id && $front_id != 0) {
        add_rewrite_rule(
            '^(ru|en|ro)/?$',
            'index.php?page_id=' . $front_id,
            'top'
        );
    } else {
        add_rewrite_rule(
            '^(ru|en|ro)/?$',
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
    $languages = ['ru','en','ro'];
    $uri_parts = explode('/', trim($_SERVER['REQUEST_URI'], '/'));
    $first_part = $uri_parts[0] ?? '';

    if ($_SERVER['REQUEST_URI'] === '/' || $_SERVER['REQUEST_URI'] === '') {
        $lang = $_COOKIE['language'] ?? 'ru';
        $lang = in_array($lang, $languages) ? $lang : 'ru';

        wp_redirect(home_url("/$lang/"), 301);
        exit;
    }

    if ($first_part && !in_array($first_part, $languages)) {
        wp_redirect(home_url('/ru/' . implode('/', $uri_parts)), 301);
        exit;
    }

    $GLOBALS['language'] = $first_part ?: 'ru';

    setcookie('language', $GLOBALS['language'], time() + 30*DAY_IN_SECONDS, COOKIEPATH, COOKIE_DOMAIN);
});
