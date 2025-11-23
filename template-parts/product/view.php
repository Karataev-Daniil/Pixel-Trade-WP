<?php
$product_id = $args['product_id'] ?? get_the_ID();
if (!$product_id) return;

$lang = $GLOBALS['language'] ?? 'ru';

/* Translation helper */
if (!function_exists('tr')) {
    function tr(array $array, string $lang, string $default = ''): string {
        if (!empty($array[$lang])) return $array[$lang];
        foreach ($array as $val) if (!empty($val)) return $val;
        return $default;
    }
}

/* Format price helper */
if (!function_exists('format_price_with_currency')) {
    function format_price_with_currency($price, $currency = 'lei') {
        if (!$price) return '-';
        switch ($currency) {
            case 'usd': $symbol = '$'; break;
            case 'eur': $symbol = '€'; break;
            default: $symbol = 'lei'; break;
        }
        return number_format((float)$price, 2, '.', ',') . ' ' . $symbol;
    }
}

/* Get feature options with translations */
if (!function_exists('get_product_category_features_from_db')) {
    function get_product_category_features_from_db(): array {
        global $wpdb;

        $rows = $wpdb->get_results("
            SELECT f.id as feature_id, f.category_id, f.key,
                   f.label_ru, f.label_en, f.label_ro, f.type,
                   o.id as option_id, o.value_ru, o.value_en, o.value_ro
            FROM {$wpdb->prefix}features f
            LEFT JOIN {$wpdb->prefix}feature_options o ON o.feature_id = f.id
            ORDER BY f.category_id, f.id, o.id
        ");

        $features = [];
        foreach ($rows as $row) {
            if (!isset($features[$row->category_id][$row->key])) {
                $features[$row->category_id][$row->key] = [
                    'label' => [
                        'ru' => $row->label_ru,
                        'en' => $row->label_en,
                        'ro' => $row->label_ro,
                    ],
                    'options' => [],
                    'type' => $row->type, // <--- добавлено
                ];
            }

            if ($row->option_id) {
                $features[$row->category_id][$row->key]['options'][$row->option_id] = [
                    'ru' => $row->value_ru,
                    'en' => $row->value_en,
                    'ro' => $row->value_ro,
                ];
            }
        }
        return $features;
    }
}

/* Get feature option by ID */
if (!function_exists('get_feature_option_label_by_id')) {
    function get_feature_option_label_by_id($option_id, string $lang = 'ru'): string {
        global $wpdb;
        if (!$option_id) return '';

        $row = $wpdb->get_row($wpdb->prepare("
            SELECT value_ru, value_en, value_ro 
            FROM {$wpdb->prefix}feature_options 
            WHERE id = %d
        ", (int)$option_id));

        if (!$row) return '';

        return match ($lang) {
            'en' => $row->value_en ?: $row->value_ru,
            'ro' => $row->value_ro ?: $row->value_ru,
            default => $row->value_ru,
        };
    }
}

/* Build product array */
$product = [
    'title' => [
        'ru' => get_the_title($product_id),
        'en' => get_post_meta($product_id, '_title_en', true),
        'ro' => get_post_meta($product_id, '_title_ro', true),
    ],
    'content' => [
        'ru' => get_the_content($product_id),
        'en' => get_post_meta($product_id, '_description_en', true),
        'ro' => get_post_meta($product_id, '_description_ro', true),
    ],
    'price' => get_post_meta($product_id, 'product_price', true),
    'currency' => get_post_meta($product_id, 'product_currency', true) ?: 'lei',
    'thumbnail_id' => get_post_thumbnail_id($product_id),
    'gallery_ids' => [],
    'author_id' => get_the_author_meta('ID'),
    'product_type' => get_post_meta($product_id, 'product_type', true),
];

/* Gallery */
$gallery_ids = get_post_meta($product_id, 'product_gallery', true);
$product['gallery_ids'] = is_array($gallery_ids) ? array_filter($gallery_ids) : [];
if ($product['thumbnail_id'] && !in_array($product['thumbnail_id'], $product['gallery_ids'])) {
    array_unshift($product['gallery_ids'], $product['thumbnail_id']);
}

/* Author data */
$current_user_id = get_current_user_id();
$author_id = $product['author_id'];
$author_registered = get_the_author_meta('user_registered');
$author_region = get_user_meta($author_id, 'region', true);

if ($author_region) {
    $regions = get_moldova_regions();
    foreach ($regions as $r) {
        if ($r['ru'] === $author_region) {
            $author_region = $r[$lang] ?? $r['ru'];
            break;
        }
    }
}

$user = get_userdata($author_id);
$avatar_id = get_user_meta($user->ID, 'profile_avatar', true);
$author_avatar = $avatar_id
    ? wp_get_attachment_image($avatar_id, 'thumbnail', false, ['alt' => esc_attr($user->display_name)])
    : get_avatar($user->ID, 64, '', esc_attr($user->display_name));

/* Features */
$features = get_product_category_features_from_db();
$saved_values = get_post_meta($product_id, 'dynamic_features', true);
$post_cats = wp_get_post_terms($product_id, 'product_cat', ['fields' => 'ids']);
$allowed_cats = array_intersect(array_keys($features), $post_cats);
?>

<div class="product-card__wrapper">
    <div class="container-medium">
        <main>
            <?php get_template_part('template-parts/breadcrumbs'); ?>

            <h1 class="product-card__title display-small">
                <?= esc_html(tr($product['title'], $lang, get_the_title($product_id))); ?>
            </h1>

            <div class="product-card">
                <article class="product-content">
                    <?php if (!empty($product['gallery_ids'])): ?>
                        <section class="product-gallery-carousel" aria-label="<?= esc_attr(t('Галерея изображений товара','Product image gallery','Galerie de imagini ale produsului')); ?>">
                            <div class="main-slider">
                                <?php foreach ($product['gallery_ids'] as $id): ?>
                                    <figure>
                                        <?= wp_get_attachment_image($id, 'large', false, [
                                            'alt' => get_post_meta($id, '_wp_attachment_image_alt', true)
                                                ?: t('Изображение товара', 'Product image', 'Imagine produs')
                                        ]); ?>
                                    </figure>
                                <?php endforeach; ?>
                            </div>
                        </section>
                    <?php endif; ?>

                    <?php
                    function get_option_label_safe($value, $field, $lang = 'ru') {
                        if (!$value) return '';
                    
                        if (isset($field['options'][$value])) {
                            $opt = $field['options'][$value];
                            return $opt[$lang] ?? $opt['ru'] ?? '';
                        }
                    
                        if (is_numeric($value)) {
                            return get_feature_option_label_by_id($value, $lang);
                        }
                    
                        return $value;
                    }
                    ?>

                    <?php if (!empty($saved_values) && is_array($saved_values) && $allowed_cats): ?>
                    <section class="product-dynamic-features">
                        <h2 class="title-largest">
                            <?= t('Дополнительные характеристики','Additional features','Caracteristici suplimentare'); ?>
                        </h2>
                    
                        <?php foreach ($allowed_cats as $cat_id): ?>
                        <div class="category-features" data-category-id="<?= esc_attr($cat_id); ?>">
                            <ul>
                                <?php
                                foreach ($features[$cat_id] as $key => $field):
                                    $js_key = '__' . strtolower(str_replace(' ', '-', preg_replace('/[^a-zA-Z0-9а-яёА-ЯЁ_\s]/u','',$key)));

                                    $value = $saved_values[$js_key] ?? $saved_values[$key] ?? '';
                                    if ($value === '' || $value === null) continue;
                                
                                    $label = $field['label'][$lang] ?? $field['label']['ru'] ?? $key;
                                    $type = $field['type'] ?? 'text';
                                
                                    $value_label = ($type === 'select')
                                        ? get_option_label_safe($value, $field, $lang)
                                        : $value;
                                
                                    if (!$value_label) continue;
                                ?>
                                <li class="body-small-medium feature-item">
                                    <span class="feature-label-dots">
                                        <span class="feature-label"><?= esc_html($label); ?></span>
                                        <span class="feature-dots"></span>
                                    </span>
                                    <span class="feature-value"><?= esc_html($value_label); ?></span>
                                </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                        <?php endforeach; ?>
                    </section>
                    <?php endif; ?>

                    <section class="content body-small-regular" aria-label="<?= t('Описание товара','Product Description','Descriere produs'); ?>">
                        <h2 class="title-largest"><?= t('Описание товара','Product Description','Descriere produs'); ?></h2>
                        <?= wpautop(tr($product['content'], $lang, get_the_content($product_id))); ?>
                    </section>

                    <section class="price title-medium" aria-label="<?= t('Цена','Price','Preț'); ?>">
                        <p>
                            <strong><?= t('Цена:','Price:','Preț:'); ?></strong>
                            <?= format_price_with_conversions($product['price'], $product['currency']); ?>
                        </p>
                        <?php if ($author_region): ?>
                            <div class="author-region body-small-regular">
                                <strong><?= t('Регион:','Region:','Regiune:'); ?></strong> <?= esc_html($author_region); ?>
                            </div>
                        <?php endif; ?>
                    </section>

                    <?php if (is_user_logged_in() && $author_id && $author_id != $current_user_id): ?>
                        <button class="primary-button-medium dm-write-btn" data-user="<?= esc_attr($author_id); ?>">
                            <?= t('Отправить сообщение', 'Send Message', 'Trimite mesaj'); ?>
                        </button>
                    <?php endif; ?>
                </article>

                <aside class="product-sidebar">
                    <?php 
                    $user_nicename = $user->user_nicename;
                    $author_url = home_url("/{$lang}/user/{$user_nicename}");
                    ?>
                    <section class="author" aria-label="<?= t('Информация об авторе','Author Info','Informații despre autor'); ?>">
                        <div class="author-avatar"><?= $author_avatar; ?></div>
                        <div class="author-profile">
                            <a class="link-button" href="<?= esc_url($author_url); ?>">
                                <strong><?= t('Автор:','Author:','Autor:'); ?></strong> <?= esc_html($user->display_name); ?>
                            </a>
                            <span class="body-small-regular"><?= t('На сайте с','On the site since','Pe site din'); ?> <?= date_i18n('d.m.Y', strtotime($author_registered)); ?></span>
                        </div>
                    </section>

                    <section class="details" aria-label="<?= t('Детали товара','Product Details','Detalii produs'); ?>">
                        <div class="item body-small-regular"><?= t('Дата публикации','Published on','Data publicării'); ?>: <?= get_the_date('d.m.Y', $product_id); ?></div>
                        <?php if ($product['product_type']): ?>
                            <div class="item body-small-regular"><?= t('Тип','Type','Tip'); ?>: <?= esc_html($product['product_type']); ?></div>
                        <?php endif; ?>
                        <div class="item body-small-regular"><?= t('Просмотры','Views','Vizualizări'); ?>: <?= get_product_views($product_id); ?></div>
                    </section>

                    <hr>

                    <section class="price title-medium">
                        <p><?= format_price_with_conversions($product['price'], $product['currency']); ?></p>
                    </section>

                    <?php if (is_user_logged_in() && $author_id && $author_id != $current_user_id): ?>
                        <hr>
                        <button class="primary-button-medium button-medium dm-write-btn" data-user="<?= esc_attr($author_id); ?>">
                            <?= t('Отправить сообщение', 'Send Message', 'Trimite mesaj'); ?>
                        </button>
                    <?php endif; ?>

                    <?php if ($author_region): ?>
                        <hr>
                        <section class="author-region">
                            <div class="item body-medium-regular">
                                <strong><?= t('Регион:','Region','Regiune:'); ?></strong> <?= esc_html($author_region); ?>
                            </div>
                        </section>
                    <?php endif; ?>

                    <?php 
                    if (is_user_logged_in() && $product_id && $author_id != $current_user_id):
                        $favorites = function_exists('favorites_get') ? favorites_get($current_user_id, 'product') : [];
                        $is_favorite = in_array($product_id, $favorites);
                    ?>
                        <hr>
                        <section class="actions favorites">
                            <button class="toggle-favorite button <?= $is_favorite ? 'favorited' : ''; ?>" 
                                    data-id="<?= esc_attr($product_id); ?>">
                                <svg width="24" height="24" viewBox="0 0 24 24"
                                     fill="<?= $is_favorite ? 'red' : 'none' ?>"
                                     stroke="<?= $is_favorite ? 'none' : 'var(--gray_-6)' ?>"
                                     stroke-width="<?= $is_favorite ? '0' : '2' ?>"
                                     stroke-linecap="round"
                                     stroke-linejoin="round">
                                    <path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 
                                             2 5.42 4.42 3 7.5 3c1.74 0 3.41 0.81 4.5 2.09 
                                             C13.09 3.81 14.76 3 16.5 3 
                                             19.58 3 22 5.42 22 8.5c0 
                                             3.78-3.4 6.86-8.55 11.54L12 21.35z"/>
                                </svg>
                                <span class="favorites-text body-small-regular">
                                    <?= $is_favorite 
                                        ? t('Удалить из избранного', 'Remove from Favorites', 'Elimina din Favorite') 
                                        : t('Сохранить в избранное', 'Save to Favorites', 'Salvează în Favorite'); ?>
                                </span>
                            </button>
                        </section>
                    <?php endif; ?>

                    <?php if ($current_user_id === $author_id): ?>
                        <hr>
                        <section class="actions">
                            <a href="<?= esc_url(add_query_arg(['edit' => 1])); ?>" class="button primary-button-small"><?= t('Редактировать','Edit','Editați'); ?></a>
                            <button class="delete-product-btn button secondary-button-small"
                                data-product-id="<?= $product_id; ?>"
                                data-nonce="<?= wp_create_nonce('delete_product_' . $product_id); ?>">
                                <?= t('Удалить','Delete','Ștergeți'); ?>
                            </button>
                        </section>
                    <?php endif; ?>
                </aside>
            </div>
            <?php get_template_part('template-parts/product/related', null, ['product_id' => $product_id]); ?>
        </main>
    </div>
</div>
