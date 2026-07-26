<?php
require_once __DIR__ . '/functions.php';
session_start();
$token = $_GET['token'] ?? '';
$success = $token !== '' && confirmSubscriber($token);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="icon" type="image/svg+xml" href="/assets/favicon.svg">
<title>Confirm Subscription - <?= e(SITE_NAME) ?></title>
<link rel="stylesheet" href="/assets/style.css?v=14">
</head>
<body <?php include __DIR__ . '/includes/theme-body.php'; ?>>
<script>if(document.body.hasAttribute('data-theme-auto')&&window.matchMedia&&window.matchMedia('(prefers-color-scheme: dark)').matches){document.body.classList.add('dark');}</script>
<?php include __DIR__ . '/includes/header.php'; ?>
<main style="text-align:center; padding:3rem 1rem;">
    <?php if ($success): ?>
        <h2>You're subscribed!</h2>
        <p>You'll now get ScratchNews articles matching your interests straight to your inbox.</p>
    <?php else: ?>
        <h2>Hmm, that link didn't work</h2>
        <p>It may have already been used, or the link might be incorrect. You can subscribe again from the homepage.</p>
    <?php endif; ?>
    <a href="/" class="btn">Back to ScratchNews</a>
</main>
<?php include __DIR__ . '/includes/footer.php'; ?>
</body>
</html>