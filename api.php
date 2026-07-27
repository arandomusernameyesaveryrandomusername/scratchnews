<?php
require_once __DIR__ . '/functions.php';
startSession();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="icon" type="image/svg+xml" href="/assets/favicon.svg">
<title>API - <?= e(SITE_NAME) ?></title>
<link rel="stylesheet" href="/assets/style.css?v=15">
</head>
<body <?php include __DIR__ . '/includes/theme-body.php'; ?>>
<script>if(document.body.hasAttribute('data-theme-auto')&&window.matchMedia&&window.matchMedia('(prefers-color-scheme: dark)').matches){document.body.classList.add('dark');}</script>
<?php include __DIR__ . '/includes/empty-header.php'; ?>
<main>
    <h2>Welcome to the ScratchNews API!</h2>
    <p>A small, read-only API for building things on top of ScratchNews — Discord bots, dashboards, whatever you want.
        Access is IP-based and closed by default. If you want in, reach out on <a href="https://discord.gg/Z6GBswx5Q">Discord</a> with the IP address you'll be calling from.</p>

    <h3 style="margin-top:2rem;">GET /api/articles.php</h3>
    <p>Returns published articles, paginated. Optional query params: <code>page</code>, <code>per_page</code> (max 50), <code>category</code> (a category slug).</p>
    <pre class="api-code-block">{
  "data": [ { "id": 15, "title": "...", "summary": "...", "content": "...",
              "image_url": "...", "author": "...", "created_at": "...",
              "updated_at": "...", "views": 36,
              "categories": [ { "id": 2, "name": "Editorials", "slug": "editorials" } ] } ],
  "page": 1,
  "per_page": 20,
  "total": 14
}</pre>

    <h3 style="margin-top:2rem;">GET /api/articles.php?id=15</h3>
    <p>Returns a single published article by ID, or a 404 if it doesn't exist or isn't published.</p>

    <h3 style="margin-top:2rem;">GET /api/categories.php</h3>
    <p>Returns the full list of categories.</p>
    <pre class="api-code-block">[
  { "id": 2, "name": "Editorials", "slug": "editorials" },
  { "id": 3, "name": "Community", "slug": "community" }
]</pre>

    <h3 style="margin-top:2rem;">Errors</h3>
    <p>Requests from an IP that isn't on the allowlist get a <code>403</code> with <code>{"error": "IP not authorized for API access"}</code>.</p>
</main>
<?php include __DIR__ . '/includes/footer.php'; ?>
</body>
</html>
