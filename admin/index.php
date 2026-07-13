<?php
require_once __DIR__ . '/../functions.php';
require_once __DIR__ . '/auth.php';

$feedback = getAllFeedback();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="icon" type="image/svg+xml" href="/assets/favicon.svg">
<title>Feedback - <?= e(SITE_NAME) ?></title>
<link rel="stylesheet" href="/assets/style.css?v=9">
</head>
<body class="<?= !empty($_SESSION['dark_mode']) ? 'dark' : '' ?>">
<?php require_once __DIR__ . '/nav.php'; ?>
<main>
    <h2>Feedback (<?= count($feedback) ?>)</h2>
    <?php if (empty($feedback)): ?>
        <p>No feedback yet.</p>
    <?php else: ?>
        <?php foreach ($feedback as $f): ?>
            <div class="submission-card" style="border:1px solid #ccc; border-radius:8px; padding:1rem; margin-bottom:1rem;">
                <p class="meta">
                    <?= $f['username'] ? '@' . e($f['username']) : 'Anonymous' ?> ·
                    <?= date('M j, Y g:i A', strtotime($f['created_at'])) ?>
                </p>
                <p><?= e($f['message']) ?></p>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</main>
</body>
</html>