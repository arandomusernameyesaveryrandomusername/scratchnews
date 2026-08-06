<?php
require_once __DIR__ . '/functions.php';
startSession();

if (!empty($_SESSION['reader_id'])) {
    header('Location: ' . (!empty($_SESSION['is_admin']) ? '/admin/' : '/'));
    exit;
}

$error = '';

$clientIP = $_SERVER['REMOTE_ADDR'] ?? '';

// Enhanced rate limiting: 1 password attempt every 2 seconds (~30 attempts per minute)
$maxAllowedFailedAttempts = 3; // Much stricter than original 5/hour

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    // Get user first before any validation
    $user = getUserByUsername($username);

    // Check if IP is locked out
    if (isIPLocked($clientIP)) {
        $error = 'Too many failed attempts. Account temporarily locked.';
    }

    if (empty($error) && tooManyFailedLogin($username, $clientIP)) {
        lockIP($clientIP);
        $error = 'Rate limit exceeded. Please wait before trying again.';
    }

    // Log failed attempts only
    if (!$user || !$password) {
        logLoginAttempt(($user ?? '')['username'] ?? '', $clientIP, false); // failure
    }

    if ($user && password_verify($password, $user['password_hash'])) {
        // Email verification check
        if (empty($error) && !$user['email_verified']) {
            $error = 'Please verify your email address before logging in.';
        }

        if (empty($error)) {
            session_regenerate_id(true); // ← NEW: prevent fixation
            updateUserIp($user['id'], $_SERVER['REMOTE_ADDR'] ?? '');
            logLoginAttempt($username, $clientIP, true); // success

            $_SESSION['reader_id'] = $user['id'];
            $_SESSION['reader_username'] = $user['username'];
            $_SESSION['is_admin'] = !empty($user['is_admin']);
            $_SESSION['dark_mode'] = $user['dark_mode'];

            $token = setRememberToken($user['id']);
            setcookie('remember_me', $user['id'] . ':' . $token, [
                'expires' => time() + 60 * 60 * 24 * 30,
                'path' => '/',
                'secure' => true,
                'httponly' => true,
                'samesite' => 'Lax',
            ]);

            header('Location: ' . ($_SESSION['is_admin'] ? '/admin/' : '/'));
            exit;
        } else {
            $error = 'Incorrect username or password.';
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
<title>Log In - <?= e(SITE_NAME) ?></title>
<link rel="stylesheet" href="/assets/style.css?v=6">
</head>
<body <?php include __DIR__ . '/includes/theme-body.php'; ?>>
<header>
    <a href="/" class="logo-link">
<svg viewBox="0 0,136.90609,31.33279" class="logo-svg" xmlns="http://www.w3.org/2000/svg"><g transform="translate(-172.33195,-164.3336)"><g stroke-miterlimit="10"><text transform="translate(217.16808,185.69599) scale(0.5,0.5)" font-size="40" fill="#ffffff" stroke="#ffaa33" stroke-width="3" font-family="Scratch" font-weight="normal" text-anchor="start"><tspan x="0" dy="0">ScratchNews</tspan></text><text transform="translate(217.16808,185.69599) scale(0.5,0.5)" font-size="40" fill="#ffffff" stroke="none" stroke-width="1" font-family="Scratch" font-weight="normal" text-anchor="start"><tspan x="0" dy="0">ScratchNews</tspan></text><path d="M181.04509,195.64879h-8.71313v-10.6397 h8.71313z" fill="#cc8829" stroke="none"/><path d="M176.88045,195.6664 v-20.46677 l3.90302,-0.07587 l0.09189,-5.0222 h6.64479 v20.71239 l-3.91923,0.16783 l-0.04923,4.68462 z" fill="#ffaa33" stroke="none"/><path d="M201.40189,164.35122 h8.71313 v10.6397 h-8.71313z" fill="#cc8829" stroke="none"/><path d="M205.56653,164.33361 v20.46677 l-3.90302,0.07587 l-0.09189,5.0222 h-6.64479 v-20.71239 l3.91923,-0.16783 l0.04923,-4.68462 z" fill="#ffaa33" stroke="none"/><path d="M190.06459,189.91166 l-0.03808,-3.62362 l-3.03158,-0.12982 v-16.02128 h5.13983 l0.07108,3.88473 l3.01904,0.05869 v15.8313 z" fill="#ffaa33" stroke="none"/></g></g></svg>
    <a href="/" class="logo-link">
        <svg viewBox="0 0,136.90609,31.33279" class="logo-svg" xmlns="http://www.w3.org/2000/svg">
            <g transform="translate(-172.33195,-164.3336)">
                <g stroke-miterlimit="10">
                    <text transform="translate(217.16808,185.69599) scale(0.5,0.5)" font-size="40" fill="#ffffff" stroke="#ffaa33" stroke-width="3" font-family="Scratch" font-weight="normal" text-anchor="start">
                        <tspan x="0" dy="0">ScratchNews</tspan>
                    </text>
                    <text transform="translate(217.16808,185.69599) scale(0.5,0.5)" font-size="40" fill="#ffffff" stroke="none" stroke-width="1" font-family="Scratch" font-weight="normal" text-anchor="start">
                        <tspan x="0" dy="0">ScratchNews</tspan>
                    </text>
                    <path d="M181.04509,195.64879h-8.71313v-10.6397 h8.71313z" fill="#cc8829" stroke="none"/>
                    <path d="M176.88045,195.6664 v-20.46677 l3.90302,-0.07587 l0.09189,-5.0222 h6.64479 v20.71239 l-3.91923,0.16783 l-0.04923,4.68462 z" fill="#ffaa33" stroke="none"/>
                    <path d="M201.40189,164.35122 h8.71313 v10.6397 h-8.71313z" fill="#cc8829" stroke="none"/>
                    <path d="M205.56653,164.33361 v20.46677 l-3.90302,0.07587 l-0.09189,5.0222 h-6.64479 v-20.71239 l3.91923,-0.16783 l0.04923,-4.68462 z" fill="#ffaa33" stroke="none"/>
                    <path d="M190.06459,189.91166 l-0.03808,-3.62362 l-3.03158,-0.12982 v-16.02128 h5.13983 l0.07108,3.88473 l3.01904,0.05869 v15.8313 z" fill="#ffaa33" stroke="none"/>
                </g>
            </g>
        </svg>
    </a>
    <nav>
        <a href="/register">Sign Up</a>
    </nav>
</header>
<main>
    <h2>Log In</h2>
    <?php if ($error): ?><div class="alert error"><?= e($error) ?></div><?php endif; ?>
    <div style="display:flex;justify-content:center;margin-bottom:1rem;">
        <div id="g_id_onload"
             data-client_id="<?= e(GOOGLE_CLIENT_ID) ?>"
             data-callback="handleGoogleCredential"
             data-auto_prompt="false">
        </div>
        <div class="g_id_signin" data-type="standard" data-size="large" data-width="300"></div>
    </div>
    <p style="text-align:center;color:#888;font-size:0.85rem;margin:0.75rem 0;">— or log in with a username and password —</p>
    <form method="post">
        <?= csrfField(); ?>
        <label for="username">Username</label>
        <input type="text" id="username" name="username" required>
        <label for="password">Password</label>
        <input type="password" id="password" name="password" required>
        <button class="btn" type="submit">Log In</button>
    </form>
    <p style="margin-top:1rem;">Don't have an account? <a href="/register">Sign up</a></p>
</main>
<script src="https://accounts.google.com/gsi/client" async defer></script>
<script>
function handleGoogleCredential(response) {
    fetch('/google-auth.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'credential=' + encodeURIComponent(response.credential)
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
        if (data.redirect) { window.location.href = data.redirect; }
        else { alert(data.error || 'Google sign-in failed.'); }
    })
    .catch(function() { alert('Google sign-in failed. Please try again.'); });
}
</script>
</body>
</html>