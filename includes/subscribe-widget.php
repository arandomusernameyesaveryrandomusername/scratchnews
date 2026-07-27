<?php
$uri = $_SERVER['REQUEST_URI'] ?? '/';
$uriParts = explode('?', $uri, 2);
$redirectQuery = [];
if (isset($uriParts[1])) {
    parse_str($uriParts[1], $redirectQuery);
    unset($redirectQuery['subscribed'], $redirectQuery['subscribe_error']);
}
$cleanRedirect = $uriParts[0] . (!empty($redirectQuery) ? '?' . http_build_query($redirectQuery) : '');
?>
<div class="subscribe-widget">
    <div class="subscribe-text">
        <strong>Subscribe to ScratchNews!</strong>
        <p class="subscribe-subtitle">Get new articles delivered straight to your inbox. Pick your favorite topics below.</p>
    </div>
    <?php if (($_GET['subscribed'] ?? '') === '1'): ?>
    <div class="subscribe-popup" id="subscribePopup">We sent you an email to confirm your subscription!</div>
    <script>setTimeout(function(){var p=document.getElementById('subscribePopup'); if(p) p.style.display='none';}, 5000);</script>
    <?php endif; ?>
    <?php if (($_GET['subscribe_error'] ?? '') === '1'): ?>
    <div class="subscribe-popup subscribe-popup-error">Please enter a valid email address.</div>
    <?php endif; ?>
    <form method="post" action="/subscribe.php" class="subscribe-form">
        <?= csrfField() ?>
        <input type="hidden" name="redirect" value="<?= e($cleanRedirect) ?>">
        <div class="subscribe-categories">
            <label class="subscribe-cat-check">
                <input type="checkbox" class="subscribe-cat-all"> All
            </label>
            <?php foreach (getAllCategories() as $cat): ?>
                <label class="subscribe-cat-check">
                    <input type="checkbox" name="categories[]" value="<?= (int)$cat['id'] ?>" class="subscribe-cat-individual"> <?= e($cat['name']) ?>
                </label>
            <?php endforeach; ?>
        </div>
        <div class="subscribe-input-row">
            <input type="email" name="email" placeholder="Your Email Here" required>
            <button type="submit" class="btn">Subscribe!</button>
        </div>
    </form>
</div>
<script>
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.subscribe-form').forEach(function(form) {
        var allBox = form.querySelector('.subscribe-cat-all');
        var catBoxes = form.querySelectorAll('.subscribe-cat-individual');
        if (!allBox || !catBoxes.length) return;

        allBox.addEventListener('change', function() {
            catBoxes.forEach(function(cb) { cb.checked = allBox.checked; });
        });

        catBoxes.forEach(function(cb) {
            cb.addEventListener('change', function() {
                allBox.checked = Array.prototype.every.call(catBoxes, function(c) { return c.checked; });
            });
        });
    });
});
</script>