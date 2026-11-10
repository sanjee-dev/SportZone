<?php
require_once __DIR__ . '/config/bootstrap.php';
$customerSession = require_customer();
$pageTitle = 'My account';
$pdo = db();
$statement = $pdo->prepare('SELECT * FROM customers WHERE id = ?');
$statement->execute([(int)$customerSession['id']]);
$customer = $statement->fetch();
if (!$customer) {
    unset($_SESSION['customer'], $_SESSION['cart_owner_loaded']);
    redirect('login.php');
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fields = [
        'full_name' => trim($_POST['full_name'] ?? ''),
        'phone' => trim($_POST['phone'] ?? ''),
        'delivery_name' => trim($_POST['delivery_name'] ?? ''),
        'delivery_phone' => trim($_POST['delivery_phone'] ?? ''),
        'address_line_1' => trim($_POST['address_line_1'] ?? ''),
        'address_line_2' => trim($_POST['address_line_2'] ?? ''),
        'city' => trim($_POST['city'] ?? ''),
        'region' => trim($_POST['region'] ?? ''),
        'postal_code' => trim($_POST['postal_code'] ?? ''),
        'country' => trim($_POST['country'] ?? ''),
    ];
    if (!csrf_valid()) {
        $error = 'Your session expired. Please try again.';
    } elseif (mb_strlen($fields['full_name']) < 2 || mb_strlen($fields['full_name']) > 120) {
        $error = 'Enter your name (2–120 characters).';
    } elseif (mb_strlen($fields['phone']) < 5 || mb_strlen($fields['phone']) > 30) {
        $error = 'Enter a valid phone number.';
    } elseif (mb_strlen($fields['delivery_name']) > 120 || mb_strlen($fields['delivery_phone']) > 30) {
        $error = 'Check the recipient name and delivery phone length.';
    } elseif (strlen($fields['address_line_1']) > 190 || strlen($fields['address_line_2']) > 190
        || strlen($fields['city']) > 100 || strlen($fields['region']) > 100
        || strlen($fields['postal_code']) > 30 || strlen($fields['country']) > 100) {
        $error = 'Some delivery details are too long. Please check the form.';
    } else {
        $statement = $pdo->prepare(
            'UPDATE customers SET full_name = ?, phone = ?, delivery_name = ?, delivery_phone = ?,
             address_line_1 = ?, address_line_2 = ?, city = ?, region = ?, postal_code = ?, country = ?
             WHERE id = ?'
        );
        $statement->execute([
            $fields['full_name'], $fields['phone'], $fields['delivery_name'] ?: $fields['full_name'],
            $fields['delivery_phone'] ?: $fields['phone'], $fields['address_line_1'] ?: null,
            $fields['address_line_2'] ?: null, $fields['city'] ?: null, $fields['region'] ?: null,
            $fields['postal_code'] ?: null, $fields['country'] ?: null, (int)$customer['id'],
        ]);
        $_SESSION['customer']['full_name'] = $fields['full_name'];
        flash('success', 'Your account and delivery details have been updated.');
        redirect('account.php');
    }
    $customer = array_merge($customer, $fields);
}

$statement = $pdo->prepare(
    'SELECT id, order_number, total, status, payment_status, created_at
     FROM customer_orders WHERE customer_id = ? ORDER BY id DESC LIMIT 10'
);
$statement->execute([(int)$customer['id']]);
$orders = $statement->fetchAll();
require __DIR__ . '/includes/header.php';
?>
<div class="page-heading">
  <div><p class="eyebrow">Your SportZone</p><h1>My account</h1></div>
  <form method="post" action="<?= e(url('logout.php')) ?>"><?= csrf_field() ?><button class="btn btn-outline">Sign out</button></form>
</div>
<?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>
<div class="account-layout">
  <section class="account-panel">
    <h2>Your details</h2>
    <p class="form-intro">Your email is your sign-in address. Card details are never stored here; checkout is cash on delivery.</p>
    <form method="post" class="form-grid">
      <?= csrf_field() ?>
      <label>Full name<input name="full_name" value="<?= e($customer['full_name']) ?>" autocomplete="name" maxlength="120" required></label>
      <label>Email address<input type="email" value="<?= e($customer['email']) ?>" readonly></label>
      <label>Phone number<input type="tel" name="phone" value="<?= e($customer['phone']) ?>" autocomplete="tel" maxlength="30" required></label>
      <span class="form-section-title">Default delivery details</span>
      <label>Recipient name<input name="delivery_name" value="<?= e($customer['delivery_name'] ?? '') ?>" autocomplete="shipping name" maxlength="120"></label>
      <label>Delivery phone<input type="tel" name="delivery_phone" value="<?= e($customer['delivery_phone'] ?? '') ?>" autocomplete="shipping tel" maxlength="30"></label>
      <label class="form-wide">Address line 1<input name="address_line_1" value="<?= e($customer['address_line_1'] ?? '') ?>" autocomplete="shipping address-line1" maxlength="190"></label>
      <label class="form-wide">Address line 2 <small>Optional</small><input name="address_line_2" value="<?= e($customer['address_line_2'] ?? '') ?>" autocomplete="shipping address-line2" maxlength="190"></label>
      <label>City<input name="city" value="<?= e($customer['city'] ?? '') ?>" autocomplete="shipping address-level2" maxlength="100"></label>
      <label>Region / state <small>Optional</small><input name="region" value="<?= e($customer['region'] ?? '') ?>" autocomplete="shipping address-level1" maxlength="100"></label>
      <label>Postal code <small>Optional</small><input name="postal_code" value="<?= e($customer['postal_code'] ?? '') ?>" autocomplete="shipping postal-code" maxlength="30"></label>
      <label>Country<input name="country" value="<?= e($customer['country'] ?? '') ?>" autocomplete="shipping country-name" maxlength="100"></label>
      <div class="form-wide"><button class="btn">Save details</button></div>
    </form>
  </section>
  <aside class="account-side">
    <div class="account-panel payment-note">
      <span class="payment-note-icon" aria-hidden="true">COD</span>
      <h2>Payment method</h2>
      <p>Cash on delivery is available at checkout. No card number or security code is collected or stored.</p>
    </div>
    <div class="account-panel">
      <div class="section-heading account-orders-heading"><div><p class="eyebrow">Keep track</p><h2>Your orders</h2></div></div>
      <?php if (!$orders): ?>
        <p class="empty">Your orders will appear here after checkout.</p>
      <?php else: ?>
        <div class="order-list">
          <?php foreach ($orders as $order): ?>
          <a class="order-row" href="<?= e(url('order.php?id=' . (int)$order['id'])) ?>">
            <span><strong><?= e($order['order_number']) ?></strong><small><?= e(date('M j, Y', strtotime($order['created_at']))) ?></small></span>
            <span><strong><?= money($order['total']) ?></strong><small><?= e(ucfirst($order['status'])) ?> · <?= e($order['payment_status'] === 'paid' ? 'Paid' : 'COD') ?></small></span>
          </a>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
  </aside>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
