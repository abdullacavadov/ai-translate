<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true) ?? [];
$text = trim((string)($input['text'] ?? ''));
$from = trim((string)($input['from'] ?? 'auto'));
$to = trim((string)($input['to'] ?? 'en'));

if ($text === '') {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Text is required.']);
    exit;
}

if (mb_strlen($text) > 5000) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Text is limited to 5000 characters.']);
    exit;
}

$languages = ['auto', 'az', 'en', 'tr', 'de', 'ru', 'fr', 'es'];

if (!in_array($from, $languages, true) || !in_array($to, $languages, true) || $to === 'auto') {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Invalid language selection.']);
    exit;
}

if ($from !== 'auto' && $from === $to) {
    echo json_encode([
        'success' => true,
        'translation' => $text,
        'detectedSourceLanguage' => $from,
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$configFile = dirname(__DIR__) . '/config/config.php';

if (!is_file($configFile)) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Translation configuration is missing. Copy config/config.example.php to config/config.php.',
    ]);
    exit;
}

$config = require $configFile;
$apiKey = trim((string)($config['google_translate_api_key'] ?? ''));

if ($apiKey === '' || $apiKey === 'YOUR_GOOGLE_TRANSLATE_API_KEY') {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Google Cloud Translation API key is not configured.',
    ]);
    exit;
}

$payload = [
    'q' => $text,
    'target' => $to,
    'format' => 'text',
    'model' => 'nmt',
];

if ($from !== 'auto') {
    $payload['source'] = $from;
}

$jsonPayload = json_encode($payload, JSON_UNESCAPED_UNICODE);

if ($jsonPayload === false) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Could not prepare translation request.']);
    exit;
}

$ch = curl_init('https://translation.googleapis.com/language/translate/v2');

curl_setopt_array($ch, [
    CURLOPT_POST => true,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        'Content-Type: application/json; charset=utf-8',
        'Accept: application/json',
        'X-Goog-Api-Key: ' . $apiKey,
    ],
    CURLOPT_POSTFIELDS => $jsonPayload,
    CURLOPT_CONNECTTIMEOUT => 5,
    CURLOPT_TIMEOUT => 15,
]);

$response = curl_exec($ch);
$curlError = curl_error($ch);
$status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($response === false || $curlError !== '') {
    http_response_code(502);
    echo json_encode([
        'success' => false,
        'message' => 'Google Translation service connection failed.',
    ]);
    exit;
}

$data = json_decode($response, true);

if ($status >= 400 || !is_array($data)) {
    $message = 'Google Translation request failed.';

    if (is_array($data)) {
        $message = (string)($data['error']['message'] ?? $message);
    }

    http_response_code($status >= 400 ? $status : 502);
    echo json_encode([
        'success' => false,
        'message' => $message,
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$translation = (string)($data['data']['translations'][0]['translatedText'] ?? '');
$detectedSourceLanguage = $data['data']['translations'][0]['detectedSourceLanguage'] ?? null;

if ($translation === '') {
    http_response_code(502);
    echo json_encode([
        'success' => false,
        'message' => 'Google Translation returned an empty result.',
    ]);
    exit;
}

echo json_encode([
    'success' => true,
    'translation' => html_entity_decode($translation, ENT_QUOTES | ENT_HTML5, 'UTF-8'),
    'detectedSourceLanguage' => $detectedSourceLanguage,
], JSON_UNESCAPED_UNICODE);
