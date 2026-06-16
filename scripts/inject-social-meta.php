<?php
declare(strict_types=1);

/**
 * Inject Open Graph / Twitter meta into static HTML pages.
 * Run: php scripts/inject-social-meta.php
 */

$root = dirname(__DIR__);
require_once $root . '/lib/content.php';

$defaultImage = 'og-share.jpg';

/** @var array<string, array{title?: string, image?: string, type?: string}> */
$overrides = [
    'index.html' => [
        'title' => 'Discover Parbat | Authentic Himalayan Treks in Nepal',
        'image' => $defaultImage,
        'image_alt' => 'Snow-capped Himalayas above a village lodge in Parbat, Nepal',
    ],
    'treks.html' => [
        'title' => 'Himalayan Treks in Nepal | Discover Parbat',
        'image' => $defaultImage,
    ],
    'articles.html' => [
        'title' => 'Trek Guides & Stories | Discover Parbat',
        'image' => $defaultImage,
    ],
    'contact.html' => ['title' => 'Contact Us | Discover Parbat', 'image' => $defaultImage],
    'about.html' => ['title' => 'About Us | Discover Parbat', 'image' => $defaultImage],
    'shop.html' => ['title' => 'Shop | Discover Parbat', 'image' => 'DP001.jpg'],
    'custom-trek.html' => ['title' => 'Custom Trek | Discover Parbat', 'image' => $defaultImage],
    'cancellation.html' => ['title' => 'Cancellation Policy | Discover Parbat', 'image' => $defaultImage],
];

$htmlFiles = glob($root . '/*.html') ?: [];

foreach ($htmlFiles as $file) {
    $base = basename($file);
    if (in_array($base, ['shop-order.html'], true)) {
        continue;
    }

    $html = file_get_contents($file);
    if ($html === false || str_contains($html, 'property="og:title"')) {
        echo $base . ": skip\n";
        continue;
    }

    if (!preg_match('/<meta name="description" content="([^"]*)"/', $html, $descMatch)) {
        echo $base . ": no description\n";
        continue;
    }

    $description = html_entity_decode($descMatch[1], ENT_QUOTES | ENT_HTML5, 'UTF-8');

    $title = $overrides[$base]['title'] ?? '';
    if ($title === '' && preg_match('/<title>([^<]*)<\/title>/', $html, $titleMatch)) {
        $title = trim(html_entity_decode($titleMatch[1], ENT_QUOTES | ENT_HTML5, 'UTF-8'));
    }

    $url = 'https://discoverparbat.com/';
    if (preg_match('/<link rel="canonical" href="([^"]+)"/', $html, $urlMatch)) {
        $url = $urlMatch[1];
    }

    $image = $overrides[$base]['image'] ?? $defaultImage;
    if (!isset($overrides[$base]['image']) && preg_match('/<section class="hero"[^>]*>[\s\S]*?<img[^>]+src="([^"]+)"/', $html, $heroMatch)) {
        $image = basename($heroMatch[1]);
    }

    $meta = cms_social_meta_html([
        'title' => $title,
        'description' => $description,
        'url' => $url,
        'image' => $image,
        'type' => $overrides[$base]['type'] ?? 'website',
        'image_alt' => $overrides[$base]['image_alt'] ?? $title,
    ]);

    $insertAfter = $descMatch[0];
    if (preg_match('/<meta name="keywords" content="[^"]*">/', $html, $kwMatch)) {
        $insertAfter = $kwMatch[0];
    }

    $html = str_replace($insertAfter, $insertAfter . "\n" . trim($meta), $html);
    file_put_contents($file, $html);
    echo $base . ": ok\n";
}
