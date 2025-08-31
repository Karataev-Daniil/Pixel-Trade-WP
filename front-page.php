<?php
get_header(); 
?>

<div class="main__wrapper content-main">
    <div class="container-medium">
        <main>
            <div class="content-columns">
                <aside class="sidebar" style="width: 260px;">
                    <h2 class="display-small"><?= t('Категории', 'Categories', 'Categorii'); ?></h2>
                    <form id="filter-form">
                        <ul class="category-list" style="margin-top: 24px;">
                        <?php
                        function render_cat_tree($parent_id = 0, $language = 'ru') {
                            $terms = get_terms([
                                'taxonomy'   => 'product_cat',
                                'hide_empty' => false,
                                'parent'     => $parent_id
                            ]);
                        
                            if (!empty($terms) && !is_wp_error($terms)) {
                                echo '<ul class="' . ($parent_id === 0 ? '' : 'sub-category') . '" style="' . ($parent_id === 0 ? '' : 'display:none;') . '">';
                                foreach ($terms as $term) {

                                    $translation_ro = get_term_meta($term->term_id, 'translation_ro', true);
                                    $translation_en = get_term_meta($term->term_id, 'translation_en', true);

                                    if ($language === 'en') {
                                        $term_name = $translation_en ?: $term->name;
                                    } elseif ($language === 'ro') {
                                        $term_name = $translation_ro ?: $term->name;
                                    } else {
                                        $term_name = $term->name;
                                    }
                                
                                    $children = get_terms([
                                        'taxonomy'   => 'product_cat',
                                        'hide_empty' => false,
                                        'parent'     => $term->term_id
                                    ]);
                                    $has_children = !empty($children) && !is_wp_error($children);
                                
                                    echo '<li data-id="' . esc_attr($term->term_id) . '">';
                                    echo '<label class="label-medium">' . esc_html($term_name) . '</label>';
                                
                                    if ($has_children) {
                                        render_cat_tree($term->term_id, $language);
                                    }
                                
                                    echo '</li>';
                                }
                                echo '</ul>';
                            }
                        }
                    
                        global $language;
                        render_cat_tree(0, $language);
                        ?>
                        </ul>
                    
                        <h3 class="title-small" style="margin-top: 32px;"><?= t('Цена', 'Price', 'Preț'); ?></h3>
                        <div id="price-slider" style="margin-top: 16px; margin-bottom: 16px;"></div>
                        <input type="hidden" name="price_min" id="price-min">
                        <input type="hidden" name="price_max" id="price-max">
                        <div style="display: flex; justify-content: space-between; font-size: 14px;">
                            <span class="label-medium" id="price-min-label"></span>
                            <span class="label-medium" id="price-max-label"></span>
                        </div>
                    
                        <h3 class="title-small" style="margin-top: 32px;"><?= t('Сортировка', 'Sort', 'Sortare'); ?></h3>
                        <select name="sort" class="input--primary">
                            <option value="date_desc"><?= t('Сначала новые', 'Newest first', 'Cele mai noi'); ?></option>
                            <option value="date_asc"><?= t('Сначала старые', 'Oldest first', 'Cele mai vechi'); ?></option>
                            <option value="views_desc"><?= t('Популярные', 'Most popular', 'Cele mai populare'); ?></option>
                            <option value="views_asc"><?= t('Менее популярные', 'Least popular', 'Mai puțin populare'); ?></option>
                        </select>
                    
                        <button type="submit" class="primary-button-small" style="margin-top: 24px;"><?= t('Применить', 'Apply', 'Aplică'); ?></button>
                        <button type="button" id="reset-filters" class="secondary-button-small" style="margin-top: 12px;"><?= t('Сбросить', 'Reset', 'Resetează'); ?></button>
                    </form>
                </aside>
                    
                <div class="product-grid" style="flex:1;">
                    <div id="products-container">
                        <div class="loader">
                            <div class="spinner">
                                <div class="dot"></div>
                            </div>
                        </div>
                        <div id="products-list"></div>
                    </div>
                <div>
            </div>
        </main>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    const $form = $('#filter-form');
    const $results = $('#products-list');
    const $loader = $('#products-container .loader');
    const $reset = $('#reset-filters');
    const $slider = $('#price-slider');
    const $priceMin = $('#price-min');
    const $priceMax = $('#price-max');
    let ajaxRequest;

    noUiSlider.create($slider[0], {
        start: [0, 50000],
        connect: true,
        range: { min: 0, max: 50000 },
        step: 100
    });

    $slider[0].noUiSlider.on('update', function(values) {
        let min = Math.round(values[0]);
        let max = Math.round(values[1]);
        $priceMin.val(min);
        $priceMax.val(max);
        $('#price-min-label').text(min + ' ₽');
        $('#price-max-label').text(max + ' ₽');
    });

    function loadProducts() {
        const selectedCategories = [];
        $('.category-list li.active').each(function() {
            selectedCategories.push($(this).data('id'));
        });

        let data = $form.serializeArray();

        selectedCategories.forEach(cat => data.push({name: 'categories[]', value: cat}));
        data.push({name: 'action', value: 'filter_products'});

        if (ajaxRequest) ajaxRequest.abort();

        $results.hide();
        $loader.show();

        ajaxRequest = $.ajax({
            url: '<?php echo admin_url('admin-ajax.php'); ?>',
            method: 'POST',
            data: $.param(data),
            success: function(response) {
                $results.html(response);
            },
            error: function() {
                $results.html('<p>Ошибка загрузки.</p>');
            },
            complete: function() {
                $loader.hide();
                $results.show();
            }
        });
    }


    $form.on('submit', function(e) {
        e.preventDefault();
        loadProducts();
    });

    $reset.on('click', function() {
        $form[0].reset();
        $slider[0].noUiSlider.set([0, 50000]);
        loadProducts();
    });

    $('.category-list li > label').on('click', function(e){
        e.preventDefault();
        const $li = $(this).parent('li');

        if($li.hasClass('active')){
            $li.removeClass('active');
            $li.find('li').removeClass('active');
            $li.find('ul.sub-category').slideUp(200);
        } else {
            $li.addClass('active');
            $li.parents('li').addClass('active');

            $li.children('ul.sub-category').slideDown(200);
        }

    });

    loadProducts();
});
</script>

<?php
get_footer(); 
?>