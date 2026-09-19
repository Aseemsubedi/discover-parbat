<?php
declare(strict_types=1);

$root = dirname(__DIR__);
$credit = '<p class="footer-credit">Website developed by <a href="https://anc.com.np" target="_blank" rel="noopener">Aseem &amp; Consulting</a></p>';

$files = array_merge(
    glob($root . '/*.html') ?: [],
    glob($root . '/*.php') ?: [],
    glob($root . '/includes/*.php') ?: []
);

foreach ($files as $file) {
    if (basename($file) === 'site-footer.php') {
        continue;
    }
    $html = file_get_contents($file);
    if ($html === false || !str_contains($html, 'footer-bottom') || str_contains($html, 'footer-credit')) {
        continue;
    }

    $new = preg_replace(
        '/(<div class="footer-bottom">\s*<p>© Discover Parbat[^<]*<\/p>)/',
        "$1\n    $credit",
        $html,
        1,
        $count
    );

    if ($count > 0 && is_string($new)) {
        file_put_contents($file, $new);
        echo 'updated ' . basename($file) . "\n";
    } else {
        echo 'no match ' . basename($file) . "\n";
    }
}
