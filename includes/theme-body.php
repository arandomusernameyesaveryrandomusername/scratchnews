<?php
$darkPref = $_SESSION['dark_mode'] ?? null;
if ($darkPref === true || $darkPref == 1) {
    echo 'class="dark"';
} elseif ($darkPref === false || $darkPref === 0 || $darkPref === '0') {
    echo 'class=""';
} else {
    echo 'data-theme-auto="1"';
}
?>
