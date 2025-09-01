<?php
global $wpdb;
add_theme_support('post-thumbnails');

function register_product_post_type() {
    register_post_type('products', [
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
    add_meta_box('product_translations', 'Переводы', 'render_product_translations', 'products');
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
    if (get_post_type($post_id) !== 'products') return;

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
    register_taxonomy('product_cat', 'products', [
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
        'rewrite' => [
            'slug' => 'categories',
            'with_front' => false,
            'hierarchical' => true,
        ],
        'capabilities' => [
            'manage_terms' => 'manage_product_categories',
            'edit_terms'   => 'manage_product_categories',
            'delete_terms' => 'manage_product_categories',
            'assign_terms' => 'edit_products',
        ],
    ]);
}
add_action('init', 'register_product_taxonomy');

function product_cat_image_field($term) {
    $image_id = get_term_meta($term->term_id, 'category_image_id', true);
    ?>
    <tr class="form-field term-group-wrap">
        <th scope="row"><label for="category_image">Изображение категории</label></th>
        <td>
            <input type="hidden" id="category_image" name="category_image" value="<?php echo esc_attr($image_id); ?>">
            <div id="category-image-wrapper">
                <?php if ($image_id) : ?>
                    <?php echo wp_get_attachment_image($image_id, 'thumbnail'); ?>
                <?php endif; ?>
            </div>
            <p>
                <input type="button" class="button button-secondary" id="category_image_upload_button" value="Выбрать изображение">
                <input type="button" class="button button-secondary" id="category_image_remove_button" value="Удалить изображение">
            </p>
        </td>
    </tr>
    <?php
}
add_action('product_cat_edit_form_fields', 'product_cat_image_field', 10, 2);

add_action('product_cat_add_form_fields', function() { ?>
    <div class="form-field term-group">
        <label for="category_image">Изображение категории</label>
        <input type="hidden" id="category_image" name="category_image" value="">
        <div id="category-image-wrapper"></div>
        <p>
            <input type="button" class="button button-secondary" id="category_image_upload_button" value="Выбрать изображение">
            <input type="button" class="button button-secondary" id="category_image_remove_button" value="Удалить изображение">
        </p>
    </div>
<?php }, 10, 2);

function save_product_cat_image($term_id) {
    if (isset($_POST['category_image']) && '' !== $_POST['category_image']) {
        update_term_meta($term_id, 'category_image_id', intval($_POST['category_image']));
    } else {
        delete_term_meta($term_id, 'category_image_id');
    }
}
add_action('edited_product_cat', 'save_product_cat_image', 10, 2);
add_action('created_product_cat', 'save_product_cat_image', 10, 2);

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

function save_product_cat_translations($term_id) {
    if (isset($_POST['translation_ro'])) {
        update_term_meta($term_id, 'translation_ro', sanitize_text_field($_POST['translation_ro']));
    }
    if (isset($_POST['translation_en'])) {
        update_term_meta($term_id, 'translation_en', sanitize_text_field($_POST['translation_en']));
    }
}
add_action('edited_product_cat', 'save_product_cat_translations', 10, 2);
add_action('created_product_cat', 'save_product_cat_translations', 10, 2);

// Счётчик просмотров
function increment_product_views($post_id) {
    if (!is_singular('products')) return;

    $user_id = is_user_logged_in() ? get_current_user_id() : null;
    $ip = $_SERVER['REMOTE_ADDR'];
    $today = date('Y-m-d');

    $cookie_key = 'viewed_product_' . $post_id;
    if (!$user_id && isset($_COOKIE[$cookie_key])) return;
    if (!$user_id) setcookie($cookie_key, '1', time() + 3600, "/");

    global $wpdb;
    $wpdb->insert(
        $wpdb->prefix . 'product_views',
        [
            'product_id' => $post_id,
            'user_id'    => $user_id,
            'ip_address' => $ip,
            'viewed_at'  => current_time('mysql')
        ],
        ['%d','%d','%s','%s']
    );

    $table = $wpdb->prefix . 'product_daily_views';
    $wpdb->query(
        $wpdb->prepare(
            "INSERT INTO $table (product_id, view_date, views) VALUES (%d, %s, 1)
             ON DUPLICATE KEY UPDATE views = views + 1",
            $post_id, $today
        )
    );
}

function get_product_daily_views($post_id = null) {
    global $wpdb;
    if (!$post_id) $post_id = get_the_ID();
    return $wpdb->get_results(
        $wpdb->prepare(
            "SELECT view_date, views FROM {$wpdb->prefix}product_daily_views WHERE product_id = %d ORDER BY view_date ASC",
            $post_id
        ),
        ARRAY_A
    );
}

function track_product_views() {
    if (is_singular('products')) {
        global $post;
        if ($post) {
            increment_product_views($post->ID);
        }
    }
}
add_action('wp', 'track_product_views');

function get_product_views($post_id = null) {
    global $wpdb;
    if (!$post_id) $post_id = get_the_ID();
    return (int)$wpdb->get_var(
        $wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}product_views WHERE product_id = %d", $post_id)
    );
}

function add_product_views_column($columns) {
    $columns['product_views'] = 'Просмотры';
    $columns['product_price'] = 'Цена';
    return $columns;
}
add_filter('manage_products_posts_columns', 'add_product_views_column');

function show_product_views_column($column, $post_id) {
    if ($column == 'product_views') {
        echo (int) get_product_views($post_id);
    }
    if ($column == 'product_price') {
        $price = get_post_meta($post_id, 'product_price', true);
        echo $price ? esc_html($price) . ' ₽' : '—';
    }
}
add_action('manage_products_posts_custom_column', 'show_product_views_column', 10, 2);

function add_product_price_metabox() {
    add_meta_box(
        'product_price_metabox',
        'Цена товара',
        'render_product_price_metabox',
        'products',
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
add_action('save_post_products', 'save_product_price_metabox');



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