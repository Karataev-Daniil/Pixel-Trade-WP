<!DOCTYPE html>
<html lang="<?= $GLOBALS['language'] ?>" data-theme="<?= $GLOBALS['theme'] ?>">
    <head>
        <meta charset="UTF-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/nouislider@15.7.0/dist/nouislider.min.css">
        <script src="https://cdn.jsdelivr.net/npm/nouislider@15.7.0/dist/nouislider.min.js"></script>
        <script>
        document.addEventListener("DOMContentLoaded", () => {
            const toggleBtn = document.querySelector("#catalogToggle");
            const dropdown = document.querySelector("#catalogDropdown");
            const overlay = document.querySelector(".catalog-overlay");
            const mainItems = dropdown.querySelectorAll(".catalog-main__item");
            const subcategories = dropdown.querySelectorAll(".catalog-subcategories__item");

            const resetActive = () => {
                mainItems.forEach(item => item.classList.remove("is-active"));
                subcategories.forEach(sub => sub.classList.remove("is-active"));
            };
          
            toggleBtn.addEventListener("click", e => {
                e.stopPropagation();
                const isOpen = dropdown.classList.toggle("is-open");
                toggleBtn.setAttribute("aria-expanded", isOpen);
                overlay.classList.toggle("is-active", isOpen);
            
                if (isOpen) {
                    resetActive();
                    if (mainItems[0] && subcategories[0]) {
                        mainItems[0].classList.add("is-active");
                        subcategories[0].classList.add("is-active");
                    }
                }
            });
          
            document.addEventListener("click", e => {
                if (!e.target.closest(".catalog-wrapper")) {
                    dropdown.classList.remove("is-open");
                    toggleBtn.setAttribute("aria-expanded", "false");
                    overlay.classList.remove("is-active");
                    resetActive();
                }
            });
          
            overlay.addEventListener("click", () => {
                dropdown.classList.remove("is-open");
                toggleBtn.setAttribute("aria-expanded", "false");
                overlay.classList.remove("is-active");
                resetActive();
            });
          
            mainItems.forEach(item => {
                item.addEventListener("mouseover", () => {
                    const id = item.dataset.category;
                    mainItems.forEach(i => i.classList.toggle("is-active", i === item));
                    subcategories.forEach(sub => {
                        sub.classList.toggle("is-active", sub.dataset.category === id);
                    });
                });
            });
          
            const submenuToggles = dropdown.querySelectorAll(".submenu-title a");
            submenuToggles.forEach(toggle => {
                toggle.addEventListener("click", e => {
                    const parent = toggle.closest(".submenu-block");
                    const grandchildren = parent.querySelector(".submenu-grandchildren");
                    if (grandchildren) {
                        e.preventDefault();
                        grandchildren.classList.toggle("is-open");
                        toggle.parentElement.classList.toggle("open");
                    }
                });
            });
          
            const allSubLinks = dropdown.querySelectorAll(".submenu-grandchildren li a");
            allSubLinks.forEach(link => {
                link.addEventListener("mouseover", () => link.classList.add("is-hovered"));
                link.addEventListener("mouseout", () => link.classList.remove("is-hovered"));
            });
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
                  <div class="logo logo-hover">
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
                        $product_count = wp_count_posts('products')->publish;
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
              
                  <button id="theme-toggle-button" class="theme-icon-button">
                    <span class="icon-sun"><?php echo file_get_contents(get_template_directory() . '/images/sun.svg'); ?></span>
                    <span class="icon-sun-solid"><?php echo file_get_contents(get_template_directory() . '/images/sun-solid.svg'); ?></span>
                    
                    <span class="icon-moon"><?php echo file_get_contents(get_template_directory() . '/images/moon.svg'); ?></span>
                    <span class="icon-moon-solid"><?php echo file_get_contents(get_template_directory() . '/images/moon-solid.svg'); ?></span>
                  </button>

                  <?php
                  $user = wp_get_current_user();
                  $is_logged_in = is_user_logged_in();
                  ?>

                  <?php if ($is_logged_in): ?>
                    <button id="dm-toggle-btn" class="dm-toggle-btn" aria-label="<?= t('Открыть чат', 'Open Chat', 'Deschide chat'); ?>">
                      <span class="icon-message"><?php echo file_get_contents(get_template_directory() . '/images/message.svg'); ?></span>
                      <span class="icon-message-solid"><?php echo file_get_contents(get_template_directory() . '/images/message-solid.svg'); ?></span>
                    </button>

                    <div class="user-menu">
                      <?php
                        $avatar_id = get_user_meta($user->ID, 'profile_avatar', true);
                        $avatar_url = $avatar_id ? wp_get_attachment_url($avatar_id) : get_avatar_url($user->ID);
                      ?>
                      <button class="user-avatar" id="user-avatar" aria-haspopup="true" aria-expanded="false" aria-label="<?= t('Меню пользователя', 'User menu', 'Meniu utilizator'); ?>">
                        <img src="<?= esc_url($avatar_url); ?>" alt="User Avatar">
                      </button>
                      <ul class="user-dropdown" id="user-dropdown">
                        <li class="label-small"><a href="/my-products" class="title-smaller"><?= t('Мои товары', 'My Products', 'Produsele mele'); ?></a></li>
                        <li class="label-small"><a href="/account/settings" class="title-smaller"><?= t('Настройки аккаунта', 'Account Settings', 'Setări cont'); ?></a></li>
                        <li class="label-small"><a href="/account/favorites" class="title-smaller"><?= t('Избраное', 'Favorites', 'Favoritele'); ?></a></li>
                        <li class="label-small"><a href="<?= wp_logout_url(home_url()); ?>" class="title-smaller"><?= t('Выход', 'Logout', 'Ieșire'); ?></a></li>
                      </ul>
                    </div>
                  <?php else: ?>
                      <a href="/account/login/" class="accent-button-small button-small"><?= t('Войти', 'Login', 'Autentificare'); ?></a>
                  <?php endif; ?>
                </div>
              </div>
            </div>
          </section>
                      
          <!-- Второй этаж -->
          <section class="header-bottom__wrapper" aria-label="<?= t('Основная навигация', 'Main Navigation', 'Navigație principală'); ?>">
            <div class="container-medium">
              <nav class="header-bottom" aria-label="<?= t('Навигационное меню', 'Navigation Menu', 'Meniu de navigare'); ?>">
                      
                <!-- Каталог -->
                <div class="catalog-wrapper">
                  <button class="secondary-button-small catalog-toggle-button"
                          id="catalogToggle"
                          type="button"
                          aria-haspopup="true"
                          aria-expanded="false"
                          aria-controls="catalogDropdown">
                    <?= t('Каталог', 'Catalog', 'Catalog'); ?>
                  </button>
                
                  <?php
                  $lang = $GLOBALS['language'] ?? 'ru';

                  $all_terms = get_terms([
                    'taxonomy'   => 'product_cat',
                    'hide_empty' => false,
                    'orderby'    => 'name',
                    'order'      => 'ASC',
                  ]);

                  $terms_hierarchy = [];
                  foreach ($all_terms as $term) {
                    $terms_hierarchy[$term->parent][] = $term;
                  }
                  ?>
                
                  <?php if (!empty($terms_hierarchy[0])) : ?>
                    <div class="catalog-dropdown" id="catalogDropdown">
                      <div class="catalog-inner container-medium">
                        <ul class="catalog-main">
                          <?php foreach ($terms_hierarchy[0] as $parent_term) : ?>
                            <li class="catalog-main__item title-medium" data-category="<?= $parent_term->term_id ?>">
                              <?= esc_html(get_category_name_translated($parent_term, $lang)) ?>
                            </li>
                          <?php endforeach; ?>
                        </ul>
                          
                        <div class="catalog-subcategories">
                          <?php foreach ($terms_hierarchy[0] as $parent_term) : ?>
                            <?php
                            $children = $terms_hierarchy[$parent_term->term_id] ?? [];
                            ?>
                            <div class="catalog-subcategories__item" data-category="<?= $parent_term->term_id ?>">
                              <?php if (!empty($children)) : ?>
                                <?php foreach ($children as $child) : ?>
                                  <?php
                                  $grandchildren = $terms_hierarchy[$child->term_id] ?? [];
                                  ?>
                                  <div class="submenu-block">
                                    <h4 class="submenu-title">
                                      <a class="title-small" href="<?= esc_url(get_term_link($child)) ?>">
                                        <?= esc_html(get_category_name_translated($child, $lang)) ?>
                                      </a>
                                    </h4>
                                
                                    <?php if (!empty($grandchildren)) : ?>
                                      <ul class="submenu-grandchildren">
                                        <?php foreach ($grandchildren as $grand) : ?>
                                          <li>
                                            <a class="link-button" href="<?= esc_url(get_term_link($grand)) ?>">
                                              <?= esc_html(get_category_name_translated($grand, $lang)) ?>
                                            </a>
                                          </li>
                                        <?php endforeach; ?>
                                      </ul>
                                    <?php endif; ?>
                                  </div>
                                <?php endforeach; ?>
                              <?php else : ?>
                                <p class="label-small"><?= t('Нет подкатегорий', 'No subcategories', 'Fără subcategorii') ?></p>
                              <?php endif; ?>
                            </div>
                          <?php endforeach; ?>
                        </div>
                      </div>
                    </div>
                  <?php endif; ?>
                  <div class="catalog-overlay"></div>
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
