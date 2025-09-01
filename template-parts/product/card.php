<?php
$price = get_post_meta(get_the_ID(), 'product_price', true);
$favorites = get_user_meta(get_current_user_id(), 'favorite_products', true);
$is_favorite = is_array($favorites) && in_array(get_the_ID(), $favorites);

$author_id = get_post_field('post_author', get_the_ID());

$author_region = get_user_meta($author_id, 'region', true);
$regions = get_option('available_regions_multi', []);
$region_name = '';

if ($author_region && !empty($regions)) {
    foreach ($regions as $region) {
        if (
            $region['ru'] === $author_region || 
            $region['en'] === $author_region || 
            $region['ro'] === $author_region
        ) {
            $region_name = $region['ru']; 
            break;
        }
    }
}
?>

<div class="product-card">
    <a href="<?php the_permalink(); ?>" class="product-card__link">
        <div class="product-card__image-wrapper">
            <?php
            $thumb_id = get_post_thumbnail_id(get_the_ID());

            if ($thumb_id) {
                echo wp_get_attachment_image($thumb_id, 'medium-thumb', false, [
                    'class' => 'product-card__image',
                    'alt'   => get_the_title()
                ]);
            } else {
                $default_img = get_template_directory_uri() . '/images/product-placeholder.png';
                echo '<img src="' . esc_url($default_img) . '" class="product-card__image" alt="' . esc_attr(get_the_title()) . '">';
            }
            ?>
        </div>
        <h3 class="product-card__title body-small-regular"><?php the_title(); ?></h3>
        <?php if ($price): ?>
            <div class="product-card__price uppercase-small"><?php echo esc_html($price); ?> MDL</div>
        <?php endif; ?>
        
        <?php if ($region_name): ?>
            <div class="product-card__region body-small-regular">Регион: <span><?php echo esc_html($region_name); ?></sapn></div>
        <?php endif; ?>
    </a>

    <?php if (is_user_logged_in()): ?>
        <button class="toggle-favorite" data-id="<?php the_ID(); ?>">
            <?php if ($is_favorite): ?>
                <svg width="24" height="24" viewBox="0 0 24 24" fill="red" xmlns="http://www.w3.org/2000/svg">
                    <path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 
                             4.42 3 7.5 3c1.74 0 3.41 0.81 4.5 2.09 
                             C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 
                             22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/>
                </svg>
            <?php else: ?>
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="black" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" xmlns="http://www.w3.org/2000/svg">
                    <path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 
                             4.42 3 7.5 3c1.74 0 3.41 0.81 4.5 2.09 
                             C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 
                             22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/>
                </svg>
            <?php endif; ?>
        </button>
    <?php endif; ?>
</div>
<script>
jQuery(document).ready(function($){
    if(typeof favorites_ajax === 'undefined') return;

    $(document).on('click', '.toggle-favorite', function(e){
        e.preventDefault();
        let button = $(this);
        let product_id = button.data('id');

        let action = button.find('svg[fill="red"]').length ? 'remove_from_favorites' : 'add_to_favorites';

        $.post(favorites_ajax.ajax_url, {
            action: action,
            product_id: product_id,
            nonce: favorites_ajax.nonce
        }, function(response){
            if(response.success){
                if(action === 'add_to_favorites'){
                    button.html('<svg width="24" height="24" viewBox="0 0 24 24" fill="red" xmlns="http://www.w3.org/2000/svg"><path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41 0.81 4.5 2.09 C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/></svg>');
                } else {
                    button.html('<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="black" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" xmlns="http://www.w3.org/2000/svg"><path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41 0.81 4.5 2.09 C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/></svg>');
                }
            } else {
                alert(response.data.message);
            }
        });
    });
});
</script>
