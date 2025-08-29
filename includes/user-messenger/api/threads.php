<?php
add_action('rest_api_init', function(){
    $currentUserCheck = fn()=>is_user_logged_in();

    register_rest_route('dm/v1','/threads',[
        'methods'=>'GET',
        'permission_callback'=>$currentUserCheck,
        'callback'=>function(){
            $uid = get_current_user_id();
            $threads = get_posts([
                'post_type'=>'dm_thread',
                'posts_per_page'=>50,
                'orderby'=>'meta_value_num',
                'order'=>'DESC',
                'meta_key'=>'_dm_last_ts',
                'meta_query'=>[
                    ['key'=>dm_thread_participants_key($uid),'value'=>1,'compare'=>'=']
                ],
                'fields'=>'ids'
            ]);

            $data = [];
            foreach($threads as $tid){
                $last_msg = get_posts([
                    'post_type'=>'dm_message',
                    'post_parent'=>$tid,
                    'posts_per_page'=>1,
                    'orderby'=>'date',
                    'order'=>'DESC'
                ]);
                $last_content = $last_msg ? $last_msg[0]->post_content : '';

                $participants = dm_thread_participants($tid);
                $other = $participants[0] == $uid ? ($participants[1] ?? $uid) : $participants[0];

                $avatar_id = get_user_meta($other, 'profile_avatar', true);
                $avatar_url = $avatar_id ? wp_get_attachment_url($avatar_id) : get_avatar_url($other, ['size'=>64]);

                $data[] = [
                    'id' => $tid,
                    'other_user' => [
                        'id' => $other,
                        'name' => get_the_author_meta('display_name', $other),
                        'avatar' => $avatar_url
                    ],
                    'updated'      => (int)get_post_meta($tid,'_dm_last_ts',true),
                    'last_message' => $last_content,
                    'blocked'      => get_post_meta($tid,'_dm_blocked',true) ? true : false,
                    'blocked_by'   => get_post_meta($tid,'_dm_blocked_by',true) ?: null,
                    'unread_count' => dm_get_unread_count($tid, $uid)
                ];
            }
            return $data;
        }
    ]);

    register_rest_route('dm/v1','/threads',[
        'methods'=>'POST',
        'permission_callback'=>$currentUserCheck,
        'callback'=>function($r){
            $uid = get_current_user_id();
            $other = (int)$r['user_id'];
            if($other==$uid) return new WP_Error('wrong','Нельзя писать себе');
            if(!get_user_by('id',$other)) return new WP_Error('no','Пользователь не найден');
            $tid = dm_get_or_create_thread($uid,$other);
            if(!$tid) return new WP_Error('fail','Не удалось создать чат');
            return ['thread_id'=>$tid];
        }
    ]);
});