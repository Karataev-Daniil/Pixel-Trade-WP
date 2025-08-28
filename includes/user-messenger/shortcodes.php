<?php
add_shortcode('dm_write_button', function($atts){
    if(!is_user_logged_in()) return '';
    $atts = shortcode_atts(['user'=>0], $atts);
    $uid = intval($atts['user']);
    if(!$uid || $uid==get_current_user_id()) return '';
    $name = get_the_author_meta('display_name',$uid);
    return '<button class="dm-write-btn" data-user="'.$uid.'">Написать '.$name.'</button>';
});
