<?php
declare(strict_types=1);

$root = dirname(__DIR__);
$old = '<p class="footer-credit">Website developed by <a href="https://anc.com.np" target="_blank" rel="noopener">Aseem &amp; Consulting</a></p>';
$new = '<p class="footer-credit"><span class="footer-credit-label">Developed by</span> <a href="https://anc.com.np" target="_blank" rel="noopener">Aseem &amp; Consulting</a></p>';

$files = array_merge(
    glob($root . '/*.html') ?: [],
    glob($root . '/*.php') ?: [],
    glob($root . '/includes/*.php') ?: []
);

foreach ($files as $file) {
    $html = file_get_contents($file);
    if ($html === false || !str_contains($html, 'footer-credit')) {
        continue;
    }
    if (!str_contains($html, $old)) {
        continue;
    }
    file_put_contents($file, str_replace($old, $new, $html));
    echo 'updated ' . basename($file) . "\n";
}
