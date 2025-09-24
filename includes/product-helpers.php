<?php
// includes/product-helpers.php

function get_product_gallery_ids($product_id, $include_thumbnail = false) {
    $gallery = get_field('product_gallery', $product_id);
    $ids = [];

    if (!empty($gallery) && is_array($gallery)) {
        foreach ($gallery as $image) {
            if (is_array($image) && isset($image['ID'])) $ids[] = $image['ID'];
            elseif (is_numeric($image)) $ids[] = $image;
        }
    }

    $thumbnail_id = get_post_thumbnail_id($product_id);
    if ($include_thumbnail && $thumbnail_id && !in_array($thumbnail_id, $ids)) {
        array_unshift($ids, $thumbnail_id);
    }

    return $ids;
}

function get_product_translations($product_id, $type = 'title') {
    $translations = [];
    foreach (['ru', 'en', 'ro'] as $lang) {
        if ($lang === 'ru') {
            $translations[$lang] = $type === 'title' ? get_the_title($product_id) : get_post_field('post_content', $product_id);
        } else {
            $translations[$lang] = get_post_meta($product_id, "_{$type}_{$lang}", true);
        }
    }
    return $translations;
}

function get_current_category_features() {
    $term = get_queried_object();
    if (!$term) return [];
    $all_features = get_product_category_features();
    return $all_features[$term->term_id] ?? [];
}

add_action('wp_ajax_load_more_products', 'load_more_products');
add_action('wp_ajax_nopriv_load_more_products', 'load_more_products');