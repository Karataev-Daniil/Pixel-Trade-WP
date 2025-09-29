<?php
global $wpdb;
$current_user_id = get_current_user_id();
$post_id = get_the_ID();
$price = get_post_meta($post_id, 'product_price', true);
$currency = get_post_meta(get_the_ID(), 'product_currency', true) ?: 'MDL';

$favorites = $wpdb->get_col($wpdb->prepare(
    "SELECT object_id FROM {$wpdb->prefix}favorites WHERE user_id = %d AND object_type = %s",
    $current_user_id,
    'product'
));

$is_favorite = in_array($post_id, $favorites);

$author_id    = get_post_field('post_author', $post_id);
$author_name  = get_the_author_meta('display_name', $author_id);
$author_email = get_the_author_meta('user_email', $author_id);
$author_phone = get_user_meta($author_id, 'phone', true);
$phone_visibility = get_user_meta($author_id, 'phone_visibility', true);

$author_region = get_user_meta($author_id, 'region', true);
$regions = get_option('available_regions_multi', []);
$region_name = '';
if ($author_region && !empty($regions)) {
    foreach ($regions as $region) {
        if ($region['ru'] === $author_region || $region['en'] === $author_region || $region['ro'] === $author_region) {
            $region_name = $region['ru']; 
            break;
        }
    }
}

$terms = get_the_terms($post_id, 'product_cat');
$cat_ids = [];
if ($terms && !is_wp_error($terms)) {
    foreach ($terms as $term) {
        $parent = $term->parent ? get_term($term->parent, 'product_cat') : $term;
        if ($parent) $cat_ids[] = $parent->term_id;
    }
}
$cat_ids_str = implode(',', $cat_ids);
?>

<div class="product-card-row-large" 
     data-id="<?= esc_attr($post_id); ?>" 
     data-date="<?= esc_attr(get_the_date('Y-m-d H:i:s')); ?>" 
     data-categories="<?= esc_attr($cat_ids_str); ?>">

    <div class="product-card-row-large__image-wrapper">
        <?php
        $thumb_id = get_post_thumbnail_id($post_id);
        if ($thumb_id) {
            echo wp_get_attachment_image($thumb_id, 'medium', false, [
                'class' => '',
                'alt'   => get_the_title()
            ]);
        } else {
            $default_img = get_template_directory_uri() . '/images/product-placeholder.png';
            echo '<img src="' . esc_url($default_img) . '" alt="' . esc_attr(get_the_title()) . '">';
        }
        ?>
    </div>

    <div class="product-card-row-large__info">
        <h2 class="product-card-row-large__title title-large"><?php the_title(); ?></h2>

        <?php if ($price): ?>
            <div class="product-card-row-large__price uppercase-small">
                <?= number_format((float)$price, 0, '', ','); ?> <?= esc_html($currency); ?>
            </div>
        <?php endif; ?>

        <?php if ($region_name): ?>
            <div class="product-card-row-large__region body-larger-semibold">
                <?= t('Регион', 'Region', 'Regiune'); ?>: <span><?= esc_html($region_name); ?></span>
            </div>
        <?php endif; ?>

        <div class="product-card-row-large__actions">
            <?php if (is_user_logged_in()): ?>
                <button class="toggle-favorite" data-id="<?= esc_attr($post_id); ?>">
                    <svg width="24" height="24" viewBox="0 0 24 24"
                         fill="<?= $is_favorite ? 'red' : 'none' ?>"
                         <?= $is_favorite ? 'stroke="none"' : 'stroke="var(--gray_-6)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"' ?>
                         xmlns="http://www.w3.org/2000/svg">
                        <path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 
                                 4.42 3 7.5 3c1.74 0 3.41 0.81 4.5 2.09 
                                 C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 
                                 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/>
                        </svg>
                </button>
            <?php endif; ?>

            <?php if (is_user_logged_in() && $author_id && $author_id != $current_user_id): ?>
                <button class="dm-write-btn secondary-button-small button-medium" data-user="<?= esc_attr($author_id); ?>">
                    <?= t('Написать', 'Write', 'Scrie'); ?>
                </button>
            <?php endif; ?>

            <?php 
            $can_show_phone = false;
            if ($author_phone) {
                if ($phone_visibility === 'all') {
                    $can_show_phone = true;
                } elseif ($phone_visibility === 'registered' && is_user_logged_in()) {
                    $can_show_phone = true;
                }
            }
            if ($can_show_phone): ?>
                <a href="tel:<?= esc_attr($author_phone); ?>" class="btn-show-phone">
                    <?= t('Показать номер', 'Show number', 'Arată numărul'); ?>
                </a>
            <?php endif; ?>
        </div>
    </div>
</div>
