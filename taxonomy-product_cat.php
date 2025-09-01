<?php
get_header();
$lang = $GLOBALS['language'] ?? 'ru';

$current_cat = get_queried_object();
?>

<div class="category__wrapper content-main">
    <div class="container-medium">
        <main>
            <?php get_template_part('template-parts/breadcrumbs'); ?>

            <h1 class="category__title display-small">
                <?= esc_html(get_category_name_translated($current_cat, $lang)); ?>
            </h1>

            <?php
            $child_cats = get_terms([
                'taxonomy' => 'product_cat',
                'hide_empty' => false,
                'parent' => $current_cat->term_id,
            ]);
            
            if ($child_cats) :
            ?>
                <div class="category__children">
                    <?php foreach ($child_cats as $child) : ?>
                        <?php
                        $grandchildren = get_terms([
                            'taxonomy' => 'product_cat',
                            'hide_empty' => false,
                            'parent' => $child->term_id,
                        ]);
                        ?>
                        <div class="category__parent">
                            <h3 class="category__parent-title title-large">
                                <?= esc_html(get_category_name_translated($child,$lang)); ?>
                            </h3>
                    
                            <div class="category__grandchildren">
                                <?php if ($grandchildren) : ?>
                                    <?php foreach ($grandchildren as $grand) : ?>
                                        <div class="category__grandchild button-small">
                                            <a class="category__grandchild-link link-button" href="<?= esc_url(get_term_link($grand)); ?>">
                                                <?= esc_html(get_category_name_translated($grand,$lang)); ?>
                                            </a>
                                            <span class="category__count">
                                                    (<?= number_format_i18n($grand->count); ?>)
                                            </span>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else : ?>
                                    <div class="category__grandchild button-small">
                                        <a class="category__grandchild-link link-button" href="<?= esc_url(get_term_link($child)); ?>">
                                            <?= esc_html(get_category_name_translated($child,$lang)); ?>
                                        </a>
                                        <span class="category__count">
                                            (<?= number_format_i18n($child->count); ?>)
                                        </span>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
                                
            <div class="category__products">
                <div id="products-container">
                    <div class="category__products-list products-list">
                        <?php
                        $paged = (get_query_var('paged')) ? get_query_var('paged') : 1;
                                            
                        $args = [
                            'post_type' => 'products',
                            'posts_per_page' => 24,
                            'paged' => $paged,
                            'tax_query' => [
                                [
                                    'taxonomy' => 'product_cat',
                                    'field' => 'term_id',
                                    'terms' => $current_cat->term_id,
                                ]
                            ]
                        ];
                    
                        $products_query = new WP_Query($args);
                    
                        if ($products_query->have_posts()) :
                            while ($products_query->have_posts()) : $products_query->the_post();
                                get_template_part('template-parts/product/card');
                            endwhile;
                        endif;
                        wp_reset_postdata();
                        ?>
                    </div>
                    <div class="category__loader loader">
                        <div class="spinner">
                            <div class="dot"></div>
                        </div>
                    </div>
                    
                    <?php if ($products_query->max_num_pages > 1) : ?>
                        <button id="load-more" class="category__load-more button-small" data-page="1" data-max="<?= $products_query->max_num_pages; ?>">
                            <?= t('Показать еще','Load more','Arată mai mult'); ?>
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const loader = document.querySelector('.loader');
    const productsList = document.querySelector('.products-list');
    const loadMoreBtn = document.getElementById('load-more');

    if (!loader || !productsList || !loadMoreBtn) return;

    loadMoreBtn.addEventListener('click', function() {
        let page = parseInt(loadMoreBtn.dataset.page);
        const maxPage = parseInt(loadMoreBtn.dataset.max);
        const nextPage = page + 1;

        loader.style.display = 'flex';

        fetch(`<?= admin_url('admin-ajax.php'); ?>?action=load_more_products&cat_id=<?= $current_cat->term_id; ?>&paged=${nextPage}`)
            .then(response => {
                if (!response.ok) throw new Error('Ошибка загрузки данных');
                return response.text();
            })
            .then(data => {
                if (data.trim().length) {
                    productsList.insertAdjacentHTML('beforeend', data);
                }

                loader.style.display = 'none';

                loadMoreBtn.dataset.page = nextPage;

                if (nextPage >= maxPage) {
                    loadMoreBtn.style.display = 'none';
                }
            })
            .catch(err => {
                console.error(err);
                loader.style.display = 'none';
                alert('Не удалось загрузить посты. Попробуйте позже.');
            });
    });
});
</script>

<?php get_footer(); ?>
