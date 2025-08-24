<?php
// === Регистрация кастомных типов записей ===
add_action('init', function(){
    register_post_type('dm_thread', ['public'=>false,'show_ui'=>false]);
    register_post_type('dm_message', ['public'=>false,'show_ui'=>false]);
});

// === Методы для участников чата ===
function dm_thread_participants_key($uid){ return '_dm_participant_'.$uid; }

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
    update_post_meta($tid,'_dm_last_ts',time());

    return $tid;
}

// === REST API ===
add_action('rest_api_init', function(){
    $currentUserCheck = fn()=>is_user_logged_in();

    // Получение всех чатов текущего пользователя
    register_rest_route('dm/v1','/threads',[
        'methods'=>'GET',
        'permission_callback'=>$currentUserCheck,
        'callback'=>function(){
            $uid = get_current_user_id();
            $threads = get_posts([
                'post_type'=>'dm_thread',
                'meta_key'=>dm_thread_participants_key($uid),
                'meta_value'=>1,
                'posts_per_page'=>50,
                'orderby'=>'meta_value_num',
                'meta_key'=>'_dm_last_ts',
                'order'=>'DESC',
                'fields'=>'ids'
            ]);
            $data=[];
            foreach($threads as $tid){
                $p = dm_thread_participants($tid);
                $other = $p[0]==$uid?$p[1]:$p[0];
                $data[]=[
                    'id'=>$tid,
                    'other_user'=>[
                        'id'=>$other,
                        'name'=>get_the_author_meta('display_name',$other),
                        'avatar'=>get_avatar_url($other,['size'=>64])
                    ],
                    'updated'=>(int)get_post_meta($tid,'_dm_last_ts',true)
                ];
            }
            return $data;
        }
    ]);

    // Создание нового чата
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

    // Получение сообщений
    register_rest_route('dm/v1','/threads/(?P<id>\d+)/messages',[
        'methods'=>'GET',
        'permission_callback'=>fn($r)=>is_user_logged_in() && dm_user_in_thread(get_current_user_id(),$r['id']),
        'callback'=>function($r){
            $tid = (int)$r['id']; $since = (int)$r['since'];
            $args=['post_type'=>'dm_message','post_parent'=>$tid,'orderby'=>'date','order'=>'ASC','posts_per_page'=>100,'fields'=>'ids'];
            if($since) $args['date_query']=[['after'=>date('Y-m-d H:i:s',$since)]];
            $posts = get_posts($args); $out=[];
            foreach($posts as $mid){
                $p = get_post($mid);
                $out[]=[
                    'id'=>(int)$mid,
                    'author'=>(int)$p->post_author,
                    'content'=>$p->post_content,
                    'created'=>strtotime($p->post_date_gmt.' GMT')
                ];
            }
            return $out;
        }
    ]);

    // Отправка сообщения
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
            update_post_meta($tid,'_dm_last_ts',time());
            return ['message_id'=>(int)$mid];
        }
    ]);
});

// === Шорткод кнопки "Написать продавцу" ===
add_shortcode('dm_write_button', function($atts){
    if(!is_user_logged_in()) return '';
    $atts = shortcode_atts(['user'=>0], $atts);
    $uid = intval($atts['user']);
    if(!$uid || $uid==get_current_user_id()) return '';
    $name = get_the_author_meta('display_name',$uid);
    return '<button class="dm-write-btn" data-user="'.$uid.'">Написать '.$name.'</button>';
});

// === Подключение JS и стилей ===
add_action('wp_enqueue_scripts', function(){
    wp_enqueue_style('dm-style',get_stylesheet_directory_uri().'/assets/css/template/messenger.css');
    wp_enqueue_script('react','https://unpkg.com/react@18/umd/react.production.min.js',[],null,true);
    wp_enqueue_script('react-dom','https://unpkg.com/react-dom@18/umd/react-dom.production.min.js',['react'],null,true);
    wp_enqueue_script('dm-app',get_stylesheet_directory_uri().'/assets/js/messenger.js',['react','react-dom'],null,true);
    wp_localize_script('dm-app','SIMPLE_DM',[
        'rest'=>rest_url('dm/v1/'),
        'nonce'=>wp_create_nonce('wp_rest'),
        'currentUser'=>[
            'id'=>get_current_user_id(),
            'name'=>wp_get_current_user()->display_name,
            'avatar'=>get_avatar_url(get_current_user_id())
        ]
    ]);
});

// === Вставка контейнера для чата и кнопки toggle ===
add_action('wp_footer', function(){
    if(!is_user_logged_in()) return;
    ?>
    <div id="simple-dm-root" style="display:none;position:fixed;right:20px;bottom:20px;width:350px;height:500px;z-index:9999;"></div>
    <button id="dm-toggle-btn" style="position:fixed;right:20px;bottom:540px;z-index:9999;padding:10px 20px;">Чат</button>
    <?php
});
