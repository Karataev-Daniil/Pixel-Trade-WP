<?php
/* Template Name: Регистрация */
get_header();
?>

<div class="auth__wrapper">
    <div class="container-xxsmall">
        <div class="register-form">
            <h2 class="auth__title display-small">
                <?php echo t('Регистрация', 'Registration', 'Înregistrare'); ?>
            </h2>

            <?php if (isset($_GET['register']) && $_GET['register'] === 'exists') : ?>
                <p class="body-small-semibold" style="color: red;">
                    <?php echo t('Пользователь с таким email уже существует.', 'A user with this email already exists.', 'Un utilizator cu acest email există deja.'); ?>
                </p>
            <?php endif; ?>

            <form action="<?php echo esc_url($_SERVER['REQUEST_URI']); ?>" method="post" class="form-ui">
                <div class="auth__field input-block">
                    <label for="reg_username" class="label-large">
                        <?php echo t('Имя пользователя', 'Username', 'Nume utilizator'); ?>
                    </label>
                    <input type="text" name="reg_username" id="reg_username" required class="input--primary" placeholder="<?php echo t('Введите имя', 'Enter username', 'Introduceți numele'); ?>">
                </div>

                <div class="auth__field input-block">
                    <label for="reg_email" class="label-large">
                        <?php echo t('Email', 'Email', 'Email'); ?>
                    </label>
                    <input type="email" name="reg_email" id="reg_email" required class="input--primary" placeholder="example@mail.com">
                </div>

                <div class="auth__field input-block">
                    <label for="reg_password" class="label-large">
                        <?php echo t('Пароль', 'Password', 'Parolă'); ?>
                    </label>
                    <input type="password" name="reg_password" id="reg_password" required class="input--primary" placeholder="<?php echo t('Введите пароль', 'Enter password', 'Introduceți parola'); ?>">
                </div>

                <div class="auth__field input-block">
                    <input type="submit" name="register" value="<?php echo t('Зарегистрироваться', 'Sign Up', 'Înregistrează-te'); ?>" class="primary-button-medium button-medium">
                </div>
            </form>

            <p class="body-small-regular auth__note">
                <?php echo t('Уже есть аккаунт?', 'Already have an account?', 'Ai deja un cont?'); ?>
                <a href="<?php echo site_url('/login'); ?>" class="link-button link-medium-underline">
                    <?php echo t('Войти', 'Login', 'Autentificare'); ?>
                </a>
            </p>
        </div>
    </div>
</div>

<?php get_footer(); ?>
