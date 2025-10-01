<!DOCTYPE html>
<html lang="<?= esc_attr($GLOBALS['language']) ?>" data-theme="<?= esc_attr($GLOBALS['theme']) ?>">
  <head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- SEO Title (уникальный для каждой страницы) -->
    <title><?php if (function_exists('get_custom_title')) { echo get_custom_title(); } else { wp_title('|', true, 'right'); } ?></title>

    <!-- Meta Description -->
    <meta name="description" content="<?php if (function_exists('get_custom_description')) { echo get_custom_description(); } ?>">

    <!-- Meta Keywords -->
    <meta name="keywords" content="<?php if (function_exists('get_custom_keywords')) { echo get_custom_keywords(); } ?>">

    <!-- Canonical -->
    <link rel="canonical" href="<?= esc_url(home_url(add_query_arg(NULL, NULL))); ?>" />

    <!-- Hreflang -->
    <link rel="alternate" hreflang="ru" href="<?= home_url('/ru/'); ?>" />
    <link rel="alternate" hreflang="ro" href="<?= home_url('/ro/'); ?>" />
    <link rel="alternate" hreflang="en" href="<?= home_url('/en/'); ?>" />

    <!-- Open Graph -->
    <meta property="og:title" content="<?php if (function_exists('get_custom_title')) { echo get_custom_title(); } ?>" />
    <meta property="og:description" content="<?php if (function_exists('get_custom_description')) { echo get_custom_description(); } ?>" />
    <meta property="og:type" content="website" />
    <meta property="og:url" content="<?= esc_url(home_url(add_query_arg(NULL, NULL))); ?>" />
    <meta property="og:image" content="<?= get_template_directory_uri(); ?>/assets/img/og-default.jpg" />

    <!-- Twitter Card -->
    <meta name="twitter:card" content="summary_large_image" />
    <meta name="twitter:title" content="<?php if (function_exists('get_custom_title')) { echo get_custom_title(); } ?>" />
    <meta name="twitter:description" content="<?php if (function_exists('get_custom_description')) { echo get_custom_description(); } ?>" />
    <meta name="twitter:image" content="<?= get_template_directory_uri(); ?>/assets/img/og-default.jpg" />

    <!-- Favicon -->
    <link rel="icon" id="favicon-dark" href="<?= get_template_directory_uri(); ?>/images/favicon-light.svg" type="image/svg+xml">
    <link rel="apple-touch-icon" href="<?= get_template_directory_uri(); ?>/assets/img/apple-touch-icon.png">

    <?php wp_head(); ?>
  </head>
  <body <?php body_class(); ?>>
    <?php if ( wp_is_mobile() ) : ?>
      <?php get_template_part('template-parts/header-mobile'); ?>
    <?php else : ?>
      <?php get_template_part('template-parts/header-desktop'); ?>
    <?php endif; ?>
