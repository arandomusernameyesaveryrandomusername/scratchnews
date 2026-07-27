<?php
require_once __DIR__ . '/../functions.php';
requireApiAccess();
header('Content-Type: application/json');

$category = $_GET['category'] ?? 'all';
$sort = $_GET['sort'] ?? 'metrics';
$author = trim($_GET['author'] ?? '');
$from = trim($_GET['from'] ?? '');
$to = trim($_GET['to'] ?? '');
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = min(50, max(1, (int)($_GET['per_page'] ?? 20)));

$articles = getExploreArticles($category, $sort, $author, $from, $to);
$total = count($articles);
$slice = array_slice($articles, ($page - 1) * $perPage, $perPage);

echo json_encode([
    'data' => array_map('formatArticleForApi', $slice),
    'page' => $page,
    'per_page' => $perPage,
    'total' => $total,
]);
