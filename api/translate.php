<?php
declare(strict_types=1);

header('Content-Type: text/event-stream; charset=utf-8');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('X-Accel-Buffering: no');
header('X-Content-Type-Options: nosniff');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Content-Type: application/json; charset=utf-8');
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
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['success'=>false,'message'=>'Text is required.']);
    exit;
}

if (mb_strlen($text) > 5000) {
    http_response_code(422);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['success'=>false,'message'=>'Text is limited to 5000 characters.']);
    exit;
}

$configFile = dirname(__DIR__) . '/config/config.php';
if (!is_file($configFile)) {
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['success'=>false,'message'=>'AI configuration is missing. Copy config/config.example.php to config/config.php.']);
    exit;
}

$config = require $configFile;
$apiKey = trim((string)($config['gemini_api_key'] ?? ''));
$model = trim((string)($config['gemini_model'] ?? 'gemini-3.6-flash'));

if ($apiKey === '' || $apiKey === 'YOUR_GEMINI_API_KEY') {
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
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
    'stream' => true,
], JSON_UNESCAPED_UNICODE);

function sendSse(array $data): void
{
    echo 'data: ' . json_encode($data, JSON_UNESCAPED_UNICODE) . "\n\n";
    if (ob_get_level() > 0) {
        ob_flush();
    }
    flush();
}

$buffer = '';

$ch = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_POST => true,
    CURLOPT_RETURNTRANSFER => false,
    CURLOPT_HTTPHEADER => [
        'Content-Type: application/json',
        'Accept: text/event-stream',
        'x-goog-api-key: ' . $apiKey,
        'Api-Revision: 2026-05-20',
    ],
    CURLOPT_POSTFIELDS => $payload,
    CURLOPT_CONNECTTIMEOUT => 10,
    CURLOPT_TIMEOUT => 60,
    CURLOPT_WRITEFUNCTION => function ($ch, string $chunk) use (&$buffer): int {
        $buffer .= $chunk;

        while (($separator = strpos($buffer, "\n\n")) !== false) {
            $eventBlock = substr($buffer, 0, $separator);
            $buffer = substr($buffer, $separator + 2);

            foreach (preg_split("/\r?\n/", $eventBlock) as $line) {
                if (!str_starts_with($line, 'data:')) {
                    continue;
                }

                $json = trim(substr($line, 5));
                if ($json === '' || $json === '[DONE]') {
                    continue;
                }

                $event = json_decode($json, true);
                if (!is_array($event)) {
                    continue;
                }

                if (($event['event_type'] ?? '') === 'step.delta'
                    && ($event['delta']['type'] ?? '') === 'text') {
                    sendSse([
                        'type' => 'text',
                        'text' => (string)($event['delta']['text'] ?? ''),
                    ]);
                }

                if (($event['event_type'] ?? '') === 'error') {
                    sendSse([
                        'type' => 'error',
                        'message' => (string)($event['error']['message'] ?? 'AI translation failed.'),
                    ]);
                }
            }
        }

        return strlen($chunk);
    },
]);

$success = curl_exec($ch);
$curlError = curl_error($ch);
$status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($success === false || $curlError) {
    sendSse([
        'type' => 'error',
        'message' => 'AI service connection failed: ' . $curlError,
    ]);
    exit;
}

if ($status >= 400) {
    sendSse([
        'type' => 'error',
        'message' => 'AI translation failed with HTTP ' . $status . '.',
    ]);
    exit;
}

sendSse(['type' => 'done']);
