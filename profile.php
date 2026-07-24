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
$view = $_GET['view'] ?? 'comments';
$userArticles = ($user && $view === 'articles') ? getArticlesByUser($user['id']) : [];
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
        <h3><a href="/@<?= urlencode($user['username']) ?>" class="stat-link">Comments (<?= count($comments) ?>)</a></h3>
    </div>
    <?php if ($view === 'articles'): ?>
        <?php if (empty($userArticles)): ?>
            <p>No articles published yet.</p>
        <?php else: ?>
            <div class="search-results-list">
                <?php foreach ($userArticles as $i => $a): ?>
                    <?php
                        $likeCount = getLikeCount($a['id']);
                        $dislikeCount = getDislikeCount($a['id']);
                        $commentCount = getCommentCount($a['id']);
                        $desc = $a['summary'] ?? '';
                        if (mb_strlen($desc) > 140) $desc = mb_substr($desc, 0, 140) . '...';
                    ?>
                    <a href="/article/<?= (int)$a['id'] ?>" class="search-result <?= $i === 0 ? 'search-result-first' : '' ?>">
                        <?php if (!empty($a['image_url'])): ?>
                            <img src="<?= e($a['image_url']) ?>" alt="" class="search-result-thumb">
                        <?php else: ?>
                            <div class="search-result-thumb search-result-thumb-placeholder"></div>
                        <?php endif; ?>
                        <div class="search-result-body">
                            <div>
                                <div class="search-result-title"><?= e($a['title']) ?></div>
                                <div class="meta">By <?= e($a['author']) ?> &middot; <?= utcTimeTag($a['created_at']) ?></div>
                                <?php if ($desc !== ''): ?><div class="search-result-desc"><?= e($desc) ?></div><?php endif; ?>
                            </div>
                            <div class="search-result-stats">
                                <span><img src="/assets/icons/unlike.svg" class="icon-svg-sm" alt=""><?= $likeCount ?></span>
                                <span><img src="/assets/icons/undislike.svg" class="icon-svg-sm" alt=""><?= $dislikeCount ?></span>
                                <span><img src="/assets/icons/comment.svg" class="icon-svg-sm" alt=""><?= $commentCount ?></span>
                            </div>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    <?php else: ?>
        <?php foreach ($comments as $c): ?>
            <div class="comment">
                <a href="/article/<?= (int)$c['article_id'] ?>"><strong><?= e($c['article_title']) ?></strong></a>
                <span class="meta"><?= date('M j, Y g:i A', strtotime($c['created_at'])) ?></span>
                <p><?= e($c['content']) ?></p>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
<?php endif; ?>
</main>
<?php include __DIR__ . '/includes/footer.php'; ?>
<script>
document.querySelectorAll('time.local-date, time.local-datetime').forEach(function(el) {
    var d = new Date(el.getAttribute('datetime'));
    if (isNaN(d.getTime())) return;
    if (el.classList.contains('local-datetime')) {
        el.textContent = d.toLocaleString(undefined, { year: 'numeric', month: 'short', day: 'numeric', hour: 'numeric', minute: '2-digit' });
    } else {
        el.textContent = d.toLocaleDateString(undefined, { year: 'numeric', month: 'long', day: 'numeric' });
    }
});
</script>
</body>
</html>