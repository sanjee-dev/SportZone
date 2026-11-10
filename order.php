<?php
require_once __DIR__ . '/config/bootstrap.php';
$customer = require_customer();
$orderId = max(0, (int)($_GET['id'] ?? 0));
$statement = db()->prepare('SELECT * FROM customer_orders WHERE id = ? AND customer_id = ?');
$statement->execute([$orderId, (int)$customer['id']]);
$order = $statement->fetch();
$pageTitle = $order ? 'Order ' . $order['order_number'] : 'Order not found';
require __DIR__ . '/includes/header.php';
if (!$order) {
    echo '<p class="empty">We could not find that order in your account. <a href="' . e(url('account.php')) . '">Back to your account</a></p>';
    require __DIR__ . '/includes/footer.php';
    exit;
}
$statement = db()->prepare(
    'SELECT product_name, sku, unit_price, quantity, line_total FROM customer_order_items WHERE order_id = ? ORDER BY id'
);
$statement->execute([$orderId]);
$items = $statement->fetchAll();
?>
<div class="page-heading">
  <div><p class="eyebrow">Thanks for shopping SportZone</p><h1>Order placed</h1></div>
  <a class="text-link" href="<?= e(url('account.php')) ?>">← My account</a>
</div>
<section class="order-confirmation">
  <div class="order-confirm-top">
    <div><span class="order-status"><?= e(ucfirst($order['status'])) ?></span><h2><?= e($order['order_number']) ?></h2><p>Placed <?= e(date('M j, Y \a\t g:i a', strtotime($order['created_at']))) ?></p></div>
    <div class="order-confirm-total"><small>Order total</small><strong><?= money($order['total']) ?></strong></div>
  </div>
  <div class="order-detail-grid">
    <div class="account-panel">
      <h2>Items</h2>
      <?php foreach ($items as $item): ?>
      <div class="order-item">
        <span><strong><?= e($item['product_name']) ?></strong><small>SKU <?= e($item['sku']) ?> · Qty <?= (int)$item['quantity'] ?> · <?= money($item['unit_price']) ?> each</small></span>
        <strong><?= money($item['line_total']) ?></strong>
      </div>
      <?php endforeach; ?>
      <div class="summary-row"><span>Subtotal</span><strong><?= money($order['subtotal']) ?></strong></div>
      <div class="summary-row"><span>Delivery</span><strong><?= (float)$order['delivery_fee'] > 0 ? money($order['delivery_fee']) : 'Free' ?></strong></div>
      <div class="summary-row summary-total"><span>Total</span><strong><?= money($order['total']) ?></strong></div>
    </div>
    <aside class="account-side">
      <div class="account-panel">
        <h2>Delivery to</h2>
        <p><strong><?= e($order['delivery_name']) ?></strong><br><?= e($order['delivery_phone']) ?></p>
        <p><?= e($order['address_line_1']) ?><?= $order['address_line_2'] ? '<br>' . e($order['address_line_2']) : '' ?><br><?= e($order['city']) ?><?= $order['region'] ? ', ' . e($order['region']) : '' ?><?= $order['postal_code'] ? ' ' . e($order['postal_code']) : '' ?><br><?= e($order['country']) ?></p>
      </div>
      <div class="account-panel">
        <h2>Payment</h2>
        <p><strong>Cash on delivery</strong><br><?= $order['payment_status'] === 'paid' ? 'Payment received' : 'Pay when your order arrives' ?></p>
      </div>
    </aside>
  </div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
