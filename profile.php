<?php
require_once __DIR__ . '/functions.php';
session_start();

$username = $_GET['username'] ?? '';
$user = $username !== '' ? getUserByUsername($username) : null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_SESSION['reader_id']) && $user && $_SESSION['reader_id'] == $user['id']) {
    requireCsrf();
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
$articleCount = $user ? getArticleCountByUser($user['id']) : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="icon" type="image/svg+xml" href="/assets/favicon.svg">
<title><?= $user ? e($user['username']) : 'User Not Found' ?> - <?= e(SITE_NAME) ?></title>
<link rel="stylesheet" href="/assets/style.css?v=9">
</head>
<body <?php include __DIR__ . '/includes/theme-body.php'; ?>>
<script>if(document.body.hasAttribute('data-theme-auto')&&window.matchMedia&&window.matchMedia('(prefers-color-scheme: dark)').matches){document.body.classList.add('dark');}</script>
<?php include __DIR__ . '/includes/header.php'; ?>
<main>
<?php if (!$user): ?>
    <h2>User Not Found</h2>
    <p>No account exists under that username.</p>
<?php else: ?>
    <h2>@<?= e($user['username']) ?></h2>
    <p class="meta">Member since <?= date('M j, Y', strtotime($user['created_at'])) ?></p>
    <?php if (!empty($_SESSION['reader_id']) && $_SESSION['reader_id'] == $user['id']): ?>
    <div style="display:flex; gap:0.5rem; align-items:center; margin:0.5rem 0;">
        <a href="/delete-account" class="btn secondary" style="padding:0.3rem 0.7rem; font-size:0.8rem;">Delete my account</a>
        <form method="post" class="profile-actions-form">
            <?= csrfField() ?>
            <input type="hidden" name="dark_mode" value="<?= !empty($_SESSION['dark_mode']) ? '0' : '1' ?>">
            <button type="submit" class="btn secondary" style="padding:0.3rem 0.7rem; font-size:0.8rem;">
                <?= !empty($_SESSION['dark_mode']) ? 'Switch to light mode' : 'Switch to dark mode' ?>
            </button>
        </form>
    </div>
<?php endif; ?>
    <div class="profile-stats-row">
        <h3><a href="/@<?= urlencode($user['username']) ?>?view=articles" class="stat-link"><?= (int)$articleCount ?> Articles</a></h3>
        <h3>Comments (<?= count($comments) ?>)</h3>
    </div>
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