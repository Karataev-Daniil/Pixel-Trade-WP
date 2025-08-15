<!DOCTYPE html>
<html lang="<?= $GLOBALS['language'] ?>" data-theme="<?= $GLOBALS['theme'] ?>">
    <head>
        <meta charset="UTF-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/nouislider@15.7.0/dist/nouislider.min.css">
        <script src="https://cdn.jsdelivr.net/npm/nouislider@15.7.0/dist/nouislider.min.js"></script>
        <script>
          document.addEventListener('DOMContentLoaded', function () {
            const toggleButton = document.getElementById('catalogToggle');
            const dropdown = document.getElementById('catalogDropdown');
          
            toggleButton.addEventListener('click', function () {
              dropdown.classList.toggle('show');
            });
          
            document.addEventListener('click', function (event) {
              if (!toggleButton.contains(event.target) && !dropdown.contains(event.target)) {
                dropdown.classList.remove('show');
              }
            });
          });
          document.querySelector('.language-toggle')?.addEventListener('click', function(e) {
            const switcher = this.closest('.language-switcher');
            switcher.classList.toggle('open');
          });
        </script>
        <?php wp_head(); ?>
    </head>

    <body <?php body_class(); ?>>
      <header class="header">
        <div class="header__wrapper main-navigation">
              
          <!-- Первый этаж -->
          <section class="header-top__wrapper" aria-label="<?= t('Верхняя панель', 'Top panel', 'Panou superior'); ?>">
            <div class="container-medium">
              <div class="header-top">
              
                <!-- Логотип и информация -->
                <div class="header-top-left">
                  <div class="logo">
                    <a href="/" aria-label="<?= t('На главную', 'Home', 'Acasă'); ?>" class="logo-link">
                      <?php echo file_get_contents(get_template_directory() . '/images/logo.svg'); ?>
                    </a>
                  </div>
              
                  <div class="header-info">
                    <div class="body-small-medium region">
                      <?= t('Молдова', 'Moldova', 'Moldova'); ?>
                    </div>
                    <div class="body-small-medium post-count">
                      <?php
                        $product_count = wp_count_posts('product')->publish;
                        echo t('Объявлений: ', 'Listings: ', 'Anunțuri: ') . $product_count;
                      ?>
                    </div>
                  </div>
                </div>
              
                <!-- Переключатели -->
                <div class="header-top-right">
                  <?php
                    $current_query = $_GET;
                    unset($current_query['lang']);
                    $current_url_base = strtok($_SERVER["REQUEST_URI"], '?');

                    $languages = [
                      'ru' => '🇷🇺',
                      'en' => '🇬🇧',
                      'ro' => '🇷🇴'
                    ];
                  
                    $current_lang = $GLOBALS['language'];
                    $current_flag = $languages[$current_lang] ?? '🌐';
                  ?>

                  <nav class="language-switcher" aria-label="<?= t('Выбор языка', 'Language selection', 'Selectarea limbii'); ?>">
                    <button class="language-toggle">
                      <span class="flag"><?= $current_flag ?></span>
                      <span class="lang-label title-smaller"><?= strtoupper($current_lang) ?></span>
                    </button>
                    <div class="language-options">
                      <?php foreach ($languages as $lang => $flag): ?>
                        <?php if ($lang === $current_lang) continue; ?>
                        <?php
                          $query = array_merge($current_query, ['lang' => $lang]);
                          $query_string = http_build_query($query);
                          $link = $current_url_base . '?' . $query_string;
                        ?>
                        <a href="<?= esc_url($link); ?>"
                           class="language-button title-smaller"
                           aria-current="false"
                           title="<?= strtoupper($lang); ?>">
                          <span class="flag"><?= $flag ?></span>
                          <span class="lang-label"><?= strtoupper($lang); ?></span>
                        </a>
                      <?php endforeach; ?>
                    </div>
                  </nav>
              
                  <!-- Темная тема -->
                  <button id="theme-toggle-button" class="tertiary-button-small theme-icon-button" aria-label="<?= t('Сменить тему', 'Toggle theme', 'Comută tema'); ?>" type="button">
                    <span class="icon-sun"><?php echo file_get_contents(get_template_directory() . '/images/sun.svg'); ?></span>
                    <span class="icon-moon"><?php echo file_get_contents(get_template_directory() . '/images/moon.svg'); ?></span>
                  </button>
              
                  <!-- Пользователь -->
                  <?php
                    $user = wp_get_current_user();
                    $is_logged_in = is_user_logged_in();
                    $avatar_url = $is_logged_in ? get_avatar_url($user->ID) : get_template_directory_uri() . '/assets/img/avatar-placeholder.png';
                  ?>
                  <div class="user-menu">
                    <button class="user-avatar" id="user-avatar" aria-haspopup="true" aria-expanded="false" aria-label="<?= t('Меню пользователя', 'User menu', 'Meniu utilizator'); ?>">
                      <img src="<?= esc_url($avatar_url); ?>" alt="User Avatar">
                    </button>
                    <ul class="user-dropdown" id="user-dropdown">
                      <?php if ($is_logged_in): ?>
                        <li class="label-small"><a href="/my-products" class="title-smaller"><?= t('Мои товары', 'My Products', 'Produsele mele'); ?></a></li>
                        <li class="label-small"><a href="/account/settings" class="title-smaller"><?= t('Настройки аккаунта', 'Account Settings', 'Setări cont'); ?></a></li>
                        <li class="label-small"><a href="<?= wp_logout_url(home_url()); ?>" class="title-smaller"><?= t('Выход', 'Logout', 'Ieșire'); ?></a></li>
                      <?php else: ?>
                        <li class="label-small"><a href="/account/login/" class="title-smaller"><?= t('Войти', 'Login', 'Autentificare'); ?></a></li>
                      <?php endif; ?>
                    </ul>
                  </div>
                </div>
              </div>
            </div>
          </section>
                      
          <!-- Второй этаж -->
          <section class="header-bottom__wrapper" aria-label="<?= t('Основная навигация', 'Main Navigation', 'Navigație principală'); ?>">
            <div class="container-medium">
              <nav class="header-bottom" aria-label="<?= t('Навигационное меню', 'Navigation Menu', 'Meniu de navigare'); ?>">
                      
                <!-- Каталог -->
                <div class="catalog-dropdown-wrapper">
                  <button class="secondary-button-small catalog-toggle-button" id="catalogToggle" type="button" aria-haspopup="true" aria-expanded="false">
                    <?= t('Каталог', 'Catalog', 'Catalog'); ?>
                  </button>
                  <ul class="catalog-dropdown-list" id="catalogDropdown">
                    <?php
                      $terms = get_terms([
                        'taxonomy' => 'product_cat',
                        'hide_empty' => false,
                      ]);
                      if (!empty($terms) && !is_wp_error($terms)) {
                        foreach ($terms as $term) {
                          echo '<li><a class="link-button" href="' . esc_url(get_term_link($term)) . '">' . esc_html($term->name) . '</a></li>';
                        }
                      } else {
                        echo '<li class="label-small">' . t('Нет категорий', 'No categories', 'Fără categorii') . '</li>';
                      }
                    ?>
                  </ul>
                </div>
                    
                <!-- Поиск -->
                <form role="search" method="get" class="search-form search-panel has-content" action="<?= esc_url(home_url('/blog/')); ?>">
                  <input id="search-field" class="search-field body-medium-regular"
                    placeholder="<?= esc_attr(t('Поиск товаров', 'Search products', 'Caută produse')); ?>"
                    value="<?= get_search_query(); ?>"
                    name="s" />
                  <button type="button" id="clear-search" class="search-clear-button" aria-label="<?= t('Очистить поиск', 'Clear search', 'Șterge căutarea'); ?>"></button>
                </form>
                    
                <!-- Подать объявление -->
                <a href="/add-product" class="primary-button-small">
                  <?= t('Подать объявление', 'Post Ad', 'Adaugă anunț'); ?>
                </a>
                    
              </nav>
            </div>
          </section>
        </div>
      </header>
