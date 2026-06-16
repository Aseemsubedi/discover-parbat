<?php
declare(strict_types=1);

/** @var array $trek */
$page = cms_merge_trek_page(is_array($trek['page'] ?? null) ? $trek['page'] : null);

$slug = (string)($trek['slug'] ?? '');
$title = (string)($page['meta_title'] !== '' ? $page['meta_title'] : ($trek['title'] ?? 'Trek'));
$metaDescription = (string)($page['meta_description'] ?: ($trek['subtitle'] ?? ''));
$metaKeywords = (string)($page['meta_keywords'] ?? '');
$canonical = 'https://discoverparbat.com/' . rawurlencode($slug);

$heroImage = (string)($page['hero_image'] !== '' ? $page['hero_image'] : ($trek['image'] ?? 'kokhe.jpg'));
$heroEyebrow = (string)$page['hero_eyebrow'];
$heroTitleHtml = strip_tags((string)$page['hero_title_html'], '<br>');
if ($heroTitleHtml === '') {
    $heroTitleHtml = cms_h((string)($trek['title'] ?? 'Trek'));
}
$heroSubtitle = (string)($page['hero_subtitle'] !== '' ? $page['hero_subtitle'] : ($trek['subtitle'] ?? ''));

$gallery = array_values(array_filter((array)($page['gallery'] ?? []), static fn($img): bool => (string)$img !== ''));
if ($gallery === []) {
    $gallery = [$heroImage];
}

$bookingTrekName = (string)($page['booking_trek_name'] !== '' ? $page['booking_trek_name'] : ($trek['title'] ?? ''));
$allTreks = cms_get_treks(true);

function cms_trek_safe_html(string $html): string
{
    return strip_tags($html, '<a><br><strong><em>');
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= cms_h($title) ?> | Discover Parbat</title>
<link rel="canonical" href="<?= cms_h($canonical) ?>">
<meta name="description" content="<?= cms_h($metaDescription) ?>">
<?php if ($metaKeywords !== ''): ?>
<meta name="keywords" content="<?= cms_h($metaKeywords) ?>">
<?php endif; ?>
<?php cms_render_social_meta([
    'title' => $title . ' | Discover Parbat',
    'description' => $metaDescription !== '' ? $metaDescription : (string)($trek['subtitle'] ?? $title),
    'url' => $canonical,
    'image' => $heroImage,
    'image_alt' => (string)($trek['title'] ?? $title),
]); ?>
<link rel="icon" type="image/png" href="/logo.png" sizes="32x32">
<link rel="icon" type="image/png" href="/logo.png" sizes="16x16">
<link rel="apple-touch-icon" href="/apple-touch-icon.png" sizes="180x180">
<link rel="icon" href="/favicon.ico">
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@400;600;700&family=DM+Sans:wght@300;400;500;600&family=Playfair+Display:ital,wght@0,700;1,600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="/styles.css">
<link rel="stylesheet" href="/trek-page.css?v=2">
<script src="/main.js?v=3" defer></script>
<script src="/trek-page.js?v=2" defer></script>
</head>
<body>

<?php include __DIR__ . '/site-header.php'; ?>

<section class="hero">
  <img src="/<?= cms_h(ltrim($heroImage, '/')) ?>" alt="<?= cms_h((string)($trek['title'] ?? '')) ?>">
  <div class="hero-content">
    <?php if ($heroEyebrow !== ''): ?>
      <div class="hero-eyebrow"><?= cms_h($heroEyebrow) ?></div>
    <?php endif; ?>
    <h1><?= $heroTitleHtml ?></h1>
    <?php if ($heroSubtitle !== ''): ?>
      <p><?= cms_h($heroSubtitle) ?></p>
    <?php endif; ?>
  </div>
</section>

<?php if (!empty($page['stats'])): ?>
<div class="stats-bar">
  <?php foreach ($page['stats'] as $stat): ?>
    <?php if ((string)($stat['value'] ?? '') === '') continue; ?>
    <div class="stat-item">
      <div class="stat-value"><?= cms_h((string)$stat['value']) ?></div>
      <div class="stat-label"><?= cms_h((string)($stat['label'] ?? '')) ?></div>
    </div>
  <?php endforeach; ?>
</div>
<?php endif; ?>

<?php if (count($gallery) > 0): ?>
<?php cms_render_trek_gallery($gallery, (string)($trek['title'] ?? 'Trek')); ?>
<?php endif; ?>

<div class="page-body">
  <div class="left-col">

    <?php if (!empty($page['overview_paragraphs']) || (string)$page['overview_title'] !== ''): ?>
    <span class="section-label">Overview</span>
    <h2 class="content-title"><?= cms_h((string)($page['overview_title'] !== '' ? $page['overview_title'] : 'About ' . ($trek['title'] ?? 'this trek'))) ?></h2>
    <div class="title-divider"></div>
    <div class="about-text">
      <?php foreach ((array)$page['overview_paragraphs'] as $para): ?>
        <p><?= cms_h((string)$para) ?></p>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <?php if (!empty($page['highlights'])): ?>
    <div class="highlights-box">
      <h3>✦ Trek Highlights</h3>
      <ul class="highlights-list">
        <?php foreach ($page['highlights'] as $item): ?>
          <li><?= cms_h((string)$item) ?></li>
        <?php endforeach; ?>
      </ul>
    </div>
    <?php endif; ?>

    <?php if (!empty($page['right_for_you_yes']) || !empty($page['right_for_you_no'])): ?>
    <div class="right-for-you">
      <h3>✦ This Trek Is Right for You If…</h3>
      <div class="rfy-grid">
        <?php foreach ((array)$page['right_for_you_yes'] as $item): ?>
          <div class="rfy-item rfy-yes">
            <span class="rfy-icon">✓</span>
            <p><?= cms_h((string)$item) ?></p>
          </div>
        <?php endforeach; ?>
        <?php foreach ((array)$page['right_for_you_no'] as $item): ?>
          <div class="rfy-item rfy-no">
            <span class="rfy-icon">✗</span>
            <p><?= cms_h((string)$item) ?></p>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endif; ?>

    <?php if (!empty($page['itinerary'])): ?>
    <div class="itinerary-section">
      <span class="section-label">Day by Day</span>
      <h2 class="content-title">Detailed Itinerary</h2>
      <div class="title-divider"></div>
      <?php foreach ($page['itinerary'] as $i => $day): ?>
        <?php
        $accId = 'acc' . ($i + 1);
        $dayNum = (int)($day['day'] ?? ($i + 1));
        $paragraphs = (array)($day['paragraphs'] ?? []);
        if ($paragraphs === [] && !empty($day['body'])) {
            $paragraphs = [(string)$day['body']];
        }
        $meta = is_array($day['meta'] ?? null) ? $day['meta'] : [];
        ?>
        <div class="accordion-item" id="<?= cms_h($accId) ?>">
          <button class="accordion-btn" type="button" onclick="toggleAcc('<?= cms_h($accId) ?>')">
            <div class="acc-day-badge">Day<br><?= $dayNum ?></div>
            <span class="acc-title"><?= cms_h((string)($day['title'] ?? '')) ?></span>
            <div class="acc-chevron"><svg viewBox="0 0 12 12"><polyline points="2 4 6 8 10 4"/></svg></div>
          </button>
          <div class="accordion-content" id="<?= cms_h($accId) ?>-content">
            <?php foreach ($paragraphs as $para): ?>
              <p class="acc-body-text"><?= cms_h((string)$para) ?></p>
            <?php endforeach; ?>
            <?php if ($meta !== []): ?>
            <div class="acc-meta">
              <?php foreach ($meta as $label => $value): ?>
                <?php if ((string)$value === '') continue; ?>
                <span class="acc-meta-chip"><strong><?= cms_h((string)$label) ?>:</strong> <?= cms_h((string)$value) ?></span>
              <?php endforeach; ?>
            </div>
            <?php endif; ?>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <?php if (!empty($page['faqs'])): ?>
    <div class="trek-faq">
      <span class="section-label">Common Questions</span>
      <h2 class="content-title">Frequently Asked Questions</h2>
      <div class="title-divider"></div>
      <div class="faq-list">
        <?php foreach ($page['faqs'] as $faq): ?>
          <div class="faq-item">
            <button class="faq-btn" type="button" onclick="toggleFaq(this)">
              <?= cms_h((string)($faq['question'] ?? '')) ?>
              <svg class="faq-chevron" viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round"><polyline points="6 9 12 15 18 9"/></svg>
            </button>
            <div class="faq-answer">
              <p><?= cms_trek_safe_html((string)($faq['answer'] ?? '')) ?></p>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endif; ?>

  </div>

  <aside class="sidebar">
    <?php
    $pricing = is_array($page['pricing'] ?? null) ? $page['pricing'] : [];
    $priceRows = (array)($pricing['rows'] ?? []);
    $included = (array)($pricing['included'] ?? []);
    $excluded = (array)($pricing['excluded'] ?? []);
    ?>

    <?php if ($priceRows !== []): ?>
    <div class="pricing-card">
      <div class="pricing-card-header">
        <h3>Cost Details</h3>
        <p>Per person · All inclusive</p>
      </div>
      <table class="pricing-table">
        <thead>
          <tr>
            <th>Group Size</th>
            <th>Per Person</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($priceRows as $row): ?>
            <tr>
              <td><?= cms_h((string)($row['group'] ?? $row['label'] ?? '')) ?></td>
              <td><?= cms_h((string)($row['price'] ?? '')) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php endif; ?>

    <?php if ($included !== [] || $excluded !== []): ?>
    <div class="includes-card">
      <?php if ($included !== []): ?>
      <h4>What's Included</h4>
      <ul class="includes-list">
        <?php foreach ($included as $item): ?>
          <li><?= cms_h((string)$item) ?></li>
        <?php endforeach; ?>
      </ul>
      <?php endif; ?>
      <?php if ($excluded !== []): ?>
      <h4 class="not-included-title">What's Not Included</h4>
      <ul class="includes-list not-included-list">
        <?php foreach ($excluded as $item): ?>
          <li><?= cms_h((string)$item) ?></li>
        <?php endforeach; ?>
      </ul>
      <?php endif; ?>
    </div>
    <?php endif; ?>

    <?php include __DIR__ . '/trek-booking-form.php'; ?>
  </aside>
</div>

<?php include __DIR__ . '/site-footer.php'; ?>

</body>
</html>
