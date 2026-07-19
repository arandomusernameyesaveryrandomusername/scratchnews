<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
require_once __DIR__ . '/functions.php';
session_start();

$query = trim($_GET['q'] ?? '');
$results = $query !== '' ? searchArticles($query) : [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="icon" type="image/svg+xml" href="/assets/favicon.svg">
<title>Search - <?= e(SITE_NAME) ?></title>
<link rel="stylesheet" href="/assets/style.css">
</head>
<body class="<?= !empty($_SESSION['dark_mode']) ? 'dark' : '' ?>">
<header id="siteHeader">
    <a href="/" class="logo-link">
<svg viewBox="0,0,136.90609,31.33279" class="logo-svg" xmlns="http://www.w3.org/2000/svg"><g transform="translate(-172.33195,-164.3336)"><g stroke-miterlimit="10"><text transform="translate(217.16808,185.69599) scale(0.5,0.5)" font-size="40" fill="#ffffff" stroke="#ffaa33" stroke-width="3" font-family="Scratch" font-weight="normal" text-anchor="start"><tspan x="0" dy="0">ScratchNews</tspan></text><text transform="translate(217.16808,185.69599) scale(0.5,0.5)" font-size="40" fill="#ffffff" stroke="none" stroke-width="1" font-family="Scratch" font-weight="normal" text-anchor="start"><tspan x="0" dy="0">ScratchNews</tspan></text><path d="M181.04509,195.64879h-8.71313v-10.6397h8.71313z" fill="#cc8829" stroke="none"/><path d="M176.88045,195.6664v-20.46677l3.90302,-0.07587l0.09189,-5.0222h6.64479v20.71239l-3.91923,0.16783l-0.04923,4.68462z" fill="#ffaa33" stroke="none"/><path d="M201.40189,164.35122h8.71313v10.6397h-8.71313z" fill="#cc8829" stroke="none"/><path d="M205.56653,164.33361v20.46677l-3.90302,0.07587l-0.09189,5.0222h-6.64479v-20.71239l3.91923,-0.16783l0.04923,-4.68462z" fill="#ffaa33" stroke="none"/><path d="M190.06459,189.91166l-0.03808,-3.62362l-3.03158,-0.12982v-16.02128h5.13983l0.07108,3.88473l3.01904,0.05869v15.8313z" fill="#ffaa33" stroke="none"/></g></g></svg>
    </a>
    <form method="get" action="/search" class="search-form">
        <input type="text" name="q" placeholder="Search articles..." value="<?= e($query) ?>">
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
<main class="home-main">
    <h2>Search results<?= $query !== '' ? ' for "' . e($query) . '"' : '' ?></h2>
    <?php if ($query === ''): ?>
        <p>Type something in the search box above.</p>
    <?php elseif (empty($results)): ?>
        <p>No articles matched your search.</p>
    <?php else: ?>
        <div class="search-results-list">
            <?php foreach ($results as $i => $a): ?>
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
