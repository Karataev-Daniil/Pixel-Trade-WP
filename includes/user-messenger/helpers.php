<?php

function dm_thread_participants_key($uid){ 
    return '_dm_participant_'.$uid; 
}

function dm_thread_participants($tid){
    $participants = [];
    $meta = get_post_meta($tid);
    foreach($meta as $key => $val){
        if(strpos($key,'_dm_participant_')===0) $participants[] = (int)str_replace('_dm_participant_','',$key);
    }
    return $participants;
}

function dm_user_in_thread($uid,$tid){
    return in_array((int)$uid, dm_thread_participants($tid));
}

function dm_get_or_create_thread($a,$b){
    $ids = [(int)$a,(int)$b];
    sort($ids);
    $hash = md5(join(':',$ids));

    $exist = get_posts([
        'post_type'=>'dm_thread',
        'meta_key'=>'_dm_key',
        'meta_value'=>$hash,
        'fields'=>'ids',
        'posts_per_page'=>1
    ]);
    if($exist) return (int)$exist[0];

    $tid = wp_insert_post([
        'post_type'=>'dm_thread',
        'post_status'=>'publish',
        'post_title'=>'DM '.$ids[0].'-'.$ids[1],
        'post_author'=>$ids[0]
    ]);
    if(!$tid || is_wp_error($tid)) return 0;

    foreach($ids as $id){
        update_post_meta($tid, dm_thread_participants_key($id), 1);
    }
    update_post_meta($tid,'_dm_key',$hash);
    update_post_meta($tid,'_dm_last_ts',current_time('timestamp', true));

    return $tid;
}

function dm_is_translatable($text){
    $clean = trim($text);
    if($clean === '') return false;
    if(preg_match('/[A-Za-zА-Яа-яЁё]/u', $clean) && preg_match('/\b\w{2,}\b/u', $clean)){
        return true;
    }
    return false;
}

if(!function_exists('translate_text')){
    function translate_text($text, $target_lang = 'ru', $source_lang = 'auto'){
        if (!defined('OPENAI_API_KEY') || !dm_is_translatable($text)) return $text;

        $api_key = OPENAI_API_KEY;
        $lang_map = [
            'en' => 'English',
            'ro' => 'Romanian',
            'ru' => 'Russian',
        ];

        $target_name = $lang_map[$target_lang] ?? $target_lang;
        $source_name = $lang_map[$source_lang] ?? 'auto';

        $messages = [
            [
                'role' => 'user',
                'content' => "You are a professional translator. Follow these rules strictly:
            
                1. Evaluate the text:
                   - If readable, translate normally from {$source_name} to {$target_name} preserving meaning and tone.
                   - If completely random or gibberish, return in double quotes, followed by dash and phrase in {$target_name} meaning 'text is unreadable'.
                2. Do NOT add extra punctuation or explanation.
                            
                Text to evaluate and translate:
                \"".trim($text)."\""
            ]
        ];

        $response = wp_remote_post('https://api.openai.com/v1/chat/completions', [
            'headers' => [
                'Authorization' => 'Bearer ' . $api_key,
                'Content-Type'  => 'application/json',
            ],
            'body' => json_encode([
                'model' => 'gpt-3.5-turbo',
                'messages' => $messages,
                'temperature' => 0.3,
            ]),
            'timeout' => 20,
        ]);

        if(is_wp_error($response)) return $text;

        $body = json_decode(wp_remote_retrieve_body($response), true);
        return $body['choices'][0]['message']['content'] ?? $text;
    }
}
