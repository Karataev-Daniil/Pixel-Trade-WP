<?php
add_theme_support('post-thumbnails');

function register_product_post_type() {
    register_post_type('product', [
        'labels' => [
            'name' => 'Товары',
            'singular_name' => 'Товар',
            'add_new' => 'Добавить новый товар',
            'edit_item' => 'Редактировать товар',
            'new_item' => 'Новый товар',
            'view_item' => 'Посмотреть товар',
            'search_items' => 'Поиск товаров',
            'not_found' => 'Товары не найдены',
            'menu_name' => 'Товары',
        ],
        'description' => 'Кастомный тип записи для товаров',
        'public' => true,
        'show_in_menu' => true,
        'show_in_rest' => true,
        'menu_position' => 5,
        'menu_icon' => 'dashicons-cart',
        'hierarchical' => false,
        'supports' => ['title', 'editor', 'author', 'thumbnail'],
        'has_archive' => true,
        'rewrite' => [
            'slug' => 'products',
            'with_front' => false,
        ],
        'query_var' => true,
        'capability_type' => ['product', 'products'],
        'map_meta_cap' => true,
    ]);
}
add_action('init', 'register_product_post_type');

add_action('add_meta_boxes', function () {
    add_meta_box('product_translations', 'Переводы', 'render_product_translations', 'product');
});

function render_product_translations($post) {
    $title_en = get_post_meta($post->ID, '_title_en', true);
    $title_ro = get_post_meta($post->ID, '_title_ro', true);
    $desc_en = get_post_meta($post->ID, '_description_en', true);
    $desc_ro = get_post_meta($post->ID, '_description_ro', true);
    ?>
    <label>Title EN</label><br>
    <input type="text" name="title_en" value="<?= esc_attr($title_en) ?>" style="width:100%"><br><br>

    <label>Title RO</label><br>
    <input type="text" name="title_ro" value="<?= esc_attr($title_ro) ?>" style="width:100%"><br><br>

    <label>Description EN</label><br>
    <textarea name="description_en" style="width:100%"><?= esc_textarea($desc_en) ?></textarea><br><br>

    <label>Description RO</label><br>
    <textarea name="description_ro" style="width:100%"><?= esc_textarea($desc_ro) ?></textarea>
    <?php
}

add_action('save_post', function ($post_id) {
    if (array_key_exists('title_en', $_POST)) {
        update_post_meta($post_id, '_title_en', sanitize_text_field($_POST['title_en']));
    }
    if (array_key_exists('title_ro', $_POST)) {
        update_post_meta($post_id, '_title_ro', sanitize_text_field($_POST['title_ro']));
    }
    if (array_key_exists('description_en', $_POST)) {
        update_post_meta($post_id, '_description_en', sanitize_textarea_field($_POST['description_en']));
    }
    if (array_key_exists('description_ro', $_POST)) {
        update_post_meta($post_id, '_description_ro', sanitize_textarea_field($_POST['description_ro']));
    }
});


function register_product_taxonomy() {
    register_taxonomy('product_cat', 'product', [
        'labels' => [
            'name' => 'Категории товаров',
            'singular_name' => 'Категория товара',
            'search_items' => 'Поиск категорий',
            'all_items' => 'Все категории',
            'edit_item' => 'Редактировать категорию',
            'update_item' => 'Обновить категорию',
            'add_new_item' => 'Добавить новую категорию',
            'new_item_name' => 'Название новой категории',
            'menu_name' => 'Категории',
        ],
        'hierarchical' => true,
        'public' => true,
        'show_in_rest' => true,
        'show_admin_column' => true,
        'rewrite' => ['slug' => 'product-category'],
        'capabilities' => [
            'manage_terms' => 'manage_product_categories',
            'edit_terms'   => 'manage_product_categories',
            'delete_terms' => 'manage_product_categories',
            'assign_terms' => 'edit_products',
        ],
    ]);
}
add_action('init', 'register_product_taxonomy');

add_action('product_cat_add_form_fields', 'add_product_cat_translations', 10);
function add_product_cat_translations() {
    ?>
    <div class="form-field">
        <label for="translation_ro">Название (румынский)</label>
        <input type="text" name="translation_ro" id="translation_ro">
    </div>
    <div class="form-field">
        <label for="translation_en">Название (английский)</label>
        <input type="text" name="translation_en" id="translation_en">
    </div>
    <?php
}
add_action('product_cat_edit_form_fields', 'edit_product_cat_translations', 10, 2);
function edit_product_cat_translations($term, $taxonomy) {
    $ro = get_term_meta($term->term_id, 'translation_ro', true);
    $en = get_term_meta($term->term_id, 'translation_en', true);
    ?>
    <tr class="form-field">
        <th scope="row"><label for="translation_ro">Название (румынский)</label></th>
        <td><input type="text" name="translation_ro" id="translation_ro" value="<?php echo esc_attr($ro); ?>"></td>
    </tr>
    <tr class="form-field">
        <th scope="row"><label for="translation_en">Название (английский)</label></th>
        <td><input type="text" name="translation_en" id="translation_en" value="<?php echo esc_attr($en); ?>"></td>
    </tr>
    <?php
}

// function delete_all_product_categories() {
//     $terms = get_terms(['taxonomy' => 'product_cat', 'hide_empty' => false]);
//     foreach ($terms as $term) {
//         wp_delete_term($term->term_id, 'product_cat');
//     }
// }
// add_action('init', 'delete_all_product_categories', 20);


// function populate_product_categories() {
//     $categories = [
//         [
//             'name' => 'Мебель и интерьер',
//             'slug' => 'furniture-interior',
//             'ro' => 'Mobilă și interior',
//             'en' => 'Furniture and Interior',
//             'children' => [
//                 ['name' => 'Кровати', 'slug' => 'beds', 'ro' => 'Paturi', 'en' => 'Beds'],
//                 ['name' => 'Диваны', 'slug' => 'sofas', 'ro' => 'Canapele', 'en' => 'Sofas'],
//                 ['name' => 'Столы и стулья', 'slug' => 'tables-chairs', 'ro' => 'Mese și scaune', 'en' => 'Tables and Chairs'],
//                 ['name' => 'Шкафы и комоды', 'slug' => 'wardrobes-dressers', 'ro' => 'Dulapuri și comode', 'en' => 'Wardrobes and Dressers'],
//             ]
//         ]
//     ];

//     foreach ($categories as $category) {
//         insert_product_category_recursive($category);
//     }
// }
// add_action('init', 'populate_product_categories', 30);

// function insert_product_category_recursive($category, $parent_id = 0) {
//     $args = [
//         'slug' => $category['slug']
//     ];

//     if ($parent_id > 0) {
//         $args['parent'] = $parent_id;
//     }

//     $term = wp_insert_term($category['name'], 'product_cat', $args);

//     if (!is_wp_error($term)) {
//         $term_id = $term['term_id'];
//         update_term_meta($term_id, 'translation_ro', $category['ro']);
//         update_term_meta($term_id, 'translation_en', $category['en']);

//         if (!empty($category['children']) && is_array($category['children'])) {
//             foreach ($category['children'] as $child_category) {
//                 insert_product_category_recursive($child_category, $term_id);
//             }
//         }
//     }
// }

// add_action('init', 'populate_product_categories', 30);



// Счётчик просмотров
function increment_product_views($post_id) {
    if (!is_singular('product')) return;

    $today = date('Y-m-d');

    if (is_user_logged_in()) {
        $user_id = get_current_user_id();
        $viewed_key = 'viewed_product_' . $post_id;
        if (get_user_meta($user_id, $viewed_key, true)) return;
        update_user_meta($user_id, $viewed_key, time());
    } else {
        $cookie_key = 'viewed_product_' . $post_id;
        if (isset($_COOKIE[$cookie_key])) return;
        setcookie($cookie_key, '1', time() + 3600, "/");
    }

    $views = get_post_meta($post_id, 'product_views', true);
    $views = $views ? (int)$views : 0;
    update_post_meta($post_id, 'product_views', ++$views);

    $daily_views = get_post_meta($post_id, '_product_views_daily', true);
    if (!is_array($daily_views)) $daily_views = [];

    if (!isset($daily_views[$today])) $daily_views[$today] = 0;
    $daily_views[$today]++;

    update_post_meta($post_id, '_product_views_daily', $daily_views);
}

function get_product_daily_views($post_id = null) {
    if (!$post_id) $post_id = get_the_ID();
    $daily_views = get_post_meta($post_id, '_product_views_daily', true);
    return is_array($daily_views) ? $daily_views : [];
}

function track_product_views() {
    if (is_singular('product')) {
        global $post;
        if ($post) {
            increment_product_views($post->ID);
        }
    }
}
add_action('wp', 'track_product_views');

function get_product_views($post_id = null) {
    if (!$post_id) $post_id = get_the_ID();
    $views = get_post_meta($post_id, 'product_views', true);
    return $views ? (int)$views : 0;
}

function add_product_views_column($columns) {
    $columns['product_views'] = 'Просмотры';
    $columns['product_price'] = 'Цена';
    return $columns;
}
add_filter('manage_product_posts_columns', 'add_product_views_column');

function show_product_views_column($column, $post_id) {
    if ($column == 'product_views') {
        echo get_product_views($post_id);
    }
    if ($column == 'product_price') {
        $price = get_post_meta($post_id, 'product_price', true);
        echo $price ? esc_html($price) . ' ₽' : '—';
    }
}
add_action('manage_product_posts_custom_column', 'show_product_views_column', 10, 2);

// Поле "Цена товара"
function add_product_price_metabox() {
    add_meta_box(
        'product_price_metabox',
        'Цена товара',
        'render_product_price_metabox',
        'product',
        'side',
        'default'
    );
}
add_action('add_meta_boxes', 'add_product_price_metabox');

function render_product_price_metabox($post) {
    $price = get_post_meta($post->ID, 'product_price', true);
    wp_nonce_field('save_product_price', 'product_price_nonce');
    ?>
    <label for="product_price_field">Цена (₽):</label>
    <input type="number" name="product_price_field" id="product_price_field" value="<?php echo esc_attr($price); ?>" step="0.01" min="0" style="width: 100%;" />
    <?php
}

function save_product_price_metabox($post_id) {
    if (!isset($_POST['product_price_nonce']) || !wp_verify_nonce($_POST['product_price_nonce'], 'save_product_price')) {
        return;
    }

    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;

    if (isset($_POST['product_price_field'])) {
        $price = sanitize_text_field($_POST['product_price_field']);
        update_post_meta($post_id, 'product_price', $price);
    }
}
add_action('save_post_product', 'save_product_price_metabox');
