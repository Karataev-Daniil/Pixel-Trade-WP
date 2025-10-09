<?php
get_header();
$lang = $GLOBALS['language'] ?? 'ru';
$current_cat = get_queried_object();

// $features = get_product_category_features()[$current_cat->term_id] ?? [];
$per_page = wp_is_mobile() ? 12 : 35;
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

            <!-- Фильтры (закомментированы)
            <?php if (!empty($features) && is_array($features)) : ?>
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
                        $args = [
                            'post_type' => 'products',
                            'posts_per_page' => $per_page,
                            'paged' => 1,
                            'tax_query' => [
                                [
                                    'taxonomy' => 'product_cat',
                                    'field' => 'term_id',
                                    'terms' => $current_cat->term_id,
                                ]
                            ],
                        ];

                        $products_query = new WP_Query($args);

                        if ($products_query->have_posts()) :
                            while ($products_query->have_posts()) : $products_query->the_post();
                                get_template_part('template-parts/product/card');
                            endwhile;
                        else :
                            echo '<p>' . t('Ничего не найдено', 'Nothing found', 'Nimic găsit') . '</p>';
                        endif;

                        wp_reset_postdata();
                        ?>
                    </div>

                    <button id="load-more" class="category__load-more primary-button-medium button-medium">
                        <?= t('Показать еще','Load more','Arată mai mult'); ?>
                    </button>
                </div>
            </div>
        </main>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const productsList = document.querySelector('.products-list');
    const loadMoreBtn = document.getElementById('load-more');
    let offset = <?= $per_page; ?>;
    const perPage = <?= $per_page; ?>;
    const catId = <?= $current_cat->term_id; ?>;

    function loadProducts() {
        if (!loadMoreBtn) return;

        loadMoreBtn.disabled = true;

        const formData = new FormData();
        formData.append('offset', offset);
        formData.append('cat_id', catId);
        formData.append('action', 'load_more_products');

        fetch('<?= admin_url('admin-ajax.php'); ?>', { method: 'POST', body: formData })
            .then(res => res.text())
            .then(html => {
                if (html.trim() !== '') {
                    productsList.insertAdjacentHTML('beforeend', html);
                    offset += perPage;

                    const newItems = html.split('class="product-card"').length - 1;
                    if (newItems < perPage) {
                        loadMoreBtn.style.display = 'none';
                    } else {
                        loadMoreBtn.style.display = 'inline-block';
                        loadMoreBtn.disabled = false;
                    }
                } else {
                    loadMoreBtn.style.display = 'none';
                }
            });
    }

    if (loadMoreBtn) {
        loadMoreBtn.addEventListener('click', loadProducts);
    }
});
</script>

<?php get_footer(); ?>
