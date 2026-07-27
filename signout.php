<?php
require_once __DIR__ . '/functions.php';
startSession();
if (!empty($_SESSION['reader_id'])) {
    clearRememberToken($_SESSION['reader_id']);
}
setcookie('remember_me', '', time() - 3600, '/');
session_destroy();
header('Location: /');
exit;