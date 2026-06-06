<?php
// api/chat.php — AI Chatbot endpoint (OpenAI primary, Anthropic fallback)

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/security.php';
require_once __DIR__ . '/../includes/helpers.php';

// Rate limiting
$ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
if (!checkRateLimit('chat_' . $ip, 30, 60)) {
    jsonResponse(['error' => 'Too many requests. Please wait a moment.'], 429);
}

// Only accept POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['error' => 'Method not allowed'], 405);
}

// Parse JSON body
$body = json_decode(file_get_contents('php://input'), true);
if (!$body || empty($body['message'])) {
    jsonResponse(['error' => 'Invalid request'], 400);
}

$message = trim(sanitize($body['message']));
$history = $body['history'] ?? [];

if (strlen($message) > 500) {
    jsonResponse(['error' => 'Message too long'], 400);
}

// -------------------------------------------------------
// Resolve API keys — OpenAI is primary, Anthropic fallback
// 1. DB (set via Admin → API Keys) takes priority
// 2. Falls back to env vars defined in config/database.php
// -------------------------------------------------------
$openaiKey    = SiteData::getSetting('openai_api_key', '');
$anthropicKey = SiteData::getSetting('anthropic_api_key', '');

if (empty($openaiKey)) {
    $openaiKey = defined('OPENAI_API_KEY') ? OPENAI_API_KEY : (getenv('OPENAI_API_KEY') ?: '');
}
if (empty($anthropicKey)) {
    $anthropicKey = defined('ANTHROPIC_API_KEY') ? ANTHROPIC_API_KEY : '';
}

$useOpenAI    = !empty($openaiKey)    && !str_starts_with($openaiKey,    'YOUR_');
$useAnthropic = !empty($anthropicKey) && !str_starts_with($anthropicKey, 'YOUR_');

if (!$useOpenAI && !$useAnthropic) {
    jsonResponse(['reply' => getLocalFallback($message)]);
}

// Build conversation history (max 10 turns)
$history = array_slice($history, -10);

// Get site context / system prompt
$systemPrompt = SiteData::getChatContext();

// -------------------------------------------------------
// OpenAI (gpt-4o-mini) — primary
// -------------------------------------------------------
if ($useOpenAI) {
    $messages = [['role' => 'system', 'content' => $systemPrompt]];
    foreach ($history as $h) {
        if (!empty($h['role']) && !empty($h['content'])) {
            $messages[] = [
                'role'    => in_array($h['role'], ['user', 'assistant']) ? $h['role'] : 'user',
                'content' => substr(sanitize($h['content']), 0, 500)
            ];
        }
    }
    $messages[] = ['role' => 'user', 'content' => $message];

    $requestBody = json_encode([
        'model'       => 'gpt-4o-mini',
        'max_tokens'  => 300,
        'temperature' => 0.7,
        'messages'    => $messages
    ]);

    $ch = curl_init('https://api.openai.com/v1/chat/completions');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $requestBody,
        CURLOPT_TIMEOUT        => 25,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $openaiKey
        ]
    ]);

    $response  = curl_exec($ch);
    $httpCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    if (!$curlError && $httpCode === 200) {
        $data = json_decode($response, true);
        if (!empty($data['choices'][0]['message']['content'])) {
            jsonResponse(['reply' => $data['choices'][0]['message']['content']]);
        }
    }

    // Log error and fall through to Anthropic or local fallback
    error_log("OpenAI API error: HTTP $httpCode | cURL: $curlError | Response: " . substr($response, 0, 300));

    if (!$useAnthropic) {
        jsonResponse(['reply' => getLocalFallback($message)]);
    }
}

// -------------------------------------------------------
// Anthropic (claude-haiku) — fallback
// -------------------------------------------------------
if ($useAnthropic) {
    $messages = [];
    foreach ($history as $h) {
        if (!empty($h['role']) && !empty($h['content'])) {
            $messages[] = [
                'role'    => in_array($h['role'], ['user', 'assistant']) ? $h['role'] : 'user',
                'content' => substr(sanitize($h['content']), 0, 500)
            ];
        }
    }
    $messages[] = ['role' => 'user', 'content' => $message];

    $requestBody = json_encode([
        'model'      => 'claude-haiku-4-5-20251001',
        'max_tokens' => 300,
        'system'     => $systemPrompt,
        'messages'   => $messages
    ]);

    $ch = curl_init('https://api.anthropic.com/v1/messages');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $requestBody,
        CURLOPT_TIMEOUT        => 20,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json',
            'x-api-key: ' . $anthropicKey,
            'anthropic-version: 2023-06-01'
        ]
    ]);

    $response  = curl_exec($ch);
    $httpCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    if (!$curlError && $httpCode === 200) {
        $data = json_decode($response, true);
        if (!empty($data['content'][0]['text'])) {
            jsonResponse(['reply' => $data['content'][0]['text']]);
        }
    }

    error_log("Anthropic API error: HTTP $httpCode | cURL: $curlError | Response: " . substr($response, 0, 300));
}

// Final fallback — local responses
jsonResponse(['reply' => getLocalFallback($message)]);

// -------------------------------------------------------
// Local fallback responses (used when no API key is set)
// -------------------------------------------------------
function getLocalFallback(string $message): string {
    $lower = strtolower($message);

    if (str_contains($lower, 'watch') || str_contains($lower, 'time')) {
        return "I'd love to help you find the perfect timepiece! Our watch collection features iconic pieces like the Rolex Submariner and premium Swiss-crafted watches. Shall I tell you more about any specific style?";
    }
    if (str_contains($lower, 'perfume') || str_contains($lower, 'fragrance') || str_contains($lower, 'scent')) {
        return "Our fragrance selection is truly curated for the discerning. Dior Sauvage is a perennial favourite for its bold freshness. Would you like recommendations by occasion or season?";
    }
    if (str_contains($lower, 'gift') || str_contains($lower, 'present')) {
        return "Wonderful! We have a beautiful range of luxury gifts — from premium wallets and designer keychains to exquisite fragrances. Who are you shopping for, and what's the occasion?";
    }
    if (str_contains($lower, 'wallet') || str_contains($lower, 'leather')) {
        return "Our leather goods collection is exceptional. The Mont Blanc Carbon Wallet is our most popular choice for professionals. Would you like to explore more options?";
    }
    if (str_contains($lower, 'purse') || str_contains($lower, 'bag') || str_contains($lower, 'handbag')) {
        return "Our designer bag collection includes some truly stunning pieces, including the iconic Hermès Birkin Mini. Each item is hand-selected for exceptional craftsmanship. Shall I share more details?";
    }
    if (str_contains($lower, 'hello') || str_contains($lower, 'hi') || str_contains($lower, 'hey')) {
        return "Welcome to Onyx & Outer! I'm delighted to assist you discover the world's finest luxury accessories. Whether you're looking for watches, fragrances, leather goods, or the perfect gift — I'm here to guide you.";
    }
    if (str_contains($lower, 'price') || str_contains($lower, 'cost') || str_contains($lower, 'how much')) {
        return "Our products span a range of luxury price points. I'd recommend viewing each product page for current pricing from our authorised retail partners. Shall I point you to a specific collection?";
    }
    if (str_contains($lower, 'about') || str_contains($lower, 'who are you') || str_contains($lower, 'onyx')) {
        return "Onyx & Outer is a curated luxury affiliate platform, hand-selecting the finest accessories from around the world. Every product is vetted by our luxury experts so you can shop with confidence.";
    }

    return "Thank you for reaching out. I'm your Onyx & Outer concierge, here to help you discover exceptional luxury products. You can ask me about watches, perfumes, wallets, bags, keychains, or gifts. How may I assist?";
}
