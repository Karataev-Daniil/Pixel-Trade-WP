<?php
add_action('rest_api_init', function(){
    register_rest_route('dm/v1','/threads/(?P<id>\d+)/read',[
        'methods'=>'POST',
        'permission_callback'=>fn($r)=>is_user_logged_in() && dm_user_in_thread(get_current_user_id(),$r['id']),
        'callback'=>function($r){
            global $wpdb;
            $uid = get_current_user_id();
            $tid = intval($r['id']);

            $last_msg = $wpdb->get_row($wpdb->prepare(
                "SELECT created_at FROM {$wpdb->prefix}dm_messages WHERE thread_id=%d ORDER BY created_at DESC LIMIT 1",$tid
            ));
            if($last_msg) update_user_meta($uid,'_dm_last_read_'.$tid,$last_msg->created_at);
            return ['success'=>true];
        }
    ]);
});
