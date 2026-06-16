<?php
declare(strict_types=1);

require_once __DIR__ . '/lib/content.php';

$slug = trim((string)($_GET['slug'] ?? ''));
if ($slug === '' || !preg_match('/^[a-z0-9-]+$/', $slug)) {
    http_response_code(404);
    require __DIR__ . '/includes/not-found.php';
    exit;
}

$trek = cms_get_trek_by_slug($slug, false);

if ($trek !== null && cms_trek_has_cms_page($trek)) {
    if (empty($trek['published'])) {
        http_response_code(404);
        require __DIR__ . '/includes/not-found.php';
        exit;
    }
    require __DIR__ . '/includes/render-trek-page.php';
    exit;
}

$htmlPath = __DIR__ . '/' . $slug . '.html';
if (is_file($htmlPath)) {
    readfile($htmlPath);
    exit;
}

http_response_code(404);
require __DIR__ . '/includes/not-found.php';
