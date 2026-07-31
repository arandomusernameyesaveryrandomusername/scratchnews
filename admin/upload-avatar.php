<?php
require_once __DIR__ . '/../functions.php';
startSession();
header('Content-Type: application/json');

if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Not logged in.']);
    exit;
}

if (empty($_FILES['image']) || empty($_POST['type']) || !in_array($_POST['type'], ['avatars', 'banners'], true)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid upload.']);
    exit;
}

$maxDim = $_POST['type'] === 'avatars' ? 512 : 1600;

try {
    $url = saveUploadedImage($_FILES['image'], $_POST['type'], $maxDim);
    if (!$url) {
        http_response_code(400);
        echo json_encode(['error' => 'Upload failed.']);
        exit;
    }
    echo json_encode(['url' => $url]);
} catch (RuntimeException $e) {
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
}