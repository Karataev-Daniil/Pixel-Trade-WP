<?php
add_action('rest_api_init', function(){
    global $wpdb;

    // GET /threads/{id}/messages — получить сообщения
    register_rest_route('dm/v1','/threads/(?P<id>\d+)/messages',[
        'methods'=>'GET',
        'permission_callback'=>fn($r)=>is_user_logged_in() && dm_user_in_thread(get_current_user_id(),$r['id']),
        'callback'=>function($r){
            global $wpdb;
            $tid=(int)$r['id'];
            $since=isset($r['since'])?(int)$r['since']:0;
            $uid=get_current_user_id();
            $user_lang=get_user_meta($uid,'preferred_lang',true)?:'en';

            $query="SELECT * FROM {$wpdb->prefix}dm_messages WHERE thread_id=%d";
            $params=[$tid];
            if($since){$query.=" AND created_at>%d";$params[]=$since;}
            $query.=" ORDER BY created_at ASC";
            $posts=$wpdb->get_results($wpdb->prepare($query,...$params));

            $out=[];
            foreach($posts as $p){
                $author_id=(int)$p->sender_id;
                $avatar_id=get_user_meta($author_id,'profile_avatar',true);
                $avatar_url=$avatar_id?wp_get_attachment_url($avatar_id):get_avatar_url($author_id,['size'=>64]);
                $original=$p->content;
                $translated=dm_is_translatable($original)?get_user_meta($p->id,'_dm_translation_'.$user_lang.'_'.$uid,true):null;
                $out[]=[
                    'id'=>$p->id,
                    'author'=>$author_id,
                    'author_name'=>get_the_author_meta('display_name',$author_id),
                    'author_avatar'=>$avatar_url,
                    'original'=>$original,
                    'translated'=>$translated,
                    'content'=>$translated?:$original,
                    'created'=>$p->created_at,
                    'edited'=>(bool)$p->edited,
                    'system'=>(bool)$p->system,
                    'event_type'=>$p->event_type
                ];
            }
            return $out;
        }
    ]);
});
