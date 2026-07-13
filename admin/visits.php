<?php
require_once __DIR__ . '/auth.php';
$visits = getRecentVisits(200);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/svg+xml" href="/assets/favicon.svg">
<title>Dashboard - <?= e(SITE_NAME) ?></title>
<link rel="stylesheet" href="/assets/style.css?v=2">
</head>
<body class="<?= !empty($_SESSION['dark_mode']) ? 'dark' : '' ?>">
<?php require_once __DIR__ . '/nav.php'; ?>
<main>
    <h2>Visitor Log</h2>
    <p>Showing the most recent 200 visits.</p>
    <table>
        <tr><th>Time</th><th>IP Address</th><th>Page</th><th>User Agent</th></tr>
        <?php foreach ($visits as $v): ?>
        <tr>
            <td><?= date('M j, Y g:i A', strtotime($v['visited_at'])) ?></td>
            <td><?= e($v['ip_address']) ?></td>
            <td><?= e($v['page']) ?></td>
            <td style="max-width:300px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;"><?= e($v['user_agent']) ?></td>
        </tr>
        <?php endforeach; ?>
    </table>
</main>
</body>
</html>