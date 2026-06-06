<?php
// api/subscribe.php — PostgreSQL edition
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/security.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['error' => 'Method not allowed'], 405);
}

$body  = json_decode(file_get_contents('php://input'), true);
$email = filter_var(trim($body['email'] ?? ''), FILTER_VALIDATE_EMAIL);

if (!$email) {
    jsonResponse(['success' => false, 'error' => 'Please enter a valid email address.']);
}

$ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
if (!checkRateLimit('subscribe_' . $ip, 3, 300)) {
    jsonResponse(['success' => false, 'error' => 'Too many attempts. Please try again later.']);
}

try {
    $db  = Database::getInstance();
    $pdo = $db->getConnection();

    // Create table if not exists (PostgreSQL syntax)
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS subscribers (
            id           SERIAL PRIMARY KEY,
            email        VARCHAR(255) NOT NULL,
            subscribed_at TIMESTAMP NOT NULL DEFAULT NOW(),
            ip_address   VARCHAR(45) DEFAULT NULL,
            UNIQUE (email)
        )
    ");

    $pdo->prepare(
        "INSERT INTO subscribers (email, ip_address) VALUES (?, ?)
         ON CONFLICT (email) DO NOTHING"
    )->execute([$email, $ip]);

    jsonResponse(['success' => true, 'message' => 'Welcome to the inner circle!']);
} catch (Exception $e) {
    error_log('Subscribe error: ' . $e->getMessage());
    jsonResponse(['success' => true, 'message' => 'Thank you for subscribing!']);
}
