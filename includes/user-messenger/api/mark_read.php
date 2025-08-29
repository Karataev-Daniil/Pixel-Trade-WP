<?php
add_action('rest_api_init', function() {
    register_rest_route('dm/v1','/threads/(?P<id>\d+)/read',[
        'methods'=>'POST',
        'permission_callback'=>fn($r)=>is_user_logged_in() && dm_user_in_thread(get_current_user_id(),$r['id']),
        'callback'=>function($r){
            $uid = get_current_user_id();
            $tid = (int)$r['id'];

            $last_msg = get_posts([
                'post_type'=>'dm_message',
                'post_parent'=>$tid,
                'orderby'=>'date',
                'order'=>'DESC',
                'posts_per_page'=>1,
                'fields'=>'ids'
            ]);


            if($last_msg){
                $last_msg_obj = get_post($last_msg[0]);
                $timestamp = strtotime($last_msg_obj->post_date_gmt.' GMT');
                update_post_meta($tid,'_dm_last_read_'.$uid,$timestamp);
            }

            return ['success'=>true];
        }
    ]);
});
function dm_get_unread_count($tid, $uid){
    $last_read = (int) get_post_meta($tid, '_dm_last_read_' . $uid, true);
    $args = [
        'post_type' => 'dm_message',
        'post_parent' => $tid,
        'orderby' => 'date',
        'order' => 'ASC',
        'fields' => 'ids'
    ];
    if($last_read){
        $args['date_query'] = [[
            'after' => gmdate('Y-m-d H:i:s', $last_read), // GMT
            'inclusive' => false,
            'column' => 'post_date_gmt'
        ]];
    }
    $posts = get_posts($args);
    return count($posts);
}
