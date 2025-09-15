<?php
/* Template Name: Мои Товары */

if (!is_user_logged_in()) {
    get_header();
    echo '<div class="container my-products-wrapper">
            <p class="body-medium-regular">
                Пожалуйста, <a href="' . wp_login_url() . '" class="link-medium-underline">войдите</a>, чтобы просмотреть свои товары.
            </p>
          </div>';
    get_footer();
    exit;
}

get_header();

$current_user_id = get_current_user_id();
$filter = $_GET['filter'] ?? 'all';
$category = intval($_GET['category'] ?? 0);
$search   = sanitize_text_field($_GET['s'] ?? '');
$paged    = max(1, get_query_var('paged', 1));

$status_map = [
    'all'        => ['publish','draft','pending'],
    'active'     => ['publish'],
    'hidden'     => ['draft'],
    'inactive'   => ['expired'],
    'blocked'    => ['blocked'],
    'draft'      => ['draft']
];

// WP_Query для товаров пользователя
$args = [
    'post_type'      => 'products',
    'posts_per_page' => 10,
    'author'         => $current_user_id,
    'post_status'    => $status_map[$filter] ?? ['publish','draft','pending'],
    'paged'          => $paged,
];

if ($search) {
    $args['s'] = $search; // поиск по названию
}

if ($category) {
    $args['tax_query'] = [[
        'taxonomy' => 'product_cat',
        'field'    => 'term_id',
        'terms'    => $category,
        'include_children' => true
    ]];
}

$products = new WP_Query($args);

// Подсчет товаров по статусам
$count_map = [];
foreach ($status_map as $key => $statuses) {
    $count_map[$key] = (new WP_Query([
        'post_type' => 'products',
        'author' => $current_user_id,
        'post_status' => $statuses,
        'fields' => 'ids',
        'posts_per_page' => -1
    ]))->found_posts;
}

// Функция для сокращенного пути категории
function get_term_path_short($post_id, $taxonomy = 'product_cat', $separator = ' &raquo; ') {
    $terms = wp_get_post_terms($post_id, $taxonomy);
    if (is_wp_error($terms) || empty($terms)) return '-';

    $main_term = null;
    $max_depth = -1;

    foreach ($terms as $term) {
        $ancestors = get_ancestors($term->term_id, $taxonomy);
        if (count($ancestors) > $max_depth) {
            $max_depth = count($ancestors);
            $main_term = $term;
            $main_ancestors = array_reverse($ancestors);
        }
    }

    if (!$main_term) return '-';

    $path = [];
    if (!empty($main_ancestors)) {
        $root = get_term($main_ancestors[0], $taxonomy);
        if ($root && !is_wp_error($root)) {
            $path[] = $root->name;
        }
    }

    $path[] = $main_term->name;
    return implode($separator, $path);
}

// Вывод категорий с количеством товаров
function display_categories_with_count($parent = 0, $level = 0, $current = 0, $user_id = 0) {
    $terms = get_terms([
        'taxonomy' => 'product_cat',
        'hide_empty' => false,
        'parent' => $parent
    ]);

    foreach ($terms as $term) {
        $count = new WP_Query([
            'post_type' => 'products',
            'author' => $user_id,
            'post_status' => ['publish','draft','pending'],
            'tax_query' => [[
                'taxonomy' => 'product_cat',
                'field' => 'term_id',
                'terms' => $term->term_id,
                'include_children' => false
            ]],
            'fields' => 'ids',
            'posts_per_page' => -1
        ]);
        $count = $count->found_posts;

        if ($count == 0) continue;

        echo '<option value="' . $term->term_id . '" ' . selected($current, $term->term_id, false) . '>' 
             . str_repeat('— ', $level) . $term->name . ' (' . $count . ')</option>';

        display_categories_with_count($term->term_id, $level + 1, $current, $user_id);
    }
}
?>

<div class="container-medium my-products-wrapper">
    <main>
        <h1 class="display-small"><?= t('Мои товары', 'My Products', 'Produsele mele'); ?></h1>

        <!-- Фильтр по статусу -->
        <div class="products-filters body-medium-regular">
            <?php foreach ($count_map as $key => $count): ?>
                <a href="?filter=<?= $key ?>" class="link-medium-underline <?= $filter === $key ? 'active' : '' ?>">
                    <?= ucfirst($key) ?> (<?= $count ?>)
                </a>
            <?php endforeach; ?>
        </div>

        <!-- Фильтр по категориям -->
        <form method="get" class="category-filter body-medium-regular">
            <select name="category" onchange="this.form.submit()">
                <option value="0"><?= t('Все категории', 'All categories', 'Toate categoriile'); ?></option>
                <?php display_categories_with_count(0, 0, $category, $current_user_id); ?>
            </select>
            <input type="hidden" name="filter" value="<?= esc_attr($filter) ?>">
        </form>

        <!-- Поиск -->
        <form method="get" class="search-products body-medium-regular" action="<?= esc_url(get_permalink()); ?>">
            <input type="text" name="s" value="<?= esc_attr($search) ?>" placeholder="<?= t('Найти в моих объявлениях', 'Search my products', 'Caută în produsele mele'); ?>">
            <input type="hidden" name="filter" value="<?= esc_attr($filter) ?>">
            <input type="hidden" name="category" value="<?= esc_attr($category) ?>">
            <button type="submit" class="button-medium"><?= t('Поиск', 'Search', 'Caută'); ?></button>
        </form>

        <!-- Таблица товаров -->
        <?php if ($products->have_posts()): ?>
            <form method="post" class="products-table-form">
                <table class="my-products-table body-medium-regular">
                    <thead>
                        <tr>
                            <th><input type="checkbox" id="select-all"></th>
                            <th><?= t('Фото', 'Image', 'Imagine'); ?></th>
                            <th><?= t('Название / Категории', 'Title / Categories', 'Titlu / Categorii'); ?></th>
                            <th><?= t('Статус / Дата', 'Status / Date', 'Status / Dată'); ?></th>
                            <th><?= t('Действия', 'Actions', 'Acțiuni'); ?></th>
                            <th><?= t('Просмотры', 'Views', 'Vizualizări'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php while ($products->have_posts()): $products->the_post(); 
                        $post_status = get_post_status();
                    ?>
                        <tr>
                            <td><input type="checkbox" name="product_ids[]" value="<?= get_the_ID(); ?>"></td>
                            <td>
                                <?php 
                                    $thumb = get_the_post_thumbnail_url(get_the_ID(), 'thumbnail') ?: get_template_directory_uri() . '/images/product-placeholder.png';
                                    echo '<img src="'.esc_url($thumb).'" alt="'.esc_attr(get_the_title()).'" class="product-thumb">';
                                ?>
                            </td>
                            <td>
                                <strong class="title-medium"><?= get_the_title(); ?></strong><br>
                                <span class="categories body-small-regular">
                                    <?= get_term_path_short(get_the_ID(), 'product_cat', ' &raquo; '); ?>
                                </span>
                            </td>
                            <td>
                                <span class="body-small-regular"><?= ucfirst($post_status); ?></span><br>
                                <span class="body-small-regular"><?= get_the_date('d M Y, H:i'); ?></span>
                            </td>
                            <td class="actions">
                                <a href="<?= add_query_arg('edit', '1', get_permalink()); ?>" class="link-small-underline"><?= t('Редактировать', 'Edit', 'Editează'); ?></a>
                                <a href="?toggle_hidden=<?= get_the_ID(); ?>" class="link-small-underline"><?= $post_status === 'draft' ? t('Показать', 'Show', 'Arată') : t('Скрыть', 'Hide', 'Ascunde'); ?></a>
                                <a href="?delete_product=<?= get_the_ID(); ?>" onclick="return confirm('<?= t('Удалить товар?', 'Delete this product?', 'Șterge produsul?'); ?>')" class="link-small-underline"><?= t('Удалить', 'Delete', 'Șterge'); ?></a>
                            </td>
                            <td>
                                <button type="button" class="view-stats-btn button-small" data-id="<?= get_the_ID(); ?>"><?= t('Просмотры', 'Views', 'Vizualizări'); ?></button>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                    </tbody>
                </table>
            </form>

            <!-- Пагинация -->
            <div class="pagination body-medium-regular">
                <?= paginate_links([
                    'total'   => $products->max_num_pages,
                    'current' => $paged,
                    'format'  => '?paged=%#%&filter=' . $filter . '&category=' . $category . '&s=' . urlencode($search) . '&lang=' . $GLOBALS['language']
                ]); ?>
            </div>

        <?php else: ?>
            <p class="body-medium-regular"><?= t('У вас пока нет товаров.', 'You don’t have any products yet.', 'Nu ai încă produse.'); ?></p>
        <?php endif; ?>
    </main>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const selectAll = document.getElementById('select-all');
    selectAll?.addEventListener('change', function() {
        document.querySelectorAll('.my-products-table tbody input[type="checkbox"]').forEach(cb => cb.checked = selectAll.checked);
    });

    const bulkBtn = document.getElementById('bulk-action-apply');
    bulkBtn?.addEventListener('click', function() {
        const action = document.getElementById('bulk-action-select').value;
        if(!action) return alert('<?= t("Выберите действие", "Select action", "Selectați acțiunea"); ?>');

        const selectedIds = Array.from(document.querySelectorAll('.my-products-table tbody input[type="checkbox"]:checked')).map(cb => cb.value);
        if(!selectedIds.length) return alert('<?= t("Выберите хотя бы один товар", "Select at least one product", "Selectați cel puțin un produs"); ?>');

        if(action === 'delete' && !confirm('<?= t("Удалить выбранные товары?", "Delete selected products?", "Șterge produsele selectate?"); ?>')) return;

        fetch('<?= admin_url("admin-ajax.php"); ?>', {
            method: 'POST',
            headers: { 'Content-Type':'application/x-www-form-urlencoded; charset=UTF-8' },
            body: 'action=bulk_products&ids=' + selectedIds.join(',') + '&task=' + action
        }).then(r=>r.json()).then(res=>{
            if(res.success) location.reload();
            else alert(res.data || 'Error');
        });
    });

    const popup = document.getElementById('views-popup');
    const popupBody = document.getElementById('views-popup-body');
    document.querySelectorAll('.view-stats-btn').forEach(btn=>{
        btn.addEventListener('click', function() {
            const id = btn.dataset.id;
            popup.style.display = 'flex';
            popupBody.innerHTML = '<?= t("Загрузка...", "Loading...", "Se încarcă..."); ?>';

            fetch('<?= admin_url("admin-ajax.php"); ?>?action=get_product_views&id=' + id)
            .then(r=>r.text()).then(html=>{
                popupBody.innerHTML = html;
            });
        });
    });

    document.querySelectorAll('.close-popup').forEach(el=>{
        el.addEventListener('click', ()=>popup.style.display='none');
    });
    popup.addEventListener('click', e=>{ if(e.target===popup) popup.style.display='none'; });
});
</script>

<?php
wp_reset_postdata();
get_footer();
?>
