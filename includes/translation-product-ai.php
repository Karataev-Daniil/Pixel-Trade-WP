<?php
function translate_with_openai($text, $target_lang, $source_lang = 'auto') {
    if (!defined('OPENAI_API_KEY')) {
        return 'API key not defined';
    }

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
            'content' => "Please translate the following text from {$source_name} to {$target_name}, preserving the original meaning and tone:\n\n" . trim($text)
        ],
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

    if (is_wp_error($response)) {
        return 'Request error: ' . $response->get_error_message();
    }

    $body = json_decode(wp_remote_retrieve_body($response), true);

    if (!isset($body['choices'][0]['message']['content'])) {
        return 'Empty response from OpenAI.';
    }

    return trim($body['choices'][0]['message']['content']);
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
