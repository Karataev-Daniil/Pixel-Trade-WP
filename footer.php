    <footer class="footer" role="contentinfo">
      <div class="footer__wrapper">
        <div class="container-medium">
          <div class="footer__top">

            <div class="footer__logo-block">
              <a href="/" class="footer__logo" aria-label="На главную">
                <?php echo file_get_contents(get_template_directory() . '/images/logo.svg'); ?>
              </a>
              <p class="body-small-regular">
                <?= t('Надежный маркетплейс для Молдовы', 'A trusted marketplace for Moldova', 'O piață de încredere pentru Moldova'); ?>
              </p>
            </div>

            <div class="footer__nav">
              <div class="footer__column">
                <h4 class="label-medium"><?= t('Маркетплейс', 'Marketplace', 'Piață'); ?></h4>
                <ul>
                  <li><a class="link-button" href="/catalog"><?= t('Каталог', 'Catalog', 'Catalog'); ?></a></li>
                  <li><a class="link-button" href="/add-product"><?= t('Подать объявление', 'Post an Ad', 'Adaugă anunț'); ?></a></li>
                  <li><a class="link-button" href="/blog"><?= t('Блог', 'Blog', 'Blog'); ?></a></li>
                </ul>
              </div>
              <div class="footer__column">
                <h4 class="label-medium"><?= t('Поддержка', 'Support', 'Suport'); ?></h4>
                <ul>
                  <li><a class="link-button" href="/help"><?= t('Центр помощи', 'Help Center', 'Centru de ajutor'); ?></a></li>
                  <li><a class="link-button" href="/rules"><?= t('Правила размещения', 'Posting Rules', 'Reguli de postare'); ?></a></li>
                  <li><a class="link-button" href="/contacts"><?= t('Связаться с нами', 'Contact Us', 'Contactează-ne'); ?></a></li>
                </ul>
              </div>
              <div class="footer__column">
                <h4 class="label-medium"><?= t('Аккаунт', 'user', 'Contul meu'); ?></h4>
                <ul>
                  <li><a class="link-button" href="/user/login"><?= t('Вход', 'Login', 'Autentificare'); ?></a></li>
                  <li><a class="link-button" href="/user/settings"><?= t('Настройки', 'Settings', 'Setări'); ?></a></li>
                  <li><a class="link-button" href="/my-products"><?= t('Мои товары', 'My Products', 'Produsele mele'); ?></a></li>
                </ul>
              </div>
            </div>
          </div>
        </div>

        <div class="footer__bottom-wrapper">
          <div class="container-medium">
            <div class="footer__bottom">
              <!-- <div class="footer__social">
                <a href="https://facebook.com" target="_blank" aria-label="Facebook"><?php echo file_get_contents(get_template_directory() . '/images/icon-facebook.svg'); ?></a>
                <a href="https://instagram.com" target="_blank" aria-label="Instagram"><?php echo file_get_contents(get_template_directory() . '/images/icon-instagram.svg'); ?></a>
              </div> -->
              <div class="footer__copy body-small-regular">
                &copy; <?= date('Y'); ?> <?= get_bloginfo('name'); ?>. <?= t('Все права защищены.', 'All rights reserved.', 'Toate drepturile rezervate.'); ?>
              </div>
            </div>
          </div>
        </div>
      </div>
    </footer>
    <div id="chatbot-root"></div>
    <div id="simple-dm-root" class="dm-root"></div>
    <?php wp_footer(); ?>
</body>
</html>