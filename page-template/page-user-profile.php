<?php
get_header();

$user_nicename = get_query_var('user_profile');
$user = get_user_by('slug', $user_nicename);

if (!$user) {
    echo '<p>User not found.</p>';
    get_footer();
    exit;
}

$user_id = $user->ID;

$avatar_id   = get_user_meta($user_id, 'profile_avatar', true);
$banner_id   = get_user_meta($user_id, 'banner', true);
$description = get_user_meta($user_id, 'description', true);
$region      = get_user_meta($user_id, 'region', true);
$phone       = get_user_meta($user_id, 'phone', true);

$avatar_img  = $avatar_id
    ? wp_get_attachment_image($avatar_id, 'avatar-large', false, ['alt' => 'User Avatar'])
    : get_avatar($user_id, 120, '', 'User Avatar');

$banner_url = $banner_id ? wp_get_attachment_url($banner_id) : '';
$is_owner   = is_user_logged_in() && get_current_user_id() === $user_id;
?>

<div class="profile__wrapper content-main">
    <div class="container-medium">
        <main class="user-profile layout-two-columns">
            <aside class="profile-sidebar">
                <div class="avatar-frame"><?= $avatar_img; ?></div>
                <h1 class="user-name title-larger"><?php echo esc_html($user->display_name); ?></h1>
                
                <?php if ($description): ?>
                    <p class="description body-medium-regular"><?php echo esc_html($description); ?></p>
                <?php endif; ?>
                
                <div class="meta body-small-regular">
                    <?php if ($region): ?>
                        <p><strong>Регион:</strong> <?= esc_html($region); ?></p>
                    <?php endif; ?>
                    <?php if ($phone): ?>
                        <p><strong>Телефон:</strong> <?= esc_html($phone); ?></p>
                    <?php endif; ?>
                </div>
                
                <?php if ($is_owner): ?>
                    <a href="<?php echo esc_url(site_url('/user/settings')); ?>" class="button primary-button-small">
                        Редактировать профиль
                    </a>
                <?php endif; ?>
            </aside>

            <section class="profile-main">
                <div class="banner-wrapper">
                    <div class="banner" style="background-image:url('<?php echo esc_url($banner_url); ?>');">
                        <?php if ($is_owner): ?>
                            <form method="post" enctype="multipart/form-data" 
                                  action="<?php echo esc_url(admin_url('admin-post.php')); ?>" 
                                  class="banner-form">
                                <?php wp_nonce_field('update_user_banner', 'update_user_banner_nonce'); ?>
                                <input type="hidden" name="action" value="update_user_banner">
                                <input type="file" name="banner" id="banner-upload" accept="image/*" style="display:none;" onchange="this.form.submit();">
                                
                                <button type="button" 
                                        class="banner-edit-btn button secondary-button-small button-small"
                                        onclick="document.getElementById('banner-upload').click();">
                                    <?php
                                    $icon_path = get_template_directory_uri() . '/images/camera.svg';
                                    echo file_get_contents( get_template_directory() . '/images/camera.svg' );
                                    ?>
                                    <?php echo $banner_url ? 'Сменить баннер' : 'Загрузить баннер'; ?>
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="user-products">
                    <h2 class="display-small">Товары пользователя</h2>
                    
                    <?php
                    $args = [
                        'post_type' => 'products',
                        'author' => $user_id,
                        'posts_per_page' => -1,
                        'orderby' => 'date',
                        'order' => 'DESC',
                    ];
                    $products = new WP_Query($args);
                    
                    if ($products->have_posts()) {
                        echo '<div class="products-grid products-list">';
                        while ($products->have_posts()) {
                            $products->the_post();
                            get_template_part('template-parts/product/card');
                        }
                        echo '</div>';
                    } else {
                        echo '<p class="body-medium-regular">У пользователя нет товаров.</p>';
                    }
                    wp_reset_postdata();
                    ?>
                </div>
            </section>
        </main>
    </div>
</div>

<?php get_footer(); ?>
