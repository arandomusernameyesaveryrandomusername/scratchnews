<?php
require_once __DIR__ . '/../functions.php';
requireApiAccess();
header('Content-Type: application/json');

$id = isset($_GET['id']) ? (int)$_GET['id'] : null;

if ($id !== null) {
    $article = getArticleById($id);
    if (!$article || $article['status'] !== 'published') {
        http_response_code(404);
        echo json_encode(['error' => 'Article not found']);
        exit;
    }
    echo json_encode(formatArticleForApi($article));
    exit;
}

$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = min(50, max(1, (int)($_GET['per_page'] ?? 20)));

$articles = getAllArticles();

$categorySlug = $_GET['category'] ?? '';
if ($categorySlug !== '') {
    $cat = getCategoryBySlug($categorySlug);
    if (!$cat) {
        echo json_encode(['data' => [], 'page' => $page, 'per_page' => $perPage, 'total' => 0]);
        exit;
    }
    $articles = array_values(array_filter($articles, function ($a) use ($cat) {
        $ids = getArticleCategoryIds((int)$a['id']);
        return in_array((int)$cat['id'], $ids, true);
    }));
}

$total = count($articles);
$slice = array_slice($articles, ($page - 1) * $perPage, $perPage);

echo json_encode([
    'data' => array_map('formatArticleForApi', $slice),
    'page' => $page,
    'per_page' => $perPage,
    'total' => $total,
]);
