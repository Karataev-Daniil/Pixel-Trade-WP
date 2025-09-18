<?php
$product_id = $args['product_id'] ?? get_the_ID();
if (!$product_id) return;

$lang = $GLOBALS['language'] ?? 'ru';

if (!function_exists('tr')) {
    function tr(array $array, string $lang, string $default = ''): string {
        if (!empty($array[$lang])) {
            return $array[$lang];
        }
        foreach ($array as $val) {
            if (!empty($val)) {
                return $val;
            }
        }
        return $default;
    }
}

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

$gallery_ids = get_post_meta($product_id, 'product_gallery', true);
$product['gallery_ids'] = is_array($gallery_ids) ? array_filter($gallery_ids) : [];
if ($product['thumbnail_id'] && !in_array($product['thumbnail_id'], $product['gallery_ids'])) {
    array_unshift($product['gallery_ids'], $product['thumbnail_id']);
}

$current_user_id = get_current_user_id();
$author_id = $product['author_id'];
$author_avatar = get_avatar($author_id, 64);
$author_registered = get_the_author_meta('user_registered');
$author_url = get_author_posts_url($author_id);
$author_region = get_user_meta($author_id, 'region', true);

$features = get_product_category_features();
$saved_values = get_post_meta($product_id, 'dynamic_features', true);
$post_cats = wp_get_post_terms($product_id, 'product_cat', ['fields' => 'ids']);
$allowed_cats = array_intersect(array_keys($features), $post_cats);
?>

<div class="product__wrapper content-main">
    <div class="container-medium">
        <main>
            <?php get_template_part('template-parts/breadcrumbs'); ?>

            <h1 class="product-card__title display-small">
                <?= esc_html(tr($product['title'], $lang, get_the_title($product_id))); ?>
            </h1>

            <div class="product-card">
                <article class="product-content">
                    <?php if (!empty($product['gallery_ids'])) : ?>
                        <section class="product-gallery-carousel" aria-label="<?= esc_attr(t('Галерея изображений товара','Product image gallery','Galerie de imagini ale produsului')); ?>">
                            <div class="main-slider">
                                <?php foreach ($product['gallery_ids'] as $id): ?>
                                    <figure>
                                        <?= wp_get_attachment_image($id, 'large', false, [
                                            'alt' => get_post_meta($id,'_wp_attachment_image_alt',true) ?: t('Изображение товара','Product image','Imagine produs')
                                        ]); ?>
                                    </figure>
                                <?php endforeach; ?>
                            </div>
                        </section>
                    <?php endif; ?>
                            
                    <section class="content body-small-regular" aria-label="<?= t('Описание товара','Product Description','Descriere produs'); ?>">
                        <?= wpautop(tr($product['content'], $lang, get_the_content($product_id))); ?>
                    </section>
                            
                    <?php if (!empty($saved_values) && is_array($saved_values) && $allowed_cats) : ?>
                        <section class="product-dynamic-features">
                            <h2><?= t('Дополнительные характеристики','Additional features','Caracteristici suplimentare'); ?></h2>
                            <?php foreach ($allowed_cats as $cat_id) : ?>
                                <div class="category-features" data-category-id="<?= esc_attr($cat_id); ?>">
                                    <ul>
                                        <?php foreach ($features[$cat_id] as $key => $field) :
                                            $js_key = '_' . strtolower(str_replace(' ', '-', preg_replace('/[^a-zA-Z0-9а-яёА-ЯЁ_\s]/u','',$key)));
                                            $value  = $saved_values[$js_key] ?? '';
                                            if ($value !== '') : ?>
                                                <li>
                                                    <strong><?= esc_html($field['label']['ru'] ?? $key); ?>:</strong>
                                                    <?= esc_html($value); ?>
                                                </li>
                                            <?php endif;
                                        endforeach; ?>
                                    </ul>
                                </div>
                            <?php endforeach; ?>
                        </section>
                    <?php endif; ?>
                            
                    <section class="price title-medium" aria-label="<?= t('Цена','Price','Preț'); ?>">
                        <p><strong><?= t('Цена:','Price:','Preț:'); ?></strong> <?= format_price_with_currency($product['price'], $product['currency']); ?></p>

                        <?php if ($author_region): ?>
                            <div class="author-region body-small-regular">
                                <strong><?= t('Регион:','Region:','Regiune:'); ?></strong> <?= esc_html($author_region); ?>
                            </div>
                        <?php endif; ?>
                    </section>

                            
                    <?php if (is_user_logged_in() && $author_id && $author_id != $current_user_id):
                        $author_name = get_the_author_meta('display_name', $author_id); ?>
                        <button class="dm-write-btn" data-user="<?= esc_attr($author_id); ?>">
                            <?= t('Написать','Write','Scrieți'); ?> <?= esc_html($author_name); ?>
                        </button>
                    <?php endif; ?>
                </article>
                    
                <aside class="product-sidebar">
                    <section class="author" aria-label="<?= t('Информация об авторе','Author Info','Informații despre autor'); ?>">
                        <div class="author-avatar"><?= $author_avatar; ?></div>
                        <div class="author-profile">
                            <a class="link-button" href="<?= esc_url($author_url); ?>">
                                <strong><?= t('Автор:','Author:','Autor:'); ?></strong> <?= get_the_author_meta('display_name',$author_id); ?>
                            </a>
                            <span class="body-small-regular"><?= t('На сайте с','On the site since','Pe site din'); ?> <?= date_i18n('d.m.Y', strtotime($author_registered)); ?></span>
                        </div>
                    </section>
                        
                    <section class="details" aria-label="<?= t('Детали товара','Product Details','Detalii produs'); ?>">
                        <div class="item body-small-regular"><?= t('Дата публикации','Published on','Data publicării'); ?>: <?= get_the_date('d.m.Y', $product_id); ?></div>
                        <div class="item body-small-regular"><?= t('Просмотры','Views','Vizualizări'); ?>: <?= get_product_views($product_id); ?></div>
                        <?php if ($product['product_type']): ?>
                            <div class="item body-small-regular"><?= t('Тип','Type','Tip'); ?>: <?= esc_html($product['product_type']); ?></div>
                        <?php endif; ?>
                    </section>

                    <section class="price title-medium" aria-label="<?= t('Цена','Price','Preț'); ?>">
                        <?= format_price_with_currency($product['price'], $product['currency']); ?>
                    </section>

                    <?php if ($author_region): ?>
                        <section class="author-region" aria-label="<?= t('Регион автора','Author Region','Regiunea autorului'); ?>">
                            <div class="item body-small-regular"><strong><?= t('Регион:','Region','Regiune:'); ?></strong> <?= esc_html($author_region); ?></div>
                        </section>
                    <?php endif; ?>

                    <?php if ($current_user_id === $author_id || current_user_can('manage_options')): ?>
                        <section class="actions" aria-label="<?= t('Управление товаром','Manage Product','Gestionați produsul'); ?>">
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
        </main>
    </div>
</div>
