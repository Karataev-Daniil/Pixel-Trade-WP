<?php
get_header();

// Рекурсивный вывод категорий с вложенностями
function render_product_cat_tree($parent_id = 0) {
    $terms = get_terms([
        'taxonomy'   => 'product_cat',
        'hide_empty' => false, // показать все, даже пустые
        'parent'     => $parent_id,
        'orderby'    => 'name',
        'order'      => 'ASC',
    ]);

    if (!empty($terms) && !is_wp_error($terms)) {
        echo '<ul>';
        foreach ($terms as $term) {
            echo '<li>';
            echo $term->name . ' (' . $term->slug . ')'; // можно добавить ID: $term->term_id

            // рекурсивный вызов для вложенных
            render_product_cat_tree($term->term_id);

            echo '</li>';
        }
        echo '</ul>';
    }
}

// Запускаем с родительских категорий
render_product_cat_tree(0);

get_footer();
