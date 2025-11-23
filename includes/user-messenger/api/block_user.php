<?php
add_action('rest_api_init', function(){
    register_rest_route('dm/v1','/threads/(?P<id>\d+)/block',[
        ['methods'=>'POST','callback'=>'simple_dm_block_thread','permission_callback'=>'is_user_logged_in'],
        ['methods'=>'DELETE','callback'=>'simple_dm_unblock_thread','permission_callback'=>'is_user_logged_in'],
    ]);
});

function simple_dm_block_thread(WP_REST_Request $req){
    global $wpdb;
    $thread_id = intval($req['id']);
    $user_id = get_current_user_id();
    $table = $wpdb->prefix.'dm_threads';

    $thread = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id=%d",$thread_id));
    if(!$thread) return new WP_Error('not_found','Чат не найден',['status'=>404]);

    $participants = [$thread->user_a,$thread->user_b];
    if(!in_array($user_id,$participants)) return new WP_Error('forbidden','Нет доступа',['status'=>403]);

    $wpdb->update($table,['blocked'=>1,'blocked_by'=>$user_id],['id'=>$thread_id],['%d','%d'],['%d']);

    $wpdb->insert($wpdb->prefix.'dm_messages',[
        'thread_id'=>$thread_id,
        'sender_id'=>$user_id,
        'content'=>sprintf('Пользователь %s заблокировал чат', wp_get_current_user()->display_name),
        'created_at'=>time(),
        'edited'=>0,
        'system'=>1,
        'event_type'=>'blocked'
    ],['%d','%d','%s','%d','%d','%s']);

    return ['success'=>true,'thread_id'=>$thread_id,'blocked'=>true];
}

function simple_dm_unblock_thread(WP_REST_Request $req){
    global $wpdb;
    $thread_id = intval($req['id']);
    $user_id = get_current_user_id();
    $table = $wpdb->prefix.'dm_threads';

    $thread = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id=%d",$thread_id));
    if(!$thread) return new WP_Error('not_found','Чат не найден',['status'=>404]);

    $participants = [$thread->user_a,$thread->user_b];
    if(!in_array($user_id,$participants)) return new WP_Error('forbidden','Нет доступа',['status'=>403]);
    if($thread->blocked_by != $user_id) return new WP_Error('forbidden','Чат не заблокирован вами',['status'=>403]);

    $wpdb->update($table,['blocked'=>0,'blocked_by'=>null],['id'=>$thread_id],['%d','%d'],['%d']);

    $wpdb->insert($wpdb->prefix.'dm_messages',[
        'thread_id'=>$thread_id,
        'sender_id'=>$user_id,
        'content'=>sprintf('Пользователь %s разблокировал чат.', wp_get_current_user()->display_name),
        'created_at'=>time(),
        'edited'=>0,
        'system'=>1,
        'event_type'=>'unblocked'
    ],['%d','%d','%s','%d','%d','%s']);

    return ['success'=>true,'thread_id'=>$thread_id,'blocked'=>false];
}
