<?php
require_once __DIR__ . '/auth.php';

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $summary = trim($_POST['summary'] ?? '');
    $content = trim($_POST['content'] ?? '');
    $author = trim($_POST['author'] ?? 'ScratchNews Staff');

    if ($title === '' || $summary === '' || $content === '') {
        $error = 'Title, summary, and content are all required.';
    } else {
        $id = createArticle($title, $summary, $content, $author ?: 'ScratchNews Staff');
        header('Location: /login/?created=' . $id);
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/svg+xml" href="/assets/favicon.svg">
<title>New Article - <?= e(SITE_NAME) ?></title>
<link rel="stylesheet" href="/assets/style.css?v=2">
<link href="https://cdn.jsdelivr.net/npm/quill@2.0.2/dist/quill.snow.css" rel="stylesheet">
</head>
<script src="https://cdn.jsdelivr.net/npm/quill@2.0.2/dist/quill.js"></script>
<body>
<?php require_once __DIR__ . '/nav.php'; ?>
<main>
    <h2>New Article</h2>
    <?php if ($error): ?><div class="alert error"><?= e($error) ?></div><?php endif; ?>
    <form method="post">
        <label for="title">Title</label>
        <input type="text" id="title" name="title" value="<?= e($_POST['title'] ?? '') ?>" required>

        <label for="summary">Summary</label>
        <input type="text" id="summary" name="summary" value="<?= e($_POST['summary'] ?? '') ?>" required>

        <label for="author">Author</label>
        <input type="text" id="author" name="author" value="<?= e($_POST['author'] ?? 'ScratchNews Staff') ?>">

        <label for="content">Full Article Content</label>
<div id="toolbar">
    <button class="ql-bold" title="Bold (Ctrl+B)"><b>B</b></button>
    <button class="ql-italic" title="Italic (Ctrl+I)"><i>I</i></button>
    <button class="ql-strike" title="Strikethrough"><s>S</s></button>
    <select class="ql-header" title="Heading">
        <option value="1">Heading 1</option>
        <option value="2">Heading 2</option>
        <option value="3">Heading 3</option>
        <option selected value="">Normal</option>
    </select>
    <select class="ql-color" title="Text color"></select>
    <select class="ql-background" title="Highlight color"></select>
    <select class="ql-size" title="Text size">
        <option value="small">Small</option>
        <option selected value="">Normal</option>
        <option value="large">Large</option>
        <option value="huge">Huge</option>
    </select>    
    <button class="ql-link" title="Insert link">🔗</button>
</div>
<div id="editor-container"><?= $_POST['content'] ?? '' ?></div>
<textarea id="content" name="content" style="display:none;"></textarea>

        <button class="btn" type="submit">Publish Article</button>
        <a href="/login/" class="btn secondary">Cancel</a>
    </form>
</main>
    <script src="https://cdn.jsdelivr.net/npm/quill@2.0.2/dist/quill.js"></script>
<script src="https://cdn.jsdelivr.net/npm/quill@2.0.2/dist/quill.js"></script>
<script>
var quill = new Quill('#editor-container', {
    theme: 'snow',
    modules: { toolbar: '#toolbar' }
});
document.querySelector('form').addEventListener('submit', function() {
    document.querySelector('#content').value = quill.root.innerHTML;
});
</script>
</body>
</html>
