<?php get_header(); ?>

<div class="404__wrapper content-main">
    <div class="container-medium">
        <main id="main" class="site-main content-main" style="text-align: center; padding: 100px 20px;">
            <h1 style="font-size: 96px; margin-bottom: 20px;">😲 О как!</h1>
            <p style="font-size: 24px; margin-bottom: 30px;">Кажется, вы попали не туда...<br>Страница не найдена.</p>

            <a href="<?php echo esc_url(home_url('/')); ?>" 
               style="display: inline-block; padding: 12px 24px; font-size: 18px; background-color: #0073aa; color: #fff; text-decoration: none; border-radius: 6px;">
               Вернуться на главную
            </a>

            <div style="margin-top: 60px;">
            </div>
        </main>
    </div>
</div>
<?php get_footer(); ?>
