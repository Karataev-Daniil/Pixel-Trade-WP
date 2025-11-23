<?php
add_action('rest_api_init', function(){
    global $wpdb;

    register_rest_route('dm/v1','/threads',[
        'methods'=>'GET',
        'permission_callback'=>fn()=>is_user_logged_in(),
        'callback'=>function(){
            global $wpdb;
            $uid = get_current_user_id();
            $table = $wpdb->prefix . 'dm_threads';
            $threads = $wpdb->get_results($wpdb->prepare(
                "SELECT * FROM $table WHERE user_a=%d OR user_b=%d ORDER BY last_ts DESC LIMIT 50",
                $uid,$uid
            ));
            $data=[];
            foreach($threads as $thread){
                $tid = (int)$thread->id;
                $other = ($thread->user_a==$uid) ? $thread->user_b : $thread->user_a;
                $avatar_id = get_user_meta($other,'profile_avatar',true);
                $avatar_url = $avatar_id ? wp_get_attachment_url($avatar_id) : get_avatar_url($other,['size'=>64]);

                $last_msg = $wpdb->get_row($wpdb->prepare(
                    "SELECT * FROM {$wpdb->prefix}dm_messages WHERE thread_id=%d ORDER BY created_at DESC LIMIT 1",
                    $tid
                ));

                $last_read = get_user_meta($uid, '_dm_last_read_'.$tid, true) ?: 0;
                $unread_count = $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) FROM {$wpdb->prefix}dm_messages WHERE thread_id=%d AND created_at>%d AND sender_id!=%d",
                    $tid, $last_read, $uid
                ));

                $data[]=[
                    'id'=>$tid,
                    'other_user'=>[
                        'id'=>$other,
                        'name'=>get_the_author_meta('display_name',$other),
                        'avatar'=>$avatar_url
                    ],
                    'updated'=>(int)$thread->last_ts,
                    'last_message'=>$last_msg ? $last_msg->content : '',
                    'blocked'=>(bool)$thread->blocked,
                    'blocked_by'=>$thread->blocked_by ?: null,
                    'unread_count'=> (int)$unread_count
                ];
            }
            return $data;
        }
    ]);

    register_rest_route('dm/v1','/threads',[
        'methods'=>'POST',
        'permission_callback'=>fn()=>is_user_logged_in(),
        'callback'=>function($r){
            $uid=get_current_user_id();
            $other=intval($r['user_id']);
            if($other==$uid) return new WP_Error('wrong','Нельзя писать себе');
            if(!get_user_by('id',$other)) return new WP_Error('no','Пользователь не найден');
            $tid=dm_get_or_create_thread($uid,$other);
            if(!$tid) return new WP_Error('fail','Не удалось создать чат');
            return ['thread_id'=>$tid];
        }
    ]);
});