<div class="subscribe-widget">
    <div class="subscribe-text">
        <strong>Subscribe to ScratchNews!</strong>
        <p>Get the most recent Scratch news and entertainment straight to your inbox.</p>
    </div>
    <form method="post" action="/subscribe.php" class="subscribe-form">
        <?= csrfField() ?>
        <input type="hidden" name="redirect" value="<?= e($_SERVER['REQUEST_URI'] ?? '/') ?>">
        <div class="subscribe-categories">
            <?php foreach (getAllCategories() as $cat): ?>
                <label class="subscribe-cat-check">
                    <input type="checkbox" name="categories[]" value="<?= (int)$cat['id'] ?>"> <?= e($cat['name']) ?>
                </label>
            <?php endforeach; ?>
        </div>
        <div class="subscribe-input-row">
            <input type="email" name="email" placeholder="your email here" required>
            <button type="submit" class="btn">Subscribe!!</button>
        </div>
    </form>
</div>
<?php if (($_GET['subscribed'] ?? '') === '1'): ?>
<div class="subscribe-popup" id="subscribePopup">We sent you an email to confirm your subscription!</div>
<script>setTimeout(function(){var p=document.getElementById('subscribePopup'); if(p) p.style.display='none';}, 5000);</script>
<?php endif; ?>
<?php if (($_GET['subscribe_error'] ?? '') === '1'): ?>
<div class="subscribe-popup subscribe-popup-error">Please enter a valid email address.</div>
<?php endif; ?>
