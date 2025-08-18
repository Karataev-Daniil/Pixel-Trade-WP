<?php
// AJAX для фильтрации товаров
add_action('wp_ajax_filter_products', 'filter_products');
add_action('wp_ajax_nopriv_filter_products', 'filter_products');

function filter_products() {
    $args = [
        'post_type' => 'product',
        'posts_per_page' => -1,
        'tax_query'  => [],
        'meta_query' => []
    ];

    // --- Категории ---
    $categories = isset($_POST['categories']) ? json_decode(stripslashes($_POST['categories']), true) : [];
    $categories = array_map('intval', (array)$categories);

    if ($categories) {
        $all_cats = [];
        foreach ($categories as $cat_id) {
            $all_cats[] = $cat_id;
            $children = get_term_children($cat_id, 'product_cat');
            if (!is_wp_error($children) && !empty($children)) $all_cats = array_merge($all_cats, $children);
        }
        $all_cats = array_unique($all_cats);

        $args['tax_query'][] = [
            'taxonomy' => 'product_cat',
            'field'    => 'term_id',
            'terms'    => $all_cats,
            'operator' => 'IN'
        ];
    }

    // --- Цена ---
    $price_min = isset($_POST['price_min']) ? intval($_POST['price_min']) : 0;
    $price_max = isset($_POST['price_max']) ? intval($_POST['price_max']) : 999999;
    $args['meta_query'][] = [
        'key' => 'product_price',
        'value' => [$price_min, $price_max],
        'compare' => 'BETWEEN',
        'type' => 'NUMERIC'
    ];

    // --- Сортировка ---
    if (!empty($_POST['sort'])) {
        switch ($_POST['sort']) {
            case 'date_asc':
                $args['orderby'] = 'date';
                $args['order'] = 'ASC';
                break;
            case 'date_desc':
                $args['orderby'] = 'date';
                $args['order'] = 'DESC';
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
        }
    }

    $q = new WP_Query($args);

    if ($q->have_posts()) {
        while ($q->have_posts()) {
            $q->the_post();
            $price = get_post_meta(get_the_ID(), 'product_price', true);
            ?>
            <div class="product-card">
                <a href="<?php the_permalink(); ?>" class="product-link">
                    <div class="product-image-wrapper">
                        <?php 
                        $thumbnail = get_the_post_thumbnail_url(get_the_ID(), 'medium');
                        if ($thumbnail) {
                            echo '<img src="' . esc_url($thumbnail) . '" class="product-image" alt="' . esc_attr(get_the_title()) . '">';
                        } else {
                            $default_img = get_template_directory_uri() . '/images/default-product.png';
                            echo '<img src="' . esc_url($default_img) . '" class="product-image" alt="' . esc_attr(get_the_title()) . '">';
                        }
                        ?>
                    </div>
                    <h3 class="product-title title-medium"><?php the_title(); ?></h3>
                    <div class="product-price body-small-regular"><?php echo esc_html($price); ?> ₽</div>
                </a>
            </div>
            <?php
        }
        wp_reset_postdata();
    } else {
        echo '<p>Товары не найдены</p>';
    }

    wp_die();
}
