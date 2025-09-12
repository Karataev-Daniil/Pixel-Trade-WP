<?php
/* 
Template Name: Избранное
*/
get_header();

// Проверка авторизации
if (!is_user_logged_in()) : ?>
    <div class="favorites-page">
        <h1><?= t('Избранное', 'Favorites', 'Favorite'); ?></h1>
        <p>
            <?= t('Пожалуйста', 'Please', 'Vă rugăm'); ?> 
            <a href="<?php echo wp_login_url(get_permalink()); ?>">
                <?= t('войдите', 'log in', 'autentificați-vă'); ?>
            </a>, 
            <?= t('чтобы просматривать избранное.', 'to view favorites.', 'pentru a vizualiza favoritele.'); ?>
        </p>
    </div>
<?php
    get_footer();
    exit;
endif;

// Получаем избранные товары пользователя
$favorites = get_user_meta(get_current_user_id(), 'favorite_products', true);
?>

<div class="favorites-products__wrapper content-main">
    <div class="container-medium">
        <main>
            <div class="favorites-products">
                <h1 class="display-medium"><?= t('Избранное', 'Favorites', 'Favorite'); ?></h1>

                <?php if (empty($favorites)) : ?>
                    <p class="body-medium-regular">
                        <?= t('У вас пока нет избранных товаров.', 'You have no favorite products yet.', 'Nu aveți încă produse favorite.'); ?>
                    </p>
                <?php else : ?>

                    <div class="favorites-main-tabs">
                        <button class="main-tab title-largest active" data-section="ads">
                            <?= t('Объявления', 'Ads', 'Anunțuri'); ?>
                        </button>
                        <button class="main-tab title-largest" data-section="profiles">
                            <?= t('Профили', 'Profiles', 'Profiluri'); ?>
                        </button>
                    </div>

                    <section class="favorites-section" data-section="ads">
                        <?php
                        $all_posts = [];
                        $category_counts = [];

                        $query = new WP_Query([
                            'post_type' => 'products',
                            'post__in'  => $favorites,
                            'posts_per_page' => -1,
                            'orderby' => 'date',
                            'order' => 'DESC'
                        ]);

                        if ($query->have_posts()) :
                            while ($query->have_posts()) : $query->the_post();
                                $all_posts[] = get_the_ID();

                                $terms = get_the_terms(get_the_ID(), 'product_cat');
                                if ($terms && !is_wp_error($terms)) {
                                    $added = [];
                                    foreach ($terms as $term) {
                                        $parent = $term->parent ? get_term($term->parent, 'product_cat') : $term;
                                        if ($parent && !in_array($parent->term_id, $added)) {
                                            $category_counts[$parent->term_id]['name'] = $parent->name;
                                            $category_counts[$parent->term_id]['count'] = ($category_counts[$parent->term_id]['count'] ?? 0) + 1;
                                            $added[] = $parent->term_id;
                                        }
                                    }
                                }
                            endwhile;
                            wp_reset_postdata();
                        endif;
                        ?>

                        <?php if (!empty($all_posts)) : ?>
                            <div class="favorites-categories">
                                <button class="category-tag label-small active" data-category="all">
                                    <?= t('Все', 'All', 'Toate'); ?> <?= count($all_posts); ?>
                                </button>
                                <?php foreach ($category_counts as $cat_id => $data): ?>
                                    <button class="category-tag label-small" data-category="<?= $cat_id; ?>">
                                        <?= esc_html($data['name']); ?> <?= $data['count']; ?>
                                    </button>
                                <?php endforeach; ?>
                            </div>

                            <div class="favorites-sort">
                                <button class="sort-btn active" data-sort="new">
                                    <?= t('Сначала новые', 'Newest first', 'Mai întâi noi'); ?>
                                </button>
                            </div>

                            <div class="favorites-content">
                                <div class="spinner hidden">
                                    <div class="dot"></div>
                                </div>

                                <ul class="products-list-row" id="favorites-list">
                                    <?php
                                    $first_ids = array_slice($all_posts, 0, 10);
                                    $query = new WP_Query([
                                        'post_type' => 'products',
                                        'post__in'  => $first_ids,
                                        'orderby'   => 'date',
                                        'order'     => 'DESC',
                                        'posts_per_page' => 10
                                    ]);
                                    while ($query->have_posts()): $query->the_post();
                                        get_template_part('template-parts/product/card-row-large');
                                    endwhile;
                                    wp_reset_postdata();
                                    ?>
                                </ul>

                                <?php if (count($all_posts) > 10): ?>
                                    <button id="load-more" 
                                        data-offset="10" 
                                        data-ids='<?= json_encode($all_posts); ?>'>
                                        <?= t('Загрузить ещё', 'Load more', 'Încarcă mai mult'); ?> (<?= count($all_posts) - 10; ?>)
                                    </button>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </section>

                    <section class="favorites-section hidden" data-section="profiles">
                        <h2><?= t('Профили', 'Profiles', 'Profiluri'); ?></h2>
                        <p><?= t('Тут можно вывести избранные профили пользователей.', 'Here you can display favorite user profiles.', 'Aici puteți afișa profilurile utilizatorilor preferați.'); ?></p>
                    </section>

                <?php endif; ?>
            </div>
        </main>
    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", () => {
    const productList = document.getElementById("favorites-list");
    const sortBtn = document.querySelector(".favorites-sort .sort-btn");
    const spinner = document.querySelector(".spinner");
    const loadMoreBtn = document.getElementById("load-more");
    const categoryWrapper = document.querySelector(".favorites-categories");
    let allItems = Array.from(productList.querySelectorAll(".product-card-row-large"));

    function updateCategoryFilter() {
        if (!categoryWrapper) return;
        const categoryTabs = categoryWrapper.querySelectorAll(".category-tag");

        categoryTabs.forEach(tab => {
            tab.onclick = () => {
                categoryTabs.forEach(t => t.classList.remove("active"));
                tab.classList.add("active");
                let selectedCat = tab.dataset.category.toString();

                let items = Array.from(document.querySelectorAll(".product-card-row-large"));
                items.forEach(item => {
                    let cats = (item.dataset.categories || '').split(",").map(c => c.trim());
                    item.style.display = (selectedCat === "all" || cats.includes(selectedCat)) ? "" : "none";
                });
            };
        });
    }

    updateCategoryFilter();

    if (sortBtn) {
        sortBtn.addEventListener("click", () => {
            let items = Array.from(productList.querySelectorAll(".product-card-row-large"));
            let sort = sortBtn.dataset.sort;

            items.sort((a, b) => {
                let da = new Date(a.dataset.date);
                let db = new Date(b.dataset.date);
                return sort === "new" ? db - da : da - db;
            });

            items.forEach(i => productList.appendChild(i));

            if (sort === "new") {
                sortBtn.dataset.sort = "old";
                sortBtn.textContent = "<?= t('Сначала старые', 'Oldest first', 'Mai întâi vechi'); ?>";
            } else {
                sortBtn.dataset.sort = "new";
                sortBtn.textContent = "<?= t('Сначала новые', 'Newest first', 'Mai întâi noi'); ?>";
            }
        });
    }

    if (loadMoreBtn) {
        loadMoreBtn.addEventListener("click", () => {
            let offset = parseInt(loadMoreBtn.dataset.offset);
            let ids = JSON.parse(loadMoreBtn.dataset.ids);
            let nextIds = ids.slice(offset, offset + 10);

            if (!nextIds.length) {
                loadMoreBtn.remove();
                return;
            }

            spinner.classList.remove("hidden");

            fetch("<?php echo admin_url('admin-ajax.php'); ?>", {
                method: "POST",
                headers: {"Content-Type": "application/x-www-form-urlencoded"},
                body: new URLSearchParams({
                    action: "load_more_favorites",
                    ids: nextIds
                })
            })
            .then(res => res.text())
            .then(html => {
                productList.insertAdjacentHTML("beforeend", html);
                offset += nextIds.length;
                loadMoreBtn.dataset.offset = offset;

                updateCategoryFilter();

                let remaining = ids.length - offset;
                if (remaining > 0) {
                    loadMoreBtn.textContent = "<?= t('Загрузить ещё', 'Load more', 'Încarcă mai mult'); ?> (" + remaining + ")";
                } else {
                    loadMoreBtn.remove();
                }
            })
            .finally(() => spinner.classList.add("hidden"));
        });
    }

    const mainTabs = document.querySelectorAll(".favorites-main-tabs .main-tab");
    const sections = document.querySelectorAll(".favorites-section");

    mainTabs.forEach(tab => {
        tab.addEventListener("click", () => {
            mainTabs.forEach(t => t.classList.remove("active"));
            tab.classList.add("active");

            let section = tab.dataset.section;
            sections.forEach(s => {
                s.classList.toggle("hidden", s.dataset.section !== section);
            });
        });
    });
});
</script>

<?php get_footer(); ?>
