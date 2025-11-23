<?php
// === SEO FUNCTIONS WITH MULTILINGUAL SUPPORT ===
function get_custom_title() {
    if (is_front_page() || is_home()) {
        return t(
            'PixelTrade – Маркетплейс в Молдове',
            'PixelTrade – Marketplace in Moldova',
            'PixelTrade – Piața online din Moldova'
        );
    }

    if (is_singular('products')) {
        return get_the_title() . ' | PixelTrade';
    }

    if (is_tax('product_cat')) {
        $term = get_queried_object();
        $lang = $GLOBALS['language'] ?? 'ru';
        $translated_name = get_category_name_translated($term, $lang);
        return sprintf('%s | PixelTrade', esc_html($translated_name));
    }

    global $post;
    if ($post instanceof WP_Post) {
        $titles = [
            'user'          => t('Мой аккаунт | PixelTrade', 'My Account | PixelTrade', 'Contul meu | PixelTrade'),
            'favorites'     => t('Избранное | PixelTrade', 'Favorites | PixelTrade', 'Favorite | PixelTrade'),
            'login'         => t('Вход | PixelTrade', 'Login | PixelTrade', 'Autentificare | PixelTrade'),
            'register'      => t('Регистрация | PixelTrade', 'Register | PixelTrade', 'Înregistrare | PixelTrade'),
            'settings'      => t('Настройки профиля | PixelTrade', 'Profile Settings | PixelTrade', 'Setări profil | PixelTrade'),
            'add-product'   => t('Добавить товар | PixelTrade', 'Add Product | PixelTrade', 'Adaugă produs | PixelTrade'),
            'user-products' => t('Мои товары | PixelTrade', 'My Products | PixelTrade', 'Produsele mele | PixelTrade'),
        ];

        return $titles[$post->post_name] ?? (get_the_title($post->ID) . ' | PixelTrade');
    }

    return 'PixelTrade';
}

function get_custom_description() {
    if (is_front_page() || is_home()) {
        return t(
            'PixelTrade – маркетплейс Молдовы. Простая регистрация, быстрые объявления и AI-помощник.',
            'PixelTrade – Moldova marketplace. Easy registration, quick listings, and AI assistant.',
            'PixelTrade – piață online din Moldova. Înregistrare simplă, anunțuri rapide și asistent AI.'
        );
    }

    if (is_singular('products')) {
        $excerpt = get_the_excerpt();
        return $excerpt ?: t(
            'Объявление на PixelTrade – маркетплейсе Молдовы.',
            'Listing on PixelTrade – Moldova marketplace.',
            'Anunț pe PixelTrade – piața online din Moldova.'
        );
    }

    if (is_tax('product_cat')) {
        $term = get_queried_object();
        $desc = term_description($term->term_id, 'product_cat');
        if ($desc) return wp_strip_all_tags($desc);

        return sprintf(t(
            'Категория %s – товары и объявления в Молдове на PixelTrade.',
            'Category %s – products and ads in Moldova on PixelTrade.',
            'Categoria %s – produse și anunțuri în Moldova pe PixelTrade.'
        ), $term->name);
    }

    global $post;
    if ($post instanceof WP_Post) {
        $descriptions = [
            'favorites'     => t('Ваш список избранных товаров.', 'Your list of favorite products.', 'Lista produselor favorite.'),
            'user'          => t('Управляйте аккаунтом и активностью.', 'Manage your account and activity.', 'Gestionează contul tău și activitatea.'),
            'login'         => t('Войдите, чтобы покупать и продавать.', 'Login to buy and sell.', 'Autentifică-te pentru a cumpăra și vinde.'),
            'register'      => t('Создайте аккаунт и начните продавать.', 'Create an account and start selling.', 'Creează un cont și începe să vinzi.'),
            'settings'      => t('Настройте профиль и пароль.', 'Customize your profile and password.', 'Configurează profilul și parola.'),
            'add-product'   => t('Добавьте новое объявление.', 'Add a new listing.', 'Adaugă un anunț nou.'),
            'user-products' => t('Редактируйте и продвигайте объявления.', 'Edit and promote your listings.', 'Editează și promovează anunțurile.'),
        ];

        return $descriptions[$post->post_name] ?? get_bloginfo('description');
    }

    return get_bloginfo('description');
}

function get_custom_keywords() {
    if (is_tax('product_cat')) {
        $term = get_queried_object();
        return sprintf('%s, товары, объявления, PixelTrade', $term->name);
    }

    if (is_singular('products')) {
        $tags = wp_get_post_terms(get_the_ID(), 'product_cat', ['fields' => 'names']);
        return implode(', ', $tags) . ', PixelTrade';
    }

    return 'PixelTrade, Молдова, объявления, маркетплейс';
}

function should_noindex() {
    global $post;
    if (is_404() || is_search() || is_tag() || is_date()) return true;
    if (is_page(['login', 'register', 'user', 'settings', 'add-product', 'favorites'])) return true;
    return false;
}

add_action('wp_head', function () {
    echo '<title>' . esc_html(get_custom_title()) . '</title>' . PHP_EOL;
    echo '<meta name="description" content="' . esc_attr(get_custom_description()) . '">' . PHP_EOL;
    echo '<meta name="keywords" content="' . esc_attr(get_custom_keywords()) . '">' . PHP_EOL;
    echo '<meta name="robots" content="' . (should_noindex() ? 'noindex, nofollow' : 'index, follow') . '">' . PHP_EOL;
});
