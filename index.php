<?php
declare(strict_types=1);

$config = require __DIR__ . '/config.php';
require __DIR__ . '/includes/data.php';
$pageTitle = 'FastNexora Digital Service';
$activePage = 'home';
require __DIR__ . '/includes/header.php';
?>

<section class="hero section-space">
  <div class="container hero-grid">
    <div class="hero-copy reveal">
      <div class="eyebrow"><span class="status-dot"></span> A smarter digital service center</div>
      <h1><span>FastNexora Print -</span> India’s Smart Print Solution.</h1>
      <p class="hero-lead">Applications, document support, online checks, and advanced photo tools in one calm, secure workspace.</p>
      <div class="hero-actions">
        <a class="btn btn-dark btn-large" href="services">Explore services <span aria-hidden="true">&rarr;</span></a>
        <a class="btn btn-white btn-large" href="tools">Open photo studio</a>
      </div>
      <div class="trust-row">
        <div class="avatar-stack" aria-hidden="true">
          <span>AK</span><span>RS</span><span>NM</span>
        </div>
        <div><strong>5,000+ happy customers</strong><small>Trusted for accurate digital assistance</small></div>
      </div>
    </div>

    <div class="hero-visual reveal">
      <div class="orbit orbit-one"></div>
      <div class="orbit orbit-two"></div>
      <div class="hero-panel glass">
        <div class="panel-top">
          <div>
            <span class="micro-label">SERVICE DESK</span>
            <h2>What can we help with?</h2>
          </div>
          <span class="live-pill"><i></i> Online</span>
        </div>
        <div class="quick-grid">
          <a href="services" class="quick-item"><span class="quick-icon blue">PC</span><strong>PAN services</strong><small>Apply and update</small></a>
          <a href="services" class="quick-item"><span class="quick-icon violet">AP</span><strong>Aadhaar help</strong><small>PVC and print</small></a>
          <a href="services" class="quick-item"><span class="quick-icon cyan">NS</span><strong>NPCI status</strong><small>Bank mapping</small></a>
          <a href="tools" class="quick-item"><span class="quick-icon pink">AI</span><strong>Photo studio</strong><small>Crop and remove BG</small></a>
        </div>
        <div class="panel-search">
          <span aria-hidden="true">&#128269;</span>
          <input type="text" placeholder="Search a digital service" data-home-search>
          <kbd>/</kbd>
        </div>
      </div>
      <div class="floating-note glass">
        <span class="note-icon">&#10003;</span>
        <span><strong>Request completed</strong><small>Your documents are ready</small></span>
      </div>
    </div>
  </div>
</section>

<section class="logo-strip">
  <div class="container">
    <p>One place for your essential digital tasks</p>
    <div class="trust-features">
      <span><i>&#10003;</i> Secure handling</span>
      <span><i>&#10003;</i> Clear guidance</span>
      <span><i>&#10003;</i> Fast turnaround</span>
      <span><i>&#10003;</i> Mobile friendly</span>
    </div>
  </div>
</section>

<section class="section-space">
  <div class="container">
    <div class="section-heading reveal">
      <div>
        <span class="eyebrow">POPULAR SERVICES</span>
        <h2>Useful help without the usual friction.</h2>
      </div>
      <a class="text-link" href="services">View all services <span>&rarr;</span></a>
    </div>
    <div class="service-grid home-services">
      <?php foreach (array_slice($siteServices, 0, 6) as $index => $service): ?>
        <article class="service-card glass reveal" style="--delay: <?= $index * 70 ?>ms">
          <div class="service-card-top">
            <span class="service-icon <?= $service['accent'] ?>"><?= $service['icon'] ?></span>
            <span class="category-pill"><?= htmlspecialchars($service['category'], ENT_QUOTES, 'UTF-8') ?></span>
          </div>
          <h3><?= htmlspecialchars($service['title'], ENT_QUOTES, 'UTF-8') ?></h3>
          <p><?= htmlspecialchars($service['description'], ENT_QUOTES, 'UTF-8') ?></p>
          <a href="<?= htmlspecialchars($service['link'] ?? 'contact', ENT_QUOTES, 'UTF-8') ?>">Get started <span>&rarr;</span></a>
        </article>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<section class="section-space soft-section">
  <div class="container split-feature">
    <div class="feature-visual reveal">
      <div class="studio-mock glass">
        <div class="mock-toolbar"><i></i><i></i><i></i><span>Photo Studio</span></div>
        <div class="mock-canvas">
          <div class="mock-photo">
            <span class="photo-head"></span>
            <span class="photo-body"></span>
          </div>
          <span class="crop-corner c1"></span><span class="crop-corner c2"></span>
          <span class="crop-corner c3"></span><span class="crop-corner c4"></span>
        </div>
        <div class="mock-controls"><span></span><span></span><button>Export PNG</button></div>
      </div>
    </div>
    <div class="feature-copy reveal">
      <span class="eyebrow">BUILT-IN PHOTO STUDIO</span>
      <h2>Professional photo tools, right in your browser.</h2>
      <p>Prepare profile photos, documents, products, and social images without installing complicated software.</p>
      <ul class="check-list">
        <li><span>&#10003;</span><div><strong>Precision crop controls</strong><small>Aspect ratios, zoom, rotation, and drag positioning.</small></div></li>
        <li><span>&#10003;</span><div><strong>Background removal</strong><small>Create a clean transparent PNG through the secure API.</small></div></li>
        <li><span>&#10003;</span><div><strong>Private by design</strong><small>Cropping happens locally in your browser.</small></div></li>
      </ul>
      <a class="btn btn-dark" href="tools">Try the photo studio <span>&rarr;</span></a>
    </div>
  </div>
</section>

<section class="section-space">
  <div class="container cta-panel glass reveal">
    <div>
      <span class="eyebrow light">READY WHEN YOU ARE</span>
      <h2>Have a task in mind? Let us make it easier.</h2>
      <p>Tell us what you need. We will reply with the next clear step.</p>
    </div>
    <a class="btn btn-white btn-large" href="contact">Send a message <span>&rarr;</span></a>
  </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>