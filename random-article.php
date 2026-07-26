<?php
require_once __DIR__ . '/functions.php';
$db = getDB();
$row = $db->query("SELECT id FROM articles WHERE status = 'published' ORDER BY RAND() LIMIT 1")->fetch_assoc();
header('Location: ' . ($row ? '/article/' . (int)$row['id'] : '/'));
exit;
