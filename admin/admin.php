<?php
require_once __DIR__ . '/functions.php';
session_start();
logVisit('/');
$articles = getAllArticles();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/svg+xml" href="/assets/favicon.svg">
<title>TEST <?= e(SITE_NAME) ?></title>
<link rel="stylesheet" href="/assets/style.css?v=2">
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
        <span>Hi, <?= e($_SESSION['reader_username']) ?>!</span>
        <?php if (empty($_SESSION['is_admin'])): ?>
        <a href="/submit.php">Submit Article</a>
        <?php endif; ?>
        <?php if (!empty($_SESSION['is_admin'])): ?>
        <a href="/admin/">Admin</a>
        <?php endif; ?>
        <a href="/logout">Log Out</a>
    <?php else: ?>
        <a href="/login">Log In</a>
        <a href="/register">Sign Up</a>
    <?php endif; ?>
    </nav>
</header>
<main class="home-main">
    <?php if (empty($articles)): ?>
        <p>No articles yet. Log in to the <a href="/admin/">login panel</a> to publish the first one.</p>
    <?php else: ?>
        <?php $featured = $articles[0]; $rest = array_slice($articles, 1); ?>
        <div class="article-featured">
            <h2><a href="/article/<?= (int)$featured['id'] ?>"><?= e($featured['title']) ?></a></h2>
            <div class="meta">By <?= e($featured['author']) ?> &middot; <?= date('F j, Y', strtotime($featured['created_at'])) ?></div>
            <div class="summary"><?= e($featured['summary']) ?></div>
        </div>
        <?php if (!empty($rest)): ?>
        <div class="article-grid">
            <?php foreach ($rest as $a): ?>
                <div class="article-card">
                    <h2><a href="/article/<?= (int)$a['id'] ?>"><?= e($a['title']) ?></a></h2>
                    <div class="meta">By <?= e($a['author']) ?> &middot; <?= date('F j, Y', strtotime($a['created_at'])) ?></div>
                    <div class="summary"><?= e($a['summary']) ?></div>
                </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    <?php endif; ?>
</main>
<footer>
    &copy; <?= e(SITE_NAME) ?>
    <?php if (!empty($_SESSION['reader_username'])): ?>
        &middot; <a href="/delete-account">Delete Account</a>
    <?php endif; ?>
    &middot; <a href="/feedback.php">Feedback</a>
</footer>
<script>
    window.addEventListener('scroll', function() {
        var header = document.getElementById('siteHeader');
        if (window.scrollY > 50) {
            header.classList.add('shrink');
        } else {
            
header.classList.remove('shrink');
        }
    });
</script>
</body>
</html>