<?php
require_once __DIR__ . '/functions.php';
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="icon" type="image/svg+xml" href="/assets/favicon.svg">
<title>About - <?= e(SITE_NAME) ?></title>
<link rel="stylesheet" href="/assets/style.css?v=9">
</head>
<body <?php include __DIR__ . '/includes/theme-body.php'; ?>>
<script>if(document.body.hasAttribute('data-theme-auto')&&window.matchMedia&&window.matchMedia('(prefers-color-scheme: dark)').matches){document.body.classList.add('dark');}</script>
<?php include __DIR__ . '/includes/empty-header.php'; ?>
<main>
    <h2>About</h2>
    <p>ScratchNews is a news platform about Scratch-related news, made to prevent misinformation and disinformation among the Scratch community.
        It transmits information in the form of articles, has many social features and created by Scratchers, for Scratchers.</br>
        ScratchNews is a growing platform, and features are added almost daily. We'd like if there were users, and users who submit articles to help our goal of making the Scratch community a little more informed every day.
    </p>
</main>
<?php include __DIR__ . '/includes/footer.php'; ?>
</body>
</html>
