<?php
declare(strict_types=1);

function cms_data_path(string $file): string
{
    return __DIR__ . '/../cms/data/' . $file;
}

function cms_read_json(string $file): array
{
    $path = cms_data_path($file);
    if (!is_file($path)) {
        return [];
    }
    $raw = file_get_contents($path);
    if ($raw === false || trim($raw) === '') {
        return [];
    }
    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
}

function cms_write_json(string $file, array $data): void
{
    $path = cms_data_path($file);
    $dir = dirname($path);
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if ($json === false) {
        throw new RuntimeException('Failed to encode JSON for ' . $file);
    }
    if (file_put_contents($path, $json . "\n", LOCK_EX) === false) {
        throw new RuntimeException('Failed to write ' . $file);
    }
}

function cms_slugify(string $text): string
{
    $text = strtolower(trim($text));
    $text = preg_replace('/[^a-z0-9]+/', '-', $text) ?? '';
    return trim($text, '-') ?: 'item';
}

function cms_get_articles(bool $publishedOnly = false): array
{
    $items = cms_read_json('articles.json');
    if ($publishedOnly) {
        $items = array_values(array_filter($items, static fn(array $a): bool => !empty($a['published'])));
    }
    usort($items, static function (array $a, array $b): int {
        return strcmp((string)($b['published_at'] ?? ''), (string)($a['published_at'] ?? ''));
    });
    return $items;
}

function cms_get_article_by_slug(string $slug, bool $publishedOnly = true): ?array
{
    foreach (cms_get_articles(false) as $article) {
        if (($article['slug'] ?? '') === $slug) {
            if ($publishedOnly && empty($article['published'])) {
                return null;
            }
            return $article;
        }
    }
    return null;
}

function cms_get_article_by_id(string $id): ?array
{
    foreach (cms_get_articles(false) as $article) {
        if (($article['id'] ?? '') === $id) {
            return $article;
        }
    }
    return null;
}

function cms_save_article(array $article): void
{
    $items = cms_get_articles(false);
    $found = false;
    foreach ($items as $i => $item) {
        if (($item['id'] ?? '') === ($article['id'] ?? '')) {
            $items[$i] = $article;
            $found = true;
            break;
        }
    }
    if (!$found) {
        $items[] = $article;
    }
    cms_write_json('articles.json', $items);
}

function cms_delete_article(string $id): void
{
    $items = array_values(array_filter(
        cms_get_articles(false),
        static fn(array $a): bool => ($a['id'] ?? '') !== $id
    ));
    cms_write_json('articles.json', $items);
}

function cms_get_treks(bool $publishedOnly = false): array
{
    $items = cms_read_json('treks.json');
    if ($publishedOnly) {
        $items = array_values(array_filter($items, static fn(array $t): bool => !empty($t['published'])));
    }
    usort($items, static fn(array $a, array $b): int => (int)($a['sort_order'] ?? 0) <=> (int)($b['sort_order'] ?? 0));
    return $items;
}

function cms_get_trek_by_id(string $id): ?array
{
    foreach (cms_get_treks(false) as $trek) {
        if (($trek['id'] ?? '') === $id) {
            return $trek;
        }
    }
    return null;
}

function cms_get_trek_by_slug(string $slug, bool $publishedOnly = true): ?array
{
    foreach (cms_get_treks(false) as $trek) {
        if (($trek['slug'] ?? '') === $slug) {
            if ($publishedOnly && empty($trek['published'])) {
                return null;
            }
            return $trek;
        }
    }
    return null;
}

/** @return list<string> */
function cms_lines_to_array(string $text): array
{
    $lines = preg_split('/\r\n|\r|\n/', $text) ?: [];
    return array_values(array_filter(array_map('trim', $lines), static fn(string $line): bool => $line !== ''));
}

function cms_trek_page_defaults(): array
{
    return [
        'meta_title' => '',
        'meta_description' => '',
        'meta_keywords' => '',
        'hero_image' => '',
        'hero_eyebrow' => '',
        'hero_title_html' => '',
        'hero_subtitle' => '',
        'stats' => [
            ['value' => '', 'label' => 'Duration'],
            ['value' => '', 'label' => 'Difficulty'],
            ['value' => '', 'label' => 'Max Altitude'],
            ['value' => '', 'label' => 'Accommodation'],
        ],
        'gallery' => [],
        'overview_title' => '',
        'overview_paragraphs' => [],
        'highlights' => [],
        'right_for_you_yes' => [],
        'right_for_you_no' => [],
        'itinerary' => [],
        'faqs' => [],
        'pricing' => [
            'rows' => [],
            'included' => [],
            'excluded' => [],
        ],
        'booking_trek_name' => '',
    ];
}

function cms_merge_trek_page(?array $page): array
{
    $defaults = cms_trek_page_defaults();
    if ($page === null || $page === []) {
        return $defaults;
    }
    $merged = array_merge($defaults, $page);
    if (!empty($page['stats']) && is_array($page['stats'])) {
        $merged['stats'] = $page['stats'];
    }
    if (!empty($page['pricing']) && is_array($page['pricing'])) {
        $merged['pricing'] = array_merge($defaults['pricing'], $page['pricing']);
    }
    return $merged;
}

function cms_trek_has_cms_page(array $trek): bool
{
    return !empty($trek['cms_page']) && !empty($trek['page']) && is_array($trek['page']);
}

/** @param list<string> $gallery */
function cms_render_trek_gallery(array $gallery, string $altTitle): void
{
    if ($gallery === []) {
        return;
    }
    $galleryAlt = $altTitle;
    include __DIR__ . '/../includes/trek-gallery.php';
}

function cms_save_trek(array $trek): void
{
    $items = cms_get_treks(false);
    $found = false;
    foreach ($items as $i => $item) {
        if (($item['id'] ?? '') === ($trek['id'] ?? '')) {
            $items[$i] = $trek;
            $found = true;
            break;
        }
    }
    if (!$found) {
        $items[] = $trek;
    }
    cms_write_json('treks.json', $items);
}

function cms_delete_trek(string $id): void
{
    $items = array_values(array_filter(
        cms_get_treks(false),
        static fn(array $t): bool => ($t['id'] ?? '') !== $id
    ));
    cms_write_json('treks.json', $items);
}

function cms_difficulty_class(string $difficulty): string
{
    return match (strtolower($difficulty)) {
        'moderate' => 'badge-moderate',
        'hard', 'challenging' => 'badge-hard',
        default => 'badge-easy',
    };
}

function cms_h(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function cms_new_id(): string
{
    return bin2hex(random_bytes(8));
}
