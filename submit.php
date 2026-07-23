<?php
require_once __DIR__ . '/functions.php';
session_start();

if (empty($_SESSION['reader_id'])) {
    header('Location: /login');
    exit;
}

$db = getDB();
$stmt = $db->prepare("SELECT email_verified FROM users WHERE id = ?");
$stmt->bind_param("i", $_SESSION['reader_id']);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

$isVerified = $user && (int)$user['email_verified'] === 1;

$error = '';
$success = false;

if ($isVerified && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $summary = trim($_POST['summary'] ?? '');
    $content = $_POST['content'] ?? '';

    if ($title === '' || $summary === '' || $content === '') {
        $error = 'All fields are required.';
    } else {
        $cleanContent = sanitizeArticleHtml($content);
        createSubmission($_SESSION['reader_id'], $title, $summary, $cleanContent);
        $success = true;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="icon" type="image/svg+xml" href="/assets/favicon.svg">
<title>Submit an Article - <?= e(SITE_NAME) ?></title>
<link rel="stylesheet" href="/assets/style.css?v=9">
<link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">
</head>
<body <?php include __DIR__ . '/includes/theme-body.php'; ?>>
<script>if(document.body.hasAttribute('data-theme-auto')&&window.matchMedia&&window.matchMedia('(prefers-color-scheme: dark)').matches){document.body.classList.add('dark');}</script>
<?php include __DIR__ . '/includes/empty-header.php'; ?>
<main>
    <h2>Submit an Article</h2>

    <?php if (!$isVerified): ?>
        <div class="alert error">
            You need to verify your email before submitting an article.
            Check your inbox for the verification link, or
            <a href="/profile">visit your profile</a> for more info.
        </div>
    <?php elseif ($success): ?>
        <div class="alert success">
            Thanks! Your submission is pending review. You'll get an email once it's approved or rejected.
        </div>
    <?php else: ?>
        <?php if ($error): ?><div class="alert error"><?= e($error) ?></div><?php endif; ?>
        <form method="post" id="submitForm">
            <label for="title">Title</label>
            <input type="text" id="title" name="title" value="<?= e($_POST['title'] ?? '') ?>" required>

            <label for="summary">Summary</label>
            <input type="text" id="summary" name="summary" value="<?= e($_POST['summary'] ?? '') ?>" required>

            <label for="editor">Content</label>
            <div id="editor" style="background:#fff; min-height:200px;"></div>
            <input type="hidden" name="content" id="content">

            <button class="btn" type="submit">Submit for Review</button>
        </form>
    <?php endif; ?>
</main>

<footer>
    &copy; <?= e(SITE_NAME) ?> &middot; <a href="/delete-account">Delete Account</a>
</footer>

<?php if ($isVerified && !$success): ?>
<script src="https://cdn.quilljs.com/1.3.6/quill.js"></script>
<script>
    var quill = new Quill('#editor', {
        theme: 'snow',
        modules: {
            toolbar: [
                ['bold', 'italic', 'strike'],
                [{ 'header': [1, 2, false] }],
                [{ 'color': [] }, { 'background': [] }],
                [{ 'size': ['small', false, 'large', 'huge'] }],
                ['link']
            ]
        }
    });

    document.getElementById('submitForm').addEventListener('submit', function(e) {
        document.getElementById('content').value = quill.root.innerHTML;
    });
</script>
<?php endif; ?>
</body>
</html>