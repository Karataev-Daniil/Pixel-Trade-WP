<?php
function t($ru, $en, $ro) {
    global $language;
    return $language == 'en' ? $en : ($language == 'ro' ? $ro : $ru);
}

if (!function_exists('get_category_name_translated')) {
    function get_category_name_translated($term, $lang) {
        if ($lang==='en') return get_term_meta($term->term_id,'translation_en',true) ?: $term->name;
        if ($lang==='ro') return get_term_meta($term->term_id,'translation_ro',true) ?: $term->name;
        return $term->name;
    }
}
function set_theme_cookie() {
    if (isset($_GET['theme'])) {
        $theme = $_GET['theme'];
        setcookie('theme', $theme, time() + (30 * 24 * 60 * 60), '/');
    } elseif (isset($_COOKIE['theme'])) {
        $theme = $_COOKIE['theme']; 
    } else {
        $theme = 'light';
    }

    $GLOBALS['theme'] = $theme;
}
add_action('init', 'set_theme_cookie');

function generate_product_slug() {
    global $wpdb;

    do {
        $number = mt_rand(1000000, 9999999);
        $exists = $wpdb->get_var($wpdb->prepare(
            "SELECT ID FROM {$wpdb->posts} WHERE post_name = %s AND post_type = 'products'",
            $number
        ));
    } while ($exists);

    return $number;
}

function generate_random_filename($filename) {
    $ext = pathinfo($filename, PATHINFO_EXTENSION);
    $random_name = wp_generate_password(12, false, false);
    return $random_name . '.' . $ext;
}