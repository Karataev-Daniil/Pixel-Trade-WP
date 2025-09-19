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

    $rate_mdl_to_eur = 0.051; // 1 MDL ≈ 0.051 €
    $rate_mdl_to_usd = 0.055; // 1 MDL ≈ 0.055 $

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

    $conversion_html = '';
    foreach ($prices as $p) {
        $conversion_html .= "<p>/ ≈ {$p}</p>";
    }

    return "<b>{$main_price}</b> <div>{$conversion_html}</div>";
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