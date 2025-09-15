<?php
// user-registration.php
function handle_registration() {
    if (isset($_POST['register'])) {

        // ✅ Проверка nonce
        if (
            !isset($_POST['user_registration_nonce']) ||
            !wp_verify_nonce($_POST['user_registration_nonce'], 'user_registration_action')
        ) {
            wp_die('Ошибка безопасности. Попробуйте снова.');
        }

        $username = sanitize_user($_POST['reg_username'] ?? '');
        $email    = sanitize_email($_POST['reg_email'] ?? '');
        $password = $_POST['reg_password'] ?? '';

        $errors = new WP_Error();

        // ✅ Проверка обязательных полей
        if (empty($username) || empty($email) || empty($password)) {
            $errors->add('empty_fields', 'Пожалуйста, заполните все поля.');
        }

        // ✅ Проверка занятости
        if (username_exists($username) || email_exists($email)) {
            $errors->add('user_exists', 'Имя пользователя или email уже заняты.');
        }

        // ✅ Проверка сложности пароля
        if (
            strlen($password) < 8 ||
            !preg_match('/[A-Z]/', $password) ||
            !preg_match('/[0-9]/', $password)
        ) {
            $errors->add('weak_password', 'Пароль должен содержать минимум 8 символов, цифру и заглавную букву.');
        }

        // ✅ Если нет ошибок — создаем пользователя
        if (!$errors->has_errors()) {
            $user_id = wp_create_user($username, $password, $email);

            if (is_wp_error($user_id)) {
                $errors->add('create_user_failed', $user_id->get_error_message());
            } else {
                // Назначаем роль "buyer" и метку ожидания
                wp_update_user([
                    'ID'   => $user_id,
                    'role' => 'buyer'
                ]);

                update_user_meta($user_id, 'pending_role', 'seller');

                // ⚠️ Убираем автологин для безопасности
                // wp_set_current_user($user_id);
                // wp_set_auth_cookie($user_id);

                // Редирект с сообщением об успехе
                wp_redirect(add_query_arg('register', 'success', home_url('/')));
                exit;
            }
        }

        // Если есть ошибки
        wp_redirect(add_query_arg('register', 'error', wp_get_referer()));
        exit;
    }
}
add_action('init', 'handle_registration');

// functions.php или отдельный файл
add_action('wp_ajax_nopriv_ajax_register_user', 'ajax_register_user');
add_action('wp_ajax_ajax_register_user', 'ajax_register_user');

function ajax_register_user() {
    check_ajax_referer('user_registration_action', 'security');

    $username = sanitize_user($_POST['reg_username'] ?? '');
    $email    = sanitize_email($_POST['reg_email'] ?? '');
    $password = $_POST['reg_password'] ?? '';

    $errors = new WP_Error();

    // Проверка обязательных полей
    if (empty($username) || empty($email) || empty($password)) {
        $errors->add('empty_fields', 'Пожалуйста, заполните все поля.');
    }

    // Проверка занятости
    if (username_exists($username) || email_exists($email)) {
        $errors->add('user_exists', 'Имя пользователя или email уже заняты.');
    }

    // Проверка сложности пароля
    if (
        strlen($password) < 8 ||
        !preg_match('/[A-Z]/', $password) ||
        !preg_match('/[0-9]/', $password)
    ) {
        $errors->add('weak_password', 'Пароль должен содержать минимум 8 символов, цифру и заглавную букву.');
    }

    if ($errors->has_errors()) {
        wp_send_json_error($errors->get_error_messages());
    }

    $user_id = wp_create_user($username, $password, $email);
    if (is_wp_error($user_id)) {
        wp_send_json_error([$user_id->get_error_message()]);
    }

    wp_update_user([
        'ID'   => $user_id,
        'role' => 'buyer'
    ]);
    update_user_meta($user_id, 'pending_role', 'seller');

    wp_send_json_success([
        'message' => 'Регистрация успешна! Ожидайте одобрения администрации.'
    ]);
}
