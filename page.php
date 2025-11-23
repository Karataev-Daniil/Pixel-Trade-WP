<?php
get_header();

function render_product_cat_tree($parent_id = 0) {
    $terms = get_terms([
        'taxonomy'   => 'product_cat',
        'hide_empty' => false,
        'parent'     => $parent_id,
        'orderby'    => 'name',
        'order'      => 'ASC',
    ]);

    if (!empty($terms) && !is_wp_error($terms)) {
        echo '<ul>';
        foreach ($terms as $term) {
            echo '<li>';
            echo $term->name . ' (' . $term->slug . ')';

            render_product_cat_tree($term->term_id);

            echo '</li>';
        }
        echo '</ul>';
    }
}

render_product_cat_tree(0);

get_footer();
