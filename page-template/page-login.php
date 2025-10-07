<?php
/* Template Name: Вход */
get_header();
?>

<div class="auth__wrapper content-main">
    <div class="container-xxsmall">
        <div class="login-form">
            <h1 class="auth__title display-small">
                <?php echo t('Вход', 'Login', 'Autentificare'); ?>
            </h1>

            <?php if (isset($_GET['login']) && $_GET['login'] === 'failed') : ?>
                <p class="body-small-semibold" style="color: red;">
                    <?php echo t('Неверное имя пользователя или пароль.', 'Invalid username or password.', 'Nume de utilizator sau parolă incorectă.'); ?>
                </p>
            <?php endif; ?>

            <form action="<?php echo esc_url($_SERVER['REQUEST_URI']); ?>" method="post" class="form-ui">
                <?php wp_nonce_field('login_action', 'login_nonce'); ?>

                <div class="auth__field input-block">
                    <label for="username" class="label-large">
                        <?php echo t('Имя пользователя или email', 'Username or Email', 'Nume utilizator sau Email'); ?>
                    </label>
                    <input type="text" name="username" required class="input--primary" placeholder="<?php echo t('Введите имя или email', 'Enter username or email', 'Introduceți numele sau emailul'); ?>">
                </div>

                <div class="auth__field input-block">
                    <label for="password" class="label-large">
                        <?php echo t('Пароль', 'Password', 'Parolă'); ?>
                    </label>
                    <input type="password" name="password" required class="input--primary" placeholder="<?php echo t('Введите пароль', 'Enter password', 'Introduceți parola'); ?>">
                </div>

                <div class="auth__field input-block">
                    <input type="submit" name="login" value="<?php echo t('Войти', 'Login', 'Autentificare'); ?>" class="primary-button-medium button-medium">
                </div>
            </form>

            <p class="body-small-regular auth__note">
                <?php echo t('Нет аккаунта?', 'Don’t have an account?', 'Nu ai un cont?'); ?>
                <a href="<?php echo site_url('/register'); ?>" class="link-button link-medium-underline">
                    <?php echo t('Зарегистрируйтесь', 'Register', 'Înregistrează-te'); ?>
                </a>
            </p>
        </div>
    </div>
</div>

<?php get_footer(); ?>
