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

// Получаем избранные товары и профили пользователя из новой таблицы
$favorites_products = favorites_get(get_current_user_id(), 'product');
$favorites_profiles = favorites_get(get_current_user_id(), 'profile');
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
                        $all_posts = [];
                        $category_counts = [];

                        $query = new WP_Query([
                            'post_type' => 'products',
                            'post__in'  => $favorites_products,
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
                                <button class="sort-btn label-small active" data-sort="new" aria-label="<?= t('Сортировка товаров', 'Sort products', 'Sortare produse'); ?>">
                                    <svg aria-hidden="true" class="sort-icon" width="24" height="24" viewBox="0 0 24 24">
                                      <path class="sort-arrow-up" d="M12 2L4 10h16L12 2z" fill="currentColor"/>
                                      <path class="sort-arrow-down" d="M12 22l8-8H4l8 8z" fill="currentColor"/>
                                    </svg>

                                    <span class="sort-label"><?= t('Сначала новые', 'Newest first', 'Mai întâi noi'); ?></span>
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
                                        class="primary-button-small button-small"
                                        data-offset="10" 
                                        data-ids='<?= json_encode($all_posts); ?>'>
                                        <?= t('Загрузить ещё', 'Load more', 'Încarcă mai mult'); ?> <?= count($all_posts) - 10; ?>
                                    </button>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
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
    const productList = document.getElementById("favorites-list");
    const sortBtn = document.querySelector(".favorites-sort .sort-btn");
    const categoryWrapper = document.querySelector(".favorites-categories");
    const mainTabs = document.querySelectorAll(".favorites-main-tabs .main-tab");
    const sections = document.querySelectorAll(".favorites-section");
    const loadMoreBtn = document.getElementById("load-more");
    const spinner = document.querySelector(".spinner");

    function updateCategoryFilter() {
        if (!categoryWrapper) return;
        const categoryTabs = categoryWrapper.querySelectorAll(".category-tag");

        categoryTabs.forEach(tab => {
            tab.onclick = () => {
                categoryTabs.forEach(t => t.classList.remove("active"));
                tab.classList.add("active");

                const selectedCat = tab.dataset.category.toString();
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
    }

    updateCategoryFilter();

    if (sortBtn) {
        const arrowUp = sortBtn.querySelector(".sort-arrow-up");
        const arrowDown = sortBtn.querySelector(".sort-arrow-down");

        sortBtn.addEventListener("click", () => {
            const items = Array.from(productList.querySelectorAll(".product-card-row-large"));
            const sort = sortBtn.dataset.sort;

            items.sort((a, b) => {
                const da = new Date(a.dataset.date);
                const db = new Date(b.dataset.date);
                return sort === "new" ? db - da : da - db;
            });

            items.forEach(item => {
                item.style.opacity = 0;
                setTimeout(() => productList.appendChild(item), 200);
                setTimeout(() => item.style.opacity = 1, 220);
            });

            const sortLabel = sortBtn.querySelector(".sort-label");

            if (sort === "new") {
                arrowUp.classList.add("active");
                arrowDown.classList.remove("active");
                sortBtn.dataset.sort = "old";
                sortLabel.textContent = "<?= t('Сначала старые', 'Oldest first', 'Mai întâi vechi'); ?>";
            } else {
                arrowUp.classList.remove("active");
                arrowDown.classList.add("active");
                sortBtn.dataset.sort = "new";
                sortLabel.textContent = "<?= t('Сначала новые', 'Newest first', 'Mai întâi noi'); ?>";
            }
        });
    }

    mainTabs.forEach(tab => {
        tab.addEventListener("click", () => {
            mainTabs.forEach(t => t.classList.remove("active"));
            tab.classList.add("active");

            const section = tab.dataset.section;
            sections.forEach(s => s.classList.toggle("hidden", s.dataset.section !== section));
        });
    });

    if (loadMoreBtn) {
        loadMoreBtn.addEventListener("click", () => {
            const offset = parseInt(loadMoreBtn.dataset.offset);
            const ids = JSON.parse(loadMoreBtn.dataset.ids);
            const nextIds = ids.slice(offset, offset + 10);
            if (!nextIds.length) return loadMoreBtn.remove();

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
                loadMoreBtn.dataset.offset = offset + nextIds.length;
                updateCategoryFilter();

                const remaining = ids.length - (offset + nextIds.length);
                if (remaining > 0) {
                    loadMoreBtn.textContent = "<?= t('Загрузить ещё', 'Load more', 'Încarcă mai mult'); ?> (" + remaining + ")";
                } else {
                    loadMoreBtn.remove();
                }
            })
            .finally(() => spinner.classList.add("hidden"));
        });
    }
});
</script>

<?php get_footer(); ?>
