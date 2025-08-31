<?php
get_header();

// Получаем поисковый запрос
$search_query = get_search_query();

// Вывод заголовка поиска
if ($search_query) {
    echo '<h1>' . sprintf(__('Результаты поиска по: %s', 'textdomain'), esc_html($search_query)) . '</h1>';
}

$product_cats = get_terms([
    'taxonomy' => 'product_cat',
    'hide_empty' => false,
    'name__like' => $search_query,
]);

if (!empty($product_cats) && !is_wp_error($product_cats)) {
    echo '<h2>' . __('Категории', 'textdomain') . '</h2>';
    echo '<ul class="product-categories">';
    foreach ($product_cats as $cat) {
        echo '<li>';
        echo '<a href="' . esc_url(get_term_link($cat)) . '">';
        echo esc_html($cat->name);
        echo '</a>';

        // Если нужно показать вложенные категории
        $child_cats = get_terms([
            'taxonomy' => 'product_cat',
            'hide_empty' => false,
            'parent' => $cat->term_id,
        ]);
        if ($child_cats) {
            echo '<ul class="child-categories">';
            foreach ($child_cats as $child) {
                echo '<li><a href="' . esc_url(get_term_link($child)) . '">' . esc_html($child->name) . '</a></li>';
            }
            echo '</ul>';
        }

        echo '</li>';
    }
    echo '</ul>';
}

$args = [
    'post_type' => 'product',
    'posts_per_page' => -1,
    's' => $search_query,
];

$query = new WP_Query($args);

if ($query->have_posts()) {
    echo '<h2>' . __('Товары', 'textdomain') . '</h2>';
    echo '<div class="product-list">';
    while ($query->have_posts()) {
        $query->the_post();
        get_template_part('template-parts/product/card');
    }
    echo '</div>';
} else {
    echo '<p>' . __('Ничего не найдено', 'textdomain') . '</p>';
}

wp_reset_postdata();
get_footer();
