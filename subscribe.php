<?php
require_once __DIR__ . '/functions.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrf();
    $email = trim($_POST['email'] ?? '');
    $categoryIds = $_POST['categories'] ?? [];
    $redirectTo = $_POST['redirect'] ?? '/';
    $sep = strpos($redirectTo, '?') !== false ? '&' : '?';

    if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $token = createSubscriber($email, $categoryIds);
        sendSubscriptionConfirmEmail($email, $token);
        header('Location: ' . $redirectTo . $sep . 'subscribed=1');
        exit;
    } else {
        header('Location: ' . $redirectTo . $sep . 'subscribe_error=1');
        exit;
    }
}
header('Location: /');
exit;
