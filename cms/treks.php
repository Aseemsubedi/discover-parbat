<?php
declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';
cms_require_login();

$treks = cms_get_treks(false);

ob_start();
?>
<div class="cms-panel">
  <div class="cms-panel-header">
    <h2>All treks</h2>
    <a class="cms-btn cms-btn-primary" href="trek-edit.php">+ New trek</a>
  </div>
  <table class="cms-table">
    <thead>
      <tr>
        <th>Order</th>
        <th>Title</th>
        <th>Slug / URL</th>
        <th>Difficulty</th>
        <th>Status</th>
        <th>Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php if ($treks === []): ?>
        <tr><td colspan="6">No treks yet.</td></tr>
      <?php else: ?>
        <?php foreach ($treks as $trek): ?>
          <tr>
            <td><?= (int)($trek['sort_order'] ?? 0) ?></td>
            <td><?= cms_h((string)($trek['title'] ?? '')) ?></td>
            <td>/<?= cms_h((string)($trek['slug'] ?? '')) ?></td>
            <td><?= cms_h(ucfirst((string)($trek['difficulty'] ?? 'easy'))) ?> · <?= cms_h((string)($trek['days_label'] ?? '')) ?></td>
            <td>
              <?php if (!empty($trek['published'])): ?>
                <span class="cms-badge cms-badge-live">Live</span>
              <?php else: ?>
                <span class="cms-badge cms-badge-draft">Hidden</span>
              <?php endif; ?>
            </td>
            <td class="cms-actions">
              <a class="cms-btn cms-btn-ghost" href="trek-edit.php?id=<?= urlencode((string)($trek['id'] ?? '')) ?>">Edit</a>
              <?php if (!empty($trek['published'])): ?>
                <a class="cms-btn cms-btn-ghost" href="/<?= urlencode((string)($trek['slug'] ?? '')) ?>" target="_blank" rel="noopener">View page</a>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
      <?php endif; ?>
    </tbody>
  </table>
</div>
<?php
cms_page('Treks', (string)ob_get_clean(), 'treks');
