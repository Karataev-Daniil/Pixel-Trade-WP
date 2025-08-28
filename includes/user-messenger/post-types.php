<?php
add_action('init', function(){
    register_post_type('dm_thread', ['public'=>false,'show_ui'=>false]);
    register_post_type('dm_message', ['public'=>false,'show_ui'=>false]);
});
