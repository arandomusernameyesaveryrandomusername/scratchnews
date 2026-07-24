<?php
require_once __DIR__ . '/../functions.php';
require_once __DIR__ . '/auth.php';

$db = getDB();
$error = '';
$success = '';

function moveUserId(mysqli $db, int $oldId, int $newId): void {
    $db->begin_transaction();
    try {
        $db->query("SET FOREIGN_KEY_CHECKS=0");

        $refs = [
            ['comments', 'user_id'],
            ['likes', 'user_id'],
            ['dislikes', 'user_id'],
            ['comment_reports', 'reporter_id'],
            ['submissions', 'user_id'],
            ['impersonation_log', 'admin_id'],
            ['impersonation_log', 'target_user_id'],
        ];

        foreach ($refs as [$table, $col]) {
            $stmt = $db->prepare("UPDATE $table SET $col = ? WHERE $col = ?");
            $stmt->bind_param("ii", $newId, $oldId);
            $stmt->execute();
            $stmt->close();
        }

        $stmt = $db->prepare("UPDATE users SET id = ? WHERE id = ?");
        $stmt->bind_param("ii", $newId, $oldId);
        $stmt->execute();
        $stmt->close();

        $db->query("SET FOREIGN_KEY_CHECKS=1");
        $db->commit();
    } catch (Throwable $e) {
        $db->rollback();
        $db->query("SET FOREIGN_KEY_CHECKS=1");
        throw $e;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrf();
    $type = $_POST['type'] ?? 'article';

    if ($type === 'article') {
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
    } elseif ($type === 'user') {
        $oldId = (int)($_POST['old_id'] ?? 0);
        $newId = (int)($_POST['new_id'] ?? 0);

        if ($oldId <= 0 || $newId <= 0) {
            $error = 'Both IDs must be positive numbers.';
        } elseif ($oldId === $newId) {
            $error = 'New ID must be different from the current ID.';
        } else {
            $stmt = $db->prepare("SELECT id FROM users WHERE id = ?");
            $stmt->bind_param("i", $oldId);
            $stmt->execute();
            $exists = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            $stmt = $db->prepare("SELECT id FROM users WHERE id = ?");
            $stmt->bind_param("i", $newId);
            $stmt->execute();
            $targetTaken = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if (!$exists) {
                $error = "User #$oldId doesn't exist.";
            } elseif ($targetTaken) {
                $error = "ID #$newId is already in use by another user.";
            } else {
                try {
                    moveUserId($db, $oldId, $newId);
                    $success = "Moved user from #$oldId to #$newId.";
                } catch (Throwable $e) {
                    $error = "Move failed: " . $e->getMessage();
                }
            }
        }
    } elseif ($type === 'cleanup_anonymized') {
        $error = 'This tool has been retired — anonymized accounts keep their original ID. anonymizeUser() already scrubs username/email/password, which is sufficient.';
    }
}

$articles = getAllArticles();
$allUsers = $db->query("SELECT id, username FROM users ORDER BY id ASC")->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="icon" type="image/svg+xml" href="/assets/favicon.svg">
<title>Move Content - <?= e(SITE_NAME) ?></title>
<link rel="stylesheet" href="/assets/style.css?v=9">
</head>
<body class="<?= !empty($_SESSION['dark_mode']) ? 'dark' : '' ?>">
<?php require_once __DIR__ . '/nav.php'; ?>
<main>
    <h2>Move Content</h2>

    <?php if ($error): ?><div class="alert error"><?= e($error) ?></div><?php endif; ?>
    <?php if ($success): ?><div class="alert success"><?= e($success) ?></div><?php endif; ?>

    <h3>Move Article</h3>
    <form method="post">
        <?= csrfField() ?>
        <input type="hidden" name="type" value="article">
        <label for="old_id">Current ID</label>
        <input type="number" id="old_id" name="old_id" required>
        <label for="new_id">New ID</label>
        <input type="number" id="new_id" name="new_id" required>
        <button class="btn" type="submit">Move</button>
    </form>

    <h3 style="margin-top:2rem;">Move User</h3>
    <form method="post">
        <?= csrfField() ?>
        <input type="hidden" name="type" value="user">
        <label for="user_old_id">Current ID</label>
        <input type="number" id="user_old_id" name="old_id" required>
        <label for="user_new_id">New ID</label>
        <input type="number" id="user_new_id" name="new_id" required>
        <button class="btn" type="submit">Move</button>
    </form>

    <h3 style="margin-top:2rem;">Current Articles</h3>
    <table>
        <tr><th>ID</th><th>Title</th></tr>
        <?php foreach ($articles as $a): ?>
            <tr><td>#<?= (int)$a['id'] ?></td><td><?= e($a['title']) ?></td></tr>
        <?php endforeach; ?>
    </table>

    <h3 style="margin-top:2rem;">Current Users</h3>
    <table>
        <tr><th>ID</th><th>Username</th></tr>
        <?php foreach ($allUsers as $u): ?>
            <tr><td>#<?= (int)$u['id'] ?></td><td><?= e($u['username']) ?></td></tr>
        <?php endforeach; ?>
    </table>
</main>
</body>
</html>