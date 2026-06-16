<?php
declare(strict_types=1);

/** @var array<int, array<string, mixed>> $treks */
$treks = array_values(array_filter(
    cms_get_treks(true),
    static fn(array $t): bool => !empty($t['featured'])
));
if ($treks === []) {
    $treks = cms_get_treks(true);
}

foreach ($treks as $trek):
    $difficulty = (string)($trek['difficulty'] ?? 'easy');
    $badgeClass = cms_difficulty_class($difficulty);
    $isModerate = $difficulty === 'moderate' || $difficulty === 'hard';
?>
  <a class="card" href="/<?= cms_h((string)($trek['slug'] ?? '')) ?>">
    <div class="card-img-wrap">
      <img src="<?= cms_h((string)($trek['image'] ?? 'kokhe.jpg')) ?>" alt="<?= cms_h((string)($trek['title'] ?? '')) ?>">
      <div class="card-overlay"></div>
      <span class="badge<?= $isModerate ? ' moderate' : '' ?>"><span class="badge-dot"></span><?= cms_h(ucfirst($difficulty)) ?> · <?= cms_h((string)($trek['days_label'] ?? '')) ?></span>
    </div>
    <div class="card-content">
      <h3><?= cms_h((string)($trek['title'] ?? '')) ?></h3>
      <p><?= cms_h((string)($trek['subtitle'] ?? '')) ?></p>
      <span class="card-arrow">View Trek →</span>
    </div>
  </a>
<?php endforeach; ?>
