<?php
require_once __DIR__ . '/functions.php';
session_start();
logVisit('/explore');

$categories = getAllCategories();
$activeSlug = $_GET['category'] ?? 'all';

if ($activeSlug === 'all') {
    $articles = getTrendingArticles(20);
} else {
    $articles = getArticlesByCategorySlug($activeSlug);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="icon" type="image/svg+xml" href="/assets/favicon.svg">
<title>Explore - <?= e(SITE_NAME) ?></title>
<link rel="stylesheet" href="/assets/style.css?v=11">
</head>
<body <?php include __DIR__ . '/includes/theme-body.php'; ?>>
<script>if(document.body.hasAttribute('data-theme-auto')&&window.matchMedia&&window.matchMedia('(prefers-color-scheme: dark)').matches){document.body.classList.add('dark');}</script>
<?php include __DIR__ . '/includes/header.php'; ?>
<main class="home-main">
    <h2>Explore</h2>
    <div class="explore-tabs">
        <a href="/explore" class="explore-tab <?= $activeSlug === 'all' ? 'active' : '' ?>">All</a>
        <?php foreach ($categories as $cat): ?>
            <a href="/explore?category=<?= e($cat['slug']) ?>" class="explore-tab <?= $activeSlug === $cat['slug'] ? 'active' : '' ?>"><?= e($cat['name']) ?></a>
        <?php endforeach; ?>
    </div>

    <?php if (empty($articles)): ?>
        <p>No articles here yet.</p>
    <?php else: ?>
        <?php
            $big = $articles[0] ?? null;
            $medium = array_slice($articles, 1, 2);
            $small = array_slice($articles, 3, 3);
            $rest = array_slice($articles, 6);
        ?>
        <div class="explore-grid">
            <?php if ($big): ?>
            <a href="/article/<?= (int)$big['id'] ?>" class="explore-card explore-card-big">
                <?php if (!empty($big['image_url'])): ?>
                    <img src="<?= e($big['image_url']) ?>" alt="" class="explore-card-img">
                <?php else: ?>
                    <div class="explore-card-img explore-card-img-placeholder"></div>
                <?php endif; ?>
                <div class="explore-card-title"><?= e($big['title']) ?></div>
            </a>
            <?php endif; ?>

            <?php if (!empty($medium)): ?>
            <div class="explore-medium-col">
                <?php foreach ($medium as $a): ?>
                <a href="/article/<?= (int)$a['id'] ?>" class="explore-card explore-card-medium">
                    <?php if (!empty($a['image_url'])): ?>
                        <img src="<?= e($a['image_url']) ?>" alt="" class="explore-card-img">
                    <?php else: ?>
                        <div class="explore-card-img explore-card-img-placeholder"></div>
                    <?php endif; ?>
                    <div class="explore-card-title"><?= e($a['title']) ?></div>
                </a>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>

        <?php if (!empty($small)): ?>
        <div class="explore-small-row">
            <?php foreach ($small as $a): ?>
            <a href="/article/<?= (int)$a['id'] ?>" class="explore-card explore-card-small">
                <?php if (!empty($a['image_url'])): ?>
                    <img src="<?= e($a['image_url']) ?>" alt="" class="explore-card-img">
                <?php else: ?>
                    <div class="explore-card-img explore-card-img-placeholder"></div>
                <?php endif; ?>
                <div class="explore-card-title"><?= e($a['title']) ?></div>
            </a>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <?php if (!empty($rest)): ?>
        <div class="row-scroll" style="margin-top:1.5rem;">
            <?php foreach ($rest as $a): ?>
            <a href="/article/<?= (int)$a['id'] ?>" class="row-card">
                <?php if (!empty($a['image_url'])): ?>
                    <img src="<?= e($a['image_url']) ?>" alt="" class="row-card-img">
                <?php else: ?>
                    <div class="row-card-img row-card-img-placeholder"></div>
                <?php endif; ?>
                <div class="row-card-title"><?= e($a['title']) ?></div>
            </a>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    <?php endif; ?>
</main>
<?php include __DIR__ . '/includes/footer.php'; ?>
<script>
document.addEventListener('click', function(e) {
    var menu = document.getElementById('userMenu');
    if (menu && !e.target.closest('.user-nav')) menu.classList.remove('open');
});
</script>
</body>
</html>
