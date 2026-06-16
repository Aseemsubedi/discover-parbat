<?php
declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';
cms_require_login();

$articles = cms_get_articles(false);

ob_start();
?>
<div class="cms-panel">
  <div class="cms-panel-header">
    <h2>All articles</h2>
    <a class="cms-btn cms-btn-primary" href="article-edit.php">+ New article</a>
  </div>
  <table class="cms-table">
    <thead>
      <tr>
        <th>Title</th>
        <th>Slug</th>
        <th>Status</th>
        <th>Date</th>
        <th>Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php if ($articles === []): ?>
        <tr><td colspan="5">No articles yet.</td></tr>
      <?php else: ?>
        <?php foreach ($articles as $article): ?>
          <tr>
            <td><?= cms_h((string)($article['title'] ?? '')) ?></td>
            <td>/article/<?= cms_h((string)($article['slug'] ?? '')) ?></td>
            <td>
              <?php if (!empty($article['published'])): ?>
                <span class="cms-badge cms-badge-live">Live</span>
              <?php else: ?>
                <span class="cms-badge cms-badge-draft">Draft</span>
              <?php endif; ?>
            </td>
            <td><?= cms_h((string)($article['published_at'] ?? '')) ?></td>
            <td class="cms-actions">
              <a class="cms-btn cms-btn-ghost" href="article-edit.php?id=<?= urlencode((string)($article['id'] ?? '')) ?>">Edit</a>
              <?php if (!empty($article['published'])): ?>
                <a class="cms-btn cms-btn-ghost" href="/article/<?= urlencode((string)($article['slug'] ?? '')) ?>" target="_blank" rel="noopener">View</a>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
      <?php endif; ?>
    </tbody>
  </table>
</div>
<?php
cms_page('Articles', (string)ob_get_clean(), 'articles');
