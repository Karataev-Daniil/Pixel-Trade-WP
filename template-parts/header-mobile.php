<header class="header header--mobile">
    <div class="container-medium">
        <div class="mobile-header-top">
            <!-- Burger menu -->
            <button id="burger-button" class="burger-button" aria-label="Open menu">
                <?php echo file_get_contents(get_template_directory() . '/images/icon-burger.svg'); ?>
            </button>

            <!-- Logo -->
            <a href="/" class="logo-link">
                <?php echo file_get_contents(get_template_directory() . '/images/logo.svg'); ?>
            </a>

            <!-- Search icon -->
            <button id="search-toggle-button" class="search-icon-button" aria-label="Open search">
                <?php echo file_get_contents(get_template_directory() . '/images/icon-search.svg'); ?>
            </button>
        </div>
    </div>

    <!-- Sidebar menu -->
    <div id="mobile-sidebar" class="mobile-sidebar">
        <?php
        $current_user  = wp_get_current_user();
        $is_logged_in  = is_user_logged_in();
        $lang          = $GLOBALS['language'] ?? 'ru';

        $avatar_id     = get_user_meta($current_user->ID, 'profile_avatar', true);
        $avatar_img    = $avatar_id
            ? wp_get_attachment_image($avatar_id, 'small-thumb', false, ['alt' => 'User Avatar'])
            : get_avatar($current_user->ID, 50, '', 'User Avatar');

        $user_nicename = $current_user->user_nicename;

        // Define current language and path for language switcher
        $languages = ['ru' => 'Рус', 'en' => 'Eng', 'ro' => 'Rom'];
        $current_path = trim($_SERVER['REQUEST_URI'], '/');
        $parts = explode('/', $current_path);
        $current_lang = in_array($parts[0], array_keys($languages)) ? $parts[0] : 'ru';
        if (in_array($parts[0], array_keys($languages))) {
            array_shift($parts);
        }
        $path_without_lang = implode('/', $parts);
        $GLOBALS['language'] = $current_lang;
        ?>

        <button id="sidebar-close" class="sidebar-close" aria-label="Close menu">
            <?php echo file_get_contents(get_template_directory() . '/images/icon-close.svg'); ?>
        </button>

        <nav class="sidebar-nav">
            <ul>
                <!-- User -->
                <li class="sidebar-item user-block">
                    <a class="title-smaller" href="/<?= $lang ?>/user/<?= $user_nicename ?>">
                        <?= $avatar_img; ?>
                        <p class="label-small"><?= esc_html($current_user->display_name); ?></p>
                    </a>
                </li>

                <hr> <!-- Divider -->

                <!-- Main actions -->
                <li class="sidebar-item">
                    <a href="/<?= $lang ?>/">
                        <?php echo file_get_contents(get_template_directory() . '/images/icon-home.svg'); ?>
                        <p class="label-small"><?= t('Главная', 'Home', 'Acasă'); ?></p>
                    </a>
                </li>
                <li class="sidebar-item">
                    <a href="/<?= $lang ?>/add-product">
                        <?php echo file_get_contents(get_template_directory() . '/images/icon-add.svg'); ?>
                        <p class="label-small"><?= t('Добавить объявление', 'Add Listing', 'Adaugă anunț'); ?></p>
                    </a>
                </li>
                <li class="sidebar-item">
                    <a href="/<?= $lang ?>/user/products">
                        <?php echo file_get_contents(get_template_directory() . '/images/icon-my-products.svg'); ?>
                        <p class="label-small"><?= t('Мои товары', 'My Products', 'Produsele mele'); ?></p>
                    </a>
                </li>
                <li class="sidebar-item">
                    <a href="/<?= $lang ?>/user/favorites">
                        <?php echo file_get_contents(get_template_directory() . '/images/icon-favorites.svg'); ?>
                        <p class="label-small"><?= t('Избранное', 'Favorites', 'Favorite'); ?></p>
                    </a>
                </li>
                <li class="sidebar-item">
                    <a href="/<?= $lang ?>/user/settings">
                        <?php echo file_get_contents(get_template_directory() . '/images/icon-settings.svg'); ?>
                        <p class="label-small"><?= t('Настройки', 'Settings', 'Setări'); ?></p>
                    </a>
                </li>

                <hr> <!-- Divider -->

                <!-- System actions -->
                <li class="sidebar-item">
                    <a href="<?= wp_logout_url(home_url("/$lang/")); ?>">
                        <?php echo file_get_contents(get_template_directory() . '/images/icon-logout.svg'); ?>
                        <p class="label-small"><?= t('Выход', 'Logout', 'Ieșire'); ?></p>
                    </a>
                </li>
                <li class="sidebar-item">
                    <button id="theme-toggle-button" class="theme-icon-button">
                        <span class="icon-sun"><?php echo file_get_contents(get_template_directory() . '/images/sun.svg'); ?></span>
                        <span class="icon-sun-solid"><?php echo file_get_contents(get_template_directory() . '/images/sun-solid.svg'); ?></span>
                        <span class="icon-moon"><?php echo file_get_contents(get_template_directory() . '/images/moon.svg'); ?></span>
                        <span class="icon-moon-solid"><?php echo file_get_contents(get_template_directory() . '/images/moon-solid.svg'); ?></span>
                        <p class="label-small"><?= t('Тема', 'Theme', 'Temă'); ?></p>
                    </button>
                </li>

                <!-- Language switcher -->
                <li class="sidebar-item dropdown">
                    <button class="dropdown__button label-small">
                        <?php echo file_get_contents(get_template_directory() . '/images/icon-language.svg'); ?>
                        <p class="label-small"><?= esc_html(strtoupper($current_lang)) ?></p>
                    </button>
                    <ul class="dropdown__list">
                        <?php foreach ($languages as $lang => $label): ?>
                            <?php if ($lang === $current_lang) continue; ?>
                            <li class="dropdown__item label-small">
                                <a href="<?= esc_url(home_url("/$lang/$path_without_lang")) ?>"><?= esc_html(strtoupper($lang)) ?></a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </li>

                <hr> <!-- Divider -->

                <!-- Messages -->
                <li class="sidebar-item">
                    <button id="dm-toggle-btn-header" class="label-small dm-toggle-btn">
                        <?php echo file_get_contents(get_template_directory() . '/images/icon-messages.svg'); ?>
                        <p class="label-small"><?= t('Сообщения', 'Messages', 'Mesaje'); ?></p>
                    </button>
                </li>
            </ul>
        </nav>
    </div>

    <!-- Full-width search -->
    <div id="mobile-search-panel" class="mobile-search-panel">
        <div class="container-medium">
            <div class="search-panel-inner">
                <form role="search" method="get" class="search-form" action="<?= esc_url(home_url('/blog/')); ?>">
                    <input id="search-field" class="search-field body-medium-regular"
                           placeholder="<?= esc_attr(t('Поиск аренды', 'Search rentals', 'Caută închirieri')); ?>"
                           value="<?= get_search_query(); ?>"
                           name="s" />
                </form>
                <button type="button" id="search-cancel" class="search-cancel-button link-larger-default">
                    <?= t('Отмена', 'Cancel', 'Anulează'); ?>
                </button>
            </div>
        </div>
    </div>

    <div id="mobile-overlay" class="mobile-overlay"></div>
</header>

<nav class="mobile-nav">
    <?php
    $current_user = wp_get_current_user();
    $is_logged_in = is_user_logged_in();
    $lang         = $GLOBALS['language'] ?? 'ru';

    $avatar_id = get_user_meta($current_user->ID, 'profile_avatar', true);
    $avatar_img = $avatar_id
        ? wp_get_attachment_image($avatar_id, 'small-thumb', false, ['alt' => 'User Avatar', 'class' => 'nav-avatar'])
        : get_avatar($current_user->ID, 40, '', 'User Avatar', ['class' => 'nav-avatar']);

    $user_nicename = $current_user->user_nicename;
    $current_path  = rtrim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');

    $menu_items = [
        'home' => [
            'href'  => "/$lang/",
            'label' => t('Главная', 'Home', 'Acasă'),
            'icon'  => file_get_contents(get_template_directory() . '/images/icon-home.svg'),
        ],
        'favorites' => [
            'href'  => "/$lang/user/favorites",
            'label' => t('Избранное', 'Favorites', 'Favorite'),
            'icon'  => file_get_contents(get_template_directory() . '/images/icon-favorites.svg'),
        ],
        'add_product' => [
            'href'  => "/$lang/add-product",
            'label' => t('Добавить', 'Add Product', 'Adaugă'),
            'icon'  => file_get_contents(get_template_directory() . '/images/icon-add.svg'),
        ],
        'my_products' => [
            'href'  => "/$lang/user/products",
            'label' => t('Мои товары', 'My Products', 'Produsele mele'),
            'icon'  => file_get_contents(get_template_directory() . '/images/icon-my-products.svg'),
        ],
    ];

    foreach ($menu_items as $item):
        $item_path = rtrim(parse_url($item['href'], PHP_URL_PATH), '/');
        $is_active = ($current_path === $item_path);
    ?>
        <a href="<?= $item['href'] ?>" class="nav-item label-small <?= $is_active ? 'active' : '' ?>">
            <span class="nav-icon"><?= $item['icon'] ?></span>
            <span class="nav-label"><?= $item['label'] ?></span>
        </a>
    <?php endforeach; ?>

    <!-- Messages -->
    <button id="dm-toggle-btn-header" class="nav-item dm-toggle-btn label-small">
        <span class="nav-icon">
            <?php echo file_get_contents(get_template_directory() . '/images/icon-messages.svg'); ?>
        </span>
        <span class="nav-label"><?= t('Сообщения', 'Messages', 'Mesaje'); ?></span>
    </button>
</nav>
