<?php
require_once __DIR__ . '/functions.php';
startSession();

if (empty($_SESSION['reader_id'])) {
    header('Location: /login');
    exit;
}
requireCsrf();

$targetId = (int)($_POST['user_id'] ?? 0);
$followerId = (int)$_SESSION['reader_id'];
$target = getUserById($targetId);

if ($target && $targetId !== $followerId) {
    if (isFollowing($followerId, $targetId)) {
        unfollowUser($followerId, $targetId);
    } else {
        followUser($followerId, $targetId);
    }
}

header('Location: /@' . urlencode($target['username'] ?? ''));
exit;