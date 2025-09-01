<?php
add_action('wp_ajax_filter_products', 'filter_products');
add_action('wp_ajax_nopriv_filter_products', 'filter_products');

function filter_products() {
    $categories = isset($_POST['categories']) ? array_map('intval', $_POST['categories']) : [];
    $price_min = isset($_POST['price_min']) ? floatval($_POST['price_min']) : 0;
    $price_max = isset($_POST['price_max']) ? floatval($_POST['price_max']) : 50000;
    $sort = isset($_POST['sort']) ? sanitize_text_field($_POST['sort']) : 'date_desc';

    $args = [
        'post_type' => 'products',
        'posts_per_page' => -1,
        'post_status' => 'publish',
        'meta_query' => [
            [
                'key' => 'product_price',
                'value' => [$price_min, $price_max],
                'compare' => 'BETWEEN',
                'type' => 'NUMERIC',
            ]
        ],
        'tax_query' => [],
        'orderby' => 'date',
        'order' => 'DESC'
    ];

    if (!empty($categories)) {
        $args['tax_query'] = [
            'relation' => 'AND',
            [
                'taxonomy' => 'product_cat',
                'field' => 'term_id',
                'terms' => $categories,
                'operator' => 'IN',
                'include_children' => false,
            ]
        ];
    }

    switch ($sort) {
        case 'date_asc':
            $args['orderby'] = 'date';
            $args['order'] = 'ASC';
            break;
        case 'views_desc':
            $args['meta_key'] = 'product_views';
            $args['orderby'] = 'meta_value_num';
            $args['order'] = 'DESC';
            break;
        case 'views_asc':
            $args['meta_key'] = 'product_views';
            $args['orderby'] = 'meta_value_num';
            $args['order'] = 'ASC';
            break;
        case 'date_desc':
        default:
            $args['orderby'] = 'date';
            $args['order'] = 'DESC';
    }

    $query = new WP_Query($args);

    if ($query->have_posts()) {
        while ($query->have_posts()) {
            $query->the_post();
            $price = get_post_meta(get_the_ID(), 'product_price', true);
        
            $is_favorite = false;
            if (is_user_logged_in()) {
                $favorites = get_user_meta(get_current_user_id(), 'favorite_products', true);
                if (is_array($favorites) && in_array(get_the_ID(), $favorites)) {
                    $is_favorite = true;
                }
            }
            get_template_part('template-parts/product/card'); 
            ?>
            <?php
        }
    } else {
        echo '<p>Товары не найдены.</p>';
    }

    wp_reset_postdata();
    wp_die();
}
