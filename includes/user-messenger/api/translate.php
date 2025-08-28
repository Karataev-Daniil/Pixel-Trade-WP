<?php
add_action('rest_api_init', function(){
    $currentUserCheck = fn()=>is_user_logged_in();

    register_rest_route('dm/v1','/translate_message',[
        'methods'=>'POST',
        'permission_callback'=>$currentUserCheck,
        'callback'=>function($r){
            $text        = sanitize_text_field($r['text']);
            $target_lang = sanitize_text_field($r['target_lang'] ?: 'ru');
            $source_lang = sanitize_text_field($r['source_lang'] ?: 'auto');

            if (empty($text) || !dm_is_translatable($text)) {
                return ['translated'=>$text];
            }

            try {
                $translated = translate_text($text, $target_lang, $source_lang);
            } catch (\Exception $e) {
                error_log('Translation error: '.$e->getMessage());
                return new WP_Error('translate_fail', 'Ошибка перевода: '.$e->getMessage(), ['status'=>500]);
            }

            return ['translated'=>$translated ?: $text];
        }
    ]);
});
