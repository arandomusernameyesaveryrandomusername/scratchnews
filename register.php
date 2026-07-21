<?php
require_once __DIR__ . '/functions.php';
session_start();

if (!empty($_SESSION['reader_id'])) { header('Location: /'); exit; }

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrf();

    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $honeypot = trim($_POST['website'] ?? '');

    if ($honeypot !== '') {
        // Silently pretend it worked; bots that fill every field get nothing to learn from
        header('Location: /?justregistered=1');
        exit;
    } elseif (tooManySignupAttempts($ip)) {
        $error = 'Too many signup attempts from your network. Please try again later.';
    } elseif ($username === '' || $email === '' || $password === '') {
        $error = 'All fields are required.';
    } elseif (!preg_match('/^[A-Za-z0-9_]{3,20}$/', $username)) {
        $error = 'Username must be 3-20 characters and can only contain letters, numbers, and underscores.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters.';
    } elseif (isDisposableEmail($email)) {
        $error = 'Please use a permanent email address, not a temporary/disposable one.';
    } elseif (!checkdnsrr(substr(strrchr($email, '@'), 1), 'MX')) {
        $error = 'That email address domain doesn\'t appear to accept mail. Please check it and try again.';
    } else {
        $result = createUser($username, $email, $password);
        if ($result === 'duplicate') {
            $error = 'That username or email is already taken.';
            logSignupAttempt($ip, false);
        } else {
            logSignupAttempt($ip, true);
            $token = issueVerificationToken($result);
            sendVerificationEmail($email, $username, $token);

            $_SESSION['reader_id'] = $result;
            $_SESSION['reader_username'] = $username;
            header('Location: /?justregistered=1');
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="icon" type="image/svg+xml" href="/assets/favicon.svg">
<title>Sign Up - <?= e(SITE_NAME) ?></title>
<link rel="stylesheet" href="/assets/style.css?v=6">
</head>
<body>
<header>
    <a href="/" class="logo-link">
<svg viewBox="0,0,136.90609,31.33279" class="logo-svg" xmlns="http://www.w3.org/2000/svg"><g transform="translate(-172.33195,-164.3336)"><g stroke-miterlimit="10"><text transform="translate(217.16808,185.69599) scale(0.5,0.5)" font-size="40" fill="#ffffff" stroke="#ffaa33" stroke-width="3" font-family="Scratch" font-weight="normal" text-anchor="start"><tspan x="0" dy="0">ScratchNews</tspan></text><text transform="translate(217.16808,185.69599) scale(0.5,0.5)" font-size="40" fill="#ffffff" stroke="none" stroke-width="1" font-family="Scratch" font-weight="normal" text-anchor="start"><tspan x="0" dy="0">ScratchNews</tspan></text><path d="M181.04509,195.64879h-8.71313v-10.6397h8.71313z" fill="#cc8829" stroke="none"/><path d="M176.88045,195.6664v-20.46677l3.90302,-0.07587l0.09189,-5.0222h6.64479v20.71239l-3.91923,0.16783l-0.04923,4.68462z" fill="#ffaa33" stroke="none"/><path d="M201.40189,164.35122h8.71313v10.6397h-8.71313z" fill="#cc8829" stroke="none"/><path d="M205.56653,164.33361v20.46677l-3.90302,0.07587l-0.09189,5.0222h-6.64479v-20.71239l3.91923,-0.16783l0.04923,-4.68462z" fill="#ffaa33" stroke="none"/><path d="M190.06459,189.91166l-0.03808,-3.62362l-3.03158,-0.12982v-16.02128h5.13983l0.07108,3.88473l3.01904,0.05869v15.8313z" fill="#ffaa33" stroke="none"/></g></g></svg>
    </a>
    <nav><a href="/login">Log In</a></nav>
</header>
<main>
    <h2>Create a ScratchNews Account</h2>
    <?php if ($error): ?><div class="alert error"><?= e($error) ?></div><?php endif; ?>
    <form method="post">
        <?= csrfField() ?>
        <div style="position:absolute;left:-9999px;" aria-hidden="true">
            <label for="website">Leave this field blank</label>
            <input type="text" id="website" name="website" tabindex="-1" autocomplete="off">
        </div>
        <label for="username">Username</label>
        <input type="text" id="username" name="username" value="<?= e($_POST['username'] ?? '') ?>" required>
        <label for="email">Email</label>
        <input type="email" id="email" name="email" value="<?= e($_POST['email'] ?? '') ?>" required>
        <label for="password">Password</label>
        <input type="password" id="password" name="password" required>
        <button class="btn" type="submit">Sign Up</button>
    </form>
    <p style="margin-top:1rem;">Already have an account? <a href="/signin">Log in</a></p>
</main>
</body>
</html>