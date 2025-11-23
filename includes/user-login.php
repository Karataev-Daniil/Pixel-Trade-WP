<?php
function handle_login() {
    $lang = $GLOBALS['language'] ?? 'ru';

    if (isset($_POST['login'])) {
        $username = sanitize_text_field($_POST['username']);
        $password = sanitize_text_field($_POST['password']);

        $user = get_user_by('login', $username);
        if (!$user) {
            $user = get_user_by('email', $username);
        }

        if ($user && wp_check_password($password, $user->user_pass, $user->ID)) {
            wp_set_auth_cookie($user->ID);

            if (get_user_meta($user->ID, '_was_approved', true)) {
                setcookie('approval_notice', '1', time() + 300, COOKIEPATH, COOKIE_DOMAIN);
                delete_user_meta($user->ID, '_was_approved');
            }

            wp_safe_redirect(home_url("/$lang/user/products/"));
            exit;
        } else {
            wp_safe_redirect(home_url("/$lang/login?login=failed"));
            exit;
        }
    }
}
add_action('init', 'handle_login');
