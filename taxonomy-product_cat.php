<?php
get_header();
$lang = $GLOBALS['language'] ?? 'ru';
$current_cat = get_queried_object();
$per_page = wp_is_mobile() ? 12 : 35;

global $wpdb;
$features = $wpdb->get_results($wpdb->prepare(
    "SELECT * FROM wp_features WHERE category_id = %d",
    $current_cat->term_id
));
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

            <?php if ($features): ?>
                <form id="filters-form" class="category__filters">
                    <?php foreach ($features as $feature):
                        $options = $wpdb->get_results($wpdb->prepare(
                            "SELECT * FROM wp_feature_options WHERE feature_id = %d",
                            $feature->id
                        ));
                        $label = ($lang == 'en') ? $feature->label_en : (($lang == 'ro') ? $feature->label_ro : $feature->label_ru);
                    ?>
                        <div class="category__filter">
                            <label><?= esc_html($label); ?></label>
                            <select name="<?= esc_attr($feature->key); ?>">
                                <option value=""><?= t('Все', 'All', 'Toate'); ?></option>
                                <?php foreach ($options as $opt):
                                    $value = ($lang == 'en') ? $opt->value_en : (($lang == 'ro') ? $opt->value_ro : $opt->value_ru);
                                ?>
                                    <option value="<?= esc_attr($value); ?>"><?= esc_html($value); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    <?php endforeach; ?>
                </form>
            <?php endif; ?>

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
                        <?= t('Показать еще', 'Load more', 'Arată mai mult'); ?>
                    </button>
                </div>
            </div>
        </main>
    </div>
</div>

<style>
/* --- Filters layout --- */
.category__filters {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 16px;
    margin-bottom: 24px;
}
.category__filter label {
    display: block;
    font-size: 14px;
    font-weight: 500;
    margin-bottom: 4px;
}
.category__filter select {
    width: 100%;
    padding: 8px;
    border: 1px solid #ddd;
    border-radius: 8px;
    background: #fff;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const filtersForm = document.getElementById('filters-form');
    const productsList = document.querySelector('.products-list');
    const loadMoreBtn = document.getElementById('load-more');
    let page = 1;
    const catId = <?= $current_cat->term_id; ?>;
    const perPage = <?= $per_page; ?>;
    let isLoading = false;

    function fetchProducts(reset = false) {
        if (isLoading) return;
        isLoading = true;
        loadMoreBtn.disabled = true;

        const formData = new FormData(filtersForm || document.createElement('form'));
        formData.append('cat_id', catId);
        formData.append('page', page);
        formData.append('per_page', perPage);
        formData.append('action', 'load_more_products');

        fetch('<?= admin_url('admin-ajax.php'); ?>', { method: 'POST', body: formData })
            .then(res => res.text())
            .then(html => {
                html = html.trim();
                if (reset) productsList.innerHTML = '';
                if (html) {
                    productsList.insertAdjacentHTML('beforeend', html);
                    loadMoreBtn.style.display = 'inline-block';
                } else if (reset) {
                    productsList.innerHTML = '<p><?= t('Ничего не найдено', 'Nothing found', 'Nimic găsit'); ?></p>';
                    loadMoreBtn.style.display = 'none';
                } else {
                    loadMoreBtn.style.display = 'none';
                }
                isLoading = false;
                loadMoreBtn.disabled = false;
            });
    }

    if (filtersForm) {
        filtersForm.addEventListener('change', () => {
            page = 1;
            fetchProducts(true);
        });
    }

    if (loadMoreBtn) {
        loadMoreBtn.addEventListener('click', () => {
            page++;
            fetchProducts(false);
        });
    }
});
</script>

<?php get_footer(); ?>
