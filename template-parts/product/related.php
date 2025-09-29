<?php
$product_id = get_the_ID();
if (!$product_id) return;

// Get categories (ids)
$post_cats = wp_get_post_terms($product_id, 'product_cat');
if (empty($post_cats)) return;

// Find the deepest category (smallest parent chain)
usort($post_cats, function($a, $b) {
    return count(get_ancestors($b->term_id, 'product_cat')) <=> count(get_ancestors($a->term_id, 'product_cat'));
});
$deepest_cat = $post_cats[0];

// Build category chain: leaf → root
$cat_chain = array_merge([$deepest_cat->term_id], get_ancestors($deepest_cat->term_id, 'product_cat'));

// Current product meta (__ fields only)
$current_meta = [];
foreach (get_post_meta($product_id) as $key => $values) {
    if (strpos($key, '__') === 0 && !empty($values[0])) {
        $current_meta[$key] = $values[0];
    }
}

// Cache key
$cache_key = 'related_products_' . $product_id;

$scored = [];

foreach ($cat_chain as $cat_id) {
    if (count($scored) >= 6) break;

    $args = [
        'post_type'      => 'products',
        'posts_per_page' => 30,
        'post__not_in'   => [$product_id],
        'tax_query'      => [
            [
                'taxonomy' => 'product_cat',
                'field'    => 'id',
                'terms'    => $cat_id,
            ],
        ],
    ];

    $q = new WP_Query($args);

    if ($q->have_posts()) {
        while ($q->have_posts()) {
            $q->the_post();
            $pid = get_the_ID();
            if ($pid == $product_id) continue;

            $meta = get_post_meta($pid);
            $score = 0;
            $matched_fields = [];

            foreach ($current_meta as $key => $val) {
                if (isset($meta[$key]) && strcasecmp(trim($meta[$key][0]), trim($val)) === 0) {
                    $score++;
                    $matched_fields[] = $key;
                }
            }

            if (array_search($pid, array_column($scored, 'id')) !== false) {
                continue;
            }

            $scored[] = [
                'id'             => $pid,
                'score'          => $score,
                'matched_fields' => $matched_fields,
            ];
        }
        wp_reset_postdata();
    }
}

// Sort by score
usort($scored, fn($a, $b) => $b['score'] <=> $a['score']);

// Limit to max 6
$scored = array_slice($scored, 0, 8);

// Save to cache
set_transient($cache_key, $scored, HOUR_IN_SECONDS * 5);

// Render
if ($scored): ?>
    <section class="related-products">
        <h2 class="title-largest"><?= t('Похожие товары', 'Similar products', 'Produse similare'); ?></h2>
        <div class="related-grid products-list">
            <?php foreach ($scored as $item):
                $post = get_post($item['id']);
                setup_postdata($post);
                get_template_part('template-parts/product/card');
            endforeach;
            wp_reset_postdata(); ?>
        </div>
    </section>
<?php else: ?>
    <p><?= t('Похожие товары не найдены', 'No related products found', 'Nu s-au găsit produse similare'); ?></p>
<?php endif; ?>
