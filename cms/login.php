<?php
declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

if (cms_is_logged_in()) {
    header('Location: index.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = cms_post_str('username');
    $password = (string)($_POST['password'] ?? '');
    if (cms_attempt_login($username, $password)) {
        header('Location: index.php');
        exit;
    }
    $error = 'Invalid username or password.';
}

$creds = cms_get_credentials();
$noPassword = $creds['password'] === '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="robots" content="noindex, nofollow">
  <title>CMS Login | Discover Parbat</title>
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="assets/admin.css">
</head>
<body>
  <div class="cms-login-wrap">
    <div class="cms-login-card">
      <h1>Discover Parbat CMS</h1>
      <p>Sign in to manage articles and treks.</p>
      <?php if ($noPassword): ?>
        <div class="cms-flash cms-flash-error">Set your password in <code>cms-config.php</code> on the server first.</div>
      <?php endif; ?>
      <?php if ($error !== ''): ?>
        <div class="cms-flash cms-flash-error"><?= cms_h($error) ?></div>
      <?php endif; ?>
      <form method="post" class="cms-form">
        <div class="cms-field">
          <label for="username">Username</label>
          <input id="username" name="username" value="<?= cms_h($_POST['username'] ?? 'admin') ?>" required>
        </div>
        <div class="cms-field">
          <label for="password">Password</label>
          <input id="password" name="password" type="password" required>
        </div>
        <button class="cms-btn cms-btn-primary" type="submit">Sign in</button>
      </form>
    </div>
  </div>
</body>
</html>
