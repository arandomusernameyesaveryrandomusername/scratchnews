<?php
require_once __DIR__ . '/functions.php';
session_start();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrf();
    stopImpersonation();
}
header('Location: /');
exit;
