<?php
require_once __DIR__ . '/functions.php';
session_start();
logVisit('/');
$articles = getAllArticles();
$popular = getPopularArticles(4);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/svg+xml" href="/assets/favicon.svg">
<title><?= e(SITE_NAME) ?></title>
<meta name="description" content="ScratchNews is a community-run news site covering updates, features, and stories from the Scratch programming community.">
<link rel="stylesheet" href="/assets/style.css?v=10">
</head>
<body class="<?= !empty($_SESSION['dark_mode']) ? 'dark' : '' ?>">
<header id="siteHeader">
    <a href="/" class="logo-link">
<svg viewBox="0,0,136.90609,31.33279" class="logo-svg" xmlns="http://www.w3.org/2000/svg"><g transform="translate(-172.33195,-164.3336)"><g stroke-miterlimit="10"><text transform="translate(217.16808,185.69599) scale(0.5,0.5)" font-size="40" fill="#ffffff" stroke="#ffaa33" stroke-width="3" font-family="Scratch" font-weight="normal" text-anchor="start"><tspan x="0" dy="0">ScratchNews</tspan></text><text transform="translate(217.16808,185.69599) scale(0.5,0.5)" font-size="40" fill="#ffffff" stroke="none" stroke-width="1" font-family="Scratch" font-weight="normal" text-anchor="start"><tspan x="0" dy="0">ScratchNews</tspan></text><path d="M181.04509,195.64879h-8.71313v-10.6397h8.71313z" fill="#cc8829" stroke="none"/><path d="M176.88045,195.6664v-20.46677l3.90302,-0.07587l0.09189,-5.0222h6.64479v20.71239l-3.91923,0.16783l-0.04923,4.68462z" fill="#ffaa33" stroke="none"/><path d="M201.40189,164.35122h8.71313v10.6397h-8.71313z" fill="#cc8829" stroke="none"/><path d="M205.56653,164.33361v20.46677l-3.90302,0.07587l-0.09189,5.0222h-6.64479v-20.71239l3.91923,-0.16783l0.04923,-4.68462z" fill="#ffaa33" stroke="none"/><path d="M190.06459,189.91166l-0.03808,-3.62362l-3.03158,-0.12982v-16.02128h5.13983l0.07108,3.88473l3.01904,0.05869v15.8313z" fill="#ffaa33" stroke="none"/></g></g></svg>
</a>
<form method="get" action="/search" class="search-form">
    <input type="text" name="q" placeholder="Search articles...">
    <button type="submit" aria-label="Search">
        <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path d="M15.5 14h-.79l-.28-.27a6.5 6.5 0 1 0-.7.7l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0A4.5 4.5 0 1 1 14 9.5 4.5 4.5 0 0 1 9.5 14z"/></svg>
    </button>
</form>
<nav>
    <?php if (!empty($_SESSION['reader_username'])): ?>
        <div class="user-nav">
            <button class="user-nav-toggle" onclick="document.getElementById('userMenu').classList.toggle('open')"><?= e($_SESSION['reader_username']) ?> &#9662;</button>
            <div id="userMenu" class="user-nav-menu">
                <a href="/@<?= e($_SESSION['reader_username']) ?>">Profile</a>
                <?php if (empty($_SESSION['is_admin'])): ?>
                <a href="/submit.php">Submit Article</a>
                <?php endif; ?>
                <?php if (!empty($_SESSION['is_admin'])): ?>
                <a href="/admin/">Admin</a>
                <?php endif; ?>
                <a href="/logout">Log Out</a>
            </div>
        </div>
    <?php else: ?>
        <a href="/login">Log In</a>
        <a href="/register">Sign Up</a>
    <?php endif; ?>
    </nav>
</header>
<?php if (!empty($_SESSION['impersonator_admin_username'])): ?>
<div class="impersonation-banner">
    Viewing as <strong><?= e($_SESSION['reader_username']) ?></strong> (impersonating)
    <form method="post" action="/stop-impersonating.php" class="impersonation-form">
        <?= csrfField() ?>
        <button type="submit" class="text-action">Return to Admin</button>
    </form>
</div>
<?php endif; ?>
<main class="home-main">
    <?php if (empty($articles)): ?>
        <p>No articles yet. Log in to the <a href="/admin/">login panel</a> to publish the first one.</p>
    <?php else: ?>
        <?php
            $featured = $articles[0];
            $side = array_slice($articles, 1, 2);
            $latestRow = array_slice($articles, 0, 4);
        ?>
        <div class="hero">
            <a href="/article/<?= (int)$featured['id'] ?>" class="hero-featured">
                <?php if (!empty($featured['image_url'])): ?>
                    <img src="<?= e($featured['image_url']) ?>" alt="" class="hero-featured-img">
                <?php endif; ?>
                <div class="hero-featured-body">
                    <h2><?= e($featured['title']) ?></h2>
                    <div class="meta">By <?= e($featured['author']) ?> &middot; <?= utcTimeTag($featured['created_at']) ?></div>
                </div>
            </a>
            <?php if (!empty($side)): ?>
            <div class="hero-side">
                <?php foreach ($side as $a): ?>
                    <a href="/article/<?= (int)$a['id'] ?>" class="hero-side-card">
                        <?php if (!empty($a['image_url'])): ?>
                            <img src="<?= e($a['image_url']) ?>" alt="" class="hero-side-img">
                        <?php endif; ?>
                        <div class="hero-side-title"><?= e($a['title']) ?></div>
                    </a>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>

        <?php if (!empty($latestRow)): ?>
        <section class="row-section">
            <h3 class="row-title">Latest</h3>
            <div class="row-scroll">
                <?php foreach ($latestRow as $a): ?>
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
        </section>
        <?php endif; ?>

        <?php if (!empty($popular)): ?>
        <section class="row-section">
            <h3 class="row-title">Popular</h3>
            <div class="row-scroll">
                <?php foreach ($popular as $a): ?>
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
        </section>
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
