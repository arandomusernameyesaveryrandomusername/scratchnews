<?php
require_once __DIR__ . '/../functions.php';
require_once __DIR__ . '/auth.php';

$db = getDB();
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $oldId = (int)($_POST['old_id'] ?? 0);
    $newId = (int)($_POST['new_id'] ?? 0);

    if ($oldId <= 0 || $newId <= 0) {
        $error = 'Both IDs must be positive numbers.';
    } elseif ($oldId === $newId) {
        $error = 'New ID must be different from the current ID.';
    } else {
        $stmt = $db->prepare("SELECT id FROM articles WHERE id = ?");
        $stmt->bind_param("i", $oldId);
        $stmt->execute();
        $exists = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        $stmt = $db->prepare("SELECT id FROM articles WHERE id = ?");
        $stmt->bind_param("i", $newId);
        $stmt->execute();
        $targetTaken = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$exists) {
            $error = "Article #$oldId doesn't exist.";
        } elseif ($targetTaken) {
            $error = "ID #$newId is already in use by another article. Move or delete that one first.";
        } else {
            $db->query("SET FOREIGN_KEY_CHECKS=0");

            $stmt = $db->prepare("UPDATE articles SET id = ? WHERE id = ?");
            $stmt->bind_param("ii", $newId, $oldId);
            $stmt->execute();
            $stmt->close();

            $stmt = $db->prepare("UPDATE comments SET article_id = ? WHERE article_id = ?");
            $stmt->bind_param("ii", $newId, $oldId);
            $stmt->execute();
            $stmt->close();

            $stmt = $db->prepare("UPDATE likes SET article_id = ? WHERE article_id = ?");
            $stmt->bind_param("ii", $newId, $oldId);
            $stmt->execute();
            $stmt->close();

            $db->query("SET FOREIGN_KEY_CHECKS=1");

            $success = "Moved article from #$oldId to #$newId.";
        }
    }
}

$articles = getAllArticles();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="icon" type="image/svg+xml" href="/assets/favicon.svg">
<title>Move Article - <?= e(SITE_NAME) ?></title>
<link rel="stylesheet" href="/assets/style.css?v=9">
</head>
<body>
<?php require_once __DIR__ . '/nav.php'; ?>
<main>
    <h2>Move Article</h2>

    <?php if ($error): ?><div class="alert error"><?= e($error) ?></div><?php endif; ?>
    <?php if ($success): ?><div class="alert success"><?= e($success) ?></div><?php endif; ?>

    <form method="post">
        <label for="old_id">Current ID</label>
        <input type="number" id="old_id" name="old_id" required>
        <label for="new_id">New ID</label>
        <input type="number" id="new_id" name="new_id" required>
        <button class="btn" type="submit">Move</button>
    </form>

    <h3 style="margin-top:2rem;">Current Articles</h3>
    <table>
        <tr><th>ID</th><th>Title</th></tr>
        <?php foreach ($articles as $a): ?>
            <tr><td>#<?= (int)$a['id'] ?></td><td><?= e($a['title']) ?></td></tr>
        <?php endforeach; ?>
    </table>
</main>
</body>
</html>