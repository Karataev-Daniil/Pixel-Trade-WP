<?php
add_action('rest_api_init', function(){
    register_rest_route('dm/v1','/messages/(?P<id>\d+)/edit', [
        'methods' => 'POST',
        'permission_callback' => fn($r) => is_user_logged_in(),
        'callback' => function($r) {
            $mid = intval($r['id']);
            $new_text = sanitize_text_field($r['text']);
            $user_id = get_current_user_id();

            $post = get_post($mid);
            if(!$post || $post->post_type !== 'dm_message') {
                return new WP_Error('not_found','Сообщение не найдено', ['status'=>404]);
            }

            if($post->post_author != $user_id) {
                return new WP_Error('forbidden','Вы не можете редактировать это сообщение', ['status'=>403]);
            }

            wp_update_post([
                'ID' => $mid,
                'post_content' => $new_text
            ]);

            $metas = get_post_meta($mid);
            foreach($metas as $key => $val){
                if(strpos($key,'_dm_translation_')===0){
                    delete_post_meta($mid, $key);
                }
            }

            update_post_meta($mid,'_dm_original',$new_text);
            update_post_meta($mid,'_dm_edited',1);

            return [
                'success'=>true,
                'message_id'=>$mid,
                'new_text'=>$new_text
            ];
        }
    ]);
});
