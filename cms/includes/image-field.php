<?php
declare(strict_types=1);

/** @var string $imageInputId */
/** @var string $imageLabel */
/** @var string $imageValue */

$imageInputId = $imageInputId ?? 'image';
$imageLabel = $imageLabel ?? 'Cover image';
$imageValue = $imageValue ?? '';
$previewUrl = $imageValue !== '' ? cms_public_image_url($imageValue) : '';
?>
<div class="cms-field cms-image-field">
  <label for="<?= cms_h($imageInputId) ?>"><?= cms_h($imageLabel) ?></label>
  <div class="cms-image-upload" data-target="<?= cms_h($imageInputId) ?>">
    <div class="cms-image-preview<?= $previewUrl === '' ? ' is-empty' : '' ?>">
      <?php if ($previewUrl !== ''): ?>
        <img src="<?= cms_h($previewUrl) ?>" alt="Image preview">
      <?php else: ?>
        <div class="cms-image-placeholder">
          <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 5a2 2 0 0 1 2-2h5l2 2h7a2 2 0 0 1 2 2v11a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V5Z"/><path d="M8 13l2.5 3 2-2.5L16 16"/></svg>
          <strong>Click to upload image</strong>
          <span>JPG, PNG, WebP or GIF · max 5 MB</span>
        </div>
      <?php endif; ?>
    </div>
    <input class="cms-image-file" type="file" accept="image/jpeg,image/png,image/webp,image/gif" hidden>
    <input id="<?= cms_h($imageInputId) ?>" name="<?= cms_h($imageInputId) ?>" type="text" value="<?= cms_h($imageValue) ?>" placeholder="uploads/your-image.jpg">
    <p class="cms-help">Upload a new image or keep an existing path such as <code>kokhe.jpg</code>.</p>
    <div class="cms-image-actions">
      <button class="cms-btn cms-btn-ghost cms-image-change" type="button">Upload image</button>
      <button class="cms-btn cms-btn-ghost cms-image-clear" type="button"<?= $previewUrl === '' ? ' style="display:none"' : '' ?>>Remove preview</button>
    </div>
    <p class="cms-image-status" aria-live="polite"></p>
  </div>
</div>
