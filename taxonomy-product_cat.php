<?php
get_header();
$current_cat = get_queried_object();
?>

<div class="main-wrapper">
  <div class="container-medium">
    <div class="content-columns">

        <main class="product-grid" style="flex:1;">
            <h1><?= single_term_title('', false); ?></h1>

            <div class="products-grid" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(250px,1fr));gap:32px;">
            <?php if (have_posts()) : ?>
                <?php while (have_posts()) : the_post(); ?>
                    <?php get_template_part('template-parts/product/card'); ?>
                <?php endwhile; ?>

                <div class="pagination">
                    <?php the_posts_pagination(); ?>
                </div>

            <?php else : ?>
                <p>Записей пока нет.</p>
            <?php endif; ?>
            </div>
        </main>
    </div>
  </div>
</div>

<?php get_footer(); ?>
