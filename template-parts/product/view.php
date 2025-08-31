<?php
$product_id = $args['product_id'] ?? get_the_ID();
if (!$product_id) return;

$lang = $GLOBALS['language'] ?? 'ru';

// Headings and descriptions
$title_translations = [
    'ru' => get_the_title($product_id),
    'en' => get_post_meta($product_id, '_title_en', true),
    'ro' => get_post_meta($product_id, '_title_ro', true),
];

$content_translations = [
    'ru' => get_the_content(),
    'en' => get_post_meta($product_id, '_description_en', true),
    'ro' => get_post_meta($product_id, '_description_ro', true),
];

// Price
$price = get_post_meta($product_id, 'product_price', true);

// Gallery
$gallery_ids = get_post_meta($product_id, 'product_gallery', true);
$gallery_ids = is_array($gallery_ids) ? array_filter($gallery_ids) : [];
$thumbnail_id = get_post_thumbnail_id($product_id);
if ($thumbnail_id && !in_array($thumbnail_id, $gallery_ids)) array_unshift($gallery_ids, $thumbnail_id);

// Author
$author_id = get_the_author_meta('ID');
$current_user_id = get_current_user_id();
$author_avatar = get_avatar($author_id, 64);
$author_registered = get_the_author_meta('user_registered');
$author_url = get_author_posts_url($author_id);
$author_region = get_user_meta($author_id, 'region', true);
$product_type = get_post_meta($product_id, 'product_type', true);
?>

<div class="product__wrapper">
    <div class="container-medium">
        <!-- Хлебные крошки -->
        <nav class="breadcrumbs body-small-regular" aria-label="<?= t('Хлебные крошки','Breadcrumb','Firimituri'); ?>">
            <a class="link-small-underline" href="<?= home_url(); ?>"><?= t('Главная','Home','Pagina principală'); ?></a> &raquo;
            <?php
            $terms = get_the_terms($product_id, 'product_cat');
            if ($terms && !is_wp_error($terms)) {
                $deepest_term = null;
                $max_depth = -1;
                foreach ($terms as $term) {
                    $depth = 0;
                    $parent = $term->parent;
                    while ($parent) {
                        $depth++;
                        $parent_term = get_term($parent, 'product_cat');
                        if (!$parent_term || is_wp_error($parent_term)) break;
                        $parent = $parent_term->parent;
                    }
                    if ($depth > $max_depth) { $max_depth = $depth; $deepest_term = $term; }
                }

                if ($deepest_term) {
                    $breadcrumbs = [];
                    $term = $deepest_term;
                    $visited = [];
                    while ($term && !in_array($term->term_id,$visited)) {
                        $visited[] = $term->term_id;
                        $translated_name = $lang==='en'?get_term_meta($term->term_id,'translation_en',true):
                                           ($lang==='ro'?get_term_meta($term->term_id,'translation_ro',true):$term->name);
                        $translated_name = $translated_name ?: $term->name;
                        $breadcrumbs[] = '<a class="link-small-underline" href="'.get_term_link($term).'">'.esc_html($translated_name).'</a>';
                        $term = $term->parent ? get_term($term->parent,'product_cat') : false;
                    }
                    echo implode(' &raquo; ', array_reverse($breadcrumbs)) . ' &raquo; ';
                }
            }
            ?>
            <span class="link-small-default"><?= esc_html($title_translations[$lang] ?? get_the_title($product_id)); ?></span>
        </nav>

        <h1 class="product-card__title display-small"><?= esc_html($title_translations[$lang] ?? get_the_title($product_id)); ?></h1>

        <div class="product-card">
            <main>
                <article class="product-content">
                    <?php if (!empty($gallery_ids)) : ?>
                        <section class="product-gallery-carousel" aria-label="<?= esc_attr(t('Галерея изображений товара','Product image gallery','Galerie de imagini ale produsului')); ?>">
                            <div class="main-slider">
                                <?php foreach ($gallery_ids as $id): ?>
                                    <?php if ($id): ?>
                                        <figure>
                                            <?= wp_get_attachment_image($id, 'large', false, [
                                                'alt' => get_post_meta($id,'_wp_attachment_image_alt',true) ?: t('Изображение товара','Product image','Imagine produs')
                                            ]); ?>
                                        </figure>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </div>
                        </section>
                    <?php endif; ?>

                    <section class="content body-small-regular" aria-label="<?= t('Описание товара','Product Description','Descriere produs'); ?>">
                        <?= wpautop($content_translations[$lang] ?? get_the_content($product_id)); ?>
                    </section>

                    <section class="price title-medium" aria-label="<?= t('Цена','Price','Preț'); ?>">
                        <p><strong><?= t('Цена:','Price:','Preț:'); ?></strong> <?= format_price_mdl_with_conversions($price); ?></p>
                    </section>

                    <?php if (is_user_logged_in() && $author_id && $author_id != $current_user_id): 
                        $author_name = get_the_author_meta('display_name', $author_id); ?>
                        <button class="dm-write-btn" data-user="<?= esc_attr($author_id); ?>">Написать <?= esc_html($author_name); ?></button>
                    <?php endif; ?>
                </article>
            </main>

            <aside class="product-sidebar">
                <section class="author" aria-label="<?= t('Информация об авторе','Author Info','Informații despre autor'); ?>">
                    <div class="author-avatar"><?= $author_avatar; ?></div>
                    <div class="author-profile">
                        <a class="link-button" href="<?= esc_url($author_url); ?>">
                            <strong><?= t('Автор:','Author:','Autor:'); ?></strong> <?php the_author_meta('display_name',$author_id); ?>
                        </a>
                        <span class="body-small-regular"><?= t('На сайте с','On the site since','Pe site din'); ?> <?= date_i18n('d.m.Y', strtotime($author_registered)); ?></span>
                    </div>
                </section>

                <section class="details" aria-label="<?= t('Детали товара','Product Details','Detalii produs'); ?>">
                    <div class="item body-small-regular"><?= t('Дата публикации','Published on','Data publicării'); ?>: <?= get_the_date('d.m.Y', $product_id); ?></div>
                    <div class="item body-small-regular"><?= t('Просмотры','Views','Vizualizări'); ?>: <?= (int)get_post_meta($product_id,'product_views',true); ?></div>
                    <?php if ($product_type): ?><div class="item body-small-regular"><?= t('Тип','Type','Tip'); ?>: <?= esc_html($product_type); ?></div><?php endif; ?>
                </section>

                <section class="price title-medium" aria-label="<?= t('Цена','Price','Preț'); ?>"><?= format_price_mdl_with_conversions($price); ?></section>

                <?php if ($author_region): ?>
                    <section class="author-region" aria-label="<?= t('Регион автора','Author Region','Regiunea autorului'); ?>">
                        <div class="item body-small-regular"><strong><?= t('Регион:','Region:','Regiune:'); ?></strong> <?= esc_html($author_region); ?></div>
                    </section>
                <?php endif; ?>

                <?php if ($current_user_id === $author_id): ?>
                    <section class="actions" aria-label="<?= t('Управление товаром','Manage Product','Gestionați produsul'); ?>">
                        <a href="<?= esc_url(add_query_arg('edit',1)); ?>" class="button primary-button-small"><?= t('Редактировать','Edit','Editați'); ?></a>
                        <form method="post" onsubmit="return confirm('<?= t('Вы уверены, что хотите удалить этот товар?','Are you sure you want to delete this product?','Sunteți sigur că doriți să ștergeți acest produs?'); ?>');">
                            <?php wp_nonce_field('delete_product_action','delete_product_nonce'); ?>
                            <input type="hidden" name="delete_product_id" value="<?= $product_id; ?>">
                            <button type="submit" class="button secondary-button-small"><?= t('Удалить','Delete','Ștergeți'); ?></button>
                        </form>
                    </section>
                <?php endif; ?>
            </aside>
        </div>
    </div>
</div>
