<?php
/* Template Name: Мои Товары */

if (!is_user_logged_in()) {
    get_header();
    ?>
    <div class="container my-products-wrapper">
        <p class="body-medium-regular">
            <?php echo t('Пожалуйста,','Please,','Vă rugăm,'); ?>
            <a href="<?php echo esc_url(wp_login_url()); ?>" class="link-medium-underline">
                <?php echo t('войдите','login','autentificați-vă'); ?>
            </a>
            <?php echo t('чтобы просмотреть свои товары.','to view your products.','pentru a vedea produsele dvs.'); ?>
        </p>
    </div>
    <?php
    get_footer();
    return;
}

get_header();
$current_user_id = get_current_user_id();
$author_info = get_userdata($current_user_id);

$status_map = [
    'publish'  => t('Активные','Active','Active'),
    'draft'    => t('Скрытые','Hidden','Ascunse'),
    'pending'  => t('Неактивные','Inactive','Inactive'),
    'private'  => t('Заблокированные','Blocked','Blocate')
];

$status_counts = [];
foreach ($status_map as $status_key => $label) {
    $query = new WP_Query([
        'post_type' => 'products',
        'post_status' => $status_key,
        'author' => $current_user_id,
        'posts_per_page' => -1,
        'fields' => 'ids',
    ]);
    $status_counts[$status_key] = $query->found_posts;
}

$all_cats = get_terms([
    'taxonomy' => 'product_cat',
    'hide_empty' => false,
]);

$user_products_by_cat = [];
$user_posts = get_posts([
    'post_type' => 'products',
    'author' => $current_user_id,
    'post_status' => ['publish','draft','pending','private'],
    'posts_per_page' => -1,
    'fields' => 'ids'
]);
if($user_posts){
    global $wpdb;
    $placeholders = implode(',', array_fill(0, count($user_posts), '%d'));
    $sql = "SELECT term_taxonomy_id, COUNT(*) as cnt 
            FROM {$wpdb->term_relationships} 
            WHERE object_id IN ($placeholders)
            GROUP BY term_taxonomy_id";
    $prepared = $wpdb->prepare($sql, $user_posts);
    $rows = $wpdb->get_results($prepared);
    foreach ($rows as $row){
        $term_id = $wpdb->get_var($wpdb->prepare(
            "SELECT term_id FROM {$wpdb->term_taxonomy} WHERE term_taxonomy_id=%d",
            $row->term_taxonomy_id
        ));
        $user_products_by_cat[$term_id] = (int)$row->cnt;
    }
}

function render_user_cats_options($cats, $counts, $parent = 0, $depth = 0){
    foreach ($cats as $cat){
        if ($cat->parent != $parent) continue;
        $count = isset($counts[$cat->term_id]) ? $counts[$cat->term_id] : 0;
        if($count > 0){
            $prefix = str_repeat('- ',$depth);
            echo '<option value="'.esc_attr($cat->term_id).'">'.esc_html($prefix.$cat->name).' ('.$count.')</option>';
        }
        render_user_cats_options($cats, $counts, $cat->term_id, $depth+1);
    }
}

$ajax_nonce = wp_create_nonce('my_products_nonce');
?>

<div class="container-medium my-products-wrapper">
    <main>
        <h1 class="display-small">
            <?php printf(t('Здравствуйте! %s','Hello! %s','Salut! %s'), esc_html($author_info->display_name)); ?>
        </h1>

        <div class="filters-wrapper body-medium-regular my-filters-wrapper">
            <div class="tabs-status">
                <span class="status-tab label-larger active" data-status="all">
                    <?php echo t('Все','All','Toate'); ?> 
                    <span class="count" data-status="all">
                        <?php 
                        $all_count = array_sum($status_counts);
                        echo $all_count; 
                        ?>
                    </span>
                </span>
                <?php foreach ($status_map as $key => $label): ?>
                    <span class="status-tab label-larger" data-status="<?php echo esc_attr($key); ?>">
                        <?php echo esc_html($label); ?> 
                        <span class="count" data-status="<?php echo esc_attr($key); ?>">
                            <?php echo isset($status_counts[$key]) ? $status_counts[$key] : 0; ?>
                        </span>
                    </span>
                <?php endforeach; ?>
            </div>

            <div class="filters-controls">
                <div class="filters-left">
                    <label class="select-all-wrapper checkbox-block">
                        <input type="checkbox" id="select-all-products">
                    </label>
                    
                    <div class="select-info" id="selection-info"></div>

                    <div id="bulk-actions" class="product-actions" style="display:none;">
                        <span class="action-btn" data-action="republish" title="Переопубликовать">
                            <svg viewBox="0 0 24 24">
                                <path d="M12 2a10 10 0 1 0 10 10h-2a8 8 0 1 1-8-8v4l5-5-5-5v4z"/>
                            </svg>
                        </span>
                        <span class="action-btn" data-action="hide" title="Скрыть">
                            <svg viewBox="0 0 24 24">
                                <path d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5C21.27 7.61 17 4.5 12 4.5zm0 12c-2.48 0-4.5-2.02-4.5-4.5S9.52 7.5 12 7.5s4.5 2.02 4.5 4.5-2.02 4.5-4.5 4.5z"/>
                                <line x1="3" y1="3" x2="21" y2="21" stroke="currentColor" stroke-width="2"/>
                            </svg>
                        </span>
                        <span class="action-btn" data-action="delete" title="Удалить">
                            <svg viewBox="0 0 24 24">
                                <line x1="6" y1="6" x2="18" y2="18" stroke="currentColor" stroke-width="2"/>
                                <line x1="6" y1="18" x2="18" y2="6" stroke="currentColor" stroke-width="2"/>
                            </svg>
                        </span>
                    </div>

                    <div class="input-block">
                        <select class="filter-category select--secondary label-medium">
                            <option value="all"><?php echo t('Все категории','All categories','Toate categoriile'); ?></option>
                            <?php render_user_cats_options($all_cats, $user_products_by_cat); ?>
                        </select>
                    </div>

                    <div class="input-block">
                        <input type="text" class="filter-search input--secondary label-medium" placeholder="<?php echo t('Найти в моих объявлениях','Search in my listings','Caută în anunțurile mele'); ?>">
                    </div>
                </div>

                <div class="input-block">
                    <select class="filter-sort select--secondary label-medium">
                        <option value="date_new"><?php echo t('По дате (новые)','By date (new)','După dată (noi)'); ?></option>
                        <option value="date_old"><?php echo t('По дате (старые)','By date (old)','După dată (vechi)'); ?></option>
                        <option value="price_low"><?php echo t('По цене (дешевле)','By price (low)','După preț (mic)'); ?></option>
                        <option value="price_high"><?php echo t('По цене (дороже)','By price (high)','După preț (mare)'); ?></option>
                    </select>
                </div>
            </div>
        </div>

        <table class="my-products-table body-medium-regular">
            <tbody id="products-list">
                <tr><td colspan="5"><?php echo t('Загрузка товаров...','Loading products...','Se încarcă produsele...'); ?></td></tr>
            </tbody>
        </table>
        <div id="pagination" style="margin-top:20px;text-align:center;"></div>
    </main>
</div>
<style>
.stats-popup {
    position: fixed;
    inset: 0;
    background: rgba(0,0,0,0.55);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 10000;
    padding: 20px;
    backdrop-filter: blur(4px);
}

.stats-popup .popup-inner {
    background: var(--gray_6); /* белый */
    border-radius: 16px;
    padding: 24px;
    max-width: 760px;
    width: 100%;
    max-height: 85vh;
    overflow-y: auto;
    box-shadow: 0 8px 28px rgba(0,0,0,0.25);
    animation: fadeIn 0.25s ease;
}

.stats-popup .stats-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-bottom: 1px solid var(--gray_3);
    padding-bottom: 12px;
    margin-bottom: 16px;
}
.stats-popup .stats-header h2 {
    font-size: 1.4rem;
    margin: 0;
    color: var(--gray_-6);
}
.stats-popup .close-popup {
    background: transparent;
    border: none;
    font-size: 1.6rem;
    cursor: pointer;
    line-height: 1;
    color: var(--gray_-1);
}
.stats-popup .close-popup:hover {
    color: var(--gray_-6);
}

.stats-summary {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
    gap: 12px;
    margin-bottom: 24px;
}
.stat-card {
    background: var(--gray_5);
    border-radius: 12px;
    padding: 14px;
    text-align: center;
}
.stat-label {
    font-size: 0.9rem;
    color: var(--gray_0);
}
.stat-value {
    font-size: 1.3rem;
    font-weight: 600;
    color: var(--gray_-6);
    margin-top: 6px;
}

.stats-section {
    margin-bottom: 28px;
}
.stats-section h3 {
    font-size: 1.1rem;
    margin-bottom: 10px;
    color: var(--gray_-5);
}

.table-wrapper {
    overflow-x: auto;
    border-radius: 8px;
    border: 1px solid var(--gray_2);
}
.stats-table {
    width: 100%;
    border-collapse: collapse;
    min-width: 400px;
}
.stats-table th, .stats-table td {
    border-bottom: 1px solid var(--gray_2);
    padding: 8px 10px;
    text-align: left;
}
.stats-table th {
    background: var(--gray_4);
    font-weight: 600;
    font-size: 0.9rem;
    color: var(--gray_-6);
}
.stats-table td {
    font-size: 0.9rem;
    color: var(--gray_-6);
}
.stats-table tr:hover td {
    background: var(--gray_5);
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(8px); }
    to { opacity: 1; transform: translateY(0); }
}

@media (max-width: 600px) {
    .stats-popup .popup-inner {
        padding: 16px;
    }
    .stat-card {
        padding: 10px;
    }
}
</style>

<?php get_footer(); ?>
