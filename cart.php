<?php
require_once __DIR__ . '/config/bootstrap.php';
$_SESSION['cart'] = $_SESSION['cart'] ?? [];   // product_id => quantity
load_customer_cart();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_valid()) {
        flash('error', 'Your session expired. Please try again.');
        redirect('cart.php');
    }
    $action = $_POST['action'] ?? '';
    $pid = (int)($_POST['product_id'] ?? 0);
    $requested = max(1, (int)($_POST['qty'] ?? 1));

    if ($action === 'remove') {
        unset($_SESSION['cart'][$pid]);
        flash('success', 'Item removed from cart.');
    } elseif (in_array($action, ['add', 'update'], true)) {
        $st = db()->prepare('SELECT stock FROM products WHERE id = ? AND is_active = 1');
        $st->execute([$pid]);
        $stock = (int)$st->fetchColumn();   // 0 when product is missing or inactive
        if ($stock < 1) {
            flash('error', 'Sorry, this product is out of stock.');
        } else {
            $wanted = $action === 'add' ? ($_SESSION['cart'][$pid] ?? 0) + $requested : $requested;
            $new = min($wanted, $stock);
            $_SESSION['cart'][$pid] = $new;
            if ($new < $wanted) {
                flash('error', "Only $stock available. Quantity set to $new.");
            } else {
                flash('success', 'Cart updated.');
            }
        }
    }
    sync_customer_cart();
    redirect('cart.php');
}

$pageTitle = 'Your cart';
$items = [];
$subtotal = 0.0;
$ids = array_keys($_SESSION['cart']);

if ($ids) {
    $in = implode(',', array_fill(0, count($ids), '?'));
    $st = db()->prepare("SELECT id, name, slug, brand, price, discount_price, stock, image FROM products WHERE is_active = 1 AND id IN ($in)");
    $st->execute($ids);
    $rows = [];
    foreach ($st->fetchAll() as $r) {
        $rows[(int)$r['id']] = $r;
    }
    foreach ($ids as $pid) {
        // Prices and stock are always read from MySQL, never trusted from the session.
        if (!isset($rows[$pid])) {
            unset($_SESSION['cart'][$pid]);
            continue;
        }
        $p = $rows[$pid];
        $qty = min((int)$_SESSION['cart'][$pid], (int)$p['stock']);
        if ($qty < 1) {
            unset($_SESSION['cart'][$pid]);
            continue;
        }
        $_SESSION['cart'][$pid] = $qty;
        $unit = (float)($p['discount_price'] ?? $p['price']);
        $p['qty'] = $qty;
        $p['unit'] = $unit;
        $p['line'] = $unit * $qty;
        $items[] = $p;
        $subtotal += $p['line'];
    }
}
sync_customer_cart();

$delivery = ($subtotal > 0 && $subtotal < FREE_DELIVERY_OVER) ? DELIVERY_FEE : 0.0;
$total = $subtotal + $delivery;

require __DIR__ . '/includes/header.php';
?>
<h1>Your cart</h1>
<?php if (!$items): ?>
  <p class="empty">Your cart is empty. <a href="<?= e(url('shop.php')) ?>">Continue shopping</a></p>
<?php else: ?>
<div class="table-wrap">
<table class="cart-table">
  <thead><tr><th>Product</th><th>Unit price</th><th>Quantity</th><th>Subtotal</th><th></th></tr></thead>
  <tbody>
  <?php foreach ($items as $it): ?>
    <tr>
      <td><div style="display:flex;gap:12px;align-items:center">
        <img src="<?= e(image_url($it['image'])) ?>" alt="">
        <div><a href="<?= e(url('product.php?slug=' . urlencode($it['slug']))) ?>"><strong><?= e($it['name']) ?></strong></a><br><small><?= e($it['brand']) ?></small></div>
      </div></td>
      <td><?= money($it['unit']) ?></td>
      <td>
        <form method="post" action="<?= e(url('cart.php')) ?>" class="inline">
          <?= csrf_field() ?>
          <input type="hidden" name="action" value="update">
          <input type="hidden" name="product_id" value="<?= (int)$it['id'] ?>">
          <input type="number" name="qty" min="1" max="<?= (int)$it['stock'] ?>" value="<?= (int)$it['qty'] ?>">
          <button class="btn">Update</button>
        </form>
      </td>
      <td><?= money($it['line']) ?></td>
      <td>
        <form method="post" action="<?= e(url('cart.php')) ?>">
          <?= csrf_field() ?>
          <input type="hidden" name="action" value="remove">
          <input type="hidden" name="product_id" value="<?= (int)$it['id'] ?>">
          <button class="link-btn">Remove</button>
        </form>
      </td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>
</div>
<div class="summary">
  <div><span>Subtotal</span><span><?= money($subtotal) ?></span></div>
  <div><span>Delivery</span><span><?= $delivery > 0 ? money($delivery) : 'Free' ?></span></div>
  <div class="total"><span>Total</span><span><?= money($total) ?></span></div>
  <?php if (current_customer()): ?>
    <a class="btn checkout-link" href="<?= e(url('checkout.php')) ?>">Continue to checkout <span aria-hidden="true">→</span></a>
  <?php else: ?>
    <a class="btn checkout-link" href="<?= e(url('login.php?next=checkout.php')) ?>">Sign in to checkout <span aria-hidden="true">→</span></a>
    <p class="form-intro">New here? <a href="<?= e(url('register.php?next=checkout.php')) ?>">Create an account</a> and your cart will be saved.</p>
  <?php endif; ?>
</div>
<?php endif; ?>
<?php require __DIR__ . '/includes/footer.php'; ?>
