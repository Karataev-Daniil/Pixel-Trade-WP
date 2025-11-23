<?php
add_action('rest_api_init', function(){
    register_rest_route('dm/v1','/threads/(?P<id>\d+)',[
        'methods'=>'DELETE',
        'permission_callback'=>fn($r)=>is_user_logged_in() && dm_user_in_thread(get_current_user_id(),$r['id']),
        'callback'=>function($r){
            global $wpdb;
            $tid = intval($r['id']);
            $table_threads = $wpdb->prefix.'dm_threads';
            $table_messages = $wpdb->prefix.'dm_messages';

            $deleted = $wpdb->delete($table_threads,['id'=>$tid],['%d']);
            if($deleted){
                $wpdb->delete($table_messages,['thread_id'=>$tid],['%d']);
                return ['success'=>true];
            }
            return new WP_Error('delete_failed','Не удалось удалить чат',['status'=>500]);
        }
    ]);
});
