<?php
declare(strict_types=1);

/**
 * Import full trek page content from static HTML into CMS.
 * Run: php scripts/migrate-treks-to-cms.php
 */

$root = dirname(__DIR__);
require_once $root . '/lib/content.php';
require_once $root . '/lib/import-trek-page.php';

$treks = cms_get_treks(false);
$updated = 0;

foreach ($treks as $i => $trek) {
    $slug = (string)($trek['slug'] ?? '');
    $htmlPath = $root . '/' . $slug . '.html';

    if (!is_file($htmlPath)) {
        echo "Skip {$slug}: no HTML file\n";
        continue;
    }

    try {
        $page = cms_parse_trek_html($htmlPath);
    } catch (Throwable $e) {
        echo "Error {$slug}: {$e->getMessage()}\n";
        continue;
    }

    $treks[$i]['page'] = $page;
    $treks[$i]['cms_page'] = true;

    if ($page['hero_image'] === '' && !empty($trek['image'])) {
        $treks[$i]['page']['hero_image'] = (string)$trek['image'];
    }
    if ($page['booking_trek_name'] === '' && !empty($trek['title'])) {
        $treks[$i]['page']['booking_trek_name'] = (string)$trek['title'];
    }

    $days = count($page['itinerary']);
    $faqs = count($page['faqs']);
    echo "Imported {$slug}: {$days} days, {$faqs} FAQs, " . count($page['gallery']) . " photos\n";
    $updated++;
}

cms_write_json('treks.json', $treks);
echo "\nDone — {$updated} treks now use full CMS pages.\n";
