<?php
get_header(); 
?>

<div class="main-wrapper">
  <div class="container-medium">
    <div class="content-columns">
        
        <aside class="sidebar" style="width: 260px;">
            <h2 class="title-medium">Категории</h2>
            <form id="filter-form">
                <ul class="category-list" style="margin-top: 24px;">
                <?php
                function render_cat_tree($parent_id = 0) {
                    $terms = get_terms([
                        'taxonomy'   => 'product_cat',
                        'hide_empty' => false,
                        'parent'     => $parent_id
                    ]);
                
                    if (!empty($terms) && !is_wp_error($terms)) {
                        echo '<ul class="' . ($parent_id === 0 ? '' : 'sub-category') . '" style="' . ($parent_id === 0 ? '' : 'display:none;') . '">';
                        foreach ($terms as $term) {
                            $children = get_terms([
                                'taxonomy'   => 'product_cat',
                                'hide_empty' => false,
                                'parent'     => $term->term_id
                            ]);
                            $has_children = !empty($children) && !is_wp_error($children);
                        
                            echo '<li data-id="' . esc_attr($term->term_id) . '">';
                            echo '<label>' . esc_html($term->name) . '</label>';
                        
                            if ($has_children) {
                                render_cat_tree($term->term_id);
                            }
                        
                            echo '</li>';
                        }
                        echo '</ul>';
                    }
                }

                render_cat_tree();
                ?>
                </ul>


                <h3 class="title-small" style="margin-top: 32px;">Цена</h3>
                <div id="price-slider" style="margin-top: 16px; margin-bottom: 16px;"></div>
                <input type="hidden" name="price_min" id="price-min">
                <input type="hidden" name="price_max" id="price-max">
                <div style="display: flex; justify-content: space-between; font-size: 14px;">
                    <span id="price-min-label"></span>
                    <span id="price-max-label"></span>
                </div>

                <h3 class="title-small" style="margin-top: 32px;">Сортировка</h3>
                <select name="sort" style="width:100%;margin-top:8px;">
                    <option value="date_desc">Сначала новые</option>
                    <option value="date_asc">Сначала старые</option>
                    <option value="views_desc">Популярные</option>
                    <option value="views_asc">Менее популярные</option>
                </select>

                <button type="submit" class="primary-button-small" style="margin-top: 24px;">Применить</button>
                <button type="button" id="reset-filters" class="secondary-button-small" style="margin-top: 12px;">Сбросить</button>
            </form>
        </aside>

        <main class="product-grid" style="flex:1;">
            <div id="products-container">
                <div class="loader">
                    <div class="spinner">
                        <div class="dot"></div>
                    </div>
                </div>
                <div id="products-list"></div>
            </div>
        </main>
    </div>
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