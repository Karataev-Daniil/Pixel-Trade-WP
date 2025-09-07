<?php
// SEO-функции с поддержкой ru/en/ro и главной страницы
function get_custom_title() {
    global $post, $language;

    // Главная страница
    if (is_front_page() || is_home()) {
        return t(
            'PixelTrade – Маркетплейс в Молдове',
            'PixelTrade – Marketplace in Moldova',
            'PixelTrade – Piața online din Moldova'
        );
    }

    if ($post instanceof WP_Post) {
        $titles = [
            'account'      => t('Мой аккаунт | PixelTrade', 'My Account | PixelTrade', 'Contul meu | PixelTrade'),
            'favorites'    => t('Избранное | PixelTrade', 'Favorites | PixelTrade', 'Favorite | PixelTrade'),
            'login'        => t('Вход в аккаунт | PixelTrade', 'Login | PixelTrade', 'Autentificare | PixelTrade'),
            'register'     => t('Регистрация | PixelTrade', 'Register | PixelTrade', 'Înregistrare | PixelTrade'),
            'settings'     => t('Настройки профиля | PixelTrade', 'Account Settings | PixelTrade', 'Setări profil | PixelTrade'),
            'add-product'  => t('Добавить товар | PixelTrade', 'Add Product | PixelTrade', 'Adaugă produs | PixelTrade'),
            'my-products'  => t('Мои товары | PixelTrade', 'My Products | PixelTrade', 'Produsele mele | PixelTrade'),
        ];

        return $titles[$post->post_name] ?? get_the_title($post->ID) . ' | PixelTrade';
    }

    // Если $post нет
    return 'PixelTrade';
}

function get_custom_description() {
    global $post, $language;

    if (is_front_page() || is_home()) {
        return t(
            'PixelTrade – это маркетплейс для продажи и покупки товаров в Молдове. Простая регистрация, быстрые объявления и удобный поиск. Используйте ИИ-помощник для составления товаров и двухкликовую многоязычность.',
            'PixelTrade – a marketplace for buying and selling products in Moldova. Easy registration, quick listings, and convenient search. Use the AI assistant to create products and two-click multilingual support.',
            'PixelTrade – piață online pentru vânzarea și cumpărarea produselor în Moldova. Înregistrare simplă, anunțuri rapide și căutare comodă. Folosește asistentul AI pentru crearea produselor și suport multilingv în două clicuri.'
        );
    }

    if ($post instanceof WP_Post) {
        $descriptions = [
            'account'      => t('Управляйте своим аккаунтом, обновляйте данные и контролируйте активность.', 
                                'Manage your account, update data, and track your activity.', 
                                'Gestionează-ți contul, actualizează datele și urmărește activitatea.'),
            'favorites'    => t('Список ваших избранных товаров и объявлений на PixelTrade.', 
                                'Your list of favorite products and ads on PixelTrade.', 
                                'Lista produselor și anunțurilor favorite pe PixelTrade.'),
            'login'        => t('Войдите в свой аккаунт, чтобы покупать и продавать на PixelTrade.', 
                                'Login to your account to buy and sell on PixelTrade.', 
                                'Autentifică-te pentru a cumpăra și vinde pe PixelTrade.'),
            'register'     => t('Создайте аккаунт и начните размещать свои объявления прямо сейчас.', 
                                'Create an account and start posting your ads right now.', 
                                'Creează-ți un cont și începe să publici anunțuri chiar acum.'),
            'settings'     => t('Настройте свой профиль, измените личные данные и пароль.', 
                                'Customize your profile, update personal info and password.', 
                                'Configurează-ți profilul, actualizează datele și parola.'),
            'add-product'  => t('Добавьте новое объявление и найдите покупателей в Молдове.', 
                                'Add a new ad and find buyers in Moldova.', 
                                'Adaugă un anunț nou și găsește cumpărători în Moldova.'),
            'my-products'  => t('Управляйте своими объявлениями: редактируйте, удаляйте и продвигайте.', 
                                'Manage your ads: edit, delete and promote them.', 
                                'Gestionează-ți anunțurile: editează, șterge și promovează.'),
        ];

        return $descriptions[$post->post_name] ?? get_bloginfo('description');
    }

    return get_bloginfo('description');
}

function get_custom_keywords() {
    global $post;

    if (is_front_page() || is_home()) {
        return 'маркетплейс, объявления, Молдова, PixelTrade, ИИ-помощник, многоязычность';
    }

    if ($post instanceof WP_Post) {
        $keywords = [
            'account'      => 'аккаунт, профиль, маркетплейс, PixelTrade',
            'favorites'    => 'избранное, сохраненные товары, PixelTrade',
            'login'        => 'вход, логин, маркетплейс, PixelTrade',
            'register'     => 'регистрация, новый аккаунт, PixelTrade',
            'settings'     => 'настройки, профиль, учетная запись, PixelTrade',
            'add-product'  => 'добавить товар, разместить объявление, PixelTrade',
            'my-products'  => 'мои товары, объявления, PixelTrade',
        ];
        return $keywords[$post->post_name] ?? 'маркетплейс, Молдова, объявления, PixelTrade';
    }

    return 'маркетплейс, Молдова, объявления, PixelTrade';
}
