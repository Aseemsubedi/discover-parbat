<?php
declare(strict_types=1);

/**
 * Parse a static trek HTML file into CMS page data structure.
 *
 * @return array<string, mixed>
 */
function cms_parse_trek_html(string $htmlPath): array
{
    $html = file_get_contents($htmlPath);
    if ($html === false) {
        throw new RuntimeException('Cannot read ' . $htmlPath);
    }

    $get = static function (string $pattern) use ($html): string {
        return preg_match($pattern, $html, $m) ? trim(html_entity_decode($m[1], ENT_QUOTES | ENT_HTML5, 'UTF-8')) : '';
    };

    $metaTitle = $get('/<title>([^<]*)<\/title>/');
    $metaTitle = preg_replace('/\s*\|\s*Discover Parbat\s*$/', '', $metaTitle) ?? $metaTitle;

    $page = cms_trek_page_defaults();
    $page['meta_title'] = $metaTitle;
    $page['meta_description'] = $get('/<meta name="description" content="([^"]*)"/');
    $page['meta_keywords'] = $get('/<meta name="keywords" content="([^"]*)"/');

    if (preg_match('/<section class="hero">[\s\S]*?<img src="([^"]+)"/', $html, $m)) {
        $page['hero_image'] = basename($m[1]);
    }
    $page['hero_eyebrow'] = $get('/<div class="hero-eyebrow">([^<]*)<\/div>/');
    if (preg_match('/<section class="hero">[\s\S]*?<h1>([\s\S]*?)<\/h1>/', $html, $m)) {
        $page['hero_title_html'] = trim($m[1]);
    }
    if (preg_match('/<section class="hero">[\s\S]*?<h1>[\s\S]*?<\/h1>\s*<p>([^<]*)<\/p>/', $html, $m)) {
        $page['hero_subtitle'] = trim(html_entity_decode($m[1], ENT_QUOTES | ENT_HTML5, 'UTF-8'));
    }

    if (preg_match_all('/<div class="stat-item">\s*<div class="stat-value">([^<]*)<\/div>\s*<div class="stat-label">([^<]*)<\/div>/', $html, $stats, PREG_SET_ORDER)) {
        $page['stats'] = [];
        foreach ($stats as $stat) {
            $page['stats'][] = ['value' => trim($stat[1]), 'label' => trim($stat[2])];
        }
    }

    if (preg_match_all('/<div class="gallery-slide[^"]*"[^>]*>[\s\S]*?<img src="\/([^"]+)"/', $html, $gallery)) {
        $page['gallery'] = $gallery[1];
    } elseif (preg_match_all('/<div class="gallery-slide[^"]*"[^>]*>[\s\S]*?<img src="([^"]+)"/', $html, $gallery)) {
        $page['gallery'] = array_map('basename', $gallery[1]);
    }

    if (preg_match('/<h2 class="content-title">([^<]*)<\/h2>/', $html, $m)) {
        $page['overview_title'] = trim(html_entity_decode($m[1], ENT_QUOTES | ENT_HTML5, 'UTF-8'));
    }
    if (preg_match('/<div class="about-text">([\s\S]*?)<\/div>/', $html, $m)) {
        preg_match_all('/<p>([\s\S]*?)<\/p>/', $m[1], $paras);
        $page['overview_paragraphs'] = array_values(array_filter(array_map(
            static fn(string $p): string => trim(html_entity_decode(strip_tags($p), ENT_QUOTES | ENT_HTML5, 'UTF-8')),
            $paras[1] ?? []
        )));
    }

    if (preg_match('/<ul class="highlights-list">([\s\S]*?)<\/ul>/', $html, $m)) {
        preg_match_all('/<li>([^<]*)<\/li>/', $m[1], $items);
        $page['highlights'] = array_map('trim', $items[1] ?? []);
    }

    if (preg_match_all('/<div class="rfy-item rfy-yes">[\s\S]*?<p>([^<]*)<\/p>/', $html, $yes)) {
        $page['right_for_you_yes'] = array_map('trim', $yes[1]);
    }
    if (preg_match_all('/<div class="rfy-item rfy-no">[\s\S]*?<p>([^<]*)<\/p>/', $html, $no)) {
        $page['right_for_you_no'] = array_map('trim', $no[1]);
    }

    if (preg_match_all('/<div class="accordion-item" id="[^"]+">([\s\S]*?)<\/div>\s*<\/div>/', $html, $days)) {
        $page['itinerary'] = [];
        foreach ($days[1] as $block) {
            $dayNum = 1;
            if (preg_match('/Day<br>(\d+)/', $block, $dm)) {
                $dayNum = (int)$dm[1];
            }
            $title = '';
            if (preg_match('/<span class="acc-title">([^<]*)<\/span>/', $block, $tm)) {
                $title = trim(html_entity_decode($tm[1], ENT_QUOTES | ENT_HTML5, 'UTF-8'));
            }
            preg_match_all('/<p class="acc-body-text">([\s\S]*?)<\/p>/', $block, $bodyParas);
            $paragraphs = array_map(
                static fn(string $p): string => trim(html_entity_decode(strip_tags($p), ENT_QUOTES | ENT_HTML5, 'UTF-8')),
                $bodyParas[1] ?? []
            );
            $meta = [];
            if (preg_match_all('/<span class="acc-meta-chip"><strong>([^:]+):<\/strong>\s*([^<]*)<\/span>/', $block, $chips, PREG_SET_ORDER)) {
                foreach ($chips as $chip) {
                    $meta[trim($chip[1])] = trim($chip[2]);
                }
            }
            $page['itinerary'][] = [
                'day' => $dayNum,
                'title' => $title,
                'paragraphs' => $paragraphs,
                'meta' => $meta,
            ];
        }
    }

    if (preg_match_all('/<div class="faq-item">([\s\S]*?)<\/div>\s*(?=<div class="faq-item">|<\/div>\s*<\/div>\s*<\/div><!-- \/faq)/', $html, $faqs)) {
        $page['faqs'] = [];
        foreach ($faqs[1] as $block) {
            if (!preg_match('/<button class="faq-btn"[^>]*>\s*([\s\S]*?)\s*<svg/', $block, $qm)) {
                continue;
            }
            $question = trim(preg_replace('/\s+/', ' ', html_entity_decode(strip_tags($qm[1]), ENT_QUOTES | ENT_HTML5, 'UTF-8')) ?? '');
            $answer = '';
            if (preg_match('/<div class="faq-answer">\s*<p>([\s\S]*?)<\/p>/', $block, $am)) {
                $answer = trim($am[1]);
            }
            if ($question !== '' || $answer !== '') {
                $page['faqs'][] = ['question' => $question, 'answer' => $answer];
            }
        }
    }

    if (preg_match('/<table class="pricing-table">[\s\S]*?<tbody>([\s\S]*?)<\/tbody>/', $html, $m)) {
        preg_match_all('/<tr><td>([^<]*)<\/td><td>([^<]*)<\/td><\/tr>/', $m[1], $rows, PREG_SET_ORDER);
        $page['pricing']['rows'] = [];
        foreach ($rows as $row) {
            $page['pricing']['rows'][] = ['group' => trim($row[1]), 'price' => trim($row[2])];
        }
    }

    if (preg_match('/<h4>What\'s Included<\/h4>\s*<ul class="includes-list">([\s\S]*?)<\/ul>/', $html, $m)) {
        preg_match_all('/<li>([^<]*)<\/li>/', $m[1], $items);
        $page['pricing']['included'] = array_map('trim', $items[1] ?? []);
    }
    if (preg_match('/<h4 class="not-included-title">What\'s Not Included<\/h4>\s*<ul class="includes-list not-included-list">([\s\S]*?)<\/ul>/', $html, $m)) {
        preg_match_all('/<li>([^<]*)<\/li>/', $m[1], $items);
        $page['pricing']['excluded'] = array_map('trim', $items[1] ?? []);
    }

    if (preg_match('/<option value="([^"]+)" selected>/', $html, $m)) {
        $page['booking_trek_name'] = trim(html_entity_decode($m[1], ENT_QUOTES | ENT_HTML5, 'UTF-8'));
    }

    return $page;
}
