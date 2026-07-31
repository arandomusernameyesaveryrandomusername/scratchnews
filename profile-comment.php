<?php
require_once __DIR__ . '/functions.php';
startSession();

if (empty($_SESSION['reader_id'])) {
    header('Location: /login');
    exit;
}
requireCsrf();

$profileUserId = (int)($_POST['profile_user_id'] ?? 0);
$content = trim($_POST['content'] ?? '');
$profileUser = getUserById($profileUserId);

if ($profileUser && $content !== '' && mb_strlen($content) <= 1000) {
    addProfileComment($profileUserId, (int)$_SESSION['reader_id'], $content);
}

header('Location: /@' . urlencode($profileUser['username'] ?? '') . '?view=profile_comments');
exit;