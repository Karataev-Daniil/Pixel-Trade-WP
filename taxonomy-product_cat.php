<?php
get_header();

// Получаем ID текущей категории
$current_cat_id = 0;
if (is_tax('product_cat')) {
    $current_cat = get_queried_object();
    $current_cat_id = $current_cat->term_id;
}
?>

<div class="main-wrapper">
  <div class="container-medium">
    <div class="content-columns">

        <?php
        // Функция перевода названий
        function cat_t($term_id) {
            $ru = get_term($term_id)->name;
            $en = get_term_meta($term_id, 'translation_en', true);
            $ro = get_term_meta($term_id, 'translation_ro', true);
            return t($ru, $en, $ro);
        }

        // Рекурсивное дерево категорий
        function render_cat_tree($parent_id) {
            $children = get_terms([
                'taxonomy'   => 'product_cat',
                'hide_empty' => false,
                'parent'     => $parent_id
            ]);
        
            if (!empty($children) && !is_wp_error($children)) {
                echo '<ul class="sub-category" style="display:none;">';
                foreach ($children as $child) {
                    $grandchildren = get_terms([
                        'taxonomy'   => 'product_cat',
                        'hide_empty' => false,
                        'parent'     => $child->term_id
                    ]);
                    $has_children = !empty($grandchildren) && !is_wp_error($grandchildren);
                    echo '<li>';
                    echo '<label><input type="checkbox" name="categories[]" value="'.$child->term_id.'"> ' . cat_t($child->term_id) . '</label>';
                    if ($has_children) {
                        render_cat_tree($child->term_id);
                    }
                    echo '</li>';
                }
                echo '</ul>';
            }
        }

        // Родительские категории
        $terms = get_terms([
            'taxonomy'   => 'product_cat',
            'hide_empty' => false,
            'parent'     => 0
        ]);
        ?>

        <aside class="sidebar" style="width: 260px;">
            <h2 class="title-medium">Категории</h2>
            <form id="filter-form">
                <ul class="category-list" style="margin-top: 24px;">
                    <?php foreach ($terms as $term) : ?>
                        <?php
                        $children = get_terms([
                            'taxonomy'   => 'product_cat',
                            'hide_empty' => false,
                            'parent'     => $term->term_id
                        ]);
                        $has_children = !empty($children) && !is_wp_error($children);
                        ?>
                        <li>
                            <label><input type="checkbox" name="categories[]" value="<?= $term->term_id ?>"> <?= cat_t($term->term_id) ?></label>
                            <?php if ($has_children) render_cat_tree($term->term_id); ?>
                        </li>
                    <?php endforeach; ?>
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
            <h1><?= single_term_title('', false); ?></h1>
            <div id="product-results" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(250px,1fr));gap:32px;"></div>
        </main>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var defaultCategoryId = <?= $current_cat_id ?>;

    if (defaultCategoryId > 0) {
        localStorage.removeItem('product_filters');
    }

    // Раскрытие вложенных категорий
    document.querySelectorAll('.category-list label').forEach(label => {
        label.addEventListener('click', function(e) {
            if (e.target.tagName === 'INPUT') return; // если кликнули на чекбокс, не сворачивать

            let li = label.closest('li');
            if (!li) return;

            // находим сразу все вложенные UL внутри li
            let subLists = li.querySelectorAll(':scope > ul');
            subLists.forEach(sub => {
                sub.style.display = sub.style.display === 'block' ? 'none' : 'block';
            });
        });
    });


    // noUiSlider
    let priceSlider = document.getElementById('price-slider');
    let priceMinInput = document.getElementById('price-min');
    let priceMaxInput = document.getElementById('price-max');
    let priceMinLabel = document.getElementById('price-min-label');
    let priceMaxLabel = document.getElementById('price-max-label');

    noUiSlider.create(priceSlider, {
        start: [0, 50000],
        connect: true,
        range: { 'min': 0, 'max': 50000 },
        step: 50
    });

    priceSlider.noUiSlider.on('update', function(values) {
        let min = Math.round(values[0]);
        let max = Math.round(values[1]);
        priceMinInput.value = min;
        priceMaxInput.value = max;
        priceMinLabel.textContent = min + ' ₽';
        priceMaxLabel.textContent = max + ' ₽';
        saveFiltersToStorage();
    });

    function saveFiltersToStorage() {
        let formData = new FormData(document.getElementById('filter-form'));
        let filters = {};
        formData.forEach((value, key) => {
            if (!filters[key]) {
                filters[key] = value;
            } else {
                if (!Array.isArray(filters[key])) filters[key] = [filters[key]];
                filters[key].push(value);
            }
        });
        localStorage.setItem('product_filters', JSON.stringify(filters));
    }

    function loadFiltersFromStorage() {
        let saved = localStorage.getItem('product_filters');
        if (!saved) return;
        let filters = JSON.parse(saved);

        for (let key in filters) {
            let field = document.querySelectorAll(`[name="${key}"]`);
            if (!field.length) continue;

            if (field[0].type === 'checkbox') {
                field.forEach(f => {
                    if (Array.isArray(filters[key])) {
                        f.checked = filters[key].includes(f.value);
                    } else {
                        f.checked = filters[key] === f.value;
                    }
                });
            } else if (field[0].tagName === 'SELECT' || field[0].type === 'hidden') {
                if (Array.isArray(filters[key])) {
                    field.forEach((f, i) => f.value = filters[key][i] || '');
                } else {
                    field.forEach(f => f.value = filters[key]);
                }
            }
        }
        if (filters.price_min !== undefined && filters.price_max !== undefined) {
            priceSlider.noUiSlider.set([filters.price_min, filters.price_max]);
        }
    }

    document.getElementById('filter-form').addEventListener('change', saveFiltersToStorage);

    document.getElementById('filter-form').addEventListener('submit', function(e) {
        e.preventDefault();
        saveFiltersToStorage();
        loadProducts();
    });

    document.getElementById('reset-filters').addEventListener('click', function() {
        localStorage.removeItem('product_filters');
        document.getElementById('filter-form').reset();
        priceSlider.noUiSlider.set([0, 50000]);
        loadProducts();
    });

    function loadProducts() {
        let formData = new FormData(document.getElementById('filter-form'));
        formData.append('action', 'filter_products');

        fetch('/wp-admin/admin-ajax.php', {
            method: 'POST',
            body: formData
        })
        .then(r => r.text())
        .then(html => {
            document.getElementById('product-results').innerHTML = html;
        });
    }

    loadFiltersFromStorage();

    if (defaultCategoryId > 0) {
        let checkbox = document.querySelector(`input[name="categories[]"][value="${defaultCategoryId}"]`);
        if (checkbox) {
            // Выбираем текущую
            checkbox.checked = true;

            // Поднимаемся по родителям
            let parentLi = checkbox.closest('li')?.closest('ul')?.closest('li');
            while (parentLi) {
                let parentCheckbox = parentLi.querySelector(':scope > label > input[name="categories[]"]');
                if (parentCheckbox) parentCheckbox.checked = true;
            
                // Раскрыть только прямой UL
                let directSubUl = parentLi.querySelector(':scope > ul.sub-category');
                if (directSubUl) directSubUl.style.display = 'block';
            
                parentLi = parentLi.closest('li')?.closest('ul')?.closest('li');
            }


            saveFiltersToStorage();
        }
    }

    loadProducts();
});
</script>

<?php get_footer(); ?>
