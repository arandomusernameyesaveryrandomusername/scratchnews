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
$parentId = !empty($_POST['parent_id']) ? (int)$_POST['parent_id'] : null;
$profileUser = getUserById($profileUserId);

$modError = '';
if ($profileUser && $content !== '' && mb_strlen($content) <= 1000) {
    $modCheck = checkAndModerateComment((int)$_SESSION['reader_id'], $content);
    if ($modCheck['allowed']) {
        addProfileComment($profileUserId, (int)$_SESSION['reader_id'], $content, $parentId);
    } else {
        $modError = $modCheck['reason'];
    }
}

$redirect = '/@' . urlencode($profileUser['username'] ?? '') . '?view=profile_comments';
if ($modError !== '') $redirect .= '&modError=' . urlencode($modError);
header('Location: ' . $redirect);
exit;