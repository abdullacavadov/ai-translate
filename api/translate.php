<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success'=>false,'message'=>'Method not allowed.']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true) ?? [];
$text = trim((string)($input['text'] ?? ''));
$from = trim((string)($input['from'] ?? 'auto'));
$to = trim((string)($input['to'] ?? 'en'));
$tone = trim((string)($input['tone'] ?? 'natural'));

if ($text === '') {
    http_response_code(422);
    echo json_encode(['success'=>false,'message'=>'Text is required.']);
    exit;
}
if (mb_strlen($text) > 5000) {
    http_response_code(422);
    echo json_encode(['success'=>false,'message'=>'Text is limited to 5000 characters.']);
    exit;
}

$configFile = dirname(__DIR__) . '/config/config.php';
if (!is_file($configFile)) {
    http_response_code(500);
    echo json_encode(['success'=>false,'message'=>'AI configuration is missing. Copy config/config.example.php to config/config.php.']);
    exit;
}
$config = require $configFile;
$apiKey = trim((string)($config['gemini_api_key'] ?? ''));
$model = trim((string)($config['gemini_model'] ?? 'gemini-3.6-flash'));

if ($apiKey === '' || $apiKey === 'YOUR_GEMINI_API_KEY') {
    http_response_code(500);
    echo json_encode(['success'=>false,'message'=>'Gemini API key is not configured.']);
    exit;
}

$languages = [
    'auto'=>'the detected source language','az'=>'Azerbaijani','en'=>'English','tr'=>'Turkish',
    'de'=>'German','ru'=>'Russian','fr'=>'French','es'=>'Spanish'
];
$fromName = $languages[$from] ?? 'the detected source language';
$toName = $languages[$to] ?? 'English';
$toneName = ['natural'=>'natural','formal'=>'formal','professional'=>'professional','casual'=>'casual'][$tone] ?? 'natural';

$prompt = "Translate the following text from {$fromName} to {$toName}. Use a {$toneName} tone. Preserve meaning, formatting, names, numbers, URLs and technical terms. Return ONLY the translated text, without explanations or quotation marks.\n\nText:\n{$text}";

$url = 'https://generativelanguage.googleapis.com/v1beta/interactions';
$payload = json_encode([
    'model' => $model,
    'input' => $prompt,
], JSON_UNESCAPED_UNICODE);

$ch = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_POST=>true,
    CURLOPT_RETURNTRANSFER=>true,
    CURLOPT_HTTPHEADER=>[
        'Content-Type: application/json',
        'x-goog-api-key: ' . $apiKey,
        'Api-Revision: 2026-05-20',
    ],
    CURLOPT_POSTFIELDS=>$payload,
    CURLOPT_TIMEOUT=>30
]);
$response = curl_exec($ch);
$curlError = curl_error($ch);
$status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($response === false || $curlError) {
    http_response_code(502);
    echo json_encode(['success'=>false,'message'=>'AI service connection failed.']);
    exit;
}

$data = json_decode($response, true);
$translation = trim((string)($data['output_text'] ?? ''));

if ($status >= 400 || $translation === '') {
    $message = $data['error']['message'] ?? 'AI translation failed.';
    http_response_code(502);
    echo json_encode(['success'=>false,'message'=>$message]);
    exit;
}

echo json_encode(['success'=>true,'translation'=>$translation], JSON_UNESCAPED_UNICODE);
