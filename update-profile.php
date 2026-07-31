<?php
require_once __DIR__ . '/functions.php';
startSession();

if (empty($_SESSION['reader_id'])) {
    header('Location: /login');
    exit;
}
requireCsrf();

$user = getUserById((int)$_SESSION['reader_id']);
if (!$user) {
    http_response_code(404);
    exit;
}

$avatarUrl = $user['avatar_url'];
$bannerUrl = $user['banner_url'];

try {
    if (!empty($_FILES['avatar']['tmp_name'])) {
        $newAvatar = saveUploadedImage($_FILES['avatar'], 'avatars', 512);
        if ($newAvatar) {
            deleteUploadedImage($avatarUrl);
            $avatarUrl = $newAvatar;
        }
    }
    if (!empty($_FILES['banner']['tmp_name'])) {
        $newBanner = saveUploadedImage($_FILES['banner'], 'banners', 1600);
        if ($newBanner) {
            deleteUploadedImage($bannerUrl);
            $bannerUrl = $newBanner;
        }
    }
} catch (RuntimeException $e) {
    http_response_code(400);
    echo 'Upload error: ' . e($e->getMessage());
    exit;
}

$bio = trim($_POST['bio'] ?? ($user['bio'] ?? ''));
if (mb_strlen($bio) > 500) $bio = mb_substr($bio, 0, 500);

updateUserProfile($user['id'], $avatarUrl, $bannerUrl, $bio);

header('Location: /@' . urlencode($user['username']));
exit;