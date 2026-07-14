<footer>
    &copy; <?= e(SITE_NAME) ?> v<?= e(SITE_VERSION) ?>
    &middot; <a href="/about.php">About</a>
    &middot; <a href="/changelog.php">Changelog</a>
    &middot; <a href="/community-guidelines.php">Community Guidelines</a>
    &middot; <a href="/feedback.php">Feedback</a>
    <?php if (!empty($_SESSION['reader_username'])): ?>
        &middot; <a href="/delete-account">Delete Account</a>
    <?php endif; ?>
</footer>