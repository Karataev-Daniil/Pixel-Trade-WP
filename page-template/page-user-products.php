<?php
/* Template Name: Мои Товары */

if (isset($_GET['delete_product'])) {
    $product_id = intval($_GET['delete_product']);
    if (current_user_can('edit_post', $product_id)) {
        wp_trash_post($product_id);
        wp_redirect(remove_query_arg(['delete_product']));
        exit;
    }
}

get_header();

if (!is_user_logged_in()) {
    echo '<div class="container my-products-wrapper"><p class="body-medium-regular">Пожалуйста, <a href="' . wp_login_url() . '" class="link-medium-underline">войдите</a>, чтобы просмотреть свои товары.</p></div>';
    get_footer();
    exit;
}

$current_user_id = get_current_user_id();
$args = [
    'post_type'      => 'product',
    'posts_per_page' => -1,
    'author'         => $current_user_id,
    'post_status'    => ['publish', 'draft', 'pending'],
];
$products = new WP_Query($args);
?>
<div class="dashboard__wrapper">
    <div class="container-medium">
        <div class="dashboard-header">
            <h2 class="title-large">Мои товары</h2>
            <a href="?add_product=true" class="primary-button-medium button-medium">Добавить товар</a>
        </div>

        <?php if ($products->have_posts()): ?>
            <ul class="product-list">
                <?php while ($products->have_posts()): $products->the_post(); ?>
                    <li class="product-item">
                        <div class="product-card">
                            <?php
                            $lang = $GLOBALS['language'] ?? 'ru';

                            // Получаем переводы из мета
                            $title_translations = [
                                'ru' => get_the_title(),
                                'en' => get_post_meta(get_the_ID(), '_product_title_en', true),
                                'ro' => get_post_meta(get_the_ID(), '_product_title_ro', true),
                            ];
                            $content_translations = [
                                'ru' => get_the_content(),
                                'en' => get_post_meta(get_the_ID(), '_product_content_en', true),
                                'ro' => get_post_meta(get_the_ID(), '_product_content_ro', true),
                            ];
                        
                            // Безопасный вывод
                            $translated_title = esc_html($title_translations[$lang] ?? $title_translations['ru']);
                            $translated_content = esc_html($content_translations[$lang] ?? $content_translations['ru']);
                            ?>

                            <h3 class="title-medium"><?= $translated_title; ?></h3>
                        
                            <div class="thumbnail">
                                <?php if (has_post_thumbnail()) the_post_thumbnail('thumbnail'); ?>
                            </div>
                        
                            <p class="body-small"><?= $translated_content; ?></p>
                        
                            <div class="product-meta">
                                <?php
                                $price = get_post_meta(get_the_ID(), 'product_price', true);
                                if ($price) {
                                    echo '<p class="product-price">Цена: ' . esc_html($price) . ' ₽</p>';
                                }
                            
                                $terms = get_the_terms(get_the_ID(), 'product_cat');
                                if ($terms && !is_wp_error($terms)) {
                                    echo '<p>Категория: ' . esc_html($terms[0]->name) . '</p>';
                                }
                            
                                $views = get_post_meta(get_the_ID(), 'product_views', true);
                                $views = $views ? (int)$views : 0;
                                echo '<p>Просмотров: ' . $views . '</p>';
                            
                                echo '<p>Опубликовано: ' . get_the_date('d.m.Y') . '</p>';
                                ?>
                            </div>
                            
                            <div class="product-actions">
                                <a href="<?php echo esc_url(add_query_arg('edit', '1', get_permalink())); ?>" class="secondary-button-small button-small">Редактировать</a>
                                <a href="?delete_product=<?php the_ID(); ?>" onclick="return confirm('Удалить товар?')" class="accent-button-small button-small">Удалить</a>
                            </div>
                        </div>
                    </li>
                <?php endwhile; ?>
            </ul>
        <?php else: ?>
            <p class="body-medium-regular no-products">У вас пока нет товаров.</p>
        <?php endif; ?>
    </div>    
</div>

<?php
wp_reset_postdata();
get_footer();
?>
