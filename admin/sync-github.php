<?php
require_once __DIR__ . '/../functions.php';
require_once __DIR__ . '/auth.php';

$results = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrf();
    $results = syncToGithub();
}