<?php
declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';
cms_require_login();

$id = cms_post_str('id') ?: trim((string)($_GET['id'] ?? ''));
$article = $id !== '' ? cms_get_article_by_id($id) : null;
$isNew = $article === null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = cms_post_str('action');

    if ($action === 'delete' && $id !== '') {
        cms_delete_article($id);
        cms_flash('success', 'Article deleted.');
        header('Location: articles.php');
        exit;
    }

    $title = cms_post_str('title');
    $slug = cms_post_str('slug') ?: cms_slugify($title);
    $slug = cms_slugify($slug);

    $data = [
        'id' => $isNew ? cms_new_id() : $id,
        'slug' => $slug,
        'title' => $title,
        'tag' => cms_post_str('tag'),
        'excerpt' => cms_post_str('excerpt'),
        'image' => cms_post_str('image'),
        'author' => cms_post_str('author') ?: 'Discover Parbat',
        'published_at' => cms_post_str('published_at') ?: date('Y-m-d'),
        'read_minutes' => max(1, (int)($_POST['read_minutes'] ?? 5)),
        'meta_description' => cms_post_str('meta_description'),
        'meta_keywords' => cms_post_str('meta_keywords'),
        'body_html' => (string)($_POST['body_html'] ?? ''),
        'published' => cms_post_bool('published'),
    ];

    if ($title === '' || $slug === '') {
        cms_flash('error', 'Title and slug are required.');
        header('Location: article-edit.php' . ($isNew ? '' : '?id=' . urlencode($id)));
        exit;
    }

    foreach (cms_get_articles(false) as $existing) {
        if (($existing['slug'] ?? '') === $slug && ($existing['id'] ?? '') !== $data['id']) {
            cms_flash('error', 'Another article already uses that slug.');
            header('Location: article-edit.php' . ($isNew ? '' : '?id=' . urlencode($id)));
            exit;
        }
    }

    cms_save_article($data);
    cms_flash('success', $isNew ? 'Article created.' : 'Article updated.');
    header('Location: article-edit.php?id=' . urlencode($data['id']));
    exit;
}

$defaults = [
    'title' => '',
    'slug' => '',
    'tag' => 'Trek Guide',
    'excerpt' => '',
    'image' => 'kokhe.jpg',
    'author' => 'Discover Parbat',
    'published_at' => date('Y-m-d'),
    'read_minutes' => 5,
    'meta_description' => '',
    'meta_keywords' => '',
    'body_html' => '',
    'published' => false,
];
$item = $article ?? $defaults;

ob_start();
?>
<form method="post" class="cms-form">
  <?php if (!$isNew): ?>
    <input type="hidden" name="id" value="<?= cms_h($id) ?>">
  <?php endif; ?>

  <div class="cms-grid-2">
    <div class="cms-field">
      <label for="title">Title *</label>
      <input id="title" name="title" value="<?= cms_h((string)$item['title']) ?>" required>
    </div>
    <div class="cms-field">
      <label for="slug">URL slug *</label>
      <input id="slug" name="slug" value="<?= cms_h((string)$item['slug']) ?>" placeholder="why-kokhe-danda-trek">
      <p class="cms-help">Published at /article/<strong>your-slug</strong></p>
    </div>
  </div>

  <div class="cms-grid-2">
    <div class="cms-field">
      <label for="tag">Category tag</label>
      <input id="tag" name="tag" value="<?= cms_h((string)$item['tag']) ?>">
    </div>
    <div class="cms-field">
      <label for="image">Cover image filename</label>
      <input id="image" name="image" value="<?= cms_h((string)$item['image']) ?>" placeholder="kokhe.jpg">
      <p class="cms-help">Upload image to site root, then enter filename here.</p>
    </div>
  </div>

  <div class="cms-field">
    <label for="excerpt">Short excerpt (listing card)</label>
    <textarea id="excerpt" name="excerpt"><?= cms_h((string)$item['excerpt']) ?></textarea>
  </div>

  <div class="cms-field">
    <label for="body_html">Article body (HTML)</label>
    <textarea id="body_html" class="body" name="body_html"><?= cms_h((string)$item['body_html']) ?></textarea>
    <p class="cms-help">Use simple HTML: &lt;p&gt;, &lt;h2&gt;, &lt;ul&gt;, &lt;img&gt;, &lt;a&gt;</p>
  </div>

  <div class="cms-grid-2">
    <div class="cms-field">
      <label for="author">Author</label>
      <input id="author" name="author" value="<?= cms_h((string)$item['author']) ?>">
    </div>
    <div class="cms-field">
      <label for="published_at">Publish date</label>
      <input id="published_at" name="published_at" type="date" value="<?= cms_h((string)$item['published_at']) ?>">
    </div>
  </div>

  <div class="cms-grid-2">
    <div class="cms-field">
      <label for="read_minutes">Read time (minutes)</label>
      <input id="read_minutes" name="read_minutes" type="number" min="1" value="<?= (int)$item['read_minutes'] ?>">
    </div>
    <div class="cms-field cms-check" style="padding-top:28px;">
      <label><input type="checkbox" name="published" value="1" <?= !empty($item['published']) ? 'checked' : '' ?>> Published (visible on website)</label>
    </div>
  </div>

  <div class="cms-field">
    <label for="meta_description">SEO meta description</label>
    <textarea id="meta_description" name="meta_description"><?= cms_h((string)$item['meta_description']) ?></textarea>
  </div>

  <div class="cms-field">
    <label for="meta_keywords">SEO keywords</label>
    <input id="meta_keywords" name="meta_keywords" value="<?= cms_h((string)$item['meta_keywords']) ?>">
  </div>

  <div class="cms-form-actions">
    <button class="cms-btn cms-btn-primary" type="submit">Save article</button>
    <a class="cms-btn cms-btn-ghost" href="articles.php">Cancel</a>
    <?php if (!$isNew): ?>
      <button class="cms-btn cms-btn-danger" type="submit" name="action" value="delete" onclick="return confirm('Delete this article?')">Delete</button>
    <?php endif; ?>
  </div>
</form>
<?php
cms_page($isNew ? 'New article' : 'Edit article', (string)ob_get_clean(), 'articles');
