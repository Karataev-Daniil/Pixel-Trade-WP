<?php
function get_default_moldova_regions() {
    return [
        ['ru' => 'Кишинёв', 'en' => 'Chisinau', 'ro' => 'Chișinău', 'parent' => ''],
        ['ru' => 'Ботаника', 'en' => 'Botanica', 'ro' => 'Botanica', 'parent' => 'Кишинёв'],
        ['ru' => 'Рышкановка', 'en' => 'Riscani', 'ro' => 'Rîșcani', 'parent' => 'Кишинёв'],
        ['ru' => 'Буюканы', 'en' => 'Buiucani', 'ro' => 'Buiucani', 'parent' => 'Кишинёв'],
        ['ru' => 'Центр', 'en' => 'Centru', 'ro' => 'Centru', 'parent' => 'Кишинёв'],
        ['ru' => 'Чеканы', 'en' => 'Ciocana', 'ro' => 'Ciocana', 'parent' => 'Кишинёв'],
    ];
}

function get_moldova_regions() {
    $regions = get_option('available_regions_multi', []);
    if (empty($regions)) {
        $regions = get_default_moldova_regions();
        update_option('available_regions_multi', $regions);
    }
    return $regions;
}

function render_user_settings_page() {
    if (!current_user_can('manage_options')) return;

    if (isset($_POST['regions_nonce']) && wp_verify_nonce($_POST['regions_nonce'], 'save_regions')) {
        $regions = [];
        if (!empty($_POST['regions'])) {
            foreach ($_POST['regions'] as $region) {
                if (empty($region['ru']) && empty($region['en']) && empty($region['ro'])) {
                    continue;
                }
                $regions[] = [
                    'ru' => sanitize_text_field($region['ru']),
                    'en' => sanitize_text_field($region['en']),
                    'ro' => sanitize_text_field($region['ro']),
                    'parent' => sanitize_text_field($region['parent']),
                ];
            }
        }
        update_option('available_regions_multi', $regions);
        echo '<div class="updated"><p>Список регионов сохранён.</p></div>';
    }

    $regions = get_moldova_regions();
    ?>
    <div class="wrap">
        <h1>Настройки пользователей — Регионы</h1>
        <form method="post">
            <?php wp_nonce_field('save_regions', 'regions_nonce'); ?>
            <table class="widefat striped">
                <thead>
                <tr>
                    <th>Русский</th>
                    <th>English</th>
                    <th>Română</th>
                    <th>Родитель</th>
                </tr>
                </thead>
                <tbody id="regions-table">
                <?php foreach ($regions as $i => $region): ?>
                    <tr>
                        <td><input type="text" name="regions[<?php echo $i; ?>][ru]" value="<?php echo esc_attr($region['ru']); ?>" class="widefat"></td>
                        <td><input type="text" name="regions[<?php echo $i; ?>][en]" value="<?php echo esc_attr($region['en']); ?>" class="widefat"></td>
                        <td><input type="text" name="regions[<?php echo $i; ?>][ro]" value="<?php echo esc_attr($region['ro']); ?>" class="widefat"></td>
                        <td><input type="text" name="regions[<?php echo $i; ?>][parent]" value="<?php echo esc_attr($region['parent']); ?>" class="widefat" placeholder="например: Кишинёв"></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <p><button type="button" class="button" id="add-region">+ Добавить регион</button></p>
            <p><button type="submit" class="button button-primary">Сохранить</button></p>
        </form>
    </div>

    <script>
        (function($){
            $('#add-region').on('click', function(){
                let rowCount = $('#regions-table tr').length;
                $('#regions-table').append(`
                    <tr>
                        <td><input type="text" name="regions[${rowCount}][ru]" class="widefat"></td>
                        <td><input type="text" name="regions[${rowCount}][en]" class="widefat"></td>
                        <td><input type="text" name="regions[${rowCount}][ro]" class="widefat"></td>
                        <td><input type="text" name="regions[${rowCount}][parent]" class="widefat" placeholder="например: Кишинёв"></td>
                    </tr>
                `);
            });
        })(jQuery);
    </script>
    <?php
}

add_action('admin_post_save_user_settings', function() {
    if (!is_user_logged_in()) {
        wp_redirect(wp_login_url());
        exit;
    }

    $current_user = wp_get_current_user();
    $user_id = $current_user->ID;

    if (!isset($_POST['user_settings_nonce']) || !wp_verify_nonce($_POST['user_settings_nonce'], 'save_user_settings')) {
        wp_die(__('Ошибка проверки безопасности.'));
    }

    if (isset($_POST['display_name'])) {
        $display_name = sanitize_text_field($_POST['display_name']);
        wp_update_user([
            'ID' => $user_id,
            'display_name' => $display_name
        ]);
    }

    if (isset($_POST['user_email'])) {
        $user_email = sanitize_email($_POST['user_email']);
        if (!is_email($user_email)) {
            wp_die(__('Неверный email.'));
        }
        wp_update_user([
            'ID' => $user_id,
            'user_email' => $user_email
        ]);
    }

    if (isset($_POST['region'])) {
        $region = sanitize_text_field($_POST['region']);
        update_user_meta($user_id, 'region', $region);
    }

    if (isset($_POST['description'])) {
        update_user_meta($user_id, 'description', sanitize_textarea_field($_POST['description']));
    }

    if (isset($_POST['phone'])) {
        update_user_meta($user_id, 'phone', sanitize_text_field($_POST['phone']));
    }

    if (isset($_POST['phone_visibility']) && in_array($_POST['phone_visibility'], ['all','registered','none'])) {
        update_user_meta($user_id, 'phone_visibility', $_POST['phone_visibility']);
    } else {
        update_user_meta($user_id, 'phone_visibility', 'none');
    }

    if (isset($_FILES['avatar']) && !empty($_FILES['avatar']['name'])) {
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        require_once(ABSPATH . 'wp-admin/includes/media.php');

        $file = $_FILES['avatar'];

        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        $file['name'] = 'avatar-' . $user_id . '-' . wp_generate_password(8, false) . '.' . $ext;

        if ($file['size'] > 500 * 1024) {
            $image = wp_get_image_editor($file['tmp_name']);
            if (!is_wp_error($image)) {
                $mime = $file['type'];
                if (in_array($mime, ['image/jpeg','image/jpg','image/webp'])) {
                    $image->set_quality(80);
                }
                $image->save($file['tmp_name']);
            }
        }

        $attachment_id = media_handle_sideload($file, 0);
        if (!is_wp_error($attachment_id)) {
            update_user_meta($user_id, 'profile_avatar', $attachment_id);
        } else {
            wp_die(__('Ошибка загрузки аватара: ') . $attachment_id->get_error_message());
        }
    }

    add_filter('get_avatar', function ($avatar, $id_or_email, $size, $default, $alt) {
        $user = false;

        if (is_numeric($id_or_email)) {
            $user_id = (int) $id_or_email;
            $user = get_user_by('id', $user_id);
        } elseif (is_object($id_or_email) && !empty($id_or_email->user_id)) {
            $user_id = (int) $id_or_email->user_id;
            $user = get_user_by('id', $user_id);
        } elseif (is_string($id_or_email)) {
            $user = get_user_by('email', $id_or_email);
        }

        if ($user) {
            $avatar_id = get_user_meta($user->ID, 'profile_avatar', true);
            if ($avatar_id) {
                $avatar_url = wp_get_attachment_url($avatar_id);
                return "<img alt='" . esc_attr($alt) . "' src='" . esc_url($avatar_url) . "' class='avatar avatar-{$size} photo' height='{$size}' width='{$size}' />";
            }
        }

        return $avatar;
    }, 10, 5);

        // ✅ редирект на страницу пользователя
    $user = get_userdata($user_id);
    $user_nicename = $user->user_nicename;

    // Если у тебя кастомный роут вида /user/{slug}
    $redirect_url = home_url('/user/' . $user_nicename . '/');

    // Если хочешь стандартный WordPress /author/{slug}, то:
    // $redirect_url = get_author_posts_url($user_id);

    wp_redirect($redirect_url);
    exit;

});


add_action('admin_post_update_user_banner', 'handle_update_user_banner');

function handle_update_user_banner() {
    if (!is_user_logged_in()) {
        wp_die('Нет доступа');
    }

    if (!isset($_POST['update_user_banner_nonce']) || !wp_verify_nonce($_POST['update_user_banner_nonce'], 'update_user_banner')) {
        wp_die('Ошибка безопасности');
    }

    $user_id = get_current_user_id();

    if (isset($_FILES['banner']) && !empty($_FILES['banner']['name'])) {
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        require_once(ABSPATH . 'wp-admin/includes/media.php');

        if ($_FILES['banner']['size'] > 200 * 1024) {
            $image = wp_get_image_editor($_FILES['banner']['tmp_name']);
            if (!is_wp_error($image)) {
                $mime = $_FILES['banner']['type'];
                if ($mime === 'image/jpeg' || $mime === 'image/jpg' || $mime === 'image/webp') {
                    $image->set_quality(80);
                }
                $image->save($_FILES['banner']['tmp_name']);
            }
        }

        $attachment_id = media_handle_upload('banner', 0);

        if (!is_wp_error($attachment_id)) {
            $old_banner = get_user_meta($user_id, 'banner', true);
            if ($old_banner && $old_banner != $attachment_id) {
                wp_delete_attachment($old_banner, true);
            }

            update_user_meta($user_id, 'banner', $attachment_id);
        } else {
            wp_die('Ошибка загрузки: ' . $attachment_id->get_error_message());
        }
    }

    wp_safe_redirect(wp_get_referer());
    exit;
}