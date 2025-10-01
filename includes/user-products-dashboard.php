<?php
function my_products_get_cached($current_user_id, $statuses, $search, $category, $paged = 1, $per_page = 20, $sort = 'date_new'){
    $cache_key = 'my_products_'.$current_user_id.'_'.md5(implode(',',$statuses).'_'.$search.'_'.$category.'_'.$paged.'_'.$per_page.'_'.$sort);

    $cached = get_transient($cache_key);
    if($cached !== false) return $cached;

    $args = [
        'post_type'      => 'products',
        'posts_per_page' => $per_page,
        'paged'          => $paged,
        'author'         => $current_user_id,
        'post_status'    => in_array('all',$statuses) ? ['publish','draft','pending','private'] : $statuses,
    ];

    if($category && $category !== 'all') {
        $args['tax_query'] = [[
            'taxonomy' => 'product_cat',
            'field'    => 'term_id',
            'terms'    => $category,
        ]];
    }

    if($search) $args['s'] = $search;

    switch($sort){
        case 'date_new':
            $args['orderby'] = 'date';
            $args['order']   = 'DESC';
            break;
        case 'date_old':
            $args['orderby'] = 'date';
            $args['order']   = 'ASC';
            break;
        case 'price_low':
            $args['meta_key'] = 'product_price';
            $args['orderby']  = 'meta_value_num';
            $args['order']    = 'ASC';
            break;
        case 'price_high':
            $args['meta_key'] = 'product_price';
            $args['orderby']  = 'meta_value_num';
            $args['order']    = 'DESC';
            break;
    }

    $query = new WP_Query($args);
    $products = [];

    if($query->have_posts()){
        while($query->have_posts()){
            $query->the_post();
            $post_id = get_the_ID();
            $thumb = get_the_post_thumbnail_url($post_id,'medium-thumb') ?: get_template_directory_uri().'/images/product-placeholder.png';
            $terms = wp_get_post_terms($post_id,'product_cat',['fields'=>'names']);
            if(!empty($terms)){
                $first = reset($terms);
                $last = end($terms);
                $categories = $first === $last ? $first : $first.' &raquo; '.$last;
            } else {
                $categories = '-';
            }

            $price = get_post_meta($post_id,'product_price',true);
            $currency = get_post_meta($post_id,'product_currency',true) ?: 'LEI';

            $products[] = [
                'id'         => $post_id,
                'title'      => get_the_title(),
                'thumb'      => $thumb,
                'categories' => $categories,
                'price'      => $price ? number_format((float)$price,2).' '.$currency : '-',
                'status'     => get_post_status($post_id),
                'date'       => get_the_date('d M Y, H:i'),
                'edit_link'  => get_permalink($post_id).'?edit=1'
            ];
        }
        wp_reset_postdata();
    }

    $result = [
        'products'     => $products,
        'current_page' => (int)$paged,
        'total_pages'  => (int)$query->max_num_pages
    ];

    set_transient($cache_key,$result,300);

    $keys = get_user_meta($current_user_id,'_my_products_cache_keys',true) ?: [];
    if(!in_array($cache_key,$keys)){
        $keys[] = $cache_key;
        update_user_meta($current_user_id,'_my_products_cache_keys',$keys);
    }

    return $result;
}

function my_products_get_status_counts($user_id){
    $statuses = ['publish','draft','pending','private'];
    $counts = [];
    foreach($statuses as $st){
        $q = new WP_Query([
            'post_type'    => 'products',
            'post_status'  => $st,
            'author'       => $user_id,
            'posts_per_page' => 1
        ]);
        $counts[$st] = (int)$q->found_posts;
        wp_reset_postdata();
    }
    return $counts;
}

function my_products_clear_cache($user_id) {
    global $wpdb;
    $transients = $wpdb->get_col("
        SELECT option_name FROM {$wpdb->options}
        WHERE option_name LIKE '_transient_recommended_products_user_{$user_id}_%'
           OR option_name LIKE '_transient_timeout_recommended_products_user_{$user_id}_%'
    ");
    foreach ($transients as $t) {
        $key = str_replace('_transient_', '', $t);
        delete_transient($key);
    }
}

add_action('wp_ajax_filter_my_products','my_products_filter_callback');
function my_products_filter_callback(){
    check_ajax_referer('my_products_nonce','nonce');
    $current_user_id = get_current_user_id();
    if(!$current_user_id) wp_send_json_error('Не авторизован');

    $statuses = isset($_POST['statuses']) ? (array)$_POST['statuses'] : ['all'];
    $search   = sanitize_text_field($_POST['search'] ?? '');
    $category = $_POST['category'] ?? 'all';
    $paged    = max(1,intval($_POST['page'] ?? 1));
    $sort     = sanitize_text_field($_POST['sort'] ?? 'date_new');

    $result = my_products_get_cached($current_user_id,$statuses,$search,$category,$paged,20,$sort);
    $result['status_counts'] = my_products_get_status_counts($current_user_id);
    wp_send_json_success($result);
}

add_action('wp_ajax_product_action','my_products_action_callback');
function my_products_action_callback(){
    check_ajax_referer('my_products_nonce','nonce');

    if(!isset($_POST['product_ids'],$_POST['action_type'])) wp_send_json_error('Нет данных');

    $action = sanitize_text_field($_POST['action_type']);
    $ids = array_map('intval',$_POST['product_ids']);
    $current_user_id = get_current_user_id();

    foreach($ids as $post_id){
        if(!current_user_can('edit_post',$post_id)) continue;

        switch($action){
            case 'republish':
                wp_update_post([
                    'ID'            => $post_id,
                    'post_status'   => 'publish',
                    'post_date'     => current_time('mysql'),
                    'post_date_gmt' => current_time('mysql',1)
                ]);
                break;
            case 'hide':
                wp_update_post([
                    'ID'          => $post_id,
                    'post_status' => 'draft'
                ]);
                break;
            case 'delete':
                wp_delete_post($post_id,true);
                break;
        }
    }

    my_products_clear_cache($current_user_id);
    wp_send_json_success();
}
