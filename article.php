<?php
declare(strict_types=1);

require_once __DIR__ . '/lib/content.php';

$slug = trim((string)($_GET['slug'] ?? ''));
$article = $slug !== '' ? cms_get_article_by_slug($slug, true) : null;

if ($article === null) {
    http_response_code(404);
    header('Content-Type: text/html; charset=utf-8');
    echo '<!DOCTYPE html><html><head><title>Article not found</title></head><body><h1>Article not found</h1><p><a href="/articles">Back to articles</a></p></body></html>';
    exit;
}

$title = (string)($article['title'] ?? 'Article');
$metaDescription = (string)($article['meta_description'] ?? $article['excerpt'] ?? '');
$metaKeywords = (string)($article['meta_keywords'] ?? '');
$canonical = 'https://discoverparbat.com/article/' . rawurlencode((string)$article['slug']);
$image = (string)($article['image'] ?? 'kokhe.jpg');
$author = (string)($article['author'] ?? 'Discover Parbat');
$publishedAt = (string)($article['published_at'] ?? '');
$year = substr($publishedAt, 0, 4);
$readMinutes = (int)($article['read_minutes'] ?? 5);
$tag = (string)($article['tag'] ?? 'Article');
$bodyHtml = (string)($article['body_html'] ?? '');
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
<link rel="icon" type="image/png" href="/logo.png" sizes="32x32">
<link rel="stylesheet" href="/styles.css">
<script src="/main.js?v=3" defer></script>
<style>
body { line-height: 1.75; }
.hero-article {
  position: relative;
  height: 58vh;
  min-height: 380px;
  overflow: hidden;
  margin-top: 72px;
}
.hero-article img { width: 100%; height: 100%; object-fit: cover; }
.hero-article::after {
  content: '';
  position: absolute;
  inset: 0;
  background: linear-gradient(to bottom, rgba(10,30,20,0.15), rgba(10,30,20,0.78));
}
.hero-article-content {
  position: absolute;
  z-index: 2;
  left: 7%;
  right: 7%;
  bottom: 12%;
  max-width: 820px;
  color: #fff;
}
.hero-article-content .tag {
  display: inline-block;
  margin-bottom: 12px;
  padding: 5px 14px;
  border-radius: 999px;
  border: 1px solid rgba(255,255,255,0.35);
  font-size: 0.72rem;
  letter-spacing: 2px;
  text-transform: uppercase;
}
.hero-article-content h1 {
  font-family: 'Cormorant Garamond', serif;
  font-size: clamp(2rem, 4vw, 3.2rem);
  line-height: 1.15;
  margin: 0 0 12px;
}
.hero-article-content .meta { opacity: 0.9; font-size: 0.9rem; }
.article-page {
  max-width: 760px;
  margin: 0 auto;
  padding: 48px 7% 80px;
}
.article-page .breadcrumb { margin-bottom: 24px; font-size: 0.9rem; }
.article-page .breadcrumb a { color: var(--green-mid); font-weight: 600; }
.article-intro {
  font-size: 1.12rem;
  color: var(--text-mid);
  margin-bottom: 28px;
}
.article-content h2 {
  font-family: 'Playfair Display', serif;
  font-size: 1.45rem;
  margin: 32px 0 12px;
  color: var(--text-dark);
}
.article-content p { margin: 0 0 16px; color: var(--text-mid); }
.article-content ul { margin: 0 0 18px 1.2rem; color: var(--text-mid); }
.article-content a { color: var(--green-mid); font-weight: 600; }
.article-cta {
  margin-top: 40px;
  padding: 24px;
  border-radius: 12px;
  background: #f3faf6;
  border: 1px solid rgba(45,106,79,0.15);
}
.article-cta a {
  display: inline-block;
  margin-top: 10px;
  background: var(--green-mid);
  color: #fff;
  padding: 10px 18px;
  border-radius: 8px;
  text-decoration: none;
  font-weight: 600;
}
</style>
</head>
<body>

<header id="main-header">
  <div class="logo"><a href="/"><img src="/logo.png" alt="Discover Parbat"></a></div>
  <nav>
    <ul>
      <li><a href="/">Home</a></li>
      <li><a href="/treks">Treks</a></li>
      <li><a href="/custom-trek">Custom Trek</a></li>
      <li><a href="/articles" aria-current="page">Articles</a></li>
      <li><a href="/shop">Shop</a></li>
    </ul>
  </nav>
  <button class="nav-toggle" type="button" aria-controls="mobile-nav" aria-expanded="false" aria-label="Open menu">
    <span class="nav-toggle-bars" aria-hidden="true"></span>
  </button>
</header>

<div id="mobile-nav" class="mobile-nav" aria-hidden="true">
  <ul>
    <li><a href="/">Home</a></li>
    <li><a href="/treks">Treks</a></li>
    <li><a href="/custom-trek">Custom Trek</a></li>
    <li><a href="/articles" aria-current="page">Articles</a></li>
    <li><a href="/shop">Shop</a></li>
  </ul>
</div>

<section class="hero-article">
  <img src="/<?= cms_h($image) ?>" alt="<?= cms_h($title) ?>">
  <div class="hero-article-content">
    <span class="tag"><?= cms_h($tag) ?></span>
    <h1><?= cms_h($title) ?></h1>
    <p class="meta">By <?= cms_h($author) ?> · <?= cms_h($year) ?> · <?= $readMinutes ?> min read</p>
  </div>
</section>

<article class="article-page">
  <p class="breadcrumb"><a href="/articles">← Articles</a></p>
  <?php if (!empty($article['excerpt'])): ?>
    <p class="article-intro"><?= cms_h((string)$article['excerpt']) ?></p>
  <?php endif; ?>
  <div class="article-content">
    <?= $bodyHtml ?>
  </div>
  <div class="article-cta">
    <p>Planning a trek in Parbat? Tell us your dates and we'll help you choose the right route.</p>
    <a href="/contact">Contact our team</a>
  </div>
</article>

<footer>
  <div class="footer-grid">
    <div class="footer-brand">
      <img src="/logo.png" alt="Discover Parbat">
      <p>Connecting travelers with authentic Himalayan experiences in Parbat, Nepal.</p>
    </div>
    <div class="footer-col">
      <h4>Explore</h4>
      <ul>
        <li><a href="/treks">Treks</a></li>
        <li><a href="/articles">Articles</a></li>
        <li><a href="/contact">Contact</a></li>
      </ul>
    </div>
  </div>
  <div class="footer-bottom">
    <p>© Discover Parbat 2026. All rights reserved.</p>
  </div>
</footer>

</body>
</html>
