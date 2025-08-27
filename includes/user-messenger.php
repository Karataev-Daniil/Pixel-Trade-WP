<?php
add_action('init', function(){
    register_post_type('dm_thread', ['public'=>false,'show_ui'=>false]);
    register_post_type('dm_message', ['public'=>false,'show_ui'=>false]);
});

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
           - If readable (even with minor typos like 'отлтично'), translate normally from {$source_name} to {$target_name} preserving meaning and tone. Do NOT use quotes.
           - If completely random, gibberish, or meaningless, return exactly in double quotes, followed by a space, a dash, another space, and then the phrase in {$target_name} meaning 'text is unreadable'.
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
                    'updated' => (int)get_post_meta($tid,'_dm_last_ts',true)
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

                $out[]=[
                    'id'=>$mid,
                    'author'=>$author_id,
                    'author_name'=>get_the_author_meta('display_name',$author_id),
                    'author_avatar'=>$avatar_url,
                    'original'=>$original,
                    'translated'=>$translated,
                    'content'=> $content_to_show,
                    'created'=>strtotime($p->post_date_gmt.' GMT')
                ];
            }

            return $out;
        }
    ]);

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

            return [
                'success'=>true,
                'message_id'=>$mid,
                'new_text'=>$new_text
            ];
        }
    ]);
});

add_action('wp_enqueue_scripts', function(){
    wp_enqueue_style('dm-style',get_stylesheet_directory_uri().'/assets/css/template/messenger.css');
    wp_enqueue_script('react','https://unpkg.com/react@18/umd/react.production.min.js',[],null,true);
    wp_enqueue_script('react-dom','https://unpkg.com/react-dom@18/umd/react-dom.production.min.js',['react'],null,true);
    wp_enqueue_script('dm-app',get_stylesheet_directory_uri().'/assets/js/messenger.js',['react','react-dom'],null,true);

    $lang = isset($_COOKIE['language'])
        ? sanitize_text_field($_COOKIE['language'])
        : (get_user_meta(get_current_user_id(), 'preferred_lang', true) ?: 'ru');

    wp_localize_script('dm-app','SIMPLE_DM',[
        'rest'=>rest_url('dm/v1/'),
        'nonce'=>wp_create_nonce('wp_rest'),
        'currentUser'=>[
            'id'=>get_current_user_id(),
            'name'=>wp_get_current_user()->display_name,
            'avatar'=>($avatar_id = get_user_meta(get_current_user_id(),'profile_avatar',true))
                ? wp_get_attachment_url($avatar_id)
                : get_avatar_url(get_current_user_id()),
            'language'=> $lang
        ]
    ]);
});

add_shortcode('dm_write_button', function($atts){
    if(!is_user_logged_in()) return '';
    $atts = shortcode_atts(['user'=>0], $atts);
    $uid = intval($atts['user']);
    if(!$uid || $uid==get_current_user_id()) return '';
    $name = get_the_author_meta('display_name',$uid);
    return '<button class="dm-write-btn" data-user="'.$uid.'">Написать '.$name.'</button>';
});
