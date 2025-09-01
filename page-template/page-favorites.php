<?php
/* 
Template Name: Избранное
*/

get_header();

if (!is_user_logged_in()) : ?>
    <div class="favorites-page">
        <h2>Избранное</h2>
        <p>Пожалуйста, <a href="<?php echo wp_login_url(get_permalink()); ?>">войдите</a>, чтобы просматривать избранное.</p>
    </div>
<?php
    get_footer();
    exit;
endif;

$favorites = get_user_meta(get_current_user_id(), 'favorite_products', true);
?>

<div class="favorites-products__wrapper content-main">
    <div class="container-medium">
        <main>
            <div class="favorites-products">
                <h2 class="display-small">Избранное</h2>

                <?php if (empty($favorites)) : ?>
                    <p class="body-medium-regular">У вас пока нет избранных товаров.</p>
                <?php else : 
                    $query = new WP_Query([
                        'post_type' => 'products',
                        'post__in'  => $favorites,
                    ]);

                    if ($query->have_posts()) : ?>
                        <ul class="products-list">
                            <?php while ($query->have_posts()) : $query->the_post(); 
                                $price = get_post_meta(get_the_ID(), 'product_price', true);
                                get_template_part('template-parts/product/card'); 
                            endwhile; ?>
                        </ul>
                        <?php wp_reset_postdata(); ?>
                    <?php endif; 
                endif; ?>
            </div>
        </main>
    </div>
</div>

<?php get_footer(); ?>
