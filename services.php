<?php
declare(strict_types=1);

$config = require __DIR__ . '/config.php';
require __DIR__ . '/includes/data.php';
$pageTitle = 'Services';
$activePage = 'services';
require __DIR__ . '/includes/header.php';
?>

<section class="page-hero section-space">
  <div class="container narrow center reveal">
    <span class="eyebrow">OUR SERVICES</span>
    <h1>CSC FastNexora Print — All-in-One Portal for VLE Operators</h1>
    <p>Explore document, banking, business, online, office, and creative support built around real everyday needs.</p>
  </div>
</section>

<section class="section-space compact-top">
  <div class="container">
    <div class="filter-shell glass reveal">
      <label class="service-search">
        <span>&#128269;</span>
        <input type="search" placeholder="Search services..." data-service-search>
      </label>
      <div class="filter-chips" data-service-filters>
        <button class="active" data-filter="all">All</button>
        <?php foreach (array_values(array_unique(array_column($siteServices, 'category'))) as $category): ?>
          <button data-filter="<?= htmlspecialchars(strtolower($category), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($category, ENT_QUOTES, 'UTF-8') ?></button>
        <?php endforeach; ?>
      </div>
    </div>

    <div class="service-grid services-page-grid" data-service-grid>
      <?php foreach ($siteServices as $index => $service): ?>
        <article class="service-card glass reveal" data-title="<?= htmlspecialchars(strtolower($service['title'] . ' ' . $service['description']), ENT_QUOTES, 'UTF-8') ?>" data-category="<?= htmlspecialchars(strtolower($service['category']), ENT_QUOTES, 'UTF-8') ?>" style="--delay: <?= ($index % 4) * 60 ?>ms">
          <div class="service-card-top">
            <span class="service-icon <?= $service['accent'] ?>"><?= $service['icon'] ?></span>
            <span class="category-pill"><?= htmlspecialchars($service['category'], ENT_QUOTES, 'UTF-8') ?></span>
          </div>
          <h3><?= htmlspecialchars($service['title'], ENT_QUOTES, 'UTF-8') ?></h3>
          <p><?= htmlspecialchars($service['description'], ENT_QUOTES, 'UTF-8') ?></p>
          <a href="<?= htmlspecialchars($service['link'] ?? 'contact?service=' . urlencode($service['title']), ENT_QUOTES, 'UTF-8') ?>">Choose service <span>&rarr;</span></a>
        </article>
      <?php endforeach; ?>
    </div>
    <div class="empty-state glass" data-service-empty hidden>
      <span>&#128269;</span>
      <h2>No matching service</h2>
      <p>Try a broader search or send us a custom request.</p>
      <a class="btn btn-dark" href="contact">Contact us</a>
    </div>
  </div>
</section>

<section class="section-space compact-top">
  <div class="container process-panel">
    <div class="section-heading reveal">
      <div>
        <span class="eyebrow">HOW IT WORKS</span>
        <h2>Three simple steps.</h2>
      </div>
    </div>
    <div class="process-grid">
      <article class="glass reveal"><span>01</span><h3>Tell us the task</h3><p>Choose a service or describe exactly what you need.</p></article>
      <article class="glass reveal"><span>02</span><h3>Review the requirements</h3><p>We confirm documents, timing, and the correct process.</p></article>
      <article class="glass reveal"><span>03</span><h3>Complete with confidence</h3><p>Receive clear status updates and your finished output.</p></article>
    </div>
  </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>

