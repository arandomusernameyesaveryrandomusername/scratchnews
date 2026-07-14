<?php
require_once __DIR__ . '/functions.php';
session_start();

$username = $_GET['username'] ?? '';
$user = $username !== '' ? getUserByUsername($username) : null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_SESSION['reader_id']) && $user && $_SESSION['reader_id'] == $user['id']) {
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
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="icon" type="image/svg+xml" href="/assets/favicon.svg">
<title><?= $user ? e($user['username']) : 'User Not Found' ?> - <?= e(SITE_NAME) ?></title>
<link rel="stylesheet" href="/assets/style.css?v=8">
</head>
<body class="<?= !empty($_SESSION['dark_mode']) ? 'dark' : '' ?>">
<header>
    <a href="/" class="logo-link">
<svg viewBox="0,0,136.90609,31.33279" class="logo-svg" xmlns="http://www.w3.org/2000/svg"><g transform="translate(-172.33195,-164.3336)"><g stroke-miterlimit="10"><text transform="translate(217.16808,185.69599) scale(0.5,0.5)" font-size="40" fill="#ffffff" stroke="#ffaa33" stroke-width="3" font-family="Scratch" font-weight="normal" text-anchor="start"><tspan x="0" dy="0">ScratchNews</tspan></text><text transform="translate(217.16808,185.69599) scale(0.5,0.5)" font-size="40" fill="#ffffff" stroke="none" stroke-width="1" font-family="Scratch" font-weight="normal" text-anchor="start"><tspan x="0" dy="0">ScratchNews</tspan></text><path d="M181.04509,195.64879h-8.71313v-10.6397h8.71313z" fill="#cc8829" stroke="none"/><path d="M176.88045,195.6664v-20.46677l3.90302,-0.07587l0.09189,-5.0222h6.64479v20.71239l-3.91923,0.16783l-0.04923,4.68462z" fill="#ffaa33" stroke="none"/><path d="M201.40189,164.35122h8.71313v10.6397h-8.71313z" fill="#cc8829" stroke="none"/><path d="M205.56653,164.33361v20.46677l-3.90302,0.07587l-0.09189,5.0222h-6.64479v-20.71239l3.91923,-0.16783l0.04923,-4.68462z" fill="#ffaa33" stroke="none"/><path d="M190.06459,189.91166l-0.03808,-3.62362l-3.03158,-0.12982v-16.02128h5.13983l0.07108,3.88473l3.01904,0.05869v15.8313z" fill="#ffaa33" stroke="none"/></g></g></svg>
    </a>
    <nav>
    <?php if (!empty($_SESSION['reader_username'])): ?>
        <span>Hi, <?= e($_SESSION['reader_username']) ?>!</span>
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
<main>
<?php if (!$user): ?>
    <h2>User Not Found</h2>
    <p>No account exists under that username.</p>
<?php else: ?>
    <h2>@<?= e($user['username']) ?></h2>
    <p class="meta">Member since <?= date('M j, Y', strtotime($user['created_at'])) ?></p>

    <?php if (!empty($_SESSION['reader_id']) && $_SESSION['reader_id'] == $user['id']): ?>
    <p><a href="/delete-account">Delete my account</a></p>
    <form method="post" style="display:inline;">
        <input type="hidden" name="dark_mode" value="<?= !empty($_SESSION['dark_mode']) ? '0' : '1' ?>">
        <button type="submit" class="text-action" style="background:none;border:none;padding:0;">
            <?= !empty($_SESSION['dark_mode']) ? 'Switch to light mode' : 'Switch to dark mode' ?>
        </button>
    </form>
<?php endif; ?>

    <h3>Comments (<?= count($comments) ?>)</h3>
    <?php foreach ($comments as $c): ?>
        <div class="comment">
            <a href="/article/<?= (int)$c['article_id'] ?>"><strong><?= e($c['article_title']) ?></strong></a>
            <span class="meta"><?= date('M j, Y g:i A', strtotime($c['created_at'])) ?></span>
            <p><?= e($c['content']) ?></p>
        </div>
    <?php endforeach; ?>
<?php endif; ?>
</main>
<?php include __DIR__ . '/includes/footer.php'; ?>
</body>
</html>