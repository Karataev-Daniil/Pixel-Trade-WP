<?php
global $wpdb;

function dm_thread_participants($tid){
    global $wpdb;
    $table = $wpdb->prefix . 'dm_threads';
    $row = $wpdb->get_row($wpdb->prepare("SELECT user_a, user_b FROM $table WHERE id=%d", $tid));
    if(!$row) return [];
    return [(int)$row->user_a, (int)$row->user_b];
}

function dm_user_in_thread($uid,$tid){
    return in_array((int)$uid, dm_thread_participants($tid));
}

function dm_get_or_create_thread($a, $b){
    global $wpdb;
    $ids = [(int)$a,(int)$b];
    sort($ids);
    $hash = md5(join(':',$ids));
    $table = $wpdb->prefix . 'dm_threads';

    $thread = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE dm_key=%s LIMIT 1", $hash));
    if($thread) return (int)$thread->id;

    $now = time();
    $wpdb->insert($table, [
        'user_a' => $ids[0],
        'user_b' => $ids[1],
        'dm_key' => $hash,
        'last_ts'=> $now,
        'blocked'=> 0,
        'blocked_by'=> null
    ], ['%d','%d','%s','%d','%d','%d']);

    return (int)$wpdb->insert_id;
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
        $lang_map = ['en'=>'English','ro'=>'Romanian','ru'=>'Russian'];
        $target_name = $lang_map[$target_lang] ?? $target_lang;
        $source_name = $lang_map[$source_lang] ?? 'auto';
        $messages = [[
            'role'=>'user',
            'content'=>"You are a professional translator. Translate from {$source_name} to {$target_name}:\n\"".trim($text)."\""
        ]];

        $response = wp_remote_post('https://api.openai.com/v1/chat/completions', [
            'headers' => [
                'Authorization' => 'Bearer ' . $api_key,
                'Content-Type'  => 'application/json',
            ],
            'body' => json_encode([
                'model'=>'gpt-3.5-turbo',
                'messages'=>$messages,
                'temperature'=>0.3
            ]),
            'timeout'=>20
        ]);

        if(is_wp_error($response)) return $text;
        $body = json_decode(wp_remote_retrieve_body($response), true);
        return $body['choices'][0]['message']['content'] ?? $text;
    }
}

function dm_get_unread_count($tid,$uid){
    global $wpdb;
    $last_read = (int)get_user_meta($uid,'_dm_last_read_'.$tid,true);
    $query = "SELECT COUNT(*) FROM {$wpdb->prefix}dm_messages WHERE thread_id=%d";
    $params = [$tid];
    if($last_read){
        $query .= " AND created_at>%d";
        $params[] = $last_read;
    }
    return (int)$wpdb->get_var($wpdb->prepare($query,...$params));
}
