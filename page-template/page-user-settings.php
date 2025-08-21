<?php
/**
 * Template Name: Настройки аккаунта
 */

get_header();

// Проверка авторизации
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

?>

<div class="user-settings__wrapper">
    <div class="container">
        <h2 class="user-settings__title display-small">
            <?php echo t('Настройки аккаунта', 'Account Settings', 'Setări cont'); ?>
        </h2>
    
        <form method="post" enctype="multipart/form-data" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
            <?php wp_nonce_field('save_user_settings', 'user_settings_nonce'); ?>
            <input type="hidden" name="action" value="save_user_settings">
    
            <div class="user-settings__field input-block">
                <label class="label-medium" for="display_name">
                    <?php echo t('Имя', 'Name', 'Nume'); ?>
                </label>
                <input type="text" name="display_name" id="display_name" 
                       value="<?php echo esc_attr($current_user->display_name); ?>" 
                       class="input--primary" placeholder=" ">
            </div>
    
            <div class="user-settings__field input-block">
                <label class="label-medium" for="user_email">
                    <?php echo t('Email', 'Email', 'Email'); ?>
                </label>
                <input type="email" name="user_email" id="user_email" 
                       value="<?php echo esc_attr($current_user->user_email); ?>" 
                       class="input--primary" placeholder=" ">
            </div>
    
            <div class="user-settings__field input-block">
              <label class="label-medium" for="avatar">
                <?php echo t('Фото профиля', 'Profile Photo', 'Fotografie de profil'); ?>
              </label>

              <div class="avatar-wrapper">
                <img id="avatar-preview" src="<?php echo esc_url($avatar_url); ?>" 
                     alt="<?php echo esc_attr($current_user->display_name); ?>">

                <input type="file" name="avatar" id="avatar" accept="image/*">
                <span class="edit-icon">✏️</span>
              </div>
            </div>
    
            <div class="user-settings__field input-block">
                <label class="label-medium" for="region">
                    <?php echo t('Регион', 'Region', 'Regiune'); ?>
                </label>
                <select name="region" id="region" class="select-tertiary <?php echo $region ? 'has-value' : ''; ?>">
                    <option value="">
                        <?php echo t('-- Выберите регион --', '-- Select region --', '-- Alegeți regiunea --'); ?>
                    </option>
                    <?php
                    $regions = get_moldova_regions();
                    foreach ($regions as $main) {
                        $main_label = $main['ru'];
                        echo '<option value="' . esc_attr($main_label) . '" ' . selected($region, $main_label, false) . '>' . esc_html($main_label) . '</option>';
                        if (!empty($main['sub'])) {
                            foreach ($main['sub'] as $sub) {
                                echo '<option value="' . esc_attr($sub['ru']) . '" ' . selected($region, $sub['ru'], false) . '>&nbsp;&nbsp;&nbsp;— ' . esc_html($sub['ru']) . '</option>';
                            }
                        }
                    }
                    ?>
                </select>
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

  wrapper.addEventListener("click", () => {
    fileInput.click(); // при клике на аватар открывается выбор файла
  });

  fileInput.addEventListener("change", function () {
    if (this.files && this.files[0]) {
      const reader = new FileReader();
      reader.onload = function (e) {
        avatarPreview.src = e.target.result; // сразу меняем картинку
      };
      reader.readAsDataURL(this.files[0]);
    }
  });
});
</script>


<?php get_footer(); ?>
