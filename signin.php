<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
require_once __DIR__ . '/functions.php';
startSession();

if (!empty($_SESSION['reader_id'])) {
    header('Location: ' . (!empty($_SESSION['is_admin']) ? '/admin/' : '/'));
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $user = getUserByUsername($username);

    if ($user && password_verify($password, $user['password_hash'])) {
    $_SESSION['reader_id'] = $user['id'];
    $_SESSION['reader_username'] = $user['username'];
    $_SESSION['is_admin'] = !empty($user['is_admin']);
    $_SESSION['dark_mode'] = $user['dark_mode'];
    $token = setRememberToken($user['id']);
    setcookie('remember_me', $user['id'] . ':' . $token, time() + 60 * 60 * 24 * 30, '/; SameSite=Lax', '', true, true);
    header('Location: ' . ($_SESSION['is_admin'] ? '/admin/' : '/'));
    exit;
} else {
    $error = 'Incorrect username or password.';
}
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="icon" type="image/svg+xml" href="/assets/favicon.svg">
<title>Log In - <?= e(SITE_NAME) ?></title>
<link rel="stylesheet" href="/assets/style.css?v=6">
</head>
<body <?php include __DIR__ . '/includes/theme-body.php'; ?>>
<header>
    <a href="/" class="logo-link">
<svg viewBox="0,0,136.90609,31.33279" class="logo-svg" xmlns="http://www.w3.org/2000/svg"><g transform="translate(-172.33195,-164.3336)"><g stroke-miterlimit="10"><text transform="translate(217.16808,185.69599) scale(0.5,0.5)" font-size="40" fill="#ffffff" stroke="#ffaa33" stroke-width="3" font-family="Scratch" font-weight="normal" text-anchor="start"><tspan x="0" dy="0">ScratchNews</tspan></text><text transform="translate(217.16808,185.69599) scale(0.5,0.5)" font-size="40" fill="#ffffff" stroke="none" stroke-width="1" font-family="Scratch" font-weight="normal" text-anchor="start"><tspan x="0" dy="0">ScratchNews</tspan></text><path d="M181.04509,195.64879h-8.71313v-10.6397h8.71313z" fill="#cc8829" stroke="none"/><path d="M176.88045,195.6664v-20.46677l3.90302,-0.07587l0.09189,-5.0222h6.64479v20.71239l-3.91923,0.16783l-0.04923,4.68462z" fill="#ffaa33" stroke="none"/><path d="M201.40189,164.35122h8.71313v10.6397h-8.71313z" fill="#cc8829" stroke="none"/><path d="M205.56653,164.33361v20.46677l-3.90302,0.07587l-0.09189,5.0222h-6.64479v-20.71239l3.91923,-0.16783l0.04923,-4.68462z" fill="#ffaa33" stroke="none"/><path d="M190.06459,189.91166l-0.03808,-3.62362l-3.03158,-0.12982v-16.02128h5.13983l0.07108,3.88473l3.01904,0.05869v15.8313z" fill="#ffaa33" stroke="none"/></g></g></svg>
    </a>
    <nav><a href="/register">Sign Up</a></nav>
</header>
<main>
    <h2>Log In</h2>
    <?php if ($error): ?><div class="alert error"><?= e($error) ?></div><?php endif; ?>
    <form method="post">
        <label for="username">Username</label>
        <input type="text" id="username" name="username" required>
        <label for="password">Password</label>
        <input type="password" id="password" name="password" required>
        <button class="btn" type="submit">Log In</button>
    </form>
    <p style="margin-top:1rem;">Don't have an account? <a href="/register">Sign up</a></p>
</main>
</body>
</html>