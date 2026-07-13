<?php
require_once __DIR__ . '/../functions.php';
session_start();

if (empty($_SESSION['is_admin'])) {
    header('Location: /login');
    exit;
}
