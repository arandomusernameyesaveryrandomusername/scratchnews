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
<body class="<?= !empty($_SESSION['dark_mode']) ? 'dark' : '' ?>">
<header>
    <a href="/" class="logo-link">
<svg viewBox="0,0,136.90609,31.33279" class="logo-svg" xmlns="http://www.w3.org/2000/svg"><g transform="translate(-172.33195,-164.3336)"><g stroke-miterlimit="10"><text transform="translate(217.16808,185.69599) scale(0.5,0.5)" font-size="40" fill="#ffffff" stroke="#ffaa33" stroke-width="3" font-family="Scratch" font-weight="normal" text-anchor="start"><tspan x="0" dy="0">ScratchNews</tspan></text><text transform="translate(217.16808,185.69599) scale(0.5,0.5)" font-size="40" fill="#ffffff" stroke="none" stroke-width="1" font-family="Scratch" font-weight="normal" text-anchor="start"><tspan x="0" dy="0">ScratchNews</tspan></text><path d="M181.04509,195.64879h-8.71313v-10.6397h8.71313z" fill="#cc8829" stroke="none"/><path d="M176.88045,195.6664v-20.46677l3.90302,-0.07587l0.09189,-5.0222h6.64479v20.71239l-3.91923,0.16783l-0.04923,4.68462z" fill="#ffaa33" stroke="none"/><path d="M201.40189,164.35122h8.71313v10.6397h-8.71313z" fill="#cc8829" stroke="none"/><path d="M205.56653,164.33361v20.46677l-3.90302,0.07587l-0.09189,5.0222h-6.64479v-20.71239l3.91923,-0.16783l0.04923,-4.68462z" fill="#ffaa33" stroke="none"/><path d="M190.06459,189.91166l-0.03808,-3.62362l-3.03158,-0.12982v-16.02128h5.13983l0.07108,3.88473l3.01904,0.05869v15.8313z" fill="#ffaa33" stroke="none"/></g></g></svg>
    </a>
    <nav><a href="/">Home</a></nav>
</header>
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