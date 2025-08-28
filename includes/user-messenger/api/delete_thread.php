<?php
// api/delete_thread.php
add_action('rest_api_init', function(){

    register_rest_route('dm/v1','/threads/(?P<id>\d+)',[
        'methods'=>'DELETE',
        'permission_callback'=>function($r){
            return is_user_logged_in() && dm_user_in_thread(get_current_user_id(), $r['id']);
        },
        'callback'=>function($r){
            $tid = (int)$r['id'];

            $thread_post = get_post($tid);
            if(!$thread_post || $thread_post->post_type !== 'dm_thread'){
                return new WP_Error('delete_failed','Чат не найден',['status'=>404]);
            }

            $messages = get_posts([
                'post_type'=>'dm_message',
                'post_parent'=>$tid,
                'posts_per_page'=>-1,
                'fields'=>'ids'
            ]);
            foreach($messages as $mid){
                wp_delete_post($mid,true);
            }

            wp_delete_post($tid,true);

            return ['success'=>true];
        }
    ]);
});


function dm_delete_thread($request) {
    $thread_id = (int) $request['id'];

    global $wpdb;
    $table_threads = $wpdb->prefix . 'dm_thread';
    $table_messages = $wpdb->prefix . 'dm_message';

    $deleted = $wpdb->delete($table_threads, ['id' => $thread_id]);

    if($deleted) {
        $wpdb->delete($table_messages, ['thread_id' => $thread_id]);
        return ['success' => true];
    }

    return new WP_Error('delete_failed', 'Не удалось удалить чат', ['status' => 500]);
}
