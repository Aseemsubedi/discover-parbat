<?php
declare(strict_types=1);

/**
 * Shared trek photo carousel.
 *
 * @var list<string> $gallery Image paths (relative or absolute)
 * @var string $galleryAlt Alt text base for images
 */
if (!isset($gallery) || $gallery === []) {
    return;
}

$gallery = array_values(array_filter($gallery, static fn($img): bool => (string)$img !== ''));
if ($gallery === []) {
    return;
}

$count = count($gallery);
$esc = static function (string $value): string {
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
};
?>
<div class="gallery-section">
  <div class="gallery-carousel" id="trek-gallery" data-count="<?= $count ?>">
    <?php foreach ($gallery as $i => $img): ?>
      <?php
      $src = '/' . ltrim((string)$img, '/');
      $alt = $galleryAlt . ' — photo ' . ($i + 1);
      ?>
      <div class="gallery-slide<?= $i === 0 ? ' active' : '' ?>" data-index="<?= (int)$i ?>">
        <img src="<?= $esc($src) ?>" alt="<?= $esc($alt) ?>" loading="<?= $i === 0 ? 'eager' : 'lazy' ?>">
      </div>
    <?php endforeach; ?>

    <div class="gallery-ui">
      <span class="gallery-counter" id="gallery-counter">1 / <?= $count ?></span>
      <button type="button" class="gallery-arrow gallery-arrow-prev" aria-label="Previous photo">
        <svg viewBox="0 0 24 24" aria-hidden="true"><polyline points="15 6 9 12 15 18"/></svg>
      </button>
      <button type="button" class="gallery-arrow gallery-arrow-next" aria-label="Next photo">
        <svg viewBox="0 0 24 24" aria-hidden="true"><polyline points="9 6 15 12 9 18"/></svg>
      </button>
      <div class="gallery-dots" id="gallery-dots">
        <?php foreach ($gallery as $i => $img): ?>
          <button type="button" class="gallery-dot<?= $i === 0 ? ' active' : '' ?>" data-index="<?= (int)$i ?>" aria-label="Go to photo <?= (int)$i + 1 ?>"></button>
        <?php endforeach; ?>
      </div>
      <span class="gallery-label">Photo Gallery</span>
    </div>
  </div>
</div>
