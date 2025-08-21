<?php
$price = get_post_meta(get_the_ID(), 'product_price', true);
$favorites = get_user_meta(get_current_user_id(), 'favorites', true);
$is_favorite = is_array($favorites) && in_array(get_the_ID(), $favorites);
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
        <?php if ($price): ?>
            <div class="product-card__price uppercase-small"><?php echo esc_html($price); ?> ₽</div>
        <?php endif; ?>
    </a>
    
    <?php if (is_user_logged_in()): ?>
        <?php if ($is_favorite): ?>
            <button class="remove-from-favorites" data-id="<?php the_ID(); ?>">Удалить из избранного</button>
        <?php else: ?>
            <button class="add-to-favorites" data-id="<?php the_ID(); ?>">В избранное</button>
        <?php endif; ?>
    <?php else: ?>
        <a href="<?php echo wp_login_url(get_permalink()); ?>" class="login-to-favorites">Войдите, чтобы добавить в избранное</a>
    <?php endif; ?>
</div>
