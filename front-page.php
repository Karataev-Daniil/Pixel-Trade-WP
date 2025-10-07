<?php
/* Template Name: Home Page */
get_header(); 
?>

<div class="main__wrapper content-main">
    <div class="container-medium">
        <main>
            <?php
            $featured_categories = [668, 804, 853, 1015, 909, 976, 1036, 1064, 730];
            if (!empty($featured_categories)):
            ?>
            <section class="featured-categories">
                <div class="categories-grid">
                    <?php foreach ($featured_categories as $cat_id):
                        $term = get_term($cat_id, 'product_cat');
                        if (!$term) continue;
                    
                        $color_id = get_term_meta($cat_id, 'category_image_color', true);

                        $color_url = $color_id ? wp_get_attachment_url($color_id) : '';

                        $name = t(
                            $term->name,
                            get_term_meta($cat_id, 'translation_en', true),
                            get_term_meta($cat_id, 'translation_ro', true)
                        );
                    
                        $link = get_term_link($term);
                    ?>
                    <a href="<?= esc_url($link); ?>" 
                       class="category-card category-<?= esc_attr($term->slug); ?>">
                        <div class="category-card__image">
                            <?php if ($color_url): ?>
                                <img class="category-card__img color" 
                                     src="<?= esc_url($color_url); ?>" 
                                     alt="<?= esc_attr($term->name); ?>">
                            <?php endif; ?>
                        </div>
                        <span class="title-smaller"><?= esc_html($name); ?></span>
                    </a>
                    <?php endforeach; ?>
                </div>
            </section>
            <?php endif; ?>


            <div class="content-columns">
                <div class="product-grid" style="flex:1;">
                    <section class="recommended-products">
                        <h2 class="display-small">
                            <?= t('Рекомендации для вас', 'Recommendations for you', 'Recomandări pentru tine'); ?>
                        </h2>
                        <div class="products-list" id="recommended-products">
                            <?php
                            $per_page = isset($_POST['per_page']) ? intval($_POST['per_page']) : (wp_is_mobile() ? 12 : 36);

                            if (function_exists('get_recommended_products_for_user')) {
                                $query = get_recommended_products_for_user($per_page);
                            } else {
                                $query = new WP_Query([
                                    'post_type'      => 'products',
                                    'posts_per_page' => $per_page,
                                    'orderby'        => 'date',
                                    'order'          => 'DESC',
                                ]);
                                $query->found_posts = $query->found_posts ?? $query->post_count;
                            }

                            $query->found_posts = $query->found_posts ?? count($query->posts);
                        
                            if ($query->have_posts()):
                                while ($query->have_posts()): $query->the_post();
                                    get_template_part('template-parts/product/card'); 
                                endwhile;
                                wp_reset_postdata();
                            else:
                                echo '<p>'.t('Товары не найдены', 'Products not found', 'Produse nu au fost găsite').'</p>';
                            endif;
                            ?>
                        </div>
                        
                        <?php if ($query->found_posts > $per_page): ?>
                            <button id="load-more-products" 
                                    data-offset="<?= $per_page ?>" 
                                    data-per-page="<?= $per_page ?>">
                                <?= t('Загрузить ещё', 'Load more', 'Încarcă mai mult'); ?>
                            </button>
                        <?php endif; ?>
                    </section>
                        
                    <script>
                    jQuery(document).ready(function($){
                        $('#load-more-products').on('click', function(){
                            var btn = $(this);
                            var offset = btn.data('offset');
                            var perPage = btn.data('per-page');
                        
                            $.post('<?php echo admin_url("admin-ajax.php"); ?>', {
                                action: 'load_more_products',
                                offset: offset,
                                per_page: perPage
                            }, function(response){
                                if(response.trim() === '') {
                                    btn.hide();
                                } else {
                                    $('#recommended-products').append(response);
                                    btn.data('offset', offset + perPage);
                                }
                            });
                        });
                    });
                    </script>
                </div>

                <?php if ( ! wp_is_mobile() ) : ?>
                <aside class="sidebar">
                    <section class="sidebar-block">
                        <h3 class="title-medium"><?= t('Последние избранные', 'Latest Favorites', 'Favorite recente'); ?></h3>
                        <?php
                        if (is_user_logged_in()) {
                            $user_id = get_current_user_id();
                            $favorites = function_exists('favorites_get') ? array_slice(favorites_get($user_id, 'product'), 0, 5) : [];
                        
                            if ($favorites):
                                $query = new WP_Query([
                                    'post_type' => 'products',
                                    'post__in'  => $favorites,
                                    'orderby'   => 'post__in',
                                    'posts_per_page' => 5,
                                ]);
                            
                                if ($query->have_posts()): ?>
                                    <ul class="products-list-row">
                                        <?php while ($query->have_posts()): $query->the_post();
                                            get_template_part('template-parts/product/card-row'); 
                                        endwhile; ?>
                                    </ul>
                                <?php wp_reset_postdata(); endif;
                            endif;
                        }
                        ?>

                        <div style="margin-top: 12px; text-align: center;">
                            <a href="/user/favorites" class="secondary-button-small">
                                <?= t('Перейти в избранное', 'Go to Favorites', 'Mergi la Favorite'); ?>
                            </a>
                        </div>
                    </section>
                    
                    <section class="sidebar-block">
                        <h3 class="title-medium"><?= t('Новые объявления', 'New Listings', 'Anunțuri noi'); ?></h3>
                        <?php
                        $query = new WP_Query([
                            'post_type' => 'products',
                            'posts_per_page' => 5,
                            'orderby' => 'date',
                            'order' => 'DESC',
                        ]);
                        if ($query->have_posts()): ?>
                            <ul class="products-list-row">
                                <?php while ($query->have_posts()): $query->the_post();
                                    get_template_part('template-parts/product/card-row'); 
                                endwhile; ?>
                            </ul>
                        <?php wp_reset_postdata(); endif; ?>
                    </section>
                            
                    <section class="sidebar-block">
                        <h3 class="title-medium"><?= t('Топ-продажи', 'Top Sales', 'Cele mai vândute'); ?></h3>
                        <?php
                        global $wpdb;
                            
                        $top_products = $wpdb->get_results("
                            SELECT product_id, SUM(views) as total_views
                            FROM {$wpdb->prefix}product_daily_views
                            GROUP BY product_id
                            ORDER BY total_views DESC
                            LIMIT 5
                        ");
                            
                        if (!empty($top_products)): ?>
                            <ul class="products-list-row">
                                <?php 
                                foreach ($top_products as $item):
                                    $post = get_post($item->product_id);
                                    if (!$post) continue;
                                    setup_postdata($post);
                                    get_template_part('template-parts/product/card-row'); 
                                endforeach; 
                                wp_reset_postdata(); 
                                ?>
                            </ul>
                        <?php endif; ?>
                    </section>
                            
                    <section class="info-blocks">
                        <div class="info-block">
                            <h3><?= t('Преимущества сайта', 'Site Benefits', 'Beneficiile site-ului'); ?></h3>
                            <ul>
                                <li><?= t('Гарантия качества', 'Quality Guarantee', 'Garanție de calitate'); ?></li>
                                <li><?= t('Поддержка 24/7', '24/7 Support', 'Suport 24/7'); ?></li>
                                <li><?= t('Уникальные преимущества', 'Unique Benefits', 'Beneficii unice'); ?></li>
                            </ul>
                        </div>
                            
                        <div class="info-block">
                            <h3><?= t('Реклама / Акции', 'Ads / Promotions', 'Publicitate / Promoții'); ?></h3>
                            <p><?= t('Здесь можно разместить баннеры или акции', 'Place banners or promotions here', 'Aici puteți plasa bannere sau promoții'); ?></p>
                        </div>
                            
                        <div class="info-block">
                            <h3><?= t('О компании', 'About Company', 'Despre companie'); ?></h3>
                            <p><?= t('Краткая информация о компании', 'Short info about the company', 'Informații scurte despre companie'); ?></p>
                        </div>
                    </section>
                </aside>
                <?php endif; ?>
            </div>
        </main>
    </div>
</div>
<div id="cpb-root"></div>
<?php
get_footer();
?>