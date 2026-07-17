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
    <h2>Community Guidelines</h2>
    <p>- community guidelines so that the site doesn't go kaboom. disrespecting these guidelines will result in a ban/permanent account deletion, or a warning</p>
    <h3><b>1 - The most important rule of all...</b></h3>
    <h3><b>1.1: Respect the Scratch Community Guidelines.</b></h3>
    <p>Every action that you make on this has to not violate any of the Scratch Community Guidelines. <a href="https://scratch.mit.edu/community_guidelines">(seen here.)</a><br>
    This includes comments, replies and articles.</p><br>
    <i>ScratchNews is a site about Scratch-related news; therefore, Scratchers will use it; therefore, Scratch <b>requires</b> the website to make the website as safe as Scratch itself or safer, and if ScratchNews isn't safer than Scratch, then say goodbye to the idea of Scratchers using it.</i><br>
    <h3><b>2 - What articles you can and can't submit</b></h3>
    <h3><b>2.1: No AI text or images.</b></h3>
    <p>Pretty self-explanatory.</p><br>
    <h3><b>2.2: No self-promotion</b></h3>
    <p>Promote <i>yourself,</i> not what you made. If everyone were to self-promote, this website might as well be the Show And Tell forum 2.0.</p><br>
    <p><b>And that's all!</b> Have fun on ScratchNews!</p>
</main>
<?php include __DIR__ . '/includes/footer.php'; ?>
</body>
</html>
