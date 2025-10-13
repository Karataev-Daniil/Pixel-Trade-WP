<?php
add_action('wp_ajax_chatbot_request', 'chatbot_request_handler');
add_action('wp_ajax_nopriv_chatbot_request', 'chatbot_request_handler');

function chatbot_request_handler() {
    header('Content-Type: application/json; charset=utf-8');
    $input = [];
    if (isset($_POST['payload'])) {
        $input = json_decode(stripslashes($_POST['payload']), true);
    }

    $question = isset($input['question']) ? substr(trim($input['question']), 0, 500) : '';
    $history  = $input['history'] ?? [];

    if ($question === '') {
        echo json_encode(['error' => 'No question provided']);
        wp_die();
    }

    $validHistory = [];
    if (is_array($history)) {
        foreach ($history as $m) {
            if (!empty($m['content']) && isset($m['role'])) {
                $role = $m['role'] === 'bot' ? 'assistant' : $m['role'];
                $validHistory[] = [
                    'role'    => $role,
                    'content' => substr($m['content'], 0, 1000)
                ];
            }
        }
    }

    $promptFile = __DIR__ . '/prompt.txt';
    $systemPrompt = file_exists($promptFile)
        ? trim(file_get_contents($promptFile))
        : 'Ты — помощник маркетплейса PixelTrade. Отвечай коротко, дружелюбно и по делу.';

    $messages = array_merge(
        [['role' => 'system', 'content' => $systemPrompt]],
        $validHistory,
        [['role' => 'user', 'content' => $question]]
    );

    $api_key = OPENAI_API_KEY;
    if (!$api_key) {
        echo json_encode(['error' => 'OpenAI API key not configured']);
        wp_die();
    }

    $data = [
        'model' => 'gpt-3.5-turbo',
        'messages' => $messages,
        'max_tokens' => 500,
        'temperature' => 0.7
    ];

    $ch = curl_init('https://api.openai.com/v1/chat/completions');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $api_key
        ],
        CURLOPT_POST    => true,
        CURLOPT_POSTFIELDS => json_encode($data),
        CURLOPT_TIMEOUT => 15
    ]);

    $response = curl_exec($ch);

    if (curl_errno($ch)) {
        echo json_encode(['error' => 'cURL error: ' . curl_error($ch)]);
        curl_close($ch);
        wp_die();
    }

    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode < 200 || $httpCode >= 300) {
        echo json_encode(['error' => 'OpenAI API error', 'http_code' => $httpCode, 'response' => $response]);
        wp_die();
    }

    echo $response;
    wp_die();
}