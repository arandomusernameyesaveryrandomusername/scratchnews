<?php
require_once __DIR__ . '/functions.php';
$db = getDB();

$stmt = $db->prepare("SELECT id FROM articles WHERE status = ? ORDER BY RAND() LIMIT 1");
$stmt->bind_param('s', 'published');
$stmt->execute();
$row = $stmt->fetch_assoc();   // returns null if none
header('Location: ' . ($row ? '/article/' . (int)$row['id'] : '/'));
exit;
