<?php
declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';
cms_require_login();

$id = cms_post_str('id') ?: trim((string)($_GET['id'] ?? ''));
$trek = $id !== '' ? cms_get_trek_by_id($id) : null;
$isNew = $trek === null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = cms_post_str('action');

    if ($action === 'delete' && $id !== '') {
        cms_delete_trek($id);
        cms_flash('success', 'Trek removed from listings.');
        header('Location: treks.php');
        exit;
    }

    $title = cms_post_str('title');
    $slug = cms_post_str('slug') ?: cms_slugify($title);
    $slug = cms_slugify($slug);

    $data = [
        'id' => $isNew ? cms_new_id() : $id,
        'slug' => $slug,
        'title' => $title,
        'subtitle' => cms_post_str('subtitle'),
        'image' => cms_post_str('image'),
        'difficulty' => cms_post_str('difficulty') ?: 'easy',
        'days' => max(1, (int)($_POST['days'] ?? 1)),
        'days_label' => cms_post_str('days_label'),
        'sort_order' => (int)($_POST['sort_order'] ?? 0),
        'published' => cms_post_bool('published'),
        'featured' => cms_post_bool('featured'),
    ];

    if (!$isNew && $trek !== null) {
        if (isset($trek['cms_page'])) {
            $data['cms_page'] = $trek['cms_page'];
        }
        if (isset($trek['page'])) {
            $data['page'] = $trek['page'];
        }
    }

    if ($data['days_label'] === '') {
        $data['days_label'] = $data['days'] . ' Days';
    }

    if ($title === '' || $slug === '') {
        cms_flash('error', 'Title and slug are required.');
        header('Location: trek-edit.php' . ($isNew ? '' : '?id=' . urlencode($id)));
        exit;
    }

    foreach (cms_get_treks(false) as $existing) {
        if (($existing['slug'] ?? '') === $slug && ($existing['id'] ?? '') !== $data['id']) {
            cms_flash('error', 'Another trek already uses that slug.');
            header('Location: trek-edit.php' . ($isNew ? '' : '?id=' . urlencode($id)));
            exit;
        }
    }

    cms_save_trek($data);
    cms_flash('success', $isNew ? 'Trek created.' : 'Trek updated.');
    header('Location: trek-edit.php?id=' . urlencode($data['id']));
    exit;
}

$defaults = [
    'title' => '',
    'slug' => '',
    'subtitle' => '',
    'image' => 'kokhe.jpg',
    'difficulty' => 'easy',
    'days' => 5,
    'days_label' => '5 Days',
    'sort_order' => count(cms_get_treks(false)) + 1,
    'published' => true,
    'featured' => true,
];
$item = $trek ?? $defaults;

ob_start();
?>
<form method="post" class="cms-form">
  <?php if (!$isNew): ?>
    <input type="hidden" name="id" value="<?= cms_h($id) ?>">
  <?php endif; ?>

  <div class="cms-grid-2">
    <div class="cms-field">
      <label for="title">Trek name *</label>
      <input id="title" name="title" value="<?= cms_h((string)$item['title']) ?>" required>
    </div>
    <div class="cms-field">
      <label for="slug">URL slug *</label>
      <input id="slug" name="slug" value="<?= cms_h((string)$item['slug']) ?>" placeholder="kokhe-danda-trek">
      <p class="cms-help">Links to existing page at /<strong>slug</strong></p>
    </div>
  </div>

  <div class="cms-field">
    <label for="subtitle">Short description (card text)</label>
    <input id="subtitle" name="subtitle" value="<?= cms_h((string)$item['subtitle']) ?>">
  </div>

  <?php
  $imageInputId = 'image';
  $imageLabel = 'Card image';
  $imageValue = (string)$item['image'];
  include __DIR__ . '/includes/image-field.php';
  ?>

  <div class="cms-field">
    <label for="sort_order">Sort order</label>
    <input id="sort_order" name="sort_order" type="number" value="<?= (int)$item['sort_order'] ?>">
  </div>

  <div class="cms-grid-2">
    <div class="cms-field">
      <label for="difficulty">Difficulty</label>
      <select id="difficulty" name="difficulty">
        <?php foreach (['easy', 'moderate', 'hard'] as $level): ?>
          <option value="<?= $level ?>" <?= ($item['difficulty'] ?? '') === $level ? 'selected' : '' ?>><?= ucfirst($level) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="cms-field">
      <label for="days">Duration (days)</label>
      <input id="days" name="days" type="number" min="1" value="<?= (int)$item['days'] ?>">
    </div>
  </div>

  <div class="cms-field">
    <label for="days_label">Duration label (shown on card)</label>
    <input id="days_label" name="days_label" value="<?= cms_h((string)$item['days_label']) ?>" placeholder="5 Days">
  </div>

  <div class="cms-grid-2">
    <div class="cms-field cms-check">
      <label><input type="checkbox" name="published" value="1" <?= !empty($item['published']) ? 'checked' : '' ?>> Published on /treks</label>
    </div>
    <div class="cms-field cms-check">
      <label><input type="checkbox" name="featured" value="1" <?= !empty($item['featured']) ? 'checked' : '' ?>> Featured on homepage (future use)</label>
    </div>
  </div>

  <div class="cms-form-actions">
    <button class="cms-btn cms-btn-primary" type="submit">Save trek</button>
    <?php if (!$isNew): ?>
      <a class="cms-btn cms-btn-ghost" href="trek-page-edit.php?id=<?= urlencode($id) ?>">Edit full page</a>
    <?php endif; ?>
    <a class="cms-btn cms-btn-ghost" href="treks.php">Cancel</a>
    <?php if (!$isNew): ?>
      <button class="cms-btn cms-btn-danger" type="submit" name="action" value="delete" onclick="return confirm('Remove this trek from CMS listings?')">Delete</button>
    <?php endif; ?>
  </div>
</form>
<?php
cms_page($isNew ? 'New trek' : 'Edit trek', (string)ob_get_clean(), 'treks');
