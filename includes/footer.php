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
<script>
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('time.local-date, time.local-datetime').forEach(function(el) {
        var d = new Date(el.getAttribute('datetime'));
        if (isNaN(d.getTime())) return;
        if (el.classList.contains('local-datetime')) {
            el.textContent = d.toLocaleString(undefined, { year: 'numeric', month: 'short', day: 'numeric', hour: 'numeric', minute: '2-digit' });
        } else {
            el.textContent = d.toLocaleDateString(undefined, { year: 'numeric', month: 'long', day: 'numeric' });
        }
    });
});
</script>