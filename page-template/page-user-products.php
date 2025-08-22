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

if (isset($_GET['toggle_hidden'])) {
    $product_id = intval($_GET['toggle_hidden']);
    if (current_user_can('edit_post', $product_id)) {
        $post_status = get_post_status($product_id);
        if ($post_status === 'publish') {
            wp_update_post(['ID' => $product_id, 'post_status' => 'draft']);
        } elseif ($post_status === 'draft') {
            wp_update_post(['ID' => $product_id, 'post_status' => 'publish']);
        }
        wp_redirect(remove_query_arg(['toggle_hidden']));
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

$count_all    = count_user_posts($current_user_id, 'product', true); // все (publish + draft)
$count_active = (new WP_Query([
    'post_type'      => 'product',
    'author'         => $current_user_id,
    'post_status'    => 'publish',
    'fields'         => 'ids',
    'posts_per_page' => -1,
]))->found_posts;

$count_hidden = (new WP_Query([
    'post_type'      => 'product',
    'author'         => $current_user_id,
    'post_status'    => 'draft',
    'fields'         => 'ids',
    'posts_per_page' => -1,
]))->found_posts;

$filter = $_GET['filter'] ?? 'all';

$args = [
    'post_type'      => 'product',
    'posts_per_page' => -1,
    'author'         => $current_user_id,
    'post_status'    => ($filter === 'active' ? ['publish'] : ($filter === 'hidden' ? ['draft'] : ['publish','draft','pending'])),
];
$products = new WP_Query($args);
?>
<div class="dashboard__wrapper">
    <div class="container-medium">
        <div class="dashboard-header">
            <h2 class="display-small"><?php echo t('Мои товары', 'My Products', 'Produsele mele'); ?></h2>
            <a href="?add_product=true" class="primary-button-medium button-medium">
                <?php echo t('Добавить товар', 'Add Product', 'Adaugă produs'); ?>
            </a>
        </div>

        <div class="product-filters body-medium-regular" style="margin: 20px 0;">
            <a href="?filter=all" class="<?php echo $filter==='all'?'active-filter':''; ?>">
                <?php echo t('Все', 'All', 'Toate'); ?> (<?php echo $count_all; ?>)
            </a> |
            <a href="?filter=active" class="<?php echo $filter==='active'?'active-filter':''; ?>">
                <?php echo t('Активные', 'Active', 'Active'); ?> (<?php echo $count_active; ?>)
            </a> |
            <a href="?filter=hidden" class="<?php echo $filter==='hidden'?'active-filter':''; ?>">
                <?php echo t('Скрытые', 'Hidden', 'Ascunse'); ?> (<?php echo $count_hidden; ?>)
            </a>
        </div>

        <?php if ($products->have_posts()): ?>
            <ul class="product-list">
                <?php while ($products->have_posts()): $products->the_post(); ?>
                    <?php 
                    $post_status = get_post_status(get_the_ID());
                    $daily_views = get_post_meta(get_the_ID(), '_product_views_daily', true);
                    if (!is_array($daily_views)) $daily_views = [];
                    $dates = json_encode(array_keys($daily_views));
                    $views = json_encode(array_values($daily_views));
                    ?>
                    <li class="product-item">
                        <div class="product-card <?php echo $post_status==='draft' ? 'product-hidden' : ''; ?>">
                            <h3 class="title-large"><?php the_title(); ?></h3>
                        
                            <div class="thumbnail">
                                <?php if (has_post_thumbnail()) {
                                    the_post_thumbnail('medium');
                                } else {
                                    echo '<div class="no-thumbnail body-small-regular">Нет изображения</div>';
                                } ?>
                            </div>
                            
                            <p class="body-small-regular"><?php the_content(); ?></p>
                            
                            <div class="product-meta body-small-regular">
                                <?php
                                $price = get_post_meta(get_the_ID(), 'product_price', true);
                                if ($price) {
                                    echo '<p><strong>' . t('Цена', 'Price', 'Preț') . ':</strong> ' . esc_html($price) . ' ₽</p>';
                                }
                            
                                $total_views = (int) get_post_meta(get_the_ID(), 'product_views', true);
                                echo '<p><strong>' . t('Просмотров', 'Views', 'Vizualizări') . ':</strong> ' . $total_views . '</p>';
                            
                                echo '<p><strong>' . t('Опубликовано', 'Published', 'Publicat') . ':</strong> ' . get_the_date('d.m.Y') . '</p>';
                            
                                if ($post_status === 'draft') {
                                    echo '<p style="color:red;"><em>' . t('Объявление скрыто', 'Hidden', 'Ascuns') . '</em></p>';
                                }
                                ?>
                            </div>
                            
                        
                            <canvas id="viewsChart-<?php the_ID(); ?>" style="width:100%; height:100px;"></canvas>
                            <script>
                            document.addEventListener('DOMContentLoaded', function() {
                                var canvas = document.getElementById('viewsChart-<?php the_ID(); ?>');
                                if (!canvas) return;

                                var rootStyles = getComputedStyle(document.documentElement);
                                var borderColor = rootStyles.getPropertyValue('--orange_0').trim();
                                var backgroundColor = rootStyles.getPropertyValue('--orange_1').trim();

                                var ctx = canvas.getContext('2d');
                                new Chart(ctx, {
                                    type: 'line',
                                    data: {
                                        labels: <?php echo $dates; ?>,
                                        datasets: [{
                                            label: 'Просмотры',
                                            data: <?php echo $views; ?>,
                                            borderColor: borderColor || 'orange',
                                            backgroundColor: backgroundColor || 'rgba(255,165,0,0.2)',
                                            fill: true,
                                            tension: 0.3
                                        }]
                                    },
                                    options: {
                                        scales: {
                                            x: { title: { display: true, text: 'Дата' } },
                                            y: { beginAtZero: true, title: { display: true, text: 'Просмотры' } }
                                        },
                                        plugins: { legend: { display: false } }
                                    }
                                });
                            });

                            </script>

                
                            <div class="product-actions">
                                <a href="<?php echo esc_url(add_query_arg('edit', '1', get_permalink())); ?>" class="secondary-button-small button-small">
                                    <?php echo t('Редактировать', 'Edit', 'Editează'); ?>
                                </a>
                                <a href="?delete_product=<?php the_ID(); ?>" onclick="return confirm('<?php echo t('Удалить товар?', 'Delete this product?', 'Șterge produsul?'); ?>')" class="accent-button-small button-small">
                                    <?php echo t('Удалить', 'Delete', 'Șterge'); ?>
                                </a>
                                <a href="?toggle_hidden=<?php the_ID(); ?>" class="secondary-button-small button-small">
                                    <?php echo $post_status==='draft' ? t('Показать', 'Show', 'Arată') : t('Скрыть', 'Hide', 'Ascunde'); ?>
                                </a>
                            </div>
                        </div>
                    </li>
                <?php endwhile; ?>
            </ul>
        <?php else: ?>
            <p class="body-medium-regular no-products">
                <?php echo t('У вас пока нет товаров.', 'You don’t have any products yet.', 'Nu ai încă produse.'); ?>
            </p>
        <?php endif; ?>
    </div>
</div>

<?php
wp_reset_postdata();
get_footer();
?>
