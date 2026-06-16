<?php
declare(strict_types=1);

/**
 * One-off patcher: apply shared carousel gallery to static trek HTML files.
 * Run: php scripts/patch-trek-galleries.php
 */

$root = dirname(__DIR__);
require_once $root . '/lib/content.php';

$trekFiles = glob($root . '/*-trek.html') ?: [];
$trekFiles = array_values(array_filter($trekFiles, static fn(string $f): bool => !str_contains(basename($f), 'custom')));

function build_gallery_html(array $images, string $alt): string
{
    ob_start();
    $gallery = $images;
    $galleryAlt = $alt;
    include dirname(__DIR__) . '/includes/trek-gallery.php';
    return (string)ob_get_clean();
}

function extract_gallery_images(string $html): array
{
    if (!preg_match('/<div class="gallery-wrap">(.*?)<span class="gallery-label">/s', $html, $m)) {
        return [];
    }
    preg_match_all('/<img\s+src="([^"]+)"/', $m[1], $imgs);
    return array_values(array_filter($imgs[1] ?? [], static fn(string $src): bool => $src !== ''));
}

function extract_gallery_alt(string $html): string
{
    if (preg_match('/<div class="gallery-wrap">.*?<img\s+src="[^"]+"\s+alt="([^"]*)"/s', $html, $m)) {
        return trim($m[1]) ?: 'Trek';
    }
    return 'Trek';
}

foreach ($trekFiles as $file) {
    $html = file_get_contents($file);
    if ($html === false) {
        echo "Skip (unreadable): $file\n";
        continue;
    }

    if (str_contains($html, 'gallery-carousel')) {
        echo "Already patched: " . basename($file) . "\n";
        continue;
    }

    $images = extract_gallery_images($html);
    if ($images === []) {
        echo "No gallery found: " . basename($file) . "\n";
        continue;
    }

    $alt = extract_gallery_alt($html);
    $carouselHtml = build_gallery_html($images, $alt);

    // Remove inline gallery CSS block
    $html = preg_replace(
        '/\/\* ─── GALLERY[\s\S]*?\.gallery-label \{[\s\S]*?\}\s*\n\s*\n/',
        '',
        $html,
        1
    ) ?? $html;

    // Remove responsive gallery-wrap height rule
    $html = str_replace("  .gallery-wrap { height: 260px; }\n", '', $html);

    // Add shared assets if missing
    if (!str_contains($html, 'trek-page.css')) {
        $html = str_replace(
            '<link rel="stylesheet" href="styles.css">',
            "<link rel=\"stylesheet\" href=\"styles.css\">\n<link rel=\"stylesheet\" href=\"/trek-page.css?v=2\">",
            $html
        );
    }

    if (!str_contains($html, 'trek-page.js')) {
        $html = str_replace(
            '<script src="main.js?v=3" defer></script>',
            "<script src=\"main.js?v=3\" defer></script>\n<script src=\"/trek-page.js?v=2\" defer></script>",
            $html
        );
    }

    // Replace gallery-wrap block
    $html = preg_replace(
        '/<div class="gallery-section">\s*<div class="gallery-wrap">[\s\S]*?<\/div>\s*<\/div>/',
        trim($carouselHtml),
        $html,
        1
    );

    if ($html === null) {
        echo "Failed regex: " . basename($file) . "\n";
        continue;
    }

    file_put_contents($file, $html);
    echo "Patched " . basename($file) . ' (' . count($images) . " photos)\n";
}

// Seed gallery arrays into CMS trek records from static HTML
$slugMap = [];
foreach ($trekFiles as $file) {
    $slug = basename($file, '.html');
    $html = file_get_contents($file);
    if ($html === false) {
        continue;
    }
    $images = extract_gallery_images($html);
    if ($images === []) {
        // Re-read after patch - extract from carousel
        preg_match_all('/<div class="gallery-slide[^"]*"[^>]*>[\s\S]*?<img src="\/([^"]+)"/', $html, $m);
        $images = $m[1] ?? [];
    }
    if ($images !== []) {
        $slugMap[$slug] = array_map(static fn(string $src): string => ltrim($src, '/'), $images);
    }
}

$treks = cms_get_treks(false);
foreach ($treks as $i => $trek) {
    $slug = (string)($trek['slug'] ?? '');
    if (!isset($slugMap[$slug])) {
        continue;
    }
    if (!isset($treks[$i]['page']) || !is_array($treks[$i]['page'])) {
        $treks[$i]['page'] = cms_trek_page_defaults();
    }
    $treks[$i]['page']['gallery'] = $slugMap[$slug];
}
cms_write_json('treks.json', $treks);
echo "Updated gallery data in cms/data/treks.json\n";
