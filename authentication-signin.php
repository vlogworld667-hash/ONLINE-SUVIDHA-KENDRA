<?php
declare(strict_types=1);

$config = require __DIR__ . '/config.php';
$pageTitle = 'Account';
$activePage = 'auth';
$pageScripts = ['assets/js/auth.js'];
require __DIR__ . '/includes/header.php';
$initialMode = ($_GET['mode'] ?? '') === 'register' ? 'register' : 'login';
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Sign in | <?= htmlspecialchars($websiteName, ENT_QUOTES, 'UTF-8') ?></title>
  <link rel="stylesheet" href="assets/css/app2.css">
</head>
<body>
  <main class="auth-section section-space">
    <div class="container auth-layout">
      <section class="auth-story">
        <a class="back-link" href="index">&larr; Back to home</a>
        <span class="eyebrow light">SECURE ACCOUNT</span>
        <h1>Welcome back.</h1>
        <p>Sign in using your registered phone number or email address.</p>
      </section>

      <section class="auth-card glass">
        <div class="form-heading">
          <span class="micro-label">ACCOUNT LOGIN</span>
          <h2>Sign in</h2>
          <p>Enter your account details below.</p>
        </div>
        <form method="post" action="">
          <input type="hidden" name="action" value="login">
          <label class="field">
            <span>Email or phone</span>
            <input id="username" type="text" name="username" autocomplete="username" required>
          </label>
          <label class="field">
            <span>Password</span>
            <input type="password" name="password" autocomplete="current-password" required>
          </label>
          <button class="btn btn-dark btn-large full-width" type="submit">Sign in</button>
        </form>
      </section>
    </div>
  </main>
</body>
</html>

<?php require __DIR__ . '/includes/footer.php'; ?>