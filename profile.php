<?php
require_once __DIR__ . '/functions.php';
session_start();

$username = $_GET['username'] ?? '';
$user = $username !== '' ? getUserByUsername($username) : null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_SESSION['reader_id']) && $user && $_SESSION['reader_id'] == $user['id']) {
    $enabled = !empty($_POST['dark_mode']);
    setDarkModePreference($user['id'], $enabled);
    $_SESSION['dark_mode'] = $enabled;
    header('Location: /@' . urlencode($user['username']));
    exit;
}

if (!$user) {
    http_response_code(404);
}

$comments = $user ? getCommentsByUser($user['id']) : [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="icon" type="image/svg+xml" href="/assets/favicon.svg">
<title><?= $user ? e($user['username']) : 'User Not Found' ?> - <?= e(SITE_NAME) ?></title>
<link rel="stylesheet" href="/assets/style.css?v=8">
</head>
<body class="<?= !empty($_SESSION['dark_mode']) ? 'dark' : '' ?>">
<?php include __DIR__ . '/includes/header.php'; ?>
<main>
<?php if (!$user): ?>
    <h2>User Not Found</h2>
    <p>No account exists under that username.</p>
<?php else: ?>
    <h2>@<?= e($user['username']) ?></h2>
    <p class="meta">Member since <?= date('M j, Y', strtotime($user['created_at'])) ?></p>

    <?php if (!empty($_SESSION['reader_id']) && $_SESSION['reader_id'] == $user['id']): ?>
    <p><a href="/delete-account">Delete my account</a></p>
    <form method="post" style="display:inline;">
        <input type="hidden" name="dark_mode" value="<?= !empty($_SESSION['dark_mode']) ? '0' : '1' ?>">
        <button type="submit" class="text-action" style="background:none;border:none;padding:0;">
            <?= !empty($_SESSION['dark_mode']) ? 'Switch to light mode' : 'Switch to dark mode' ?>
        </button>
    </form>
<?php endif; ?>

    <h3>Comments (<?= count($comments) ?>)</h3>
    <?php foreach ($comments as $c): ?>
        <div class="comment">
            <a href="/article/<?= (int)$c['article_id'] ?>"><strong><?= e($c['article_title']) ?></strong></a>
            <span class="meta"><?= date('M j, Y g:i A', strtotime($c['created_at'])) ?></span>
            <p><?= e($c['content']) ?></p>
        </div>
    <?php endforeach; ?>
<?php endif; ?>
</main>
<?php include __DIR__ . '/includes/footer.php'; ?>
</body>
</html>