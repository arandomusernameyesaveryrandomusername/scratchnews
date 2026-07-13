<?php
require_once 'functions.php';

$token = isset($_GET['token']) ? $_GET['token'] : '';
$message = '';
$success = false;

if ($token === '') {
    $message = "No verification token provided.";
} else {
    $user = getUserByVerificationToken($token);
    if ($user) {
        markEmailVerified($user['id']);
        $success = true;
        $message = "Your email has been verified! You can now like and comment on articles.";
    } else {
        $message = "This verification link is invalid or has already been used.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Verify Email - ScratchNews</title>
<link rel="stylesheet" href="assets/style.css?v=9">
</head>
<body>

<!-- paste the inline SVG logo header here, same as other pages -->

<main style="max-width: 600px; margin: 60px auto; text-align: center;">
    <h1><?php echo $success ? "Email Verified" : "Verification Failed"; ?></h1>
    <p><?php echo htmlspecialchars($message); ?></p>
    <p><a href="/">Return to ScratchNews</a></p>
</main>

</body>
</html>