<?php
global $wpdb;
add_theme_support('post-thumbnails');

// Register Custom Post Type "Products"
function register_product_post_type() {
    register_post_type('products', [
        'labels' => [
            'name' => 'Products',
            'singular_name' => 'Product',
            'add_new' => 'Add New Product',
            'edit_item' => 'Edit Product',
            'new_item' => 'New Product',
            'view_item' => 'View Product',
            'search_items' => 'Search Products',
            'not_found' => 'No products found',
            'menu_name' => 'Products',
        ],
        'description' => 'Custom post type for products',
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

// Product Translations (EN / RO)
add_action('add_meta_boxes', function () {
    add_meta_box('product_translations', 'Translations', 'render_product_translations', 'products');
});

function render_product_translations($post) {
    $title_en = get_post_meta($post->ID, '_title_en', true);
    $title_ro = get_post_meta($post->ID, '_title_ro', true);
    $desc_en = get_post_meta($post->ID, '_description_en', true);
    $desc_ro = get_post_meta($post->ID, '_description_ro', true);
    ?>
    <label>Title (EN)</label><br>
    <input type="text" name="title_en" value="<?= esc_attr($title_en) ?>" style="width:100%"><br><br>

    <label>Title (RO)</label><br>
    <input type="text" name="title_ro" value="<?= esc_attr($title_ro) ?>" style="width:100%"><br><br>

    <label>Description (EN)</label><br>
    <textarea name="description_en" style="width:100%"><?= esc_textarea($desc_en) ?></textarea><br><br>

    <label>Description (RO)</label><br>
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

// Product Views Counter
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
    $columns['product_views'] = 'Views';
    $columns['product_price'] = 'Price';
    return $columns;
}
add_filter('manage_products_posts_columns', 'add_product_views_column');

function show_product_views_column($column, $post_id) {
    if ($column == 'product_views') {
        echo (int) get_product_views($post_id);
    }
    if ($column == 'product_price') {
        $price = get_post_meta($post_id, 'product_price', true);
        echo $price ? esc_html($price) : '—';
    }
}
add_action('manage_products_posts_custom_column', 'show_product_views_column', 10, 2);

// Product Price Metabox
function add_product_price_metabox() {
    add_meta_box(
        'product_price_metabox',
        'Product Price',
        'render_product_price_metabox',
        'products',
        'side',
        'default'
    );
}
add_action('add_meta_boxes', 'add_product_price_metabox');

function render_product_price_metabox($post) {
    $price = get_post_meta($post->ID, 'product_price', true);
    $currency = get_post_meta($post->ID, 'product_currency', true) ?: 'LEI';
    $currencies = ['LEI' => 'LEI', 'USD' => '$', 'EUR' => '€'];
    wp_nonce_field('save_product_price', 'product_price_nonce');
    ?>
    <label for="product_price_field">Price:</label>
    <div style="display:flex; gap:8px; align-items:center;">
        <input type="number" name="product_price_field" id="product_price_field" value="<?php echo esc_attr($price); ?>" step="0.01" min="0" style="flex:1;" />
        <select name="product_currency_field" id="product_currency_field">
            <?php foreach ($currencies as $key => $symbol): ?>
                <option value="<?php echo esc_attr($key); ?>" <?php selected($currency, $key); ?>><?php echo esc_html($symbol); ?></option>
            <?php endforeach; ?>
        </select>
    </div>
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

    if (isset($_POST['product_currency_field'])) {
        $currency = sanitize_text_field($_POST['product_currency_field']);
        update_post_meta($post_id, 'product_currency', $currency);
    }
}
add_action('save_post_products', 'save_product_price_metabox');


// Product Type Metabox
function add_product_type_metabox() {
    add_meta_box(
        'product_type_metabox',
        'Ad Type',
        'render_product_type_metabox',
        'products',
        'side',
        'default'
    );
}
add_action('add_meta_boxes', 'add_product_type_metabox');

function render_product_type_metabox($post) {
    $type = get_post_meta($post->ID, 'product_type', true) ?: 'sell';
    ?>
    <select name="product_type_field" id="product_type_field" style="width:100%;">
        <option value="sell" <?php selected($type, 'sell'); ?>>For Sale</option>
        <option value="buy" <?php selected($type, 'buy'); ?>>Wanted</option>
    </select>
    <?php
}

function save_product_type_metabox($post_id) {
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;

    if (isset($_POST['product_type_field'])) {
        update_post_meta($post_id, 'product_type', sanitize_text_field($_POST['product_type_field']));
    }
}
add_action('save_post_products', 'save_product_type_metabox');

// Product Taxonomy (Categories with images and translations)
function register_product_taxonomy() {
    register_taxonomy('product_cat', 'products', [
        'labels' => [
            'name'          => 'Product Categories',
            'singular_name' => 'Product Category',
            'search_items'  => 'Search Categories',
            'all_items'     => 'All Categories',
            'edit_item'     => 'Edit Category',
            'update_item'   => 'Update Category',
            'add_new_item'  => 'Add New Category',
            'new_item_name' => 'New Category Name',
            'menu_name'     => 'Categories',
        ],
        'hierarchical'      => true,
        'public'            => true,
        'show_in_rest'      => true,
        'show_admin_column' => true,
        'rewrite' => [
            'slug'         => 'categories',
            'with_front'   => false,
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

// Category image fields
function product_cat_add_image_fields() { ?>
    <div class="form-field term-group">
        <label for="category_image_color">Category Image (Color)</label>
        <input type="hidden" id="category_image_color" name="category_image_color" value="">
        <div id="category-image-color-wrapper"></div>
        <p>
            <input type="button" class="button button-secondary category_image_upload_button" data-target="category_image_color" value="Select Image">
            <input type="button" class="button button-secondary category_image_remove_button" data-target="category_image_color" value="Remove Image">
        </p>
    </div>

    <div class="form-field term-group">
        <label for="category_image_outline">Category Image (Outline)</label>
        <input type="hidden" id="category_image_outline" name="category_image_outline" value="">
        <div id="category-image-outline-wrapper"></div>
        <p>
            <input type="button" class="button button-secondary category_image_upload_button" data-target="category_image_outline" value="Select Image">
            <input type="button" class="button button-secondary category_image_remove_button" data-target="category_image_outline" value="Remove Image">
        </p>
    </div>
<?php }
add_action('product_cat_add_form_fields', 'product_cat_add_image_fields', 10, 2);

function product_cat_edit_image_fields($term) {
    $color_id   = get_term_meta($term->term_id, 'category_image_color', true);
    $outline_id = get_term_meta($term->term_id, 'category_image_outline', true);
    ?>
    <tr class="form-field term-group-wrap">
        <th scope="row"><label for="category_image_color">Category Image (Color)</label></th>
        <td>
            <input type="hidden" id="category_image_color" name="category_image_color" value="<?php echo esc_attr($color_id); ?>">
            <div id="category-image-color-wrapper">
                <?php if ($color_id) echo wp_get_attachment_image($color_id, 'thumbnail'); ?>
            </div>
            <p>
                <input type="button" class="button button-secondary category_image_upload_button" data-target="category_image_color" value="Select Image">
                <input type="button" class="button button-secondary category_image_remove_button" data-target="category_image_color" value="Remove Image">
            </p>
        </td>
    </tr>
    <tr class="form-field term-group-wrap">
        <th scope="row"><label for="category_image_outline">Category Image (Outline)</label></th>
        <td>
            <input type="hidden" id="category_image_outline" name="category_image_outline" value="<?php echo esc_attr($outline_id); ?>">
            <div id="category-image-outline-wrapper">
                <?php if ($outline_id) echo wp_get_attachment_image($outline_id, 'thumbnail'); ?>
            </div>
            <p>
                <input type="button" class="button button-secondary category_image_upload_button" data-target="category_image_outline" value="Select Image">
                <input type="button" class="button button-secondary category_image_remove_button" data-target="category_image_outline" value="Remove Image">
            </p>
        </td>
    </tr>
    <?php
}
add_action('product_cat_edit_form_fields', 'product_cat_edit_image_fields', 10, 2);

function save_product_cat_images($term_id) {
    $fields = ['category_image_color', 'category_image_outline'];

    foreach ($fields as $field) {
        if (!empty($_POST[$field])) {
            update_term_meta($term_id, $field, intval($_POST[$field]));
        } else {
            delete_term_meta($term_id, $field);
        }
    }
}
add_action('edited_product_cat', 'save_product_cat_images', 10, 2);
add_action('created_product_cat', 'save_product_cat_images', 10, 2);

// Category translations
function add_product_cat_translations() { ?>
    <div class="form-field">
        <label for="translation_ro">Name (RO)</label>
        <input type="text" name="translation_ro" id="translation_ro">
    </div>
    <div class="form-field">
        <label for="translation_en">Name (EN)</label>
        <input type="text" name="translation_en" id="translation_en">
    </div>
<?php }
add_action('product_cat_add_form_fields', 'add_product_cat_translations', 10);

function edit_product_cat_translations($term) {
    $ro = get_term_meta($term->term_id, 'translation_ro', true);
    $en = get_term_meta($term->term_id, 'translation_en', true);
    ?>
    <tr class="form-field">
        <th scope="row"><label for="translation_ro">Name (RO)</label></th>
        <td><input type="text" name="translation_ro" id="translation_ro" value="<?php echo esc_attr($ro); ?>"></td>
    </tr>
    <tr class="form-field">
        <th scope="row"><label for="translation_en">Name (EN)</label></th>
        <td><input type="text" name="translation_en" id="translation_en" value="<?php echo esc_attr($en); ?>"></td>
    </tr>
    <?php
}
add_action('product_cat_edit_form_fields', 'edit_product_cat_translations', 10, 2);

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


function add_dynamic_features_metabox() {
    add_meta_box(
        'dynamic_features_metabox',
        'Дополнительные характеристики',
        'render_dynamic_features_metabox',
        'products',
        'normal',
        'default'
    );
}
add_action('add_meta_boxes', 'add_dynamic_features_metabox');

function render_dynamic_features_metabox($post) {
    $features    = get_product_category_features();
    $post_cats   = wp_get_post_terms($post->ID, 'product_cat', ['fields' => 'ids']);
    $language    = $GLOBALS['language'] ?? 'ru';
    $allowed_cats = array_intersect(array_keys($features), $post_cats);

    if (!$allowed_cats) {
        echo '<p>' . __('Дополнительные характеристики недоступны для этой категории', 'text-domain') . '</p>';
        return;
    }

    echo '<div id="dynamic-features-container">';

    $saved_values = get_post_meta($post->ID, 'dynamic_features', true);
    if (!is_array($saved_values)) $saved_values = [];

    foreach ($allowed_cats as $cat_id) {
        echo '<div class="category-features" data-cat-id="'.esc_attr($cat_id).'" style="border:1px solid #ccc;padding:10px;margin-bottom:10px">';
        echo '<strong>' . __('Категория ID', 'text-domain') . ' ' . esc_html($cat_id) . '</strong>';

        foreach ($features[$cat_id] as $key => $field) {
            // создаём такой же ключ, как на фронте
            $js_key = '_' . strtolower(str_replace(' ', '-', preg_replace('/[^a-zA-Z0-9а-яёА-ЯЁ_\s]/u','',$key)));
            $value  = $saved_values[$js_key] ?? '';

            echo '<p>';
            echo '<label style="font-weight:bold;">'.esc_html($field['label'][$language] ?? $field['label']['ru']).'</label>';

            if (!empty($field['options'])) {
                echo '<select class="dynamic-feature" data-key="'.esc_attr($js_key).'" style="width:100%">';
                echo '<option value="">— ' . __('Выберите', 'text-domain') . ' —</option>';
                foreach ($field['options'] as $opt) {
                    $opt_value = $opt[$language] ?? $opt['ru'];
                    $selected  = ($value == $opt_value) ? 'selected' : '';
                    echo '<option value="'.esc_attr($opt_value).'" '.$selected.'>'.esc_html($opt_value).'</option>';
                }
                echo '</select>';
            } else {
                echo '<input type="text" class="dynamic-feature" data-key="'.esc_attr($js_key).'" value="'.esc_attr($value).'" style="width:100%">';
            }

            echo '</p>';
        }

        echo '</div>';
    }

    echo '<input type="hidden" name="dynamic_fields" id="dynamic_fields_input" value="'.esc_attr(json_encode($saved_values)).'">';
    echo '</div>';

    // JS для обновления скрытого поля
    ?>
    <script>
    document.addEventListener("DOMContentLoaded", function(){
        const container = document.getElementById("dynamic-features-container");
        const hiddenInput = document.getElementById("dynamic_fields_input");
        if(container && hiddenInput){
            container.addEventListener("change", function(e){
                if(e.target.classList.contains("dynamic-feature")){
                    const allFields = container.querySelectorAll(".dynamic-feature");
                    const data = {};
                    allFields.forEach(f => {
                        const key = f.dataset.key;
                        if(key) data[key] = f.value;
                    });
                    hiddenInput.value = JSON.stringify(data);
                }
            });
        }
    });
    </script>
    <?php
}

function save_dynamic_features_metabox($post_id){
    if(defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    // проверяем пост-тип через get_post_type, так надёжнее
    if (get_post_type($post_id) !== 'products') return;

    if(!empty($_POST['dynamic_fields'])){
        $dynamic_fields = json_decode(wp_unslash($_POST['dynamic_fields']), true);
        if(is_array($dynamic_fields)){
            $sanitized = [];
            foreach($dynamic_fields as $k => $v){
                $sanitized[$k] = sanitize_text_field($v);
            }
            update_post_meta($post_id, 'dynamic_features', $sanitized);
        }
    }
}
add_action('save_post_products', 'save_dynamic_features_metabox');


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