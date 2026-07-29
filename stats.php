<?php
require_once __DIR__ . '/functions.php';
startSession();
logVisit('/stats');

$db = getDB();
$totalUsers = $db->query("SELECT COUNT(*) AS c FROM users WHERE id < 1000000")->fetch_assoc()['c'];
$totalArticles = $db->query("SELECT COUNT(*) AS c FROM articles WHERE status = 'published'")->fetch_assoc()['c'];
$totalViews = $db->query("SELECT SUM(views) AS c FROM articles WHERE status = 'published'")->fetch_assoc()['c'];
$totalVisits = $db->query("SELECT COUNT(DISTINCT ip_address) AS c FROM daily_unique_visitors")->fetch_assoc()['c'];
$topArticles = $db->query("SELECT id, title, views, (SELECT COUNT(*) FROM likes WHERE article_id = articles.id) AS likes FROM articles WHERE status = 'published' ORDER BY views DESC LIMIT 10")->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="icon" type="image/svg+xml" href="/assets/favicon.svg">
<title>Stats - <?= e(SITE_NAME) ?></title>
<link rel="stylesheet" href="/assets/style.css?v=15">
</head>
<body <?php include __DIR__ . '/includes/theme-body.php'; ?>>
<script>if(document.body.hasAttribute('data-theme-auto')&&window.matchMedia&&window.matchMedia('(prefers-color-scheme: dark)').matches){document.body.classList.add('dark');}</script>
<?php include __DIR__ . '/includes/header.php'; ?>
<main>
    <h2>ScratchNews Stats</h2>
    <p><?= (int)$totalUsers ?> users &middot; <?= (int)$totalArticles ?> articles &middot; <?= (int)$totalViews ?> total article views &middot; <?= (int)$totalVisits ?> unique visitors (all-time)</p>

    <h3 style="margin-top:2rem;">Top Articles</h3>
    <table>
        <tr><th>Title</th><th>Views</th><th>Likes</th></tr>
        <?php foreach ($topArticles as $a): ?>
            <tr><td><a href="/article/<?= (int)$a['id'] ?>"><?= e($a['title']) ?></a></td><td><?= (int)$a['views'] ?></td><td><?= (int)$a['likes'] ?></td></tr>
        <?php endforeach; ?>
    </table>
</main>
<?php include __DIR__ . '/includes/footer.php'; ?>
</body>
</html>
