<?php
/* 
Template Name: Избранное
*/
get_header();

$current_lang = get_current_lang();

if (!is_user_logged_in()) : ?>
    <div class="favorites-products__wrapper content-main">
        <div class="container-medium">
            <main>
                <h1 class="display-medium"><?= t('Избранное', 'Favorites', 'Favorite'); ?></h1>
                <p class="body-small-regular">
                    <?= t('Пожалуйста', 'Please', 'Vă rugăm'); ?> 
                    <a class="link-button" href="<?= esc_url(home_url("/$current_lang/user/login/")); ?>">
                        <?= t('войдите', 'log in', 'autentificați-vă'); ?>
                    </a>, 
                    <?= t('чтобы просматривать избранное.', 'to view favorites.', 'pentru a vizualiza favoritele.'); ?>
                </p>
            </main>
        </div>
    </div>
<?php
    get_footer();
    exit;
endif;

$favorites_products = favorites_get(get_current_user_id(), 'product');
$favorites_profiles = favorites_get(get_current_user_id(), 'profile');

$paged = max(1, get_query_var('paged', 1));
$posts_per_page = 10;
?>

<div class="favorites-products__wrapper content-main">
    <div class="container-medium">
        <main>
            <div class="favorites-products">
                <h1 class="display-medium"><?= t('Избранное', 'Favorites', 'Favorite'); ?></h1>

                <?php if (empty($favorites_products) && empty($favorites_profiles)) : ?>
                    <p class="body-medium-regular">
                        <?= t('У вас пока нет избранных товаров или профилей.', 'You have no favorite products or profiles yet.', 'Nu aveți încă produse sau profiluri favorite.'); ?>
                    </p>
                <?php else : ?>

                    <div class="favorites-main-tabs">
                        <?php if (!empty($favorites_products)) : ?>
                            <button class="main-tab title-largest active" data-section="ads">
                                <?= t('Объявления', 'Ads', 'Anunțuri'); ?>
                            </button>
                        <?php endif; ?>
                        <?php if (!empty($favorites_profiles)) : ?>
                            <button class="main-tab title-largest <?= empty($favorites_products) ? 'active' : ''; ?>" data-section="profiles">
                                <?= t('Профили', 'Profiles', 'Profiluri'); ?>
                            </button>
                        <?php endif; ?>
                    </div>

                    <?php if (!empty($favorites_products)) : ?>
                    <section class="favorites-section <?= empty($favorites_products) ? 'hidden' : ''; ?>" data-section="ads">
                        <?php
                        $category_counts = [];
                        $all_posts_for_count = new WP_Query([
                            'post_type' => 'products',
                            'post__in' => $favorites_products,
                            'posts_per_page' => -1
                        ]);
                        if ($all_posts_for_count->have_posts()) :
                            while ($all_posts_for_count->have_posts()) : $all_posts_for_count->the_post();
                                $terms = get_the_terms(get_the_ID(), 'product_cat');
                                if ($terms && !is_wp_error($terms)) {
                                    foreach ($terms as $term) {
                                        if ($term->parent == 0) {
                                            $category_counts[$term->term_id]['name']  = $term->name;
                                            $category_counts[$term->term_id]['count'] = ($category_counts[$term->term_id]['count'] ?? 0) + 1;
                                        }
                                    }
                                }
                            endwhile;
                            wp_reset_postdata();
                        endif;
                        ?>

                        <?php if (!empty($category_counts)) : ?>
                            <div class="favorites-categories">
                                <a href="<?= esc_url(add_query_arg('cat', 'all', home_url("/$current_lang/user/favorites/"))); ?>" 
                                   class="category-tag label-small <?= (!isset($_GET['cat']) || $_GET['cat'] === 'all') ? 'active' : ''; ?>"
                                   data-category="all">
                                    <?= t('Все', 'All', 'Toate'); ?> <?= count($favorites_products); ?>
                                </a>
                                <?php foreach ($category_counts as $cat_id => $data): ?>
                                    <a href="<?= esc_url(add_query_arg('cat', $cat_id, home_url("/$current_lang/user/favorites/"))); ?>" 
                                       class="category-tag label-small <?= (isset($_GET['cat']) && $_GET['cat'] == $cat_id) ? 'active' : ''; ?>"
                                       data-category="<?= $cat_id; ?>">
                                        <?= esc_html(
                                            t(
                                                $data['name'], 
                                                get_term_meta($cat_id, 'translation_en', true), 
                                                get_term_meta($cat_id, 'translation_ro', true)
                                            )
                                        ); ?> <?= $data['count']; ?>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>

                        <?php 
                        $current_order = isset($_GET['order']) ? $_GET['order'] : 'desc';
                        $is_new_first = $current_order === 'desc';
                        ?>
                        <div class="favorites-sort">
                            <button 
                                class="sort-btn label-small <?= $is_new_first ? 'active' : ''; ?>" 
                                data-sort="<?= $is_new_first ? 'new' : 'old'; ?>" 
                                aria-label="<?= t('Сортировка товаров', 'Sort products', 'Sortare produse'); ?>"
                            >
                                <svg aria-hidden="true" class="sort-icon" width="24" height="24" viewBox="0 0 24 24">
                                  <path class="sort-arrow-up" d="M12 2L4 10h16L12 2z" fill="currentColor"/>
                                  <path class="sort-arrow-down" d="M12 22l8-8H4l8 8z" fill="currentColor"/>
                                </svg>
                                <span class="sort-label">
                                    <?= $is_new_first ? t('Сначала новые', 'Newest first', 'Mai întâi noi') : t('Сначала старые', 'Oldest first', 'Mai întâi vechi'); ?>
                                </span>
                            </button>
                        </div>

                        <!-- PRODUCTS LIST -->
                        <div class="favorites-content">
                            <ul class="products-list-row" id="favorites-list">
                                <?php
                                $tax_query = [];
                                if (isset($_GET['cat']) && $_GET['cat'] !== 'all') {
                                    $tax_query = [
                                        [
                                            'taxonomy' => 'product_cat',
                                            'field'    => 'term_id',
                                            'terms'    => intval($_GET['cat']),
                                        ],
                                    ];
                                }
                            
                                $order = (isset($_GET['order']) && $_GET['order'] === 'asc') ? 'ASC' : 'DESC';
                            
                                $query = new WP_Query([
                                    'post_type'      => 'products',
                                    'post__in'       => $favorites_products,
                                    'orderby'        => 'date',
                                    'order'          => $order,
                                    'posts_per_page' => $posts_per_page,
                                    'paged'          => $paged,
                                    'tax_query'      => $tax_query
                                ]);

                                if ($query->have_posts()) :
                                    while ($query->have_posts()) : $query->the_post();
                                        get_template_part('template-parts/product/card-row-large');
                                    endwhile;
                                    wp_reset_postdata();
                                else :
                                    echo '<p>' . t('Нет товаров на этой странице.', 'No products on this page.', 'Nu există produse pe această pagină.') . '</p>';
                                endif;
                                ?>
                            </ul>
                            
                            <div class="favorites-pagination">
                                <?php
                                $base = user_trailingslashit(
                                    home_url("/$current_lang/user/favorites/%_%/"),
                                    'paged'
                                );
                            
                                $pagination_links = paginate_links([
                                    'base'      => add_query_arg('paged', '%#%', $base),
                                    'format'    => '',
                                    'total'     => $query->max_num_pages,
                                    'current'   => $paged,
                                    'mid_size'  => 1,
                                    'prev_text' => '«',
                                    'next_text' => '»',
                                    'type'      => 'array',
                                    'add_args'  => isset($_GET['cat']) ? ['cat' => $_GET['cat']] : [],
                                ]);
                            
                                $has_prev = $paged > 1;
                                $has_next = $paged < $query->max_num_pages;
                            
                                echo '<ul class="pagination">';
                            
                                if ($has_prev) {
                                    echo '<li class="item button-small">' . get_previous_posts_link('«') . '</li>';
                                } else {
                                    echo '<li class="item button-small disabled"><span>«</span></li>';
                                }
                            
                                if ($pagination_links) {
                                    foreach ($pagination_links as $link) {
                                        if (strpos($link, '«') !== false || strpos($link, '»') !== false) continue;
                                        echo '<li class="item button-small">' . $link . '</li>';
                                    }
                                }
                            
                                if ($has_next) {
                                    echo '<li class="item button-small">' . get_next_posts_link('»', $query->max_num_pages) . '</li>';
                                } else {
                                    echo '<li class="item button-small disabled"><span>»</span></li>';
                                }
                            
                                echo '</ul>';
                                ?>
                            </div>
                        </div>
                    </section>
                    <?php endif; ?>

                    <?php if (!empty($favorites_profiles)) : ?>
                    <section class="favorites-section <?= !empty($favorites_products) ? 'hidden' : ''; ?>" data-section="profiles">
                        <h2><?= t('Профили', 'Profiles', 'Profiluri'); ?></h2>
                        <ul class="profiles-list">
                            <?php foreach ($favorites_profiles as $profile_id): ?>
                                <?php 
                                $user = get_userdata($profile_id);
                                if ($user): ?>
                                    <li class="profile-card">
                                        <a href="<?= esc_url(get_author_posts_url($user->ID)); ?>">
                                            <?= esc_html($user->display_name); ?>
                                        </a>
                                    </li>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </ul>
                    </section>
                    <?php endif; ?>

                <?php endif; ?>
            </div>
        </main>
    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", () => {
    const categoryTabs = document.querySelectorAll(".favorites-categories .category-tag");
    const mainTabs = document.querySelectorAll(".favorites-main-tabs .main-tab");
    const sections = document.querySelectorAll(".favorites-section");

    categoryTabs.forEach(tab => {
        tab.onclick = () => {
            categoryTabs.forEach(t => t.classList.remove("active"));
            tab.classList.add("active");

            const selectedCat = tab.dataset.category;
            const items = Array.from(document.querySelectorAll(".product-card-row-large"));

            items.forEach(item => {
                const cats = (item.dataset.categories || '').split(",").map(c => c.trim());
                if (selectedCat === "all" || cats.includes(selectedCat)) {
                    item.classList.remove("hidden");
                } else {
                    item.classList.add("hidden");
                }
            });
        };
    });

    mainTabs.forEach(tab => {
        tab.addEventListener("click", () => {
            mainTabs.forEach(t => t.classList.remove("active"));
            tab.classList.add("active");

            const section = tab.dataset.section;
            sections.forEach(s => s.classList.toggle("hidden", s.dataset.section !== section));
        });
    });

    const sortBtn = document.querySelector(".sort-btn");
    if (sortBtn) {
        sortBtn.addEventListener("click", () => {
            const url = new URL(window.location.href);
            const currentOrder = url.searchParams.get("order") || "desc";
            const newOrder = currentOrder === "desc" ? "asc" : "desc";
            url.searchParams.set("order", newOrder);
            url.searchParams.set("paged", 1);
            window.location.href = url.toString();
        });
    }
});
</script>

<?php get_footer(); ?>
