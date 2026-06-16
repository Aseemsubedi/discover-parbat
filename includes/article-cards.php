<?php
declare(strict_types=1);

/** @var array<int, array<string, mixed>> $articles */
$articles = cms_get_articles(true);
foreach ($articles as $article):
?>
    <a href="/article/<?= cms_h((string)($article['slug'] ?? '')) ?>" class="article-card">
      <div class="article-img-wrap">
        <img src="<?= cms_h((string)($article['image'] ?? 'kokhe.jpg')) ?>" alt="<?= cms_h((string)($article['title'] ?? '')) ?>">
        <span class="article-tag"><?= cms_h((string)($article['tag'] ?? 'Article')) ?></span>
      </div>
      <div class="article-body">
        <p class="article-meta"><?= cms_h((string)($article['author'] ?? 'Discover Parbat')) ?> &nbsp;·&nbsp; <?= cms_h(substr((string)($article['published_at'] ?? ''), 0, 4)) ?></p>
        <h2><?= cms_h((string)($article['title'] ?? '')) ?></h2>
        <span class="article-read-more">Read Article →</span>
      </div>
    </a>
<?php endforeach; ?>
