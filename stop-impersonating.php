<?php
require_once __DIR__ . '/functions.php';
startSession();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrf();
    stopImpersonation();
}
header('Location: /');
exit;
