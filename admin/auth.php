<?php
require_once __DIR__ . '/../functions.php';
secureStartSession();  // Enhanced session management with security measures

// Admin password verification for authentication
if (empty($_SESSION['is_admin']) || !verifyAdminCredentials()) {
    // Clear any existing session and redirect to login
    session_unset();
    session_destroy();
    setcookie('remember_me', '', time() - 3600, '/');
    header('Location: /login?error=session_required');
    exit;
}

// Additional security check: re-verify admin password for sensitive operations
if (isset($_GET['require_reauth']) && $_GET['require_reauth'] === 'true') {
    if (!verifyAdminCredentials(true)) {  // force_password_check = true
        header('Location: /login?error=reauth_required');
        exit;
    }
}
