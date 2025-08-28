<?php
add_action('rest_api_init', function(){
    register_rest_route('dm/v1','/send_message', [
        'methods'=>'POST',
        'permission_callback'=>fn()=>is_user_logged_in(),
        'callback'=>function ($r) {
            $sender_id   = get_current_user_id();
            $receiver_id = intval($r['receiver_id']);
            $text        = sanitize_text_field($r['text']);
            $thread_id   = intval($r['thread_id']);

            $sender_lang   = get_user_meta($sender_id, 'preferred_lang', true) ?: 'en';
            $receiver_lang = get_user_meta($receiver_id, 'preferred_lang', true) ?: 'en';

            $mid = wp_insert_post([
                'post_type'=>'dm_message',
                'post_status'=>'publish',
                'post_parent'=>$thread_id,
                'post_author'=>$sender_id,
                'post_content'=>$text
            ]);

            update_post_meta($mid,'_dm_original',$text);

            $translated = $text;
            if($sender_lang !== $receiver_lang && dm_is_translatable($text) && defined('OPENAI_API_KEY')){
                try {
                    $translated = translate_text($text, $receiver_lang, $sender_lang);
                } catch (\Exception $e){
                    error_log('Translation error: '.$e->getMessage());
                    $translated = $text;
                }
                update_post_meta($mid,'_dm_translation_'.$receiver_lang,$translated);
            } else {
                $translated = null;
            }

            update_post_meta($thread_id,'_dm_last_ts',current_time('timestamp', true));

            return [
                'success'=>true,
                'message'=>[
                    'id'=>$mid,
                    'original'=>$text,
                    'translated'=>$translated,
                    'lang'=>$receiver_lang
                ]
            ];
        }
    ]);
});
