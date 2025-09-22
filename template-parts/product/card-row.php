<?php
$price = get_post_meta(get_the_ID(), 'product_price', true);
$user_id = get_current_user_id();
$favorites = function_exists('favorites_get') ? favorites_get($user_id, 'product') : [];
$is_favorite = in_array(get_the_ID(), $favorites);

$author_id = get_post_field('post_author', get_the_ID());
$region_name = get_translated_region($author_id);

$terms = get_the_terms(get_the_ID(), 'product_cat');
$cat_ids = [];
if ($terms && !is_wp_error($terms)) {
    foreach ($terms as $term) {
        $parent = $term->parent ? get_term($term->parent, 'product_cat') : $term;
        if ($parent) $cat_ids[] = $parent->term_id;
    }
}
$cat_ids_str = implode(',', $cat_ids);
?>

<div class="product-card-row" 
    data-id="<?= get_the_ID(); ?>" 
    data-date="<?= get_the_date('Y-m-d H:i:s'); ?>" 
    data-categories="<?= esc_attr($cat_ids_str); ?>">
    <a href="<?php the_permalink(); ?>" class="product-card-row__link">
        <div class="product-card-row__image-wrapper">
            <?php
            $thumb_id = get_post_thumbnail_id(get_the_ID());
            if ($thumb_id) {
                echo wp_get_attachment_image($thumb_id, 'thumbnail', false, [
                    'class' => '',
                    'alt'   => esc_attr(get_translated_title())
                ]);
            } else {
                $default_img = get_template_directory_uri() . '/images/product-placeholder.png';
                echo '<img src="' . esc_url($default_img) . '" alt="' . esc_attr(get_translated_title()) . '">';
            }
            ?>
        </div>
        <div class="product-card-row__info">
            <h3 class="product-card-row__title body-small-regular">
                <?= esc_html(get_translated_title()); ?>
            </h3>
            <?php if ($price): ?>
                <div class="product-card-row__price uppercase-small">
                    <?= number_format((float)$price, 0, '', ','); ?> MDL
                </div>
            <?php endif; ?>
            <?php if ($region_name): ?>
                <div class="product-card-row__region body-small-regular">
                    <?= t('Регион', 'Region', 'Regiune'); ?>: 
                    <span><?= esc_html($region_name); ?></span>
                </div>
            <?php endif; ?>
        </div>
    </a>

    <?php if (is_user_logged_in()): ?>
        <button class="toggle-favorite" data-id="<?= get_the_ID(); ?>">
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
</div>
