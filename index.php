<?php
require_once __DIR__ . '/config/bootstrap.php';

$pageTitle = 'Sports gear for every game';
$featured = db()->query(
    'SELECT * FROM products WHERE is_active = 1 AND is_featured = 1 ORDER BY id DESC LIMIT 4'
)->fetchAll();
$categories = db()->query(
    'SELECT name, slug FROM categories WHERE is_active = 1 ORDER BY name'
)->fetchAll();

require __DIR__ . '/includes/header.php';
?>
<section class="home-hero">
  <div class="hero-copy">
    <p class="eyebrow"><span></span> Find your game. Find your gear.</p>
    <h1>Made for the<br>love of <span>sport.</span></h1>
    <p class="hero-intro">From match-day essentials to everyday training gear, find what you need to get out there and play.</p>
    <div class="hero-actions">
      <a class="btn" href="<?= e(url('shop.php')) ?>">Explore the shop <span aria-hidden="true">→</span></a>
      <a class="text-link" href="#categories">Browse sports <span aria-hidden="true">↓</span></a>
    </div>
    <div class="hero-note"><span class="hero-note-mark" aria-hidden="true">SZ</span><span>Gear up for your next<br>great moment.</span></div>
  </div>
  <div class="hero-art" aria-label="SportZone sports collection">
    <div class="hero-art-ring hero-art-ring-one"></div>
    <div class="hero-art-ring hero-art-ring-two"></div>
    <div class="hero-art-label">PLAY YOUR WAY <span>—</span> SPORTZONE</div>
    <div class="hero-ball" aria-hidden="true">
      <svg viewBox="0 0 220 220" role="presentation">
        <circle cx="110" cy="110" r="96" fill="#fff"/>
        <path d="M75 26 110 39l35-13 25 31-4 36 31 25-18 37-34 12-14 40H89l-14-40-34-12-18-37 31-25-4-36z" fill="#0b1a2e"/>
        <path d="m75 26 19 39-21 27-44-5m81-48-17 36 29 24 44-5m-81-4-22 39 15 34m88-73-22 39 15 34m-103 0 37-8 22 32m44-24-37-8-22 32m-61-71 41-1m82 1-41-1" fill="none" stroke="#ff6a13" stroke-linecap="round" stroke-linejoin="round" stroke-width="5"/>
      </svg>
    </div>
    <div class="hero-art-caption"><strong>ONE STORE.</strong><span>ALL YOUR SPORTS.</span></div>
    <div class="hero-art-index">01 / 04</div>
  </div>
</section>

<section class="promise-strip" aria-label="What you can find at SportZone">
  <div><span class="promise-icon" aria-hidden="true">01</span><span><strong>Gear for every game</strong><small>From the court to the track</small></span></div>
  <div><span class="promise-icon" aria-hidden="true">02</span><span><strong>Picked for performance</strong><small>Ready for practice and play</small></span></div>
  <div><span class="promise-icon" aria-hidden="true">03</span><span><strong>Shop your way</strong><small>Browse by sport or product</small></span></div>
</section>

<section class="home-section" id="categories">
  <div class="section-heading">
    <div><p class="eyebrow">Find your starting line</p><h2>Shop by sport</h2></div>
    <a class="text-link" href="<?= e(url('shop.php')) ?>">View all gear <span aria-hidden="true">→</span></a>
  </div>
  <?php if ($categories): ?>
  <div class="category-grid">
    <?php foreach ($categories as $index => $category): ?>
    <a class="category-tile" href="<?= e(url('shop.php?cat=' . urlencode($category['slug']))) ?>">
      <span class="category-number"><?= e(str_pad((string)($index + 1), 2, '0', STR_PAD_LEFT)) ?></span>
      <span class="category-name"><?= e($category['name']) ?></span>
      <span class="category-arrow" aria-hidden="true">↗</span>
      <span class="category-rule"></span>
    </a>
    <?php endforeach; ?>
  </div>
  <?php else: ?>
    <p class="empty">Sports categories will appear here soon.</p>
  <?php endif; ?>
</section>

<section class="featured-section home-section">
  <div class="section-heading">
    <div><p class="eyebrow">Handpicked for you</p><h2>Featured gear</h2></div>
    <a class="text-link" href="<?= e(url('shop.php')) ?>">Shop all products <span aria-hidden="true">→</span></a>
  </div>
  <?php if (!$featured): ?><p class="empty">Featured products will appear here soon.</p><?php endif; ?>
  <div class="grid">
    <?php foreach ($featured as $product) { echo product_card($product); } ?>
  </div>
</section>

<section class="about-section" id="about">
  <div class="about-mark" aria-hidden="true"><span>SPORT</span><strong>ZONE</strong><i>PLAY EVERY DAY</i></div>
  <div class="about-copy">
    <p class="eyebrow">A little about us</p>
    <h2>For the first run,<br>the big match, and<br>every day in between.</h2>
    <p>SportZone is a sports gear shop built around one simple idea: getting ready to play should feel easy. We bring together equipment, footwear, fitness essentials, and sportswear in one place, so you can spend less time searching and more time doing what you love.</p>
    <p>Whether you are learning a new sport, keeping up your training, or getting your team ready, start with the gear that fits your game.</p>
    <a class="btn btn-light" href="<?= e(url('shop.php')) ?>">Find your gear <span aria-hidden="true">→</span></a>
  </div>
</section>

<section class="home-cta">
  <div><p class="eyebrow">Your next game starts here</p><h2>Ready when you are.</h2></div>
  <a class="btn" href="<?= e(url('shop.php')) ?>">Shop SportZone <span aria-hidden="true">→</span></a>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
