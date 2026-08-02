<?php
require_once __DIR__ . '/functions.php';
startSession();

if (empty($_SESSION['is_admin'])) {
    header('Location: /login');
    exit;
}

$text = trim($_GET['text'] ?? 'fuck you idiot');

$rawResult = null;
$httpCode = null;
$curlErr = null;

if (defined('OPENAI_API_KEY')) {
    $ch = curl_init('https://api.openai.com/v1/moderations');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 8);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . OPENAI_API_KEY,
    ]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['model' => 'omni-moderation-latest', 'input' => $text]));
    $rawResult = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlErr = curl_error($ch);
    curl_close($ch);
}
?>
<!DOCTYPE html>
<html><head><meta charset="UTF-8"><title>Moderation Debug</title></head>
<body style="font-family:monospace;background:#111;color:#eee;padding:2rem;white-space:pre-wrap;">
<h2>Moderation Debug</h2>
<p>Tested string: <strong><?= e($text) ?></strong> (change with ?text=... in the URL)</p>

<p><?php if (!defined('OPENAI_API_KEY')): ?>
<span style="color:#f66;">OPENAI_API_KEY is NOT defined in config.php.</span>
<?php else: ?>
<span style="color:#6f6;">OPENAI_API_KEY is defined (length <?= strlen(OPENAI_API_KEY) ?>).</span>
<?php endif; ?></p>

<p>curl error: <?= e($curlErr ?: '(none)') ?></p>
<p>HTTP status code: <?= e((string)$httpCode) ?></p>
<p>Raw response body:</p>
<pre><?= e($rawResult ?: '(empty)') ?></pre>
</body></html>
