<?php
declare(strict_types=1);

$config = require __DIR__ . '/config.php';
$pageTitle = 'Contact';
$activePage = 'contact';
$pageScripts = ['assets/js/contact.js'];
require __DIR__ . '/includes/header.php';
$selectedService = trim(isset($_GET['service']) ? (string) $_GET['service'] : '');
?>

<section class="page-hero section-space">
  <div class="container narrow center reveal">
    <span class="eyebrow">CONTACT US</span>
    <h1>Let us get your task moving with FastNexora Print Portal</h1>
    <p>Send the details below. Your message is securely stored and delivered to our support inbox.</p>
  </div>
</section>

<section class="section-space compact-top">
  <div class="container contact-layout">
    <aside class="contact-aside reveal">
      <span class="eyebrow">SUPPORT DESK</span>
      <h2>Real help from a real person.</h2>
      <p>Share your goal, deadline, and the issue you are facing.</p>
      <div class="contact-detail">
        <span class="detail-icon">@</span>
        <div>
          <small>Email</small>
         <?php $email = 'contact@' . $websiteMailDomain; ?>
<a href="mailto:<?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8') ?>">
    <?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8') ?>
</a>
        </div>
      </div>
      <div class="response-card glass">
        <span class="status-dot"></span>
        <div><strong>Typical response time</strong><small>Within one business day</small></div>
      </div>
    </aside>

    <div class="form-card glass reveal">
      <div class="form-heading">
        <span class="micro-label">SEND A MESSAGE</span>
        <h2>How can we help?</h2>
      </div>
      <form data-contact-form novalidate>
        <div class="form-row">
          <label class="field">
            <span>Full name</span>
            <input type="text" name="name" autocomplete="name" maxlength="80" required>
            <small class="field-error"></small>
          </label>
          <label class="field">
            <span>Email address</span>
            <input type="email" name="email" autocomplete="email" required>
            <small class="field-error"></small>
          </label>
        </div>
        <div class="form-row">
          <label class="field">
            <span>Phone <em>optional</em></span>
            <input type="tel" name="phone" maxlength="20">
            <small class="field-error"></small>
          </label>
          <label class="field">
            <span>Subject</span>
            <input type="text" name="subject" maxlength="120" value="<?= htmlspecialchars($selectedService, ENT_QUOTES, 'UTF-8') ?>" required>
            <small class="field-error"></small>
          </label>
        </div>
        <label class="field">
          <span>Message</span>
          <textarea name="message" rows="6" maxlength="3000" required></textarea>
          <small class="field-error"></small>
        </label>
        <div class="form-submit">
          <p>We will use these details only to respond to this request.</p>
          <button class="btn btn-dark btn-large" type="submit">
            <span class="button-label">Send message</span>
            <span class="button-loader" aria-hidden="true"></span>
          </button>
        </div>
        <div class="form-result" role="status" hidden></div>
      </form>
    </div>
  </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
