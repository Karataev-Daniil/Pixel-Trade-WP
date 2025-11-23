<?php
add_action('wp_ajax_generate_translations', 'ajax_generate_translations');
function ajax_generate_translations() {
    if (!is_user_logged_in()) wp_send_json_error('Not logged in');

    $title = sanitize_text_field($_POST['title'] ?? '');
    $desc  = sanitize_textarea_field($_POST['desc'] ?? '');
    $source_lang = sanitize_text_field($_POST['lang'] ?? 'ru');

    if (empty($title) || empty($desc)) {
        wp_send_json_error('Empty title or description.');
    }

    $all_langs = ['ru', 'en', 'ro'];
    $target_langs = array_filter($all_langs, fn($lang) => $lang !== $source_lang);

    $translations = [];
    foreach ($target_langs as $lang) {
        $prompt = "Переведи этот текст с {$source_lang} на {$lang}:\n\n".
                  "Заголовок: {$title}\nОписание: {$desc}\n\n".
                  "Ответь строго валидным JSON с двойными кавычками: {\"title\":\"\",\"desc\":\"\"}";

        $result = call_openai($prompt);

        if (isset($result['error'])) {
            wp_send_json_error($result['error']);
        }

        $translations[$lang] = $result;
    }

    wp_send_json_success($translations);
}

add_action('wp_ajax_improve_text', 'ajax_improve_text');
function ajax_improve_text() {
    if (!is_user_logged_in()) wp_send_json_error('Not logged in');

    $title = sanitize_text_field($_POST['title'] ?? '');
    $desc  = sanitize_textarea_field($_POST['desc'] ?? '');
    $lang  = sanitize_text_field($_POST['lang'] ?? 'ru');

    if (empty($title) && empty($desc)) {
        wp_send_json_error('Empty title and description.');
    }

    $prompt = "Проверь текст на ошибки, исправь орфографию и пунктуацию, добавь разделение на абзацы, " .
              "не изменяя смысл текста ({$lang}): " .
              "Заголовок: {$title}, Описание: {$desc}. " .
              "Ответь строго валидным JSON с двойными кавычками: {\"title\":\"\",\"desc\":\"\"}";

    $result = call_openai($prompt);

    wp_send_json($result);
}


add_action('wp_ajax_generate_seo_text', 'ajax_generate_seo_text');
function ajax_generate_seo_text() {
    if (!is_user_logged_in()) wp_send_json_error('Not logged in');

    $desc  = sanitize_textarea_field($_POST['desc'] ?? '');
    $lang  = sanitize_text_field($_POST['lang'] ?? 'ru');

    if (empty($desc)) {
        wp_send_json_error('Empty description.');
    }

    $prompt = "Сгенерируй SEO-оптимизированное описание ({$lang}) на основе: {$desc}. ".
              "Ответь строго валидным JSON с двойными кавычками: {\"seo_text\":\"\"}";

    $result = call_openai($prompt);
    wp_send_json($result);
}

function call_openai($prompt) {
    $api_key = OPENAI_API_KEY;
    $url     = 'https://api.openai.com/v1/chat/completions';

    $body = [
        'model' => 'gpt-4o-mini',
        'messages' => [
            ['role' => 'system', 'content' => 'Ты полезный переводчик/копирайтер. Отвечай строго валидным JSON с двойными кавычками.'],
            ['role' => 'user', 'content' => $prompt]
        ],
        'temperature' => 0.4
    ];

    $response = wp_remote_post($url, [
        'headers' => [
            'Content-Type'  => 'application/json',
            'Authorization' => 'Bearer ' . $api_key
        ],
        'body' => json_encode($body),
        'timeout' => 60
    ]);

    if (is_wp_error($response)) {
        return ['error' => $response->get_error_message()];
    }

    $data = json_decode(wp_remote_retrieve_body($response), true);
    $content = $data['choices'][0]['message']['content'] ?? '{}';

    $content = preg_replace('/^```(json)?|```$/m', '', trim($content));
    $json = json_decode($content, true);
    if (json_last_error() === JSON_ERROR_NONE) return $json;

    $content_fixed = str_replace("'", '"', $content);
    $json = json_decode($content_fixed, true);
    if (json_last_error() === JSON_ERROR_NONE) return $json;

    error_log("call_openai JSON parse error: " . $content);

    return ['error' => 'JSON parse error', 'raw' => $content];
}
