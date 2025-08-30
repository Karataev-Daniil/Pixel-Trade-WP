<?php
add_action('rest_api_init', function(){
    register_rest_route('dm/v1','/messages/(?P<id>\d+)/edit', [
        'methods'=>'POST',
        'permission_callback'=>fn()=>is_user_logged_in(),
        'callback'=>function($r){
            global $wpdb;
            $mid = intval($r['id']);
            $new_text = sanitize_text_field($r['text']);
            $user_id = get_current_user_id();
            $table = $wpdb->prefix.'dm_messages';
            $msg = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id=%d",$mid));
            if(!$msg) return new WP_Error('not_found','Сообщение не найдено',['status'=>404]);
            if($msg->sender_id != $user_id) return new WP_Error('forbidden','Вы не можете редактировать это сообщение',['status'=>403]);

            $wpdb->update($table,['content'=>$new_text,'edited'=>1],['id'=>$mid],['%s','%d'],['%d']);

            $user_meta = get_user_meta($user_id);
            foreach($user_meta as $key=>$val){
                if(strpos($key,'_dm_translation_'.$mid.'_')===0) delete_user_meta($user_id,$key);
            }

            return ['success'=>true,'message_id'=>$mid,'new_text'=>$new_text];
        }
    ]);
});
