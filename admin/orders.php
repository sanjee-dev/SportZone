<?php
require_once __DIR__ . '/../config/bootstrap.php';
require_admin();
if (!can_manage_orders()) {
    http_response_code(403);
    exit('Your role does not have permission to manage orders.');
}

$pdo = db();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_valid()) {
        flash('error', 'Your session expired. Please try again.');
        redirect('admin/orders.php');
    }
    $orderId = (int)($_POST['order_id'] ?? 0);
    $status = $_POST['status'] ?? '';
    $paymentStatus = $_POST['payment_status'] ?? '';
    $validStatuses = ['pending', 'confirmed', 'shipped', 'delivered'];
    $validPaymentStatuses = ['unpaid', 'paid'];
    if ($orderId < 1 || !in_array($status, $validStatuses, true)
        || !in_array($paymentStatus, $validPaymentStatuses, true)) {
        flash('error', 'Choose a valid order and status.');
    } else {
        $statement = $pdo->prepare(
            'UPDATE customer_orders SET status = ?, payment_status = ? WHERE id = ?'
        );
        $statement->execute([$status, $paymentStatus, $orderId]);
        flash('success', 'Order updated.');
    }
    redirect('admin/orders.php');
}

$pageTitle = 'Orders';
$orders = $pdo->query(
    'SELECT o.*,
        (SELECT GROUP_CONCAT(CONCAT(i.product_name, " × ", i.quantity) ORDER BY i.id SEPARATOR ", ")
         FROM customer_order_items i WHERE i.order_id = o.id) AS item_summary
     FROM customer_orders o ORDER BY o.id DESC LIMIT 100'
)->fetchAll();
require __DIR__ . '/includes/header.php';
?>
<div class="card-box">
  <h2>Recent customer orders</h2>
  <p class="form-intro">Update delivery progress and mark cash-on-delivery orders as paid after collection.</p>
  <?php if (!$orders): ?>
    <p class="empty">No customer orders yet.</p>
  <?php else: ?>
  <div class="table-wrap">
    <table>
      <thead><tr><th>Order</th><th>Customer / delivery</th><th>Items</th><th>Total</th><th>Status & payment</th><th>Update</th></tr></thead>
      <tbody>
      <?php foreach ($orders as $order): ?>
        <tr>
          <td><strong><?= e($order['order_number']) ?></strong><br><small><?= e(date('M j, Y', strtotime($order['created_at']))) ?></small></td>
          <td><?= e($order['customer_name']) ?><br><small><?= e($order['customer_email']) ?><br><?= e($order['delivery_phone']) ?><br><?= e($order['city']) ?>, <?= e($order['country']) ?></small></td>
          <td><small><?= e($order['item_summary'] ?? '') ?></small></td>
          <td><strong><?= money($order['total']) ?></strong><br><small>Cash on delivery</small></td>
          <td>
            <form method="post" id="order-<?= (int)$order['id'] ?>">
              <?= csrf_field() ?>
              <input type="hidden" name="order_id" value="<?= (int)$order['id'] ?>">
              <label class="order-select-label">Delivery
                <select name="status">
                  <?php foreach (['pending', 'confirmed', 'shipped', 'delivered'] as $status): ?>
                  <option value="<?= e($status) ?>" <?= $order['status'] === $status ? 'selected' : '' ?>><?= e(ucfirst($status)) ?></option>
                  <?php endforeach; ?>
                </select>
              </label>
              <label class="order-select-label">Payment
                <select name="payment_status">
                  <option value="unpaid" <?= $order['payment_status'] === 'unpaid' ? 'selected' : '' ?>>Not collected</option>
                  <option value="paid" <?= $order['payment_status'] === 'paid' ? 'selected' : '' ?>>Collected</option>
                </select>
              </label>
            </form>
          </td>
          <td><button class="btn" type="submit" form="order-<?= (int)$order['id'] ?>">Save</button></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
