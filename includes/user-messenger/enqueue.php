<?php
add_action('wp_enqueue_scripts', function(){
    wp_enqueue_style('dm-style',get_stylesheet_directory_uri().'/assets/css/template/messenger.css');
    wp_enqueue_script('react','https://unpkg.com/react@18/umd/react.production.min.js',[],null,true);
    wp_enqueue_script('react-dom','https://unpkg.com/react-dom@18/umd/react-dom.production.min.js',['react'],null,true);
    wp_enqueue_script('dm-app',get_stylesheet_directory_uri().'/assets/js/messenger.js',['react','react-dom'],null,true);

    $lang = isset($_COOKIE['language'])
        ? sanitize_text_field($_COOKIE['language'])
        : (get_user_meta(get_current_user_id(), 'preferred_lang', true) ?: 'ru');

    wp_localize_script('dm-app','SIMPLE_DM',[
        'rest'=>rest_url('dm/v1/'),
        'nonce'=>wp_create_nonce('wp_rest'),
        'currentUser'=>[
            'id'=>get_current_user_id(),
            'name'=>wp_get_current_user()->display_name,
            'avatar'=>($avatar_id = get_user_meta(get_current_user_id(),'profile_avatar',true))
                ? wp_get_attachment_url($avatar_id)
                : get_avatar_url(get_current_user_id()),
            'language'=> $lang
        ]
    ]);
});
