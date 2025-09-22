<?php
get_header();

$user_nicename = get_query_var('user_profile');
$user = get_user_by('slug', $user_nicename);

if (!$user) {
    echo '<p>User not found.</p>';
    get_footer();
    exit;
}

// Данные пользователя
$user_id = $user->ID;
$avatar_id = get_user_meta($user_id, 'profile_avatar', true);
$banner_id = get_user_meta($user_id, 'banner', true);
$description = get_user_meta($user_id, 'description', true);
$region = get_user_meta($user_id, 'region', true);
$phone = get_user_meta($user_id, 'phone', true);

$avatar_img = $avatar_id ? wp_get_attachment_image($avatar_id, 'medium-thumb', false, ['alt' => 'User Avatar'])
                          : get_avatar($user_id, 120, '', 'User Avatar');

$banner_url = $banner_id ? wp_get_attachment_url($banner_id) : '';
$is_owner = is_user_logged_in() && get_current_user_id() === $user_id;
?>

<div class="content-main">
    <div class="container-medium">
        <main>
            <div class="user-profile">
                <?php if ($banner_url): ?>
                    <div class="banner" style="background-image:url('<?php echo esc_url($banner_url); ?>');"></div>
                <?php endif; ?>

                <div class="profile-info">
                    <div class="avatar-frame">
                        <?= $avatar_img; ?>
                    </div>
                    <h1><?php echo esc_html($user->display_name); ?></h1>

                    <?php if ($description): ?>
                        <p><?php echo esc_html($description); ?></p>
                    <?php endif; ?>

                    <?php if ($region): ?>
                        <p><strong>Регион:</strong> <?= esc_html($region); ?></p>
                    <?php endif; ?>

                    <?php if ($phone): ?>
                        <p><strong>Телефон:</strong> <?= esc_html($phone); ?></p>
                    <?php endif; ?>

                    <?php if ($is_owner): ?>
                        <p>
                            <a href="<?php echo esc_url(site_url('/account/settings')); ?>" class="primary-button-medium">
                                Редактировать профиль
                            </a>
                        </p>
                    <?php endif; ?>
                </div>

                <div class="user-products">
                    <h2>Товары пользователя</h2>
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
                        echo '<p>У пользователя нет товаров.</p>';
                    }
                    wp_reset_postdata();
                    ?>
                </div>
            </div>
        </main>
    </div>
</div>

<?php get_footer(); ?>
