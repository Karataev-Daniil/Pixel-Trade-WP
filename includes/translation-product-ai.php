<?php
// === Базовая функция для работы с OpenAI ===
function ai_text_helper($text, $mode = 'improve', $style = 'formal', $lang = 'ru') {
    if (!defined('OPENAI_API_KEY')) {
        return 'API key not defined';
    }

    $api_key = OPENAI_API_KEY;
    $prompt = '';

    switch ($mode) {
        case 'improve':
            if ($style === 'formal') {
                $prompt = "Rewrite the following {$lang} text in a professional, formal style:\n\n" . trim($text);
            } else {
                $prompt = "Rewrite the following {$lang} text in a casual, friendly style:\n\n" . trim($text);
            }
            break;

        case 'seo':
            $prompt = "Rewrite the following {$lang} product description optimized for SEO. 
            Use relevant keywords, improve readability, and make it more appealing:\n\n" . trim($text);
            break;

        case 'translate':
        default:
            $prompt = "Improve the following text:\n\n" . trim($text);
    }

    $messages = [
        ['role' => 'user', 'content' => $prompt],
    ];

    $response = wp_remote_post('https://api.openai.com/v1/chat/completions', [
        'headers' => [
            'Authorization' => 'Bearer ' . $api_key,
            'Content-Type'  => 'application/json',
        ],
        'body' => json_encode([
            'model' => 'gpt-3.5-turbo',
            'messages' => $messages,
            'temperature' => 0.7,
        ]),
        'timeout' => 20,
    ]);

    if (is_wp_error($response)) {
        return 'Request error: ' . $response->get_error_message();
    }

    $body = json_decode(wp_remote_retrieve_body($response), true);

    if (!isset($body['choices'][0]['message']['content'])) {
        return 'Empty response from OpenAI.';
    }

    return trim($body['choices'][0]['message']['content']);
}

function translate_with_openai($text, $target_lang, $source_lang = 'auto') {
    $lang_map = [
        'en' => 'English',
        'ro' => 'Romanian',
        'ru' => 'Russian',
    ];
    $target_name = $lang_map[$target_lang] ?? $target_lang;
    $source_name = $lang_map[$source_lang] ?? 'auto';

    return ai_text_helper(
        "Please translate the following text from {$source_name} to {$target_name}, preserving meaning and tone:\n\n" . $text,
        'translate',
        'formal',
        $source_lang
    );
}

function handle_generate_translations() {
    check_ajax_referer('generate_translations_nonce');

    $title = sanitize_text_field($_POST['title']);
    $description = sanitize_textarea_field($_POST['description']);
    $source_lang = sanitize_text_field($_POST['source_lang'] ?? 'ru');
    $product_id = isset($_POST['product_id']) ? (int) $_POST['product_id'] : 0;

    if (empty($title) || empty($description)) {
        wp_send_json_error('Empty title or description.');
    }

    $all_langs = ['en', 'ro', 'ru'];
    $target_langs = array_filter($all_langs, fn($lang) => $lang !== $source_lang);
    $translations = [];

    foreach ($target_langs as $lang) {
        $translations["title_{$lang}"] = translate_with_openai($title, $lang, $source_lang);
        $translations["description_{$lang}"] = translate_with_openai($description, $lang, $source_lang);
    }

    if ($product_id > 0) {
        foreach ($translations as $meta_key => $value) {
            update_post_meta($product_id, '_' . $meta_key, $value);
        }
    }

    wp_send_json_success($translations);
}
add_action('wp_ajax_generate_translations', 'handle_generate_translations');

function handle_ai_improve_text() {
    check_ajax_referer('generate_translations_nonce');

    $text  = sanitize_textarea_field($_POST['text']);
    $style = sanitize_text_field($_POST['style'] ?? 'formal');
    $lang  = sanitize_text_field($_POST['lang'] ?? 'ru');

    if (empty($text)) {
        wp_send_json_error('Empty text.');
    }

    $result = ai_text_helper($text, 'improve', $style, $lang);

    wp_send_json_success(['improved_text' => $result]);
}
add_action('wp_ajax_ai_improve_text', 'handle_ai_improve_text');

function handle_ai_seo_text() {
    check_ajax_referer('generate_translations_nonce');

    $text       = sanitize_textarea_field($_POST['text'] ?? '');
    $title      = sanitize_text_field($_POST['title'] ?? '');
    $category   = sanitize_text_field($_POST['category'] ?? '');
    $lang       = sanitize_text_field($_POST['lang'] ?? 'ru');

    if (empty($text) && empty($title) && empty($category)) {
        wp_send_json_error('Недостаточно данных для генерации SEO-текста.');
    }

    // Собираем промпт
    $prompt = "Сгенерируй SEO-текст на {$lang} для карточки товара.
    Используй следующие данные:
    - Название: {$title}
    - Категория: {$category}
    - Описание: {$text}
    
    Требования:
    • уникальный текст,
    • 1000–2000 символов,
    • включай ключевые слова естественно,
    • сохраняй информативность и пользу для покупателя.";

    $result = ai_text_helper($prompt, 'seo', 'formal', $lang);

    wp_send_json_success(['seo_text' => $result]);
}
add_action('wp_ajax_ai_seo_text', 'handle_ai_seo_text');
