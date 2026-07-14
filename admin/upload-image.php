<?php
require_once __DIR__ . '/auth.php';
header('Content-Type: application/json');

if (empty($_FILES['image'])) {
    http_response_code(400);
    echo json_encode(['error' => 'No file uploaded.']);
    exit;
}

try {
    $url = saveUploadedImage($_FILES['image']);
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
