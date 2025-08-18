<?php get_header(); ?>
<div class="main-wrapper">
  <div class="container-medium">
    <div class="content-columns">
        <?php
        function cat_t($term_id) {
            $ru = get_term($term_id)->name;
            $en = get_term_meta($term_id, 'translation_en', true);
            $ro = get_term_meta($term_id, 'translation_ro', true);
            return t($ru, $en, $ro);
        }

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
            <h1>Каталог товаров</h1>
            <div id="product-results" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(250px,1fr));gap:32px;"></div>
        </main>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {

    // --- Показ/скрытие подкатегорий ---
    document.querySelectorAll('.category-list label').forEach(label => {
        label.addEventListener('click', function(e) {
            if (e.target.tagName === 'INPUT') return;
            const li = label.closest('li');
            if (!li) return;
            const subLists = li.querySelectorAll(':scope > ul.sub-category');
            subLists.forEach(sub => sub.style.display = sub.style.display === 'block' ? 'none' : 'block');
        });
    });

    // --- noUiSlider для цены ---
    const priceSlider = document.getElementById('price-slider');
    const priceMinInput = document.getElementById('price-min');
    const priceMaxInput = document.getElementById('price-max');
    const priceMinLabel = document.getElementById('price-min-label');
    const priceMaxLabel = document.getElementById('price-max-label');

    noUiSlider.create(priceSlider, {
        start: [0, 50000],
        connect: true,
        range: { min: 0, max: 50000 },
        step: 50
    });

    priceSlider.noUiSlider.on('update', function(values) {
        const min = Math.round(values[0]);
        const max = Math.round(values[1]);
        priceMinInput.value = min;
        priceMaxInput.value = max;
        priceMinLabel.textContent = min + ' ₽';
        priceMaxLabel.textContent = max + ' ₽';
        saveFiltersToStorage();
    });

    // --- localStorage ---
    function saveFiltersToStorage() {
        const formData = new FormData(document.getElementById('filter-form'));
        const filters = {};
        formData.forEach((value, key) => {
            if (!filters[key]) filters[key] = value;
            else {
                if (!Array.isArray(filters[key])) filters[key] = [filters[key]];
                filters[key].push(value);
            }
        });
        localStorage.setItem('product_filters', JSON.stringify(filters));
    }

    function loadFiltersFromStorage() {
        const saved = localStorage.getItem('product_filters');
        if (!saved) return;
        const filters = JSON.parse(saved);

        for (const key in filters) {
            const fields = document.querySelectorAll(`[name="${key}"]`);
            if (!fields.length) continue;
            if (fields[0].type === 'checkbox') {
                fields.forEach(f => {
                    if (Array.isArray(filters[key])) f.checked = filters[key].includes(f.value);
                    else f.checked = filters[key] === f.value;
                });
            } else if (fields[0].tagName === 'SELECT' || fields[0].type === 'hidden') {
                if (Array.isArray(filters[key])) {
                    fields.forEach((f, i) => f.value = filters[key][i] || '');
                } else {
                    fields.forEach(f => f.value = filters[key]);
                }
            }
        }

        // Раскрываем UL для выбранных категорий
        document.querySelectorAll('.category-list input[type="checkbox"]:checked').forEach(checkbox => {
            let li = checkbox.closest('li');
            while (li) {
                const ul = li.querySelector(':scope > ul.sub-category');
                if (ul) ul.style.display = 'block';
                li = li.parentElement.closest('li');
            }
        });

        if (filters.price_min !== undefined && filters.price_max !== undefined) {
            priceSlider.noUiSlider.set([filters.price_min, filters.price_max]);
        }
    }

    document.getElementById('filter-form').addEventListener('change', saveFiltersToStorage);

    // --- AJAX фильтр ---
    function loadProducts() {
        const categories = [];
        document.querySelectorAll('input[name="categories[]"]:checked').forEach(cb => categories.push(cb.value));

        const formData = new FormData();
        formData.append('categories', JSON.stringify(categories));
        formData.append('price_min', priceMinInput.value);
        formData.append('price_max', priceMaxInput.value);
        formData.append('sort', document.querySelector('select[name="sort"]').value);
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

    document.getElementById('filter-form').addEventListener('submit', function(e) {
        e.preventDefault();
        loadProducts();
    });

    // --- Сброс фильтров ---
    document.getElementById('reset-filters').addEventListener('click', function() {
        localStorage.removeItem('product_filters');
        document.getElementById('filter-form').reset();
        priceSlider.noUiSlider.set([0, 50000]);
        document.querySelectorAll('.sub-category').forEach(ul => ul.style.display = 'none');
        loadProducts();
    });

    // --- Инициализация ---
    loadFiltersFromStorage();
    loadProducts(); // сразу показываем все товары
});
</script>


<?php
get_footer();
?>