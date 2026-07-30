<?php
require_once __DIR__ . '/../functions.php';
require_once __DIR__ . '/auth.php';

function githubApiRequest(string $method, string $path, ?array $body = null): array {
    $ch = curl_init('https://api.github.com' . $path);
    curl_setopt_array($ch, [
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . GITHUB_TOKEN,
            'User-Agent: ScratchNews-Sync',
            'Accept: application/vnd.github+json',
            'Content-Type: application/json',
        ],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST => $method,
        CURLOPT_POSTFIELDS => $body !== null ? json_encode($body) : null,
    ]);
    $response = curl_exec($ch);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return ['status' => $status, 'body' => json_decode($response, true)];
}

function pushJsonToGithub(string $path, array $data): array {
    $content = base64_encode(json_encode($data, JSON_PRETTY_PRINT));
    $existing = githubApiRequest('GET', '/repos/' . GITHUB_REPO . '/contents/' . $path . '?ref=' . GITHUB_BRANCH);
    $sha = $existing['status'] === 200 ? ($existing['body']['sha'] ?? null) : null;

    $payload = [
        'message' => 'Sync ' . $path . ' via admin sync tool',
        'content' => $content,
        'branch' => GITHUB_BRANCH,
    ];
    if ($sha) $payload['sha'] = $sha;

    return githubApiRequest('PUT', '/repos/' . GITHUB_REPO . '/contents/' . $path, $payload);
}

$results = null;

function getEngagementCounts(): array {
    $db = getDB();
    $counts = [];
    foreach (['likes' => 'like_count', 'dislikes' => 'dislike_count', 'comments' => 'comment_count'] as $table => $key) {
        $rows = $db->query("SELECT article_id, COUNT(*) AS c FROM $table GROUP BY article_id")->fetch_all(MYSQLI_ASSOC);
        foreach ($rows as $row) {
            $counts[(int)$row['article_id']][$key] = (int)$row['c'];
        }
    }
    return $counts;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrf();

    $engagement = getEngagementCounts();
$articles = array_map(function ($a) use ($engagement) {
    $formatted = formatArticleForApi($a);
    $id = $formatted['id'];
    $formatted['likes'] = $engagement[$id]['like_count'] ?? 0;
    $formatted['dislikes'] = $engagement[$id]['dislike_count'] ?? 0;
    $formatted['comments'] = $engagement[$id]['comment_count'] ?? 0;
    return $formatted;
}, getAllArticles());
    $articlesPayload = ['data' => $articles, 'total' => count($articles), 'synced_at' => gmdate('c')];

    $categoriesPayload = ['data' => getAllCategories(), 'synced_at' => gmdate('c')];

    $results = [
        'articles.json' => pushJsonToGithub('data/articles.json', $articlesPayload),
        'categories.json' => pushJsonToGithub('data/categories.json', $categoriesPayload),
    ];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="icon" type="image/svg+xml" href="/assets/favicon.svg">
<title>GitHub Sync - <?= e(SITE_NAME) ?></title>
<link rel="stylesheet" href="/assets/style.css?v=9">
</head>
<body class="<?= !empty($_SESSION['dark_mode']) ? 'dark' : '' ?>">
<?php require_once __DIR__ . '/nav.php'; ?>
<main>
    <h2>GitHub Sync (Admin)</h2>
    <p>Pushes published articles and categories to GitHub as static JSON, for API consumers blocked by InfinityFree's bot protection (e.g. ScratchStats).</p>

    <form method="post">
        <?= csrfField() ?>
        <button class="btn" type="submit">Sync Now</button>
    </form>

    <?php if ($results): ?>
        <h3 style="margin-top:2rem;">Last sync result</h3>
        <?php foreach ($results as $file => $r): ?>
            <p><?= e($file) ?>: HTTP <?= (int)$r['status'] ?>
                — <?= $r['status'] < 300 ? 'OK' : e(json_encode($r['body'])) ?></p>
        <?php endforeach; ?>
    <?php endif; ?>
</main>
</body>
</html>