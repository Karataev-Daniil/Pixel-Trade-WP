<?php
add_action('rest_api_init', function(){
    register_rest_route('dm/v1','/threads/(?P<id>\d+)/messages',[
        'methods'=>'POST',
        'permission_callback'=>fn($r)=>is_user_logged_in() && dm_user_in_thread(get_current_user_id(),$r['id']),
        'callback'=>function($r){
            global $wpdb;
            $uid = get_current_user_id();
            $tid = (int)$r['id'];
            $content = sanitize_text_field($r['content']);
            $is_system = !empty($r['system']);
            $event_type = $r['event'] ?? null;

            $wpdb->insert($wpdb->prefix.'dm_messages', [
                'thread_id'=>$tid,
                'sender_id'=>$uid,
                'content'=>$content,
                'created_at'=>time(),
                'edited'=>0,
                'system'=>$is_system?1:0,
                'event_type'=>$event_type
            ], ['%d','%d','%s','%d','%d','%s']);

            $wpdb->update($wpdb->prefix.'dm_threads',['last_ts'=>time()],['id'=>$tid],['%d'],['%d']);

            return ['message_id'=>$wpdb->insert_id];
        }
    ]);

    register_rest_route('dm/v1','/send_message', [
        'methods'=>'POST',
        'permission_callback'=>fn()=>is_user_logged_in(),
        'callback'=>function ($r) {
            global $wpdb;
            $sender_id   = get_current_user_id();
            $receiver_id = intval($r['receiver_id']);
            $text        = sanitize_text_field($r['text']);
            $thread_id   = intval($r['thread_id']);

            $sender_lang   = get_user_meta($sender_id,'preferred_lang',true)?:'en';
            $receiver_lang = get_user_meta($receiver_id,'preferred_lang',true)?:'en';

            $table = $wpdb->prefix.'dm_messages';
            $wpdb->insert($table, [
                'thread_id'=>$thread_id,
                'sender_id'=>$sender_id,
                'content'=>$text,
                'created_at'=>time(),
                'edited'=>0,
                'system'=>0,
                'event_type'=>null
            ], ['%d','%d','%s','%d','%d','%d','%s']);

            $mid = $wpdb->insert_id;
            $translated = null;

            if($sender_lang!==$receiver_lang && dm_is_translatable($text) && defined('OPENAI_API_KEY')){
                try{
                    $translated = translate_text($text,$receiver_lang,$sender_lang);
                } catch(\Exception $e){
                    error_log('Translation error: '.$e->getMessage());
                    $translated = $text;
                }
                update_user_meta($receiver_id,'_dm_translation_'.$mid.'_'.$receiver_lang,$translated);
            }

            $wpdb->update($wpdb->prefix.'dm_threads',['last_ts'=>time()],['id'=>$thread_id],['%d'],['%d']);

            return ['success'=>true,'message'=>['id'=>$mid,'original'=>$text,'translated'=>$translated,'lang'=>$receiver_lang]];
        }
    ]);
});
