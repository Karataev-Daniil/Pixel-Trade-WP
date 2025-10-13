<?php
/**
 * Template Name: Настройки аккаунта
 */
get_header();

if (!is_user_logged_in()) {
    echo '<div class="container"><p>' . sprintf(
        __('Пожалуйста, <a href="%s">войдите</a>, чтобы управлять аккаунтом.', 'your-text-domain'),
        wp_login_url()
    ) . '</p></div>';
    get_footer();
    exit;
}

$current_user = wp_get_current_user();
$user_id = $current_user->ID;
$region = get_user_meta($user_id, 'region', true);
$avatar_id = get_user_meta($user_id, 'profile_avatar', true);
$avatar_url = $avatar_id ? wp_get_attachment_url($avatar_id) : get_avatar_url($user_id);
$banner = get_user_meta($user_id, 'banner', true);
$description = get_user_meta($user_id, 'description', true);
$phone = get_user_meta($user_id, 'phone', true);
?>

<div class="user-settings__wrapper content-main">
    <div class="container-xxsmall">
        <main>
            <h1 class="user-settings__title display-small">
                <?php echo t('Настройки аккаунта', 'Account Settings', 'Setări cont'); ?>
            </h1>

            <form method="post" enctype="multipart/form-data" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <?php wp_nonce_field('save_user_settings', 'user_settings_nonce'); ?>
                <input type="hidden" name="action" value="save_user_settings">

                <div class="user-settings__field input-block">
                    <label class="label-large" for="display_name"><?php echo t('Имя', 'Name', 'Nume'); ?></label>
                    <input type="text" name="display_name" id="display_name" 
                           value="<?php echo esc_attr($current_user->display_name); ?>" 
                           class="input--primary" placeholder="">
                </div>

                <div class="user-settings__field input-block">
                    <label class="label-large" for="user_email"><?php echo t('Email', 'Email', 'Email'); ?></label>
                    <input type="email" name="user_email" id="user_email" 
                           value="<?php echo esc_attr($current_user->user_email); ?>" 
                           class="input--primary" placeholder="">
                </div>
                
                <div class="">
                    <label class="label-large"><?php echo t('Фото профиля', 'Profile Photo', 'Fotografie de profil'); ?></label>
                    <div class="avatar-wrapper">
                        <img id="avatar-preview" src="<?php echo esc_url($avatar_url); ?>" 
                             alt="<?php echo esc_attr($current_user->display_name); ?>">
                        <input type="file" name="avatar" id="avatar" accept="image/*" style="display:none;">
                        <span class="edit-icon">
                            <svg viewBox="0 0 24 24">
                                <path d="M3 17.25V21h3.75l11.06-11.06-3.75-3.75L3 17.25zM21.41 6.34a1 1 0 0 0 0-1.41l-2.34-2.34a1 1 0 0 0-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83z"/>
                            </svg>
                        </span>
                    </div>
                </div>

                <div class="user-settings__field input-block">
                    <label class="label-large" for="description"><?php echo t('Описание', 'Description', 'Descriere'); ?></label>
                    <textarea name="description" class="textarea--primary body-medium-regular"><?= esc_textarea($description); ?></textarea>
                </div>

                <div class="user-settings__field input-block">
                    <label class="label-large" for="region"><?php echo t('Регион', 'Region', 'Regiune'); ?></label>
                    <select name="region" id="region" class="select--primary <?php echo $region ? 'has-value' : ''; ?>">
                        <option value=""><?= t('-- Выберите регион --', '-- Select region --', '-- Alegeți regiunea --'); ?></option>
                        <?php
                        $regions = get_moldova_regions();
                        foreach ($regions as $r) {
                            $selected = selected($region, $r['ru'], false);
                            echo "<option value='" . esc_attr($r['ru']) . "' $selected>" . esc_html($r['ru']) . "</option>";
                        }
                        ?>
                    </select>
                </div>

                <div class="user-settings__field input-block">
                    <label class="label-large" for="phone">
                        <?= t('Телефон', 'Phone', 'Telefon'); ?>
                    </label>
                    <input type="text" name="phone" value="<?= esc_attr($phone); ?>" class="input--primary" placeholder="">
                                    
                    <label>
                        <input type="checkbox" name="phone_visibility" value="all" 
                            <?= checked(get_user_meta($user_id, 'phone_visibility', true), 'all'); ?>>
                        <?= t(
                            'Показывать номер телефона в моих объявлениях', 
                            'Show phone number in my listings', 
                            'Afișează numărul de telefon în anunțurile mele'
                        ); ?>
                    </label>
                </div>

                <div class="user-settings__field input-block" style="margin-top: 20px;">
                    <button type="submit" class="primary-button-medium">
                        <?php echo t('Сохранить изменения', 'Save Changes', 'Salvează modificările'); ?>
                    </button>
                </div>
            </form>
        </main>
    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function () {
  const fileInput = document.getElementById("avatar");
  const avatarPreview = document.getElementById("avatar-preview");
  const wrapper = document.querySelector(".avatar-wrapper");
  wrapper.addEventListener("click", () => fileInput.click());
  fileInput.addEventListener("change", function () {
    if (this.files && this.files[0]) {
      const reader = new FileReader();
      reader.onload = function (e) { avatarPreview.src = e.target.result; };
      reader.readAsDataURL(this.files[0]);
    }
  });
});
</script>

<?php get_footer(); ?>
