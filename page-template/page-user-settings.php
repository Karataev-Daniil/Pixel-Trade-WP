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
        <h1 class="user-settings__title display-small">
            <?php echo t('Настройки аккаунта', 'Account Settings', 'Setări cont'); ?>
        </h1>

        <form method="post" enctype="multipart/form-data" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
            <?php wp_nonce_field('save_user_settings', 'user_settings_nonce'); ?>
            <input type="hidden" name="action" value="save_user_settings">

            <div class="user-settings__field input-block">
                <label for="display_name"><?php echo t('Имя', 'Name', 'Nume'); ?></label>
                <input type="text" name="display_name" id="display_name" 
                       value="<?php echo esc_attr($current_user->display_name); ?>" 
                       class="input--primary" placeholder="">
            </div>

            <div class="user-settings__field input-block">
                <label for="user_email"><?php echo t('Email', 'Email', 'Email'); ?></label>
                <input type="email" name="user_email" id="user_email" 
                       value="<?php echo esc_attr($current_user->user_email); ?>" 
                       class="input--primary" placeholder="">
            </div>

            <!-- Аватар -->
            <div class="user-settings__field input-block">
                <label><?php echo t('Фото профиля', 'Profile Photo', 'Fotografie de profil'); ?></label>
                <div class="avatar-wrapper">
                    <img id="avatar-preview" src="<?php echo esc_url($avatar_url); ?>" 
                         alt="<?php echo esc_attr($current_user->display_name); ?>">
                    <input type="file" name="avatar" id="avatar" accept="image/*" style="display:none;">
                    <span class="edit-icon">✏️</span>
                </div>
            </div>

            <div class="user-settings__field input-block">
                <label><?php echo t('Баннер профиля', 'Profile Banner', 'Banner profil'); ?></label>
                <div class="banner-wrapper" style="position: relative; cursor: pointer; display: inline-block;">
                    <?php 
                    $banner_url = '';
                    if ($banner) {
                        $banner_url = is_numeric($banner) ? wp_get_attachment_url($banner) : esc_url($banner);
                    }
                    ?>
                    <img id="banner-preview" src="<?= $banner_url ? esc_url($banner_url) : ''; ?>" 
                         alt="<?= esc_attr($current_user->display_name); ?>" 
                         style="max-width:100%; display:block; <?= $banner_url ? '' : 'display:none;'; ?>">
                    <input type="file" name="banner" id="banner" accept="image/*" style="display:none;">
                    <span class="edit-icon" style="position:absolute; top:10px; right:10px;">✏️</span>
                </div>
            </div>

            <div class="user-settings__field input-block">
                <label for="description"><?php echo t('Описание', 'Description', 'Descriere'); ?></label>
                <textarea name="description" class="textarea--primary body-medium-regular"><?= esc_textarea($description); ?></textarea>
            </div>

            <div class="user-settings__field input-block">
                <label for="region"><?php echo t('Регион', 'Region', 'Regiune'); ?></label>
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
                <label for="phone"><?php echo t('Телефон', 'Phone', 'Telefon'); ?></label>
                <input type="text" name="phone" value="<?= esc_attr($phone); ?>" class="input--secondary" placeholder="">
            </div>

            <div class="user-settings__field input-block" style="margin-top: 20px;">
                <button type="submit" class="primary-button-medium">
                    <?php echo t('Сохранить изменения', 'Save Changes', 'Salvează modificările'); ?>
                </button>
            </div>
        </form>
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

  const bannerWrapper = document.querySelector(".banner-wrapper");
  const bannerInput = document.getElementById("banner");
  const bannerPreview = document.getElementById("banner-preview");
  bannerWrapper.addEventListener("click", () => bannerInput.click());
  bannerInput.addEventListener("change", function () {
    if (this.files && this.files[0]) {
      const reader = new FileReader();
      reader.onload = function (e) { 
          bannerPreview.src = e.target.result;
          bannerPreview.style.display = 'block';
      };
      reader.readAsDataURL(this.files[0]);
    }
  });
});
</script>

<?php get_footer(); ?>
