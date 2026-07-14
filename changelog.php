<?php
require_once __DIR__ . '/functions.php';
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="icon" type="image/svg+xml" href="/assets/favicon.svg">
<title>About - <?= e(SITE_NAME) ?></title>
<link rel="stylesheet" href="/assets/style.css?v=9">
</head>
<body class="<?= !empty($_SESSION['dark_mode']) ? 'dark' : '' ?>">
<header>
    <a href="/" class="logo-link">
<svg viewBox="0,0,136.90609,31.33279" class="logo-svg" xmlns="http://www.w3.org/2000/svg"><g transform="translate(-172.33195,-164.3336)"><g stroke-miterlimit="10"><text transform="translate(217.16808,185.69599) scale(0.5,0.5)" font-size="40" fill="#ffffff" stroke="#ffaa33" stroke-width="3" font-family="Scratch" font-weight="normal" text-anchor="start"><tspan x="0" dy="0">ScratchNews</tspan></text><text transform="translate(217.16808,185.69599) scale(0.5,0.5)" font-size="40" fill="#ffffff" stroke="none" stroke-width="1" font-family="Scratch" font-weight="normal" text-anchor="start"><tspan x="0" dy="0">ScratchNews</tspan></text><path d="M181.04509,195.64879h-8.71313v-10.6397h8.71313z" fill="#cc8829" stroke="none"/><path d="M176.88045,195.6664v-20.46677l3.90302,-0.07587l0.09189,-5.0222h6.64479v20.71239l-3.91923,0.16783l-0.04923,4.68462z" fill="#ffaa33" stroke="none"/><path d="M201.40189,164.35122h8.71313v10.6397h-8.71313z" fill="#cc8829" stroke="none"/><path d="M205.56653,164.33361v20.46677l-3.90302,0.07587l-0.09189,5.0222h-6.64479v-20.71239l3.91923,-0.16783l0.04923,-4.68462z" fill="#ffaa33" stroke="none"/><path d="M190.06459,189.91166l-0.03808,-3.62362l-3.03158,-0.12982v-16.02128h5.13983l0.07108,3.88473l3.01904,0.05869v15.8313z" fill="#ffaa33" stroke="none"/></g></g></svg>
    </a>
    <nav><a href="/">Home</a></nav>
</header>
<main>
    <h2>Changelog</h2>
    <p>v0.12.1 - Static Pages (about, changelog, community guidelines) added to replace admin pages</p>
<br>
    <p>[Jul14] v0.12 - Added Dislike, Share, new icons for social features, and moved social features at the top</p>
<br>
    <p>[Jul12] v0.11 - Moderation features: report comments, ban users, delete users and ban IPs.</p>
<br>  
    <p>v0.1 - Biggest update YET! Reply, reply to replies, website redesign (articles in different boxes), smooth size change, SEARCH BAR, unified menu for admins...</p>
<br>
    <p>v0.09 - Added Feedback page, moving ID articles for admins</p>
<br>
    <p>[Jul10] v0.08 - Delete Account at bottom of page, non-admin users can submit articles and get results via email, fixed /article/id linking before id is defined</p>
<br>
    <p>[Jul9] v0.07 - Email Verification</p>
<br>
    <p>[Jul8] v0.06 - Users, Delete Account at /delete-account</p>
<br>
    <p>[Jul7/8?] v0.05 - Link Text, fixed Admin tab showing to non-admin users</p>
<br>
    <p>[Jul7] v0.04 - Introduced account creation beyond admin, likes and comments. You will see a test comment below showing the new feature</p>
<br>
    <p>[Jul6] v0.03 - Formatting! Bold, Italic, Strikethrough, Headers, Colors, Color text and highlight color text!</p>
<br>
    <p>v0.02 - Branding. Logo and top page color.</p>
<br>
    <p>v0.01 - View articles and edit/create articles. Only the dev can create articles.</p>
<br>
    <p>[Jul5, 2026] v0.00 - Initial website, launched at scratchnews.freedev.app</p>
</main>
<?php include __DIR__ . '/includes/footer.php'; ?>
</body>
</html>