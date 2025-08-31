<?php
/**
 * Универсальные хлебные крошки для категории и продукта с переводом всех пунктов
 */

$lang = $GLOBALS['language'] ?? 'ru';
$product_id = get_the_ID();
?>

<nav class="breadcrumbs body-small-regular" aria-label="<?= t('Хлебные крошки','Breadcrumb','Firimituri'); ?>">
    <a class="link-small-underline" href="<?= esc_url(home_url('/')); ?>">
        <?= t('Главная','Home','Pagina principală'); ?>
    </a>

<?php
function get_term_name_translated($term, $lang) {
    if ($lang === 'en') $name = get_term_meta($term->term_id,'translation_en',true);
    elseif ($lang === 'ro') $name = get_term_meta($term->term_id,'translation_ro',true);
    else $name = $term->name;
    return $name ?: $term->name;
}

if (is_tax('product_cat')) {
    // Категория товара
    $current_cat = get_queried_object();
    if ($current_cat->parent) {
        $ancestors = array_reverse(get_ancestors($current_cat->term_id, 'product_cat'));
        foreach ($ancestors as $ancestor_id) {
            $ancestor = get_term($ancestor_id, 'product_cat');
            echo ' &raquo; <a class="link-small-underline" href="'.esc_url(get_term_link($ancestor)).'">'.esc_html(get_term_name_translated($ancestor,$lang)).'</a>';
        }
    }
    echo ' &raquo; <span class="link-small-default">'.esc_html(get_term_name_translated($current_cat,$lang)).'</span>';

} elseif (is_singular('product')) {
    // Страница продукта
    $terms = get_the_terms($product_id, 'product_cat');
    if ($terms && !is_wp_error($terms)) {
        // Находим самую глубокую категорию
        $deepest_term = null;
        $max_depth = -1;
        foreach ($terms as $term) {
            $depth = 0;
            $parent = $term->parent;
            while ($parent) {
                $depth++;
                $parent_term = get_term($parent, 'product_cat');
                if (!$parent_term || is_wp_error($parent_term)) break;
                $parent = $parent_term->parent;
            }
            if ($depth > $max_depth) { $max_depth = $depth; $deepest_term = $term; }
        }

        if ($deepest_term) {
            $breadcrumbs = [];
            $term = $deepest_term;
            $visited = [];
            while ($term && !in_array($term->term_id, $visited)) {
                $visited[] = $term->term_id;
                $breadcrumbs[] = '<a class="link-small-underline" href="'.get_term_link($term).'">'.esc_html(get_term_name_translated($term,$lang)).'</a>';
                $term = $term->parent ? get_term($term->parent,'product_cat') : false;
            }
            echo ' &raquo; ' . implode(' &raquo; ', array_reverse($breadcrumbs));
        }
    }
    echo ' &raquo; <span class="link-small-default">'.esc_html($title_translations[$lang] ?? get_the_title($product_id)).'</span>';
}
?>
</nav>

