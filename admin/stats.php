<?php
require_once __DIR__ . '/../functions.php';
require_once __DIR__ . '/auth.php';

$db = getDB();

$daily = $db->query("SELECT visit_date, COUNT(*) AS unique_visitors FROM daily_unique_visitors GROUP BY visit_date ORDER BY visit_date DESC LIMIT 30")->fetch_all(MYSQLI_ASSOC);

$totalUniqueIps = $db->query("SELECT COUNT(DISTINCT ip_address) AS c FROM daily_unique_visitors")->fetch_assoc()['c'];
$totalSignups = $db->query("SELECT COUNT(DISTINCT ip) AS c FROM signup_attempts WHERE successful = 1")->fetch_assoc()['c'];
$conversionRate = $totalUniqueIps > 0 ? round(($totalSignups / $totalUniqueIps) * 100, 2) : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="icon" type="image/svg+xml" href="/assets/favicon.svg">
<title>Stats - <?= e(SITE_NAME) ?></title>
<link rel="stylesheet" href="/assets/style.css?v=9">
</head>
<body class="<?= !empty($_SESSION['dark_mode']) ? 'dark' : '' ?>">
<?php require_once __DIR__ . '/nav.php'; ?>
<main>
    <h2>Stats</h2>

    <p><strong>Overall conversion rate:</strong> <?= e($conversionRate) ?>%
        (<?= (int)$totalSignups ?> unique-IP signups / <?= (int)$totalUniqueIps ?> unique visitor IPs, all-time within retention window)</p>

    <h3 style="margin-top:2rem;">Daily Unique Visitors (last 30 days)</h3>
    <table>
        <tr><th>Date</th><th>Unique Visitors</th></tr>
        <?php foreach ($daily as $d): ?>
            <tr><td><?= e($d['visit_date']) ?></td><td><?= (int)$d['unique_visitors'] ?></td></tr>
        <?php endforeach; ?>
    </table>
</main>
</body>
</html>
