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

add_action('wp_ajax_get_category_features', 'ajax_get_category_features');
add_action('wp_ajax_nopriv_get_category_features', 'ajax_get_category_features');

function ajax_get_category_features() {
    global $wpdb;

    $category_id = intval($_GET['category_id'] ?? 0);
    if (!$category_id) {
        wp_send_json_error(['message' => 'No category_id']);
    }

    $features = $wpdb->get_results($wpdb->prepare("
        SELECT id, `key`, label_ru, label_en, label_ro
        FROM wp_features
        WHERE category_id = %d
        ORDER BY id ASC
    ", $category_id), ARRAY_A);

    $result = [];

    foreach ($features as $feature) {
        $options = $wpdb->get_results($wpdb->prepare("
            SELECT value_ru, value_en, value_ro
            FROM wp_feature_options
            WHERE feature_id = %d
            ORDER BY id ASC
        ", $feature['id']), ARRAY_A);

        $result[$feature['key']] = [
            'label' => [
                'ru' => $feature['label_ru'],
                'en' => $feature['label_en'],
                'ro' => $feature['label_ro'],
            ],
            'options' => $options
        ];
    }

    wp_send_json_success($result);
}