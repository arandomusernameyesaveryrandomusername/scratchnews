<?php
require_once __DIR__ . '/functions.php';
startSession();
header('Content-Type: application/json');

if (empty($_SESSION['reader_id'])) {
    http_response_code(403);
    echo json_encode(['error' => 'You must be logged in to upload images.']);
    exit;
}

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
