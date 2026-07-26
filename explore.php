<?php
require_once __DIR__ . '/functions.php';
session_start();
logVisit('/explore');

$categories = getAllCategories();
$activeSlug = $_GET['category'] ?? 'all';
$sort = $_GET['sort'] ?? 'metrics';

$articles = getExploreArticles($activeSlug, $sort);

function exploreLink(string $cat, string $sort): string {
    return '/explore?category=' . urlencode($cat) . ($sort !== 'metrics' ? '&sort=' . urlencode($sort) : '');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="icon" type="image/svg+xml" href="/assets/favicon.svg">
<title>Explore - <?= e(SITE_NAME) ?></title>
<link rel="stylesheet" href="/assets/style.css?v=13">
</head>
<body <?php include __DIR__ . '/includes/theme-body.php'; ?>>
<script>if(document.body.hasAttribute('data-theme-auto')&&window.matchMedia&&window.matchMedia('(prefers-color-scheme: dark)').matches){document.body.classList.add('dark');}</script>
<?php include __DIR__ . '/includes/header.php'; ?>
<main class="home-main">
    <h2 class="explore-title">Explore</h2>
    <div class="explore-tabs">
        <a href="<?= exploreLink('all', $sort) ?>" class="explore-tab <?= $activeSlug === 'all' ? 'active' : '' ?>">All</a>
        <?php foreach ($categories as $cat): ?>
            <a href="<?= exploreLink($cat['slug'], $sort) ?>" class="explore-tab <?= $activeSlug === $cat['slug'] ? 'active' : '' ?>"><?= e($cat['name']) ?></a>
        <?php endforeach; ?>
        <div class="explore-filter-wrap">
            <button type="button" class="explore-filter-btn explore-tab" onclick="document.getElementById('filterMenu').classList.toggle('open')">
                <svg viewBox="0 0 24 24" width="14" height="14" xmlns="http://www.w3.org/2000/svg"><path d="M3 4h18l-7 8v6l-4 2v-8z" fill="none" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/></svg>
                Filter
            </button>
            <div id="filterMenu" class="explore-filter-menu">
                <a href="<?= exploreLink($activeSlug, 'metrics') ?>" class="<?= $sort === 'metrics' ? 'active' : '' ?>">Metrics (default)</a>
                <a href="<?= exploreLink($activeSlug, 'recent') ?>" class="<?= $sort === 'recent' ? 'active' : '' ?>">Recent</a>
                <a href="<?= exploreLink($activeSlug, 'popular') ?>" class="<?= $sort === 'popular' ? 'active' : '' ?>">Popular</a>
                <a href="<?= exploreLink($activeSlug, 'most_liked') ?>" class="<?= $sort === 'most_liked' ? 'active' : '' ?>">Most Liked</a>
                <a href="<?= exploreLink($activeSlug, 'most_disliked') ?>" class="<?= $sort === 'most_disliked' ? 'active' : '' ?>">Most Disliked</a>
                <a href="<?= exploreLink($activeSlug, 'author') ?>" class="<?= $sort === 'author' ? 'active' : '' ?>">Sort by Author</a>
                <a href="<?= exploreLink($activeSlug, 'oldest') ?>" class="<?= $sort === 'oldest' ? 'active' : '' ?>">Age/Date</a>
            </div>
        </div>
    </div>

    <?php if (empty($articles)): ?>
        <p>No articles here yet.</p>
    <?php else: ?>
        <?php
            $big = $articles[0] ?? null;
            $medium = array_slice($articles, 1, 2);
            $rest = array_slice($articles, 3);
        ?>
        <?php if ($big): ?>
        <div class="explore-grid">
            <a href="/article/<?= (int)$big['id'] ?>" class="explore-card explore-card-big">
                <?php if (!empty($big['image_url'])): ?>
                    <img src="<?= e($big['image_url']) ?>" alt="" class="explore-card-img">
                <?php else: ?>
                    <div class="explore-card-img explore-card-img-placeholder"></div>
                <?php endif; ?>
                <div class="explore-card-title"><?= e($big['title']) ?></div>
            </a>
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
        <?php endif; ?>

        <div class="search-results-list" style="margin-top:1.5rem;">
            <?php foreach ($rest as $a):
                $likeCount = getLikeCount($a['id']);
                $dislikeCount = getDislikeCount($a['id']);
                $commentCount = getCommentCount($a['id']);
                $desc = $a['summary'] ?? '';
                if (mb_strlen($desc) > 140) $desc = mb_substr($desc, 0, 140) . '...';
            ?>
                <a href="/article/<?= (int)$a['id'] ?>" class="search-result">
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
</main>
<?php include __DIR__ . '/includes/footer.php'; ?>
<script>
document.addEventListener('click', function(e) {
    var menu = document.getElementById('userMenu');
    if (menu && !e.target.closest('.user-nav')) menu.classList.remove('open');
    var filterMenu = document.getElementById('filterMenu');
    if (filterMenu && !e.target.closest('.explore-filter-wrap')) filterMenu.classList.remove('open');
});
</script>
</body>
</html>