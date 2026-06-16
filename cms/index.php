<?php
declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';
cms_require_login();

$articles = cms_get_articles(false);
$treks = cms_get_treks(false);
$publishedArticles = count(array_filter($articles, static fn($a) => !empty($a['published'])));
$publishedTreks = count(array_filter($treks, static fn($t) => !empty($t['published'])));

ob_start();
?>
<div class="cms-cards">
  <div class="cms-card"><strong><?= count($articles) ?></strong><span>Total articles</span></div>
  <div class="cms-card"><strong><?= $publishedArticles ?></strong><span>Published articles</span></div>
  <div class="cms-card"><strong><?= count($treks) ?></strong><span>Total treks</span></div>
  <div class="cms-card"><strong><?= $publishedTreks ?></strong><span>Published treks</span></div>
</div>

<div class="cms-panel">
  <div class="cms-panel-header">
    <h2>Quick actions</h2>
  </div>
  <div class="cms-actions">
    <a class="cms-btn cms-btn-primary" href="article-edit.php">New article</a>
    <a class="cms-btn cms-btn-primary" href="trek-edit.php">New trek</a>
    <a class="cms-btn cms-btn-ghost" href="/articles" target="_blank" rel="noopener">View articles page</a>
    <a class="cms-btn cms-btn-ghost" href="/treks" target="_blank" rel="noopener">View treks page</a>
  </div>
</div>

<div class="cms-panel" style="margin-top:18px;">
  <div class="cms-panel-header"><h2>How it works</h2></div>
  <p style="margin:0; line-height:1.7; color:#556;">
    Articles are published at <strong>/article/your-slug</strong> and listed on <strong>/articles</strong>.
    Treks control the cards on <strong>/treks</strong> and link to your existing trek detail pages.
    Trek detail pages (itinerary, FAQ, booking form) stay as they are for now — the CMS manages listings and metadata.
  </p>
</div>
<?php
cms_page('Dashboard', (string)ob_get_clean(), 'dashboard');
