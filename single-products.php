<?php
get_header();

if (have_posts()) :
    while (have_posts()) : the_post();
        $current_user_id = get_current_user_id();
        $post_author_id  = get_the_author_meta('ID');
        $is_editing = isset($_GET['edit']) && $_GET['edit'] == 1 && $current_user_id === $post_author_id;

        $product_id = get_the_ID();
        if ($is_editing && $product_id && get_post_type($product_id) === 'products') {
            get_template_part('template-parts/product/edit', null, ['product_id' => $product_id]);
        } else {
            get_template_part('template-parts/product/view', null, ['product_id' => $product_id]);
        }
    endwhile;
else :
    echo '<p>Товар не найден.</p>';
endif;

get_footer();
?>
