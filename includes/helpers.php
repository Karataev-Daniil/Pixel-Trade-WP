<?php
function resize_image_url($image, $width = 150, $height = 150) {
    if (is_numeric($image)) {
        $image = wp_get_attachment_url($image);
    }

    if (!$image) return '';

    $path_parts = pathinfo($image);
    return $path_parts['dirname'] . '/' . $path_parts['filename'] . '-' . $width . 'x' . $height . '.' . $path_parts['extension'];
}

function format_price_with_conversions($price, $currency = 'lei') {
    $price_number = floatval($price);

    $rate_mdl_to_eur = 0.051;
    $rate_mdl_to_usd = 0.055;

    $currency = strtolower($currency);

    switch ($currency) {
        case 'usd':
            $price_mdl = $price_number / $rate_mdl_to_usd;
            break;
        case 'eur':
            $price_mdl = $price_number / $rate_mdl_to_eur;
            break;
        default:
            $price_mdl = $price_number;
            break;
    }

    $prices = [
        'lei' => number_format($price_mdl, 2, '.', ',') . ' лей',
        'eur' => number_format($price_mdl * $rate_mdl_to_eur, 2, '.', ',') . ' €',
        'usd' => number_format($price_mdl * $rate_mdl_to_usd, 2, '.', ',') . ' $'
    ];

    $main_price = $prices[$currency];
    unset($prices[$currency]);

    $other_prices = implode(' / ≈ ', $prices);

    return "<b>{$main_price}</b><br><span>{$other_prices}</span>";
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

function get_translated_title($post_id = null) {
    $post_id = $post_id ?: get_the_ID();
    $lang = $GLOBALS['language'] ?? 'ru';

    if ($lang !== 'ru') {
        $meta = get_post_meta($post_id, '_title_' . $lang, true);
        if (!empty($meta)) {
            return $meta;
        }
    }
    return get_the_title($post_id);
}

function get_translated_region($author_id) {
    $lang = $GLOBALS['language'] ?? 'ru';
    $author_region = get_user_meta($author_id, 'region', true);
    $regions = get_option('available_regions_multi', []);
    if ($author_region && !empty($regions)) {
        foreach ($regions as $region) {
            if (
                $region['ru'] === $author_region ||
                $region['en'] === $author_region ||
                $region['ro'] === $author_region
            ) {
                return $region[$lang] ?: $region['ru'];
            }
        }
    }
    return '';
}