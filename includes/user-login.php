<?php
function kayo_handle_login() {
    if (isset($_POST['kayo_login'])) {
        $username = sanitize_text_field($_POST['username']);
        $password = sanitize_text_field($_POST['password']);

        $user = get_user_by('login', $username);
        if (!$user) {
            $user = get_user_by('email', $username);
        }

        if ($user && wp_check_password($password, $user->user_pass, $user->ID)) {
            wp_set_auth_cookie($user->ID);

            if (get_user_meta($user->ID, '_was_approved', true)) {
                setcookie('kayo_approval_notice', '1', time() + 300, COOKIEPATH, COOKIE_DOMAIN);
                delete_user_meta($user->ID, '_was_approved');
            }

            wp_redirect(home_url('/my-products'));
            exit;
        } else {
            wp_redirect(home_url('/login?login=failed'));
            exit;
        }
    }
}
add_action('init', 'kayo_handle_login');


function kayo_show_approval_popup() {
    if (is_user_logged_in() && isset($_COOKIE['kayo_approval_notice'])) {
        ?>
        <div id="kayo-approval-popup" style="position:fixed;top:20px;right:20px;background:#4CAF50;color:#fff;padding:15px;border-radius:5px;z-index:1000;">
            <p>Ваш аккаунт продавца был одобрен! Теперь вы можете публиковать товары.</p>
            <button onclick="this.parentElement.remove();" style="margin-top:10px;background:#fff;color:#4CAF50;border:none;padding:5px 10px;border-radius:3px;cursor:pointer;">OK</button>
        </div>
        <script>
            setTimeout(() => {
                const popup = document.getElementById('kayo-approval-popup');
                if (popup) popup.remove();
            }, 10000);
        </script>
        <?php
        setcookie('kayo_approval_notice', '', time() - 3600, COOKIEPATH, COOKIE_DOMAIN);
    }
}
add_action('wp_footer', 'kayo_show_approval_popup');
