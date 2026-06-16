<?php
declare(strict_types=1);

require_once __DIR__ . '/lib/auth.php';
require_once dirname(__DIR__) . '/lib/content.php';
require_once __DIR__ . '/lib/upload.php';

function cms_page(string $title, string $content, string $active = ''): void
{
    $flash = cms_take_flash();
    $user = $_SESSION['cms_user'] ?? 'admin';
    ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="robots" content="noindex, nofollow">
  <title><?= cms_h($title) ?> | Discover Parbat CMS</title>
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="assets/admin.css">
</head>
<body>
  <div class="cms-shell">
    <aside class="cms-sidebar">
      <div class="cms-brand">
        <strong>Discover Parbat</strong>
        <span>Content Manager</span>
      </div>
      <nav>
        <a href="index.php" class="<?= $active === 'dashboard' ? 'active' : '' ?>">Dashboard</a>
        <a href="articles.php" class="<?= $active === 'articles' ? 'active' : '' ?>">Articles</a>
        <a href="treks.php" class="<?= $active === 'treks' ? 'active' : '' ?>">Treks</a>
        <a href="/" target="_blank" rel="noopener">View Website</a>
        <a href="logout.php">Log out</a>
      </nav>
      <p class="cms-user">Signed in as <?= cms_h($user) ?></p>
    </aside>
    <main class="cms-main">
      <header class="cms-top">
        <h1><?= cms_h($title) ?></h1>
      </header>
      <?php if ($flash): ?>
        <div class="cms-flash cms-flash-<?= cms_h($flash['type']) ?>"><?= cms_h($flash['message']) ?></div>
      <?php endif; ?>
      <?= $content ?>
    </main>
  </div>
  <script src="assets/admin.js" defer></script>
</body>
</html>
    <?php
}

function cms_post_str(string $key): string
{
    return trim((string)($_POST[$key] ?? ''));
}

function cms_post_bool(string $key): bool
{
    return !empty($_POST[$key]);
}
