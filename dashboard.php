<?php
session_start();

if (empty($_SESSION['phone'])) {
    header('Location: auth');
    exit;
}

require __DIR__ . '/includes/config.php';
$pageTitle = 'Dashboard';
$activePage = 'dashboard';
$config = require __DIR__ . '/config.php';
require __DIR__ . '/includes/header.php';

$displayName = isset($_SESSION['name']) ? $_SESSION['name'] : 'Customer';
$displayPhone = isset($_SESSION['phone']) ? $_SESSION['phone'] : '';
?>

<section class="dashboard-section section-space">
  <div class="container">
    <div class="dashboard-welcome">
      <div>
        <span class="eyebrow">CUSTOMER DASHBOARD</span>
        <h1>Welcome, <?= htmlspecialchars($displayName, ENT_QUOTES, 'UTF-8') ?>.</h1>
        <p>Your digital service shortcuts are ready.</p>
      </div>
      <a class="btn btn-white" href="logout">Sign out</a>
    </div>

    <div class="dashboard-grid">
      <article class="profile-card glass">
        <span class="profile-avatar"><?= htmlspecialchars(strtoupper(substr($displayName, 0, 1)), ENT_QUOTES, 'UTF-8') ?></span>
        <h2><?= htmlspecialchars($displayName, ENT_QUOTES, 'UTF-8') ?></h2>
        <p><?= htmlspecialchars($displayPhone, ENT_QUOTES, 'UTF-8') ?></p>
        <span class="role-pill">Customer</span>
      </article>

      <div class="dashboard-main">
        <div class="quick-actions glass">
          <div class="card-heading">
            <span class="micro-label">QUICK ACTIONS</span>
            <h2>What would you like to do?</h2>
          </div>
          <div class="action-list">
            <a href="services"><span class="service-icon blue">DS</span><div><strong>Browse digital services</strong><small>Documents, banking, and business help</small></div><b>&rarr;</b></a>
            <a href="tools"><span class="service-icon pink">AI</span><div><strong>Open photo studio</strong><small>Crop or remove an image background</small></div><b>&rarr;</b></a>
            <a href="contact"><span class="service-icon green">@</span><div><strong>Contact support</strong><small>Send a new service request</small></div><b>&rarr;</b></a>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
