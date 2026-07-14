<?php
require_once __DIR__ . '/auth.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$article = getArticleById($id);

if (!$article) {
    header('Location: /admin/');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $summary = trim($_POST['summary'] ?? '');
    $content = trim($_POST['content'] ?? '');
    $author = trim($_POST['author'] ?? 'ScratchNews Staff');

    if ($title === '' || $summary === '' || $content === '') {
        $error = 'Title, summary, and content are all required.';
    } else {
        try {
            $imageUrl = $article['image_url'] ?? null;
            if (!empty($_POST['remove_image']) && $imageUrl) {
                deleteUploadedImage($imageUrl);
                $imageUrl = null;
            }
            if (!empty($_FILES['cover_image']['tmp_name'])) {
                $newUrl = saveUploadedImage($_FILES['cover_image']);
                if ($newUrl) {
                    if ($imageUrl) deleteUploadedImage($imageUrl);
                    $imageUrl = $newUrl;
                }
            }
            updateArticle($id, $title, $summary, $content, $author ?: 'ScratchNews Staff', $imageUrl);
            header('Location: /admin/?updated=' . $id);
            exit;
        } catch (RuntimeException $e) {
            $error = $e->getMessage();
        }
    }
    $article = ['id' => $id, 'title' => $title, 'summary' => $summary, 'content' => $content, 'author' => $author, 'image_url' => $imageUrl ?? null];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/svg+xml" href="/assets/favicon.svg">
<title>Edit Article - <?= e(SITE_NAME) ?></title>
<link rel="stylesheet" href="/assets/style.css?v=2">
    <link href="https://cdn.jsdelivr.net/npm/quill@2.0.2/dist/quill.snow.css" rel="stylesheet">
</head>
<body class="<?= !empty($_SESSION['dark_mode']) ? 'dark' : '' ?>">
<?php require_once __DIR__ . '/nav.php'; ?>
<main>
    <h2>Edit Article #<?= (int)$id ?></h2>
    <?php if ($error): ?><div class="alert error"><?= e($error) ?></div><?php endif; ?>
    <form method="post" enctype="multipart/form-data">
        <label for="title">Title</label>
        <input type="text" id="title" name="title" value="<?= e($article['title']) ?>" required>

        <label for="summary">Summary (shown on homepage)</label>
        <input type="text" id="summary" name="summary" value="<?= e($article['summary']) ?>" required>

        <label for="author">Author</label>
        <input type="text" id="author" name="author" value="<?= e($article['author']) ?>">

        <label for="cover_image">Cover Image (optional)</label>
        <?php if (!empty($article['image_url'])): ?>
            <div class="cover-preview">
                <img src="<?= e($article['image_url']) ?>" alt="" style="max-width:200px;display:block;margin-bottom:8px;">
                <label><input type="checkbox" name="remove_image" value="1"> Remove image</label>
            </div>
        <?php endif; ?>
        <input type="file" id="cover_image" name="cover_image" accept="image/jpeg,image/png,image/gif,image/webp">

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
    <button class="ql-image" title="Insert image">🖼️</button>
</div>
<div id="editor-container"><?= $article['content'] ?></div>
<textarea id="content" name="content" style="display:none;"></textarea>

        <button class="btn" type="submit">Save Changes</button>
        <a href="/admin/" class="btn secondary">Cancel</a>
    </form>
</main>
    <script src="https://cdn.jsdelivr.net/npm/quill@2.0.2/dist/quill.js"></script>
<script>
var quill = new Quill('#editor-container', {
    theme: 'snow',
    modules: { toolbar: '#toolbar' }
});
quill.getModule('toolbar').addHandler('image', function() {
    var input = document.createElement('input');
    input.type = 'file';
    input.accept = 'image/*';
    input.onchange = function() {
        var file = input.files[0];
        if (!file) return;
        var formData = new FormData();
        formData.append('image', file);
        fetch('/admin/upload-image.php', { method: 'POST', body: formData })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (data.url) {
                    var range = quill.getSelection(true);
                    quill.insertEmbed(range.index, 'image', data.url);
                } else {
                    alert(data.error || 'Upload failed.');
                }
            });
    };
    input.click();
});
document.querySelector('form').addEventListener('submit', function() {
    document.querySelector('#content').value = quill.root.innerHTML;
});
</script>
</body>
</html>