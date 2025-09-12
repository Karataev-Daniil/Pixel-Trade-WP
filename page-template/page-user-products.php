<?php
/* Template Name: Мои Товары */
if (is_user_logged_in()) {
    if (isset($_GET['delete_product'])) {
        $product_id = intval($_GET['delete_product']);
        if (current_user_can('delete_post', $product_id)) {
            wp_trash_post($product_id);
            wp_safe_redirect(remove_query_arg('delete_product'));
            exit;
        }
    }

    if (isset($_GET['toggle_hidden'])) {
        $product_id = intval($_GET['toggle_hidden']);
        if (current_user_can('edit_post', $product_id)) {
            $post_status = get_post_status($product_id);
            wp_update_post([
                'ID' => $product_id,
                'post_status' => $post_status === 'publish' ? 'draft' : 'publish'
            ]);
            wp_safe_redirect(remove_query_arg('toggle_hidden'));
            exit;
        }
    }
}

get_header();

if (!is_user_logged_in()) {
    echo '<div class="container my-products-wrapper"><p class="body-medium-regular">Пожалуйста, <a href="' . wp_login_url() . '" class="link-medium-underline">войдите</a>, чтобы просмотреть свои товары.</p></div>';
    get_footer();
    exit;
}

$current_user_id = get_current_user_id();

$filter = $_GET['filter'] ?? 'active';
$paged = max(1, get_query_var('paged', 1));

$args = [
    'post_type'      => 'products',
    'posts_per_page' => 6,
    'author'         => $current_user_id,
    'post_status'    => ($filter === 'active' ? ['publish'] : ($filter === 'hidden' ? ['draft'] : ['publish','draft','pending'])),
    'paged'          => $paged
];

$products = new WP_Query($args);

$count_all = count_user_posts($current_user_id, 'products', true);
$count_active = (new WP_Query([
    'post_type'      => 'products',
    'author'         => $current_user_id,
    'post_status'    => 'publish',
    'fields'         => 'ids',
    'posts_per_page' => -1,
]))->found_posts;

$count_hidden = (new WP_Query([
    'post_type'      => 'products',
    'author'         => $current_user_id,
    'post_status'    => 'draft',
    'fields'         => 'ids',
    'posts_per_page' => -1,
]))->found_posts;
?>
<div class="dashboard__wrapper content-main">
    <div class="container-medium">
        <div class="dashboard__header">
            <h1 class="dashboard__title display-small">
                <?php echo t('Мои товары', 'My Products', 'Produsele mele'); ?>
            </h1>
            <a href="?add_product=true" class="dashboard__add-button primary-button-medium button-medium">
                <?php echo t('Добавить товар', 'Add Product', 'Adaugă produs'); ?>
            </a>
        </div>

        <div class="dashboard__filters body-medium-regular">
            <a href="?filter=all" class="dashboard__filter <?php echo $filter==='all'?'dashboard__filter--active':''; ?>">
                <?php echo t('Все', 'All', 'Toate'); ?> (<?php echo $count_all; ?>)
            </a> |
            <a href="?filter=active" class="dashboard__filter <?php echo $filter==='active'?'dashboard__filter--active':''; ?>">
                <?php echo t('Активные', 'Active', 'Active'); ?> (<?php echo $count_active; ?>)
            </a> |
            <a href="?filter=hidden" class="dashboard__filter <?php echo $filter==='hidden'?'dashboard__filter--active':''; ?>">
                <?php echo t('Скрытые', 'Hidden', 'Ascunse'); ?> (<?php echo $count_hidden; ?>)
            </a>
        </div>

        <?php if ($products->have_posts()): ?>
            <ul class="dashboard__product-list">
                <?php while ($products->have_posts()): $products->the_post(); ?>
                    <?php 
                    $post_status = get_post_status(get_the_ID());

                    $total_views = get_product_views(get_the_ID());

                    $daily_views_data = get_product_daily_views(get_the_ID());
                    $daily_views = [];
                    foreach ($daily_views_data as $item) {
                        $daily_views[$item['view_date']] = (int)$item['views'];
                    }

                    $dates = json_encode(array_keys($daily_views));
                    $views = json_encode(array_values($daily_views));
                    ?>
                    <li class="dashboard__product-item">
                        <div class="product-card <?php echo $post_status==='draft' ? 'product-card--hidden' : ''; ?>">
                            <h3 class="product-card__title title-largest"><?php the_title(); ?></h3>

                            <div class="product-card__description body-medium-regular"><?php the_content(); ?></div>
                        
                            <div class="product-card__thumbnail">
                                <?php 
                                $thumbnail = get_the_post_thumbnail_url(get_the_ID(), 'medium');
                                if ($thumbnail) {
                                    echo '<img src="' . esc_url($thumbnail) . '" class="product-card__image" alt="' . esc_attr(get_the_title()) . '">';
                                } else {
                                    $default_img = get_template_directory_uri() . '/images/product-placeholder.png';
                                    echo '<img src="' . esc_url($default_img) . '" class="product-card__image" alt="' . esc_attr(get_the_title()) . '">';
                                }
                                ?>
                            </div>
                            
                            <div class="product-card__meta body-small-regular">
                                <div class="product-card__meta-info">

                                    <?php $price = get_post_meta(get_the_ID(), 'product_price', true); ?>
                                    <?php if ($price): ?>
                                        <div class="product-card__meta-item product-card__meta-price">
                                            <span class="product-card__meta-label"><strong><?= t('Цена', 'Price', 'Preț'); ?>:</strong></span>
                                            <span class="product-card__meta-value"><?= esc_html($price); ?> MDL</span>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <div class="product-card__meta-item product-card__meta-views">
                                        <span class="product-card__meta-label"><strong><?= t('Просмотров', 'Views', 'Vizualizări'); ?>:</strong></span>
                                        <span class="product-card__meta-value"><?= $total_views; ?></span>
                                    </div>
                                    
                                    <div class="product-card__meta-item product-card__meta-date">
                                        <span class="product-card__meta-label"><strong><?= t('Опубликовано', 'Published', 'Publicat'); ?>:</strong></span>
                                        <span class="product-card__meta-value"><?= get_the_date('d.m.Y'); ?></span>
                                    </div>
                                    
                                    <?php if ($post_status === 'draft'): ?>
                                        <div class="product-card__meta-item product-card__hidden-label">
                                            <em><?= t('Объявление скрыто', 'Hidden', 'Ascuns'); ?></em>
                                        </div>
                                    <?php endif; ?>
                                    
                                </div>
                                    
                                <div class="product-card__actions">
                                    <a href="<?= esc_url(add_query_arg('edit', '1', get_permalink())); ?>"
                                       class="product-card__action-button secondary-button-small button-small">
                                        <?= t('Редактировать', 'Edit', 'Editează'); ?>
                                    </a>
                                    
                                    <a href="?delete_product=<?= get_the_ID(); ?>"
                                       onclick="return confirm('<?= t('Удалить товар?', 'Delete this product?', 'Șterge produsul?'); ?>')"
                                       class="product-card__action-button accent-button-small button-small">
                                        <?= t('Удалить', 'Delete', 'Șterge'); ?>
                                    </a>
                                    
                                    <a href="?toggle_hidden=<?= get_the_ID(); ?>"
                                       class="product-card__action-button secondary-button-small button-small">
                                        <?= $post_status === 'draft'
                                            ? t('Показать', 'Show', 'Arată')
                                            : t('Скрыть', 'Hide', 'Ascunde'); ?>
                                    </a>
                                </div>
                            </div>

                            <div class="product-card__chart-wrapper">
                                <canvas id="viewsChart-<?php the_ID(); ?>" class="product-card__chart" style="width:100%; height:150px;"></canvas>
                            </div>
                            
                            <script>
                            document.addEventListener('DOMContentLoaded', function() {
                                var canvas = document.getElementById('viewsChart-<?php the_ID(); ?>');
                                if (!canvas) return;
                                                        
                                var rootStyles = getComputedStyle(document.documentElement);
                                                        
                                var lineColor = rootStyles.getPropertyValue('--orange_0').trim();
                                var fillColor = rootStyles.getPropertyValue('--orange_3').trim();
                                var xTitleColor = rootStyles.getPropertyValue('--gray_-6').trim();
                                var yTitleColor = rootStyles.getPropertyValue('--gray_-6').trim();
                                var xTicksColor = rootStyles.getPropertyValue('--gray_-6').trim();
                                var yTicksColor = rootStyles.getPropertyValue('--gray_-5').trim();
                                var gridColor = rootStyles.getPropertyValue('--gray_0').trim();
                                var legendColor = rootStyles.getPropertyValue('--gray_-2').trim();
                                                        
                                var ctx = canvas.getContext('2d');
                                                        
                                var viewsLabel = '<?php echo t('Просмотры', 'Views', 'Vizualizări'); ?>';
                                var xAxisLabel = '<?php echo t('Дата', 'Date', 'Data'); ?>';
                                                        
                                new Chart(ctx, {
                                    type: 'line',
                                    data: {
                                        labels: <?php echo $dates; ?>,
                                        datasets: [{
                                            label: viewsLabel,
                                            data: <?php echo $views; ?>,
                                            borderColor: lineColor,
                                            backgroundColor: fillColor,
                                            fill: true,
                                            tension: 0.3
                                        }]
                                    },
                                    options: {
                                        scales: {
                                            x: {
                                                title: { 
                                                    display: true, 
                                                    text: xAxisLabel,
                                                    color: xTitleColor,
                                                    font: { size: 10 }
                                                },
                                                ticks: { color: xTicksColor },
                                                grid: { color: gridColor }
                                            },
                                            y: {
                                                beginAtZero: true,
                                                title: { 
                                                    display: true, 
                                                    text: viewsLabel,
                                                    color: yTitleColor,
                                                    font: { size: 10 }
                                                },
                                                ticks: { color: yTicksColor },
                                                grid: { color: gridColor }
                                            }
                                        },
                                        plugins: { 
                                            legend: { 
                                                display: true,
                                                labels: { color: legendColor, font: { size: 10 } }
                                            }
                                        }
                                    }
                                });
                            });

                            </script>
                        </div>
                    </li>
                <?php endwhile; ?>
            </ul>

            <div class="dashboard__pagination body-medium-regular">
                <?php
                    echo paginate_links([
                        'total'   => $products->max_num_pages,
                        'current' => $paged,
                        'format'  => '?paged=%#%&filter=' . $filter,
                        'add_args' => false,
                        'prev_text' => '«',
                        'next_text' => '»'
                    ]);
                ?>
            </div>
        <?php else: ?>
            <p class="dashboard__no-products body-medium-regular">
                <?php echo t('У вас пока нет товаров.', 'You don’t have any products yet.', 'Nu ai încă produse.'); ?>
            </p>
        <?php endif; ?>
    </div>
</div>

<?php
wp_reset_postdata();
get_footer();
?>
