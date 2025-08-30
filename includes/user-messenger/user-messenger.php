<?php
require_once __DIR__ . '/helpers.php';

require_once __DIR__ . '/enqueue.php';

require_once __DIR__ . '/api/threads.php';
require_once __DIR__ . '/api/messages.php';
require_once __DIR__ . '/api/send.php';
require_once __DIR__ . '/api/edit_message.php';
require_once __DIR__ . '/api/translate.php';
require_once __DIR__ . '/api/block_user.php';
require_once __DIR__ . '/api/mark_read.php';
require_once __DIR__ . '/api/delete_thread.php';





// global $wpdb;

// // Получение участников чата
// function dm_thread_participants($tid){
//     global $wpdb;
//     $table = $wpdb->prefix . 'dm_threads';
//     $row = $wpdb->get_row($wpdb->prepare("SELECT user_a, user_b FROM $table WHERE id=%d", $tid));
//     if(!$row) return [];
//     return [(int)$row->user_a, (int)$row->user_b];
// }

// // Проверка участия пользователя в чате
// function dm_user_in_thread($uid,$tid){
//     return in_array((int)$uid, dm_thread_participants($tid));
// }

// // Создание или получение чата
// function dm_get_or_create_thread($a, $b){
//     global $wpdb;
//     $ids = [(int)$a,(int)$b];
//     sort($ids);
//     $hash = md5(join(':',$ids));
//     $table = $wpdb->prefix . 'dm_threads';

//     $thread = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE dm_key=%s LIMIT 1", $hash));
//     if($thread) return (int)$thread->id;

//     $now = time();
//     $wpdb->insert($table, [
//         'user_a' => $ids[0],
//         'user_b' => $ids[1],
//         'dm_key' => $hash,
//         'last_ts'=> $now,
//         'blocked'=> 0,
//         'blocked_by'=> null
//     ], ['%d','%d','%s','%d','%d','%d']);

//     return (int)$wpdb->insert_id;
// }

// // Проверка, стоит ли переводить текст
// function dm_is_translatable($text){
//     $clean = trim($text);
//     if($clean === '') return false;
//     if(preg_match('/[A-Za-zА-Яа-яЁё]/u', $clean) && preg_match('/\b\w{2,}\b/u', $clean)){
//         return true;
//     }
//     return false;
// }

// // Перевод текста через OpenAI
// if(!function_exists('translate_text')){
//     function translate_text($text, $target_lang = 'ru', $source_lang = 'auto'){
//         if (!defined('OPENAI_API_KEY') || !dm_is_translatable($text)) return $text;

//         $api_key = OPENAI_API_KEY;
//         $lang_map = ['en'=>'English','ro'=>'Romanian','ru'=>'Russian'];
//         $target_name = $lang_map[$target_lang] ?? $target_lang;
//         $source_name = $lang_map[$source_lang] ?? 'auto';
//         $messages = [[
//             'role'=>'user',
//             'content'=>"You are a professional translator. Follow these rules strictly:
// 1. Evaluate the text:
//    - If readable, translate normally from {$source_name} to {$target_name} preserving meaning and tone.
//    - If completely random or gibberish, return in double quotes, followed by dash and phrase in {$target_name} meaning 'text is unreadable'.
// 2. Do NOT add extra punctuation or explanation.

// Text to evaluate and translate:
// \"".trim($text)."\""
//         ]];

//         $response = wp_remote_post('https://api.openai.com/v1/chat/completions', [
//             'headers' => [
//                 'Authorization' => 'Bearer ' . $api_key,
//                 'Content-Type'  => 'application/json',
//             ],
//             'body' => json_encode([
//                 'model'=>'gpt-3.5-turbo',
//                 'messages'=>$messages,
//                 'temperature'=>0.3
//             ]),
//             'timeout'=>20
//         ]);

//         if(is_wp_error($response)) return $text;

//         $body = json_decode(wp_remote_retrieve_body($response), true);
//         return $body['choices'][0]['message']['content'] ?? $text;
//     }
// }

// // Подключение скриптов и стилей
// add_action('wp_enqueue_scripts', function(){
//     wp_enqueue_style('dm-style',get_stylesheet_directory_uri().'/assets/css/template/messenger.css');
//     wp_enqueue_script('react','https://unpkg.com/react@18/umd/react.production.min.js',[],null,true);
//     wp_enqueue_script('react-dom','https://unpkg.com/react-dom@18/umd/react-dom.production.min.js',['react'],null,true);
//     wp_enqueue_script('dm-app',get_stylesheet_directory_uri().'/assets/js/messenger.js',['react','react-dom'],null,true);

//     $lang = isset($_COOKIE['language'])
//         ? sanitize_text_field($_COOKIE['language'])
//         : (get_user_meta(get_current_user_id(), 'preferred_lang', true) ?: 'ru');

//     wp_localize_script('dm-app','SIMPLE_DM',[
//         'rest'=>rest_url('dm/v1/'),
//         'nonce'=>wp_create_nonce('wp_rest'),
//         'currentUser'=>[
//             'id'=>get_current_user_id(),
//             'name'=>wp_get_current_user()->display_name,
//             'avatar'=>($avatar_id = get_user_meta(get_current_user_id(),'profile_avatar',true))
//                 ? wp_get_attachment_url($avatar_id)
//                 : get_avatar_url(get_current_user_id()),
//             'language'=> $lang
//         ]
//     ]);
// });

// // Кнопка "Написать"
// add_shortcode('dm_write_button', function($atts){
//     if(!is_user_logged_in()) return '';
//     $atts = shortcode_atts(['user'=>0], $atts);
//     $uid = intval($atts['user']);
//     if(!$uid || $uid==get_current_user_id()) return '';
//     $name = get_the_author_meta('display_name',$uid);
//     return '<button class="dm-write-btn" data-user="'.$uid.'">Написать '.$name.'</button>';
// });

// // REST API: Получение и создание чатов
// add_action('rest_api_init', function(){
//     global $wpdb;
//     $table_threads = $wpdb->prefix . 'dm_threads';
//     $table_messages = $wpdb->prefix . 'dm_messages';
//     $currentUserCheck = fn()=>is_user_logged_in();

//     // Получение чатов
//     register_rest_route('dm/v1','/threads',[
//         'methods'=>'GET',
//         'permission_callback'=>$currentUserCheck,
//         'callback'=>function(){
//             global $wpdb;
//             $uid = get_current_user_id();
//             $table = $wpdb->prefix . 'dm_threads';
//             $threads = $wpdb->get_results($wpdb->prepare("
//                 SELECT * FROM $table
//                 WHERE user_a=%d OR user_b=%d
//                 ORDER BY last_ts DESC
//                 LIMIT 50
//             ", $uid, $uid));

//             $data = [];
//             foreach($threads as $thread){
//                 $tid = (int)$thread->id;
//                 $participants = [(int)$thread->user_a, (int)$thread->user_b];
//                 $other = $participants[0]==$uid ? ($participants[1] ?? $uid) : $participants[0];

//                 $avatar_id = get_user_meta($other, 'profile_avatar', true);
//                 $avatar_url = $avatar_id ? wp_get_attachment_url($avatar_id) : get_avatar_url($other, ['size'=>64]);

//                 $last_msg = $wpdb->get_row($wpdb->prepare("
//                     SELECT * FROM {$wpdb->prefix}dm_messages
//                     WHERE thread_id=%d
//                     ORDER BY created_at DESC
//                     LIMIT 1
//                 ", $tid));

//                 $data[] = [
//                     'id'=>$tid,
//                     'other_user'=>[
//                         'id'=>$other,
//                         'name'=>get_the_author_meta('display_name',$other),
//                         'avatar'=>$avatar_url
//                     ],
//                     'updated'=>(int)$thread->last_ts,
//                     'last_message'=>$last_msg ? $last_msg->content : '',
//                     'blocked'=> (bool)$thread->blocked,
//                     'blocked_by'=> $thread->blocked_by ?: null,
//                     'unread_count'=>0 // здесь позже можно добавить подсчёт непрочитанных
//                 ];
//             }
//             return $data;
//         }
//     ]);

//     // Создание чата
//     register_rest_route('dm/v1','/threads',[
//         'methods'=>'POST',
//         'permission_callback'=>$currentUserCheck,
//         'callback'=>function($r){
//             $uid = get_current_user_id();
//             $other = (int)$r['user_id'];
//             if($other==$uid) return new WP_Error('wrong','Нельзя писать себе');
//             if(!get_user_by('id',$other)) return new WP_Error('no','Пользователь не найден');
//             $tid = dm_get_or_create_thread($uid,$other);
//             if(!$tid) return new WP_Error('fail','Не удалось создать чат');
//             return ['thread_id'=>$tid];
//         }
//     ]);

//     // Получение сообщений чата
//     register_rest_route('dm/v1','/threads/(?P<id>\d+)/messages',[
//         'methods'=>'GET',
//         'permission_callback'=>fn($r)=>is_user_logged_in() && dm_user_in_thread(get_current_user_id(),$r['id']),
//         'callback'=>function($r){
//             global $wpdb;
//             $tid = (int)$r['id'];
//             $since = isset($r['since']) ? (int)$r['since'] : 0;
//             $uid = get_current_user_id();
//             $user_lang = get_user_meta($uid,'preferred_lang',true) ?: 'en';

//             $query = "SELECT * FROM {$wpdb->prefix}dm_messages WHERE thread_id=%d";
//             $params = [$tid];
//             if($since) {
//                 $query .= " AND created_at>%d";
//                 $params[] = $since;
//             }
//             $query .= " ORDER BY created_at ASC";
//             $posts = $wpdb->get_results($wpdb->prepare($query, ...$params));

//             $out = [];
//             foreach($posts as $p){
//                 $author_id = (int)$p->sender_id;
//                 $avatar_id = get_user_meta($author_id, 'profile_avatar', true);
//                 $avatar_url = $avatar_id ? wp_get_attachment_url($avatar_id) : get_avatar_url($author_id, ['size'=>64]);

//                 $original = $p->content;
//                 $translated = dm_is_translatable($original)
//                     ? get_user_meta($p->id,'_dm_translation_'.$user_lang.'_'.$uid,true)
//                     : null;

//                 $out[] = [
//                     'id'=>$p->id,
//                     'author'=>$author_id,
//                     'author_name'=>get_the_author_meta('display_name',$author_id),
//                     'author_avatar'=>$avatar_url,
//                     'original'=>$original,
//                     'translated'=>$translated,
//                     'content'=>$translated ?: $original,
//                     'created'=>$p->created_at,
//                     'edited'=>(bool)$p->edited,
//                     'system'=>(bool)$p->system,
//                     'event_type'=>$p->event_type
//                 ];
//             }
//             return $out;
//         }
//     ]);

//     // Отправка сообщения
//     register_rest_route('dm/v1','/threads/(?P<id>\d+)/messages',[
//         'methods'=>'POST',
//         'permission_callback'=>fn($r)=>is_user_logged_in() && dm_user_in_thread(get_current_user_id(),$r['id']),
//         'callback'=>function($r){
//             global $wpdb;
//             $uid = get_current_user_id();
//             $tid = (int)$r['id'];
//             $content = sanitize_text_field($r['content']);
//             $is_system = !empty($r['system']);
//             $event_type = $r['event'] ?? null;

//             $wpdb->insert($wpdb->prefix.'dm_messages', [
//                 'thread_id'=>$tid,
//                 'sender_id'=>$uid,
//                 'content'=>$content,
//                 'created_at'=>time(),
//                 'edited'=>0,
//                 'system'=>$is_system ? 1 : 0,
//                 'event_type'=>$event_type
//             ], ['%d','%d','%s','%d','%d','%s']);

//             $wpdb->update($wpdb->prefix.'dm_threads', ['last_ts'=>time()], ['id'=>$tid], ['%d'], ['%d']);

//             return ['message_id'=>$wpdb->insert_id];
//         }
//     ]);
// });

// // Отправка сообщения
// add_action('rest_api_init', function(){
//     register_rest_route('dm/v1','/send_message', [
//         'methods'=>'POST',
//         'permission_callback'=>fn()=>is_user_logged_in(),
//         'callback'=>function ($r) {
//             global $wpdb;
//             $sender_id   = get_current_user_id();
//             $receiver_id = intval($r['receiver_id']);
//             $text        = sanitize_text_field($r['text']);
//             $thread_id   = intval($r['thread_id']);

//             $sender_lang   = get_user_meta($sender_id, 'preferred_lang', true) ?: 'en';
//             $receiver_lang = get_user_meta($receiver_id, 'preferred_lang', true) ?: 'en';

//             $table = $wpdb->prefix . 'dm_messages';
//             $wpdb->insert($table, [
//                 'thread_id' => $thread_id,
//                 'sender_id' => $sender_id,
//                 'content'   => $text,
//                 'created_at'=> time(),
//                 'edited'    => 0,
//                 'system'    => 0,
//                 'event_type'=> null
//             ], ['%d','%d','%s','%d','%d','%d','%s']);

//             $mid = $wpdb->insert_id;

//             $translated = $text;
//             if($sender_lang !== $receiver_lang && dm_is_translatable($text) && defined('OPENAI_API_KEY')){
//                 try {
//                     $translated = translate_text($text, $receiver_lang, $sender_lang);
//                 } catch (\Exception $e){
//                     error_log('Translation error: '.$e->getMessage());
//                     $translated = $text;
//                 }
//                 update_user_meta($receiver_id, '_dm_translation_'.$mid.'_'.$receiver_lang, $translated);
//             } else {
//                 $translated = null;
//             }

//             $wpdb->update($wpdb->prefix . 'dm_threads', ['last_ts'=>time()], ['id'=>$thread_id], ['%d'], ['%d']);

//             return [
//                 'success'=>true,
//                 'message'=>[
//                     'id'=>$mid,
//                     'original'=>$text,
//                     'translated'=>$translated,
//                     'lang'=>$receiver_lang
//                 ]
//             ];
//         }
//     ]);
// });

// // Редактирование сообщения
// add_action('rest_api_init', function(){
//     register_rest_route('dm/v1','/messages/(?P<id>\d+)/edit', [
//         'methods' => 'POST',
//         'permission_callback' => fn()=>is_user_logged_in(),
//         'callback' => function($r) {
//             global $wpdb;
//             $mid = intval($r['id']);
//             $new_text = sanitize_text_field($r['text']);
//             $user_id = get_current_user_id();

//             $table = $wpdb->prefix . 'dm_messages';
//             $msg = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id=%d", $mid));
//             if(!$msg) return new WP_Error('not_found','Сообщение не найдено',['status'=>404]);
//             if($msg->sender_id != $user_id) return new WP_Error('forbidden','Вы не можете редактировать это сообщение',['status'=>403]);

//             $wpdb->update($table, ['content'=>$new_text,'edited'=>1], ['id'=>$mid], ['%s','%d'], ['%d']);

//             // Удаляем переводы
//             $user_meta = get_user_meta($user_id);
//             foreach($user_meta as $key=>$val){
//                 if(strpos($key,'_dm_translation_'.$mid.'_')===0) delete_user_meta($user_id,$key);
//             }

//             return ['success'=>true,'message_id'=>$mid,'new_text'=>$new_text];
//         }
//     ]);
// });

// // Перевод сообщения
// add_action('rest_api_init', function(){
//     register_rest_route('dm/v1','/translate_message',[
//         'methods'=>'POST',
//         'permission_callback'=>fn()=>is_user_logged_in(),
//         'callback'=>function($r){
//             $text        = sanitize_text_field($r['text']);
//             $target_lang = sanitize_text_field($r['target_lang'] ?: 'ru');
//             $source_lang = sanitize_text_field($r['source_lang'] ?: 'auto');

//             if(empty($text) || !dm_is_translatable($text)) return ['translated'=>$text];

//             try{
//                 $translated = translate_text($text, $target_lang, $source_lang);
//             } catch(\Exception $e){
//                 error_log('Translation error: '.$e->getMessage());
//                 return new WP_Error('translate_fail','Ошибка перевода: '.$e->getMessage(),['status'=>500]);
//             }

//             return ['translated'=>$translated ?: $text];
//         }
//     ]);
// });

// // Блокировка и разблокировка чата
// add_action('rest_api_init', function(){
//     register_rest_route('dm/v1','/threads/(?P<id>\d+)/block',[
//         ['methods'=>'POST','callback'=>'simple_dm_block_thread','permission_callback'=>'is_user_logged_in'],
//         ['methods'=>'DELETE','callback'=>'simple_dm_unblock_thread','permission_callback'=>'is_user_logged_in'],
//     ]);
// });

// function simple_dm_block_thread(WP_REST_Request $req){
//     global $wpdb;
//     $thread_id = intval($req['id']);
//     $user_id = get_current_user_id();
//     $table = $wpdb->prefix.'dm_threads';

//     $thread = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id=%d",$thread_id));
//     if(!$thread) return new WP_Error('not_found','Чат не найден',['status'=>404]);

//     $participants = [$thread->user_a,$thread->user_b];
//     if(!in_array($user_id,$participants)) return new WP_Error('forbidden','Нет доступа к этому чату',['status'=>403]);

//     $wpdb->update($table,['blocked'=>1,'blocked_by'=>$user_id],['id'=>$thread_id],['%d','%d'],['%d']);

//     $msg_id = $wpdb->insert($wpdb->prefix.'dm_messages',[
//         'thread_id'=>$thread_id,
//         'sender_id'=>$user_id,
//         'content'=>sprintf('Пользователь %s заблокировал чат', wp_get_current_user()->display_name),
//         'created_at'=>time(),
//         'edited'=>0,
//         'system'=>1,
//         'event_type'=>'blocked'
//     ],['%d','%d','%s','%d','%d','%d','%s']);

//     return ['success'=>true,'thread_id'=>$thread_id,'blocked'=>true,'blocked_by'=>$user_id,'system_message'=>[
//         'id'=>$wpdb->insert_id,
//         'date'=>current_time('mysql'),
//         'content'=>'Вы заблокировали этого пользователя — переписка остановлена',
//         'system'=>true,
//         'event'=>'blocked'
//     ]];
// }

// function simple_dm_unblock_thread(WP_REST_Request $req){
//     global $wpdb;
//     $thread_id = intval($req['id']);
//     $user_id = get_current_user_id();
//     $table = $wpdb->prefix.'dm_threads';

//     $thread = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id=%d",$thread_id));
//     if(!$thread) return new WP_Error('not_found','Чат не найден',['status'=>404]);

//     $participants = [$thread->user_a,$thread->user_b];
//     if(!in_array($user_id,$participants)) return new WP_Error('forbidden','Нет доступа к этому чату',['status'=>403]);
//     if($thread->blocked_by != $user_id) return new WP_Error('forbidden','Чат не заблокирован вами',['status'=>403]);

//     $wpdb->update($table,['blocked'=>0,'blocked_by'=>null],['id'=>$thread_id],['%d','%d'],['%d']);

//     $msg_id = $wpdb->insert($wpdb->prefix.'dm_messages',[
//         'thread_id'=>$thread_id,
//         'sender_id'=>$user_id,
//         'content'=>sprintf('Пользователь %s разблокировал чат.', wp_get_current_user()->display_name),
//         'created_at'=>time(),
//         'edited'=>0,
//         'system'=>1,
//         'event_type'=>'unblocked'
//     ],['%d','%d','%s','%d','%d','%d','%s']);

//     return ['success'=>true,'thread_id'=>$thread_id,'blocked'=>false,'blocked_by'=>null,'system_message'=>[
//         'id'=>$wpdb->insert_id,
//         'date'=>current_time('mysql'),
//         'content'=>'Вы разблокировали чат — переписка восстановлена.',
//         'system'=>true,
//         'event'=>'unblocked'
//     ]];
// }

// // Отметка чата как прочитанного
// add_action('rest_api_init', function(){
//     register_rest_route('dm/v1','/threads/(?P<id>\d+)/read',[
//         'methods'=>'POST',
//         'permission_callback'=>fn($r)=>is_user_logged_in() && dm_user_in_thread(get_current_user_id(),$r['id']),
//         'callback'=>function($r){
//             global $wpdb;
//             $uid = get_current_user_id();
//             $tid = intval($r['id']);

//             $last_msg = $wpdb->get_row($wpdb->prepare("SELECT created_at FROM {$wpdb->prefix}dm_messages WHERE thread_id=%d ORDER BY created_at DESC LIMIT 1",$tid));
//             if($last_msg){
//                 update_user_meta($uid,'_dm_last_read_'.$tid,$last_msg->created_at);
//             }
//             return ['success'=>true];
//         }
//     ]);
// });

// // Подсчёт непрочитанных сообщений
// function dm_get_unread_count($tid,$uid){
//     global $wpdb;
//     $last_read = (int)get_user_meta($uid,'_dm_last_read_'.$tid,true);
//     $query = "SELECT COUNT(*) FROM {$wpdb->prefix}dm_messages WHERE thread_id=%d";
//     $params = [$tid];
//     if($last_read){
//         $query .= " AND created_at>%d";
//         $params[] = $last_read;
//     }
//     return (int)$wpdb->get_var($wpdb->prepare($query,...$params));
// }

// // Удаление чата
// add_action('rest_api_init', function(){
//     register_rest_route('dm/v1','/threads/(?P<id>\d+)',[
//         'methods'=>'DELETE',
//         'permission_callback'=>fn($r)=>is_user_logged_in() && dm_user_in_thread(get_current_user_id(),$r['id']),
//         'callback'=>function($r){
//             global $wpdb;
//             $tid = intval($r['id']);
//             $table_threads = $wpdb->prefix.'dm_threads';
//             $table_messages = $wpdb->prefix.'dm_messages';

//             $deleted = $wpdb->delete($table_threads,['id'=>$tid],['%d']);
//             if($deleted){
//                 $wpdb->delete($table_messages,['thread_id'=>$tid],['%d']);
//                 return ['success'=>true];
//             }
//             return new WP_Error('delete_failed','Не удалось удалить чат',['status'=>500]);
//         }
//     ]);
// });