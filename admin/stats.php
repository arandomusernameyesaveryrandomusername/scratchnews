<?php
require_once __DIR__ . '/../functions.php';
require_once __DIR__ . '/auth.php';

$db = getDB();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrf();
    $sessionKey = $_POST['session_key'] ?? '';
    if (($_POST['action'] ?? '') === 'toggle_exclude' && $sessionKey !== '') {
        setSessionExcluded($sessionKey, ($_POST['excluded'] ?? '0') === '1');
    } elseif (($_POST['action'] ?? '') === 'delete_session' && $sessionKey !== '') {
        deleteSession($sessionKey);
    }
    header('Location: /admin/stats.php');
    exit;
}
$sessions = getRecentSessions(30);
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
    <h2>Stats (Admin)</h2>
    <p><a href="/stats.php">View public stats page &rarr;</a></p>

    <p><strong>Overall conversion rate:</strong> <?= e($conversionRate) ?>%
        (<?= (int)$totalSignups ?> unique-IP signups / <?= (int)$totalUniqueIps ?> unique visitor IPs, all-time within retention window)</p>

    <h3 style="margin-top:2rem;">Visitor Map (last 90 days)</h3>
    <div id="visitorMap" style="height:400px; background:#222; border-radius:8px; display:flex; align-items:center; justify-content:center; color:#888;">
        Map rendering — needs a follow-up session to wire up (Leaflet/simple SVG world map + click-into-region), have the lat/long data now.
    </div>

    <h3 style="margin-top:2rem;">Time on Site (last 30 days)</h3>
    <?php $tos = getTimeOnSiteStats(30); ?>
    <?php if ($tos['count'] > 0): ?>
        <p>Average: <?= (int)floor($tos['avg_seconds'] / 60) ?>m <?= (int)($tos['avg_seconds'] % 60) ?>s
            &nbsp;|&nbsp; Median: <?= (int)floor($tos['median_seconds'] / 60) ?>m <?= (int)($tos['median_seconds'] % 60) ?>s
            &nbsp;|&nbsp; Sessions counted: <?= (int)$tos['count'] ?> (excludes flagged sessions below)</p>
    <?php else: ?>
        <p style="color:#888;">No session data yet — will populate as visitors browse with the heartbeat script live.</p>
    <?php endif; ?>

    <table>
        <tr><th>User</th><th>Source</th><th>Time Active</th><th>First Seen</th><th>Last Seen</th><th></th></tr>
        <?php foreach ($sessions as $s): ?>
            <tr style="<?= $s['excluded'] ? 'opacity:0.5;' : '' ?>">
                <td><?= $s['username'] ? e($s['username']) : '-' ?></td>
                <td><?= $s['source'] ? e($s['source']) : '-' ?></td>
                <td><?= (int)floor($s['seconds_active'] / 60) ?>m <?= (int)($s['seconds_active'] % 60) ?>s</td>
                <td><?= e($s['first_seen']) ?></td>
                <td><?= e($s['last_seen']) ?></td>
                <td style="white-space:nowrap;">
                    <form method="post" style="display:inline;">
                        <?= csrfField() ?>
                        <input type="hidden" name="action" value="toggle_exclude">
                        <input type="hidden" name="session_key" value="<?= e($s['session_key']) ?>">
                        <input type="hidden" name="excluded" value="<?= $s['excluded'] ? '0' : '1' ?>">
                        <button class="btn inline" type="submit"><?= $s['excluded'] ? 'Include' : 'Exclude' ?></button>
                    </form>
                    <form method="post" style="display:inline;">
                        <?= csrfField() ?>
                        <input type="hidden" name="action" value="delete_session">
                        <input type="hidden" name="session_key" value="<?= e($s['session_key']) ?>">
                        <button class="btn inline" type="submit">Delete</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
    </table>
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
