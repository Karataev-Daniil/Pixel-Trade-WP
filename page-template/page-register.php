<?php
/* Template Name: Регистрация */
get_header();
?>

<div class="auth__wrapper content-main">
    <div class="container-xxsmall">
        <div class="register-form">
            <h1 class="auth__title display-small">
                <?php echo t('Регистрация', 'Registration', 'Înregistrare'); ?>
            </h1>

            <?php if (isset($_GET['register']) && $_GET['register'] === 'success') : ?>
                <div id="register-popup" class="popup">
                    <div class="popup__content">
                        <p class="body-small-semibold" style="color: green;">
                            <?php echo t(
                                'Регистрация успешна! Ожидайте одобрения администрации. Вас одобрят в течение 2х часов.',
                                'Registration successful! Please wait for admin approval. You will be approved within 2 hours.',
                                'Înregistrarea a fost efectuată cu succes! Așteptați aprobarea administrației. Veți fi aprobat în termen de 2 ore.'
                            ); ?>
                        </p>
                        <button id="popup-close" class="primary-button-medium">
                            <?php echo t('Закрыть', 'Close', 'Închide'); ?>
                        </button>
                    </div>
                </div>
                        
                <script>
                    document.getElementById('popup-close').addEventListener('click', function() {
                        window.location.href = "<?php echo esc_url(home_url('/')); ?>";
                    });
                </script>
            <?php endif; ?>

            <form action="<?php echo esc_url($_SERVER['REQUEST_URI']); ?>" method="post" class="form-ui">
                <?php wp_nonce_field('user_registration_action', 'user_registration_nonce'); ?>

                <div class="auth__field input-block">
                    <label for="reg_username" class="label-large">
                        <?php echo t('Имя пользователя', 'Username', 'Nume utilizator'); ?>
                    </label>
                    <input type="text" name="reg_username" id="reg_username" class="input--primary" placeholder="<?php echo t('Введите имя', 'Enter username', 'Introduceți numele'); ?>">
                    <div class="field-message" id="reg_username_message"></div>
                </div>

                <div class="auth__field input-block">
                    <label for="reg_email" class="label-large">
                        <?php echo t('Email', 'Email', 'Email'); ?>
                    </label>
                    <input type="email" name="reg_email" id="reg_email" class="input--primary" placeholder="example@mail.com">
                    <div class="field-message" id="reg_email_message"></div>
                </div>

                <div class="auth__field input-block">
                    <label for="reg_password" class="label-large">
                        <?php echo t('Пароль', 'Password', 'Parolă'); ?>
                    </label>
                    <input type="password" name="reg_password" id="reg_password" class="input--primary" placeholder="<?php echo t('Введите пароль', 'Enter password', 'Introduceți parola'); ?>">
                    <div class="password-hint body-small-regular">
                        Пароль должен содержать:
                        <ul>
                            <li id="pw_length">минимум 8 символов</li>
                            <li id="pw_upper">хотя бы одну заглавную букву</li>
                            <li id="pw_digit">хотя бы одну цифру</li>
                        </ul>
                    </div>
                    <div class="field-message" id="reg_password_message"></div>
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

<script>
document.addEventListener('DOMContentLoaded', function () {
    const form = document.querySelector('.form-ui');
    const usernameInput = document.getElementById('reg_username');
    const emailInput = document.getElementById('reg_email');
    const passwordInput = document.getElementById('reg_password');

    // Вспомогательные функции
    function setFieldMessage(fieldId, message, type) {
        const msgBlock = document.getElementById(fieldId + "_message");
        if (msgBlock) {
            msgBlock.textContent = message;
            msgBlock.className = "form-message " + type; // используем CSS классы
        }
    }

    function clearFieldMessage(fieldId) {
        const msgBlock = document.getElementById(fieldId + "_message");
        if (msgBlock) {
            msgBlock.textContent = "";
            msgBlock.className = "form-message"; // сброс к дефолту
        }
    }

    // Подсветка требований к паролю на лету
    passwordInput.addEventListener("input", function () {
        const value = passwordInput.value;
        document.getElementById("pw_length").style.color = value.length >= 8 ? "green" : "red";
        document.getElementById("pw_upper").style.color = /[A-Z]/.test(value) ? "green" : "red";
        document.getElementById("pw_digit").style.color = /[0-9]/.test(value) ? "green" : "red";
    });

    function validateForm() {
        let valid = true;

        clearFieldMessage('reg_username');
        clearFieldMessage('reg_email');
        clearFieldMessage('reg_password');

        // Проверка имени пользователя
        if (usernameInput.value.trim().length < 3) {
            setFieldMessage('reg_username', 'Имя пользователя должно содержать минимум 3 символа.', 'error');
            valid = false;
        }

        // Проверка email
        const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailPattern.test(emailInput.value.trim())) {
            setFieldMessage('reg_email', 'Введите корректный email.', 'error');
            valid = false;
        }

        // Проверка пароля
        const password = passwordInput.value;
        if (password.length < 8) {
            setFieldMessage('reg_password', 'Пароль должен содержать минимум 8 символов.', 'error');
            valid = false;
        } else if (!/[A-Z]/.test(password)) {
            setFieldMessage('reg_password', 'Пароль должен содержать хотя бы одну заглавную букву.', 'error');
            valid = false;
        } else if (!/[0-9]/.test(password)) {
            setFieldMessage('reg_password', 'Пароль должен содержать хотя бы одну цифру.', 'error');
            valid = false;
        }

        return valid;
    }

    // Отправка формы
    form.addEventListener('submit', function (e) {
        e.preventDefault();

        if (!validateForm()) return; // 🚫 если не прошло валидацию — не отправляем

        const formData = new FormData(form);
        formData.append('action', 'ajax_register_user');

        fetch("<?php echo admin_url('admin-ajax.php'); ?>", {
            method: "POST",
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if (!data.success) {
                data.data.forEach(msg => {
                    if (msg.includes('Имя пользователя')) {
                        setFieldMessage('reg_username', msg, 'error');
                    } else if (msg.includes('email')) {
                        setFieldMessage('reg_email', msg, 'error');
                    } else if (msg.includes('Пароль')) {
                        setFieldMessage('reg_password', msg, 'error');
                    } else {
                        showPopup({
                            title: "Ошибка",
                            message: msg,
                            type: "danger"
                        });
                    }
                });
            } else {
                showPopup({
                    title: "Успешно!",
                    message: data.data.message,
                    type: "success",
                    buttons: [{
                        text: "Ок",
                        className: "primary",
                        callback: () => {
                            window.location.href = "<?php echo esc_url(home_url('/')); ?>";
                        }
                    }]
                });
                form.reset();
            }
        })
        .catch(() => {
            showPopup({
                title: "Ошибка сети",
                message: "Не удалось отправить форму. Попробуйте позже.",
                type: "danger"
            });
        });
    });
});

</script>

<?php get_footer(); ?>
