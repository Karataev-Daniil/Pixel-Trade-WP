<?php
/* 
Template Name: Избранное
*/

get_header();

if (!is_user_logged_in()) {
    echo '<div class="favorites-page">';
    echo '<h2>Избранное</h2>';
    echo '<p>Пожалуйста, <a href="' . wp_login_url(get_permalink()) . '">войдите</a>, чтобы просматривать избранное.</p>';
    echo '</div>';
    get_footer();
    exit;
}

$favorites = get_user_meta(get_current_user_id(), 'favorite_products', true);

echo '<div class="favorites-page">';
echo '<h2>Избранное</h2>';

if (empty($favorites)) {
    echo '<p>У вас пока нет избранных товаров.</p>';
} else {
    $query = new WP_Query([
        'post_type' => 'product',
        'post__in'  => $favorites,
    ]);

    if ($query->have_posts()) {
        echo '<div class="favorites-grid">';
        while ($query->have_posts()) {
            $query->the_post();
            $price = get_post_meta(get_the_ID(), 'product_price', true);

            ?>
            <div class="product-card">
                <a href="<?php the_permalink(); ?>" class="product-card__link">
                    <div class="product-card__image-wrapper">
                        <?php 
                        $thumbnail = get_the_post_thumbnail_url(get_the_ID(), 'medium');
                        if ($thumbnail) {
                            echo '<img src="' . esc_url($thumbnail) . '" class="product-card__image" alt="' . esc_attr(get_the_title()) . '">';
                        } else {
                            $default_img = get_template_directory_uri() . '/images/default-product.png';
                            echo '<img src="' . esc_url($default_img) . '" class="product-card__image" alt="' . esc_attr(get_the_title()) . '">';
                        }
                        ?>
                    </div>
                    <h3 class="product-card__title body-small-regular"><?php the_title(); ?></h3>
                    <div class="product-card__price uppercase-small"><?php echo esc_html($price); ?> ₽</div>
                </a>

                <button class="remove-from-favorites" data-id="<?php the_ID(); ?>">Удалить из избранного</button>
            </div>
            <?php
        }
        echo '</div>';
        wp_reset_postdata();
    }
}

echo '</div>';

get_footer();
