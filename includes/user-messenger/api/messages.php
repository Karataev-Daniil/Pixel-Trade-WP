<?php
add_action('rest_api_init', function(){

    // GET сообщений треда
    register_rest_route('dm/v1','/threads/(?P<id>\d+)/messages',[
        'methods'=>'GET',
        'permission_callback'=>fn($r)=>is_user_logged_in() && dm_user_in_thread(get_current_user_id(),$r['id']),
        'callback'=>function($r){
            $tid = (int)$r['id'];
            $since = isset($r['since']) ? (int)$r['since'] : 0;
            $uid = get_current_user_id();
            $user_lang = get_user_meta($uid,'preferred_lang',true) ?: 'en';

            $args = [
                'post_type'=>'dm_message',
                'post_parent'=>$tid,
                'orderby'=>'date',
                'order'=>'ASC',
                'posts_per_page'=>100,
                'fields'=>'ids'
            ];
            if($since) $args['date_query']=[['after'=>date('Y-m-d H:i:s',$since)]];

            $posts = get_posts($args);
            $out=[];

            foreach($posts as $mid){
                $p = get_post($mid);
                $author_id = (int)$p->post_author;

                $avatar_id = get_user_meta($author_id, 'profile_avatar', true);
                $avatar_url = $avatar_id ? wp_get_attachment_url($avatar_id) : get_avatar_url($author_id, ['size'=>64]);

                $original = $p->post_content;
                if(dm_is_translatable($original)){
                    $translated = get_post_meta($mid,'_dm_translation_'.$user_lang.'_'.$uid,true);
                    $content_to_show = $translated ?: $original;
                } else {
                    $translated = null;
                    $content_to_show = $original;
                }

                $out[] = [
                    'id'      => $mid,
                    'author'  => $author_id,
                    'author_name' => get_the_author_meta('display_name',$author_id),
                    'author_avatar' => $avatar_url,
                    'original'=> $original,
                    'translated'=>$translated,
                    'content' => $content_to_show,
                    'created' => strtotime($p->post_date_gmt.' GMT'),
                    'edited'  => (bool)get_post_meta($mid,'_dm_edited',true)
                ];
            }

            return $out;
        }
    ]);

    // POST нового сообщения в тред
    register_rest_route('dm/v1','/threads/(?P<id>\d+)/messages',[
        'methods'=>'POST',
        'permission_callback'=>fn($r)=>is_user_logged_in() && dm_user_in_thread(get_current_user_id(),$r['id']),
        'callback'=>function($r){
            $uid = get_current_user_id();
            $tid = (int)$r['id'];
            $c = sanitize_text_field($r['content']);

            $mid = wp_insert_post([
                'post_type'=>'dm_message',
                'post_status'=>'publish',
                'post_parent'=>$tid,
                'post_author'=>$uid,
                'post_content'=>$c
            ]);

            update_post_meta($tid,'_dm_last_ts',current_time('timestamp', true));

            return ['message_id'=>$mid];
        }
    ]);
});
