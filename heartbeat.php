<?php
require_once __DIR__ . '/functions.php';
header('Content-Type: application/json');

$sessionKey = $_POST['session_key'] ?? '';
if (!preg_match('/^[a-f0-9]{32,64}$/i', $sessionKey)) {
    http_response_code(400);
    echo json_encode(['error' => 'invalid session key']);
    exit;
}

recordHeartbeat($sessionKey);
echo json_encode(['ok' => true]);