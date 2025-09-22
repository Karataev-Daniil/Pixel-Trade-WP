<?php
get_header();
$lang = $GLOBALS['language'] ?? 'ru';
$current_cat = get_queried_object();

// $features = get_product_category_features()[$current_cat->term_id] ?? [];
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
                    <?php foreach ($child_cats as $child) : 
                        $grandchildren = get_terms([
                            'taxonomy' => 'product_cat',
                            'hide_empty' => false,
                            'parent' => $child->term_id,
                        ]);
                    ?>
                        <div class="category__parent">
                            <h3 class="category__parent-title title-large">
                                <?= esc_html(get_category_name_translated($child, $lang)); ?>
                            </h3>

                            <div class="category__grandchildren">
                                <?php if ($grandchildren) : ?>
                                    <?php foreach ($grandchildren as $grand) : ?>
                                        <div class="category__grandchild button-small">
                                            <a class="category__grandchild-link link-button" href="<?= esc_url(get_term_link($grand)); ?>">
                                                <?= esc_html(get_category_name_translated($grand, $lang)); ?>
                                            </a>
                                            <span class="category__count">(<?= number_format_i18n($grand->count); ?>)</span>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else : ?>
                                    <div class="category__grandchild button-small">
                                        <a class="category__grandchild-link link-button" href="<?= esc_url(get_term_link($child)); ?>">
                                            <?= esc_html(get_category_name_translated($child, $lang)); ?>
                                        </a>
                                        <span class="category__count">(<?= number_format_i18n($child->count); ?>)</span>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <!-- <?php if (!empty($features) && is_array($features)) : ?>
                <form id="category-filters" class="category-filters">
                    <?php foreach ($features as $key => $feature) : ?>
                        <?php if (empty($feature['options'])) continue; ?>
                        <?php $selected = $_GET[$key] ?? ''; ?>
                        <div class="filter-group">
                            <label><?= esc_html($feature['label'][$lang] ?? $feature['label']['ru']); ?></label>
                            <select name="<?= esc_attr($key); ?>">
                                <option value="">Любое</option>
                                <?php foreach ($feature['options'] as $option) : ?>
                                    <?php $value = esc_attr($option[$lang] ?? $option['ru']); ?>
                                    <option value="<?= $value; ?>" <?= selected($selected, $value, false); ?>>
                                        <?= esc_html($option[$lang] ?? $option['ru']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    <?php endforeach; ?>
                    <button type="submit" class="button-small"><?= t('Фильтровать','Filter','Filtrează'); ?></button>
                </form>
            <?php endif; ?> -->


            <div class="category__products">
                <div id="products-container">
                    <div class="category__products-list products-list">
                        <?php
                        $paged = get_query_var('paged') ?: 1;

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
                            ],
                        ];
                    
                        $meta_query = [];
                        if (!empty($features) && is_array($features)) {
                            foreach ($features as $key => $feature) {
                                $val = $_GET[$key] ?? '';
                                if ($val !== '') {
                                    $meta_query[] = [
                                        'key'     => '_' . $key,
                                        'value'   => sanitize_text_field($val),
                                        'compare' => 'LIKE',
                                    ];
                                }
                            }
                        }

                        if (!empty($meta_query)) {
                            $args['meta_query'] = $meta_query;
                        }

                        $products_query = new WP_Query($args);
                    
                        if ($products_query->have_posts()) :
                            while ($products_query->have_posts()) : $products_query->the_post();
                                get_template_part('template-parts/product/card');
                            endwhile;
                        else :
                            echo '<p>Ничего не найдено</p>';
                        endif;
                    
                        wp_reset_postdata();
                        ?>
                    </div>
                    
                    <?php if ($products_query->max_num_pages > 1) : ?>
                        <button id="load-more" class="category__load-more button-small"
                                data-page="1"
                                data-max="<?= $products_query->max_num_pages; ?>">
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
    const form = document.getElementById('category-filters');

    let currentPage = 1;
    const cat_id = <?= $current_cat->term_id ?>;

    function loadProducts(page = 1, append = false) {
        loader.style.display = 'flex';
        const formData = new FormData();
        for (const [key, value] of new FormData(form)) {
            const trimmed = value.trim();
            if (trimmed !== '' && trimmed !== 'Любое') {
                formData.append(key, trimmed);
            }
        }

        formData.append('paged', page);
        formData.append('cat_id', cat_id);
        formData.append('action', 'load_more_products');

        fetch('<?= admin_url('admin-ajax.php'); ?>', { method: 'POST', body: formData })
            .then(res => res.text())
            .then(data => {
                if (!append) productsList.innerHTML = '';
                productsList.insertAdjacentHTML('beforeend', data);
                loader.style.display = 'none';
                currentPage = page;
                const maxPage = parseInt(loadMoreBtn.dataset.max);
                if (currentPage < maxPage) loadMoreBtn.style.display = 'inline-block';
                else loadMoreBtn.style.display = 'none';
            });
    }


    loadProducts();

    form.addEventListener('submit', function(e) {
        e.preventDefault();
        currentPage = 1;
        loadProducts(1, false);
    });

    loadMoreBtn.addEventListener('click', function() {
        loadProducts(currentPage + 1, true);
    });
});
</script>

<?php get_footer(); ?>
