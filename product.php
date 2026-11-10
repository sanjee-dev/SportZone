<?php
require_once __DIR__ . '/config/bootstrap.php';
$st = db()->prepare('SELECT p.*, c.name AS category FROM products p LEFT JOIN categories c ON c.id = p.category_id WHERE p.slug = ? AND p.is_active = 1');
$st->execute([$_GET['slug'] ?? '']);
$p = $st->fetch();
$pageTitle = $p ? $p['name'] : 'Product not found';
require __DIR__ . '/includes/header.php';
if (!$p) {
    echo '<p class="empty">This product is not available.</p>';
    require __DIR__ . '/includes/footer.php';
    exit;
}
?>
<div class="product-page">
  <img src="<?= e(image_url($p['image'])) ?>" alt="<?= e($p['name']) ?>">
  <div>
    <small><?= e($p['brand']) ?> · <?= e($p['category'] ?? 'Uncategorised') ?> · SKU <?= e($p['sku']) ?></small>
    <h1><?= e($p['name']) ?></h1>
    <p class="price">
      <?php if ($p['discount_price'] !== null): ?>
        <s><?= money($p['price']) ?></s> <strong><?= money($p['discount_price']) ?></strong>
      <?php else: ?>
        <strong><?= money($p['price']) ?></strong>
      <?php endif; ?>
    </p>
    <p><?= $p['stock'] > 0 ? 'In stock: ' . (int)$p['stock'] . ' available' : 'Out of stock' ?></p>
    <?php if ($p['stock'] > 0): ?>
    <form method="post" action="<?= e(url('cart.php')) ?>" class="add-form">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="add">
      <input type="hidden" name="product_id" value="<?= (int)$p['id'] ?>">
      <input type="number" name="qty" value="1" min="1" max="<?= (int)$p['stock'] ?>" required>
      <button class="btn">Add to cart</button>
    </form>
    <?php endif; ?>
    <p><?= nl2br(e($p['description'])) ?></p>
  </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
