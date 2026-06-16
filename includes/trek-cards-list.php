<?php
declare(strict_types=1);

/** @var array<int, array<string, mixed>> $treks */
$treks = cms_get_treks(true);
$num = 0;
foreach ($treks as $trek):
    $num++;
    $difficulty = (string)($trek['difficulty'] ?? 'easy');
    $badgeClass = cms_difficulty_class($difficulty);
?>
  <a class="trek-card" href="/<?= cms_h((string)($trek['slug'] ?? '')) ?>">
    <div class="trek-card-img">
      <img src="<?= cms_h((string)($trek['image'] ?? 'kokhe.jpg')) ?>" alt="<?= cms_h((string)($trek['title'] ?? '')) ?>">
      <span class="trek-number"><?= str_pad((string)$num, 2, '0', STR_PAD_LEFT) ?></span>
    </div>
    <div class="trek-card-body">
      <h3><?= cms_h((string)($trek['title'] ?? '')) ?></h3>
      <p><?= cms_h((string)($trek['subtitle'] ?? '')) ?></p>
      <div class="trek-meta">
        <span class="badge <?= cms_h($badgeClass) ?>"><span class="badge-dot"></span><?= cms_h(ucfirst($difficulty)) ?></span>
        <span class="badge <?= cms_h($badgeClass) ?>"><?= cms_h((string)($trek['days_label'] ?? '')) ?></span>
      </div>
      <span class="trek-arrow">View Trek →</span>
    </div>
  </a>
<?php endforeach; ?>
