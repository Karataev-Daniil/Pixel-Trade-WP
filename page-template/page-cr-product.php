<?php
/* Template Name: Создание товара */

if (!is_user_logged_in()) {
    wp_die(t('Только для зарегистрированных пользователей.', 'Only for registered users.', 'Doar pentru utilizatori înregistrați.'));
}

$current_user_id = get_current_user_id();

get_header();

get_template_part('template-parts/product/create', null, ['current_user_id' => $current_user_id]);

get_footer();
