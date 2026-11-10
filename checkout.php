<?php
require_once __DIR__ . '/config/bootstrap.php';
$customerSession = require_customer('checkout.php');
load_customer_cart();
$pageTitle = 'Checkout';
$pdo = db();
$customerStatement = $pdo->prepare('SELECT * FROM customers WHERE id = ?');
$customerStatement->execute([(int)$customerSession['id']]);
$customer = $customerStatement->fetch();
if (!$customer) {
    unset($_SESSION['customer'], $_SESSION['cart_owner_loaded']);
    redirect('login.php');
}

$deliveryDetails = [
    'delivery_name' => $customer['delivery_name'] ?: $customer['full_name'],
    'delivery_phone' => $customer['delivery_phone'] ?: $customer['phone'],
    'address_line_1' => $customer['address_line_1'] ?? '',
    'address_line_2' => $customer['address_line_2'] ?? '',
    'city' => $customer['city'] ?? '',
    'region' => $customer['region'] ?? '',
    'postal_code' => $customer['postal_code'] ?? '',
    'country' => $customer['country'] ?? '',
];
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($deliveryDetails as $field => $value) {
        $deliveryDetails[$field] = trim($_POST[$field] ?? '');
    }
    if (!csrf_valid()) {
        $error = 'Your session expired. Please try again.';
    } elseif (mb_strlen($deliveryDetails['delivery_name']) < 2 || mb_strlen($deliveryDetails['delivery_name']) > 120) {
        $error = 'Enter a valid recipient name.';
    } elseif (mb_strlen($deliveryDetails['delivery_phone']) < 5 || mb_strlen($deliveryDetails['delivery_phone']) > 30) {
        $error = 'Enter a valid delivery phone number.';
    } elseif ($deliveryDetails['address_line_1'] === '' || strlen($deliveryDetails['address_line_1']) > 190) {
        $error = 'Enter a delivery address.';
    } elseif ($deliveryDetails['city'] === '' || strlen($deliveryDetails['city']) > 100) {
        $error = 'Enter a city.';
    } elseif ($deliveryDetails['country'] === '' || strlen($deliveryDetails['country']) > 100) {
        $error = 'Enter a country.';
    } elseif (strlen($deliveryDetails['address_line_2']) > 190 || strlen($deliveryDetails['region']) > 100
        || strlen($deliveryDetails['postal_code']) > 30) {
        $error = 'Some delivery details are too long. Please check the form.';
    } else {
        try {
            $pdo->beginTransaction();
            $cartStatement = $pdo->prepare(
                'SELECT ci.product_id, ci.quantity, p.name, p.sku, p.price, p.discount_price, p.stock, p.is_active
                 FROM customer_cart_items ci
                 INNER JOIN products p ON p.id = ci.product_id
                 WHERE ci.customer_id = ? FOR UPDATE'
            );
            $cartStatement->execute([(int)$customer['id']]);
            $orderItems = $cartStatement->fetchAll();
            $valid = count($orderItems) === count($_SESSION['cart'] ?? []) && $orderItems;
            $subtotal = 0.0;
            foreach ($orderItems as &$item) {
                if (!(int)$item['is_active'] || (int)$item['quantity'] < 1 || (int)$item['quantity'] > (int)$item['stock']) {
                    $valid = false;
                    break;
                }
                $item['unit_price'] = (float)($item['discount_price'] ?? $item['price']);
                $item['line_total'] = $item['unit_price'] * (int)$item['quantity'];
                $subtotal += $item['line_total'];
            }
            unset($item);

            if (!$valid) {
                $pdo->rollBack();
                load_customer_cart();
                flash('error', 'Your cart changed because an item is unavailable or stock changed. Please review it and try again.');
                redirect('cart.php');
            }

            $deliveryFee = $subtotal > 0 && $subtotal < FREE_DELIVERY_OVER ? DELIVERY_FEE : 0.0;
            $total = $subtotal + $deliveryFee;
            $orderNumber = 'SZ' . date('ymd') . strtoupper(bin2hex(random_bytes(3)));
            $insertOrder = $pdo->prepare(
                'INSERT INTO customer_orders
                 (order_number, customer_id, customer_name, customer_email, customer_phone,
                  delivery_name, delivery_phone, address_line_1, address_line_2, city, region, postal_code, country,
                  subtotal, delivery_fee, total, payment_method)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, "cash_on_delivery")'
            );
            $insertOrder->execute([
                $orderNumber, (int)$customer['id'], $customer['full_name'], $customer['email'], $customer['phone'],
                $deliveryDetails['delivery_name'], $deliveryDetails['delivery_phone'], $deliveryDetails['address_line_1'],
                $deliveryDetails['address_line_2'] ?: null, $deliveryDetails['city'], $deliveryDetails['region'] ?: null,
                $deliveryDetails['postal_code'] ?: null, $deliveryDetails['country'], $subtotal, $deliveryFee, $total,
            ]);
            $orderId = (int)$pdo->lastInsertId();
            $insertItem = $pdo->prepare(
                'INSERT INTO customer_order_items (order_id, product_id, product_name, sku, unit_price, quantity, line_total)
                 VALUES (?, ?, ?, ?, ?, ?, ?)'
            );
            $decreaseStock = $pdo->prepare('UPDATE products SET stock = stock - ? WHERE id = ? AND stock >= ?');
            foreach ($orderItems as $item) {
                $quantity = (int)$item['quantity'];
                $insertItem->execute([
                    $orderId, (int)$item['product_id'], $item['name'], $item['sku'],
                    $item['unit_price'], $quantity, $item['line_total'],
                ]);
                $decreaseStock->execute([$quantity, (int)$item['product_id'], $quantity]);
                if ($decreaseStock->rowCount() !== 1) {
                    throw new RuntimeException('An item just sold out. Please review your cart and try again.');
                }
            }
            $saveDelivery = $pdo->prepare(
                'UPDATE customers SET delivery_name = ?, delivery_phone = ?, address_line_1 = ?, address_line_2 = ?,
                 city = ?, region = ?, postal_code = ?, country = ? WHERE id = ?'
            );
            $saveDelivery->execute([
                $deliveryDetails['delivery_name'], $deliveryDetails['delivery_phone'], $deliveryDetails['address_line_1'],
                $deliveryDetails['address_line_2'] ?: null, $deliveryDetails['city'], $deliveryDetails['region'] ?: null,
                $deliveryDetails['postal_code'] ?: null, $deliveryDetails['country'], (int)$customer['id'],
            ]);
            $pdo->prepare('DELETE FROM customer_cart_items WHERE customer_id = ?')->execute([(int)$customer['id']]);
            $pdo->commit();
            $_SESSION['cart'] = [];
            redirect('order.php?id=' . $orderId);
        } catch (RuntimeException $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $error = $exception->getMessage();
        } catch (PDOException $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log($exception->getMessage());
            $error = 'We could not place your order. Please try again.';
        }
    }
}

$items = [];
$subtotal = 0.0;
$ids = array_keys($_SESSION['cart'] ?? []);
if ($ids) {
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $statement = $pdo->prepare(
        "SELECT id, name, sku, price, discount_price, stock, image FROM products
         WHERE is_active = 1 AND id IN ($placeholders)"
    );
    $statement->execute($ids);
    $products = [];
    foreach ($statement->fetchAll() as $product) {
        $products[(int)$product['id']] = $product;
    }
    foreach ($_SESSION['cart'] as $productId => $quantity) {
        $productId = (int)$productId;
        if (!isset($products[$productId])) {
            continue;
        }
        $product = $products[$productId];
        $product['quantity'] = min((int)$quantity, (int)$product['stock']);
        if ($product['quantity'] < 1) {
            continue;
        }
        $product['unit_price'] = (float)($product['discount_price'] ?? $product['price']);
        $product['line_total'] = $product['quantity'] * $product['unit_price'];
        $subtotal += $product['line_total'];
        $items[] = $product;
    }
}
if (!$items) {
    flash('error', 'Add an available item to your cart before checkout.');
    redirect('cart.php');
}
$deliveryFee = $subtotal < FREE_DELIVERY_OVER ? DELIVERY_FEE : 0.0;
$total = $subtotal + $deliveryFee;
require __DIR__ . '/includes/header.php';
?>
<div class="page-heading"><div><p class="eyebrow">Almost game time</p><h1>Checkout</h1></div><a class="text-link" href="<?= e(url('cart.php')) ?>">← Back to cart</a></div>
<?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>
<form method="post" class="checkout-layout">
  <section class="account-panel">
    <h2>Delivery details</h2>
    <p class="form-intro">We’ll save these as your default delivery details for next time.</p>
    <?= csrf_field() ?>
    <div class="form-grid">
      <label>Recipient name<input name="delivery_name" value="<?= e($deliveryDetails['delivery_name']) ?>" autocomplete="shipping name" maxlength="120" required></label>
      <label>Delivery phone<input type="tel" name="delivery_phone" value="<?= e($deliveryDetails['delivery_phone']) ?>" autocomplete="shipping tel" maxlength="30" required></label>
      <label class="form-wide">Address line 1<input name="address_line_1" value="<?= e($deliveryDetails['address_line_1']) ?>" autocomplete="shipping address-line1" maxlength="190" required></label>
      <label class="form-wide">Address line 2 <small>Optional</small><input name="address_line_2" value="<?= e($deliveryDetails['address_line_2']) ?>" autocomplete="shipping address-line2" maxlength="190"></label>
      <label>City<input name="city" value="<?= e($deliveryDetails['city']) ?>" autocomplete="shipping address-level2" maxlength="100" required></label>
      <label>Region / state <small>Optional</small><input name="region" value="<?= e($deliveryDetails['region']) ?>" autocomplete="shipping address-level1" maxlength="100"></label>
      <label>Postal code <small>Optional</small><input name="postal_code" value="<?= e($deliveryDetails['postal_code']) ?>" autocomplete="shipping postal-code" maxlength="30"></label>
      <label>Country<input name="country" value="<?= e($deliveryDetails['country']) ?>" autocomplete="shipping country-name" maxlength="100" required></label>
    </div>
  </section>
  <aside class="account-panel checkout-summary">
    <h2>Order summary</h2>
    <div class="checkout-lines">
      <?php foreach ($items as $item): ?>
      <div><span><?= e($item['name']) ?> × <?= (int)$item['quantity'] ?></span><strong><?= money($item['line_total']) ?></strong></div>
      <?php endforeach; ?>
    </div>
    <div class="summary-row"><span>Subtotal</span><strong><?= money($subtotal) ?></strong></div>
    <div class="summary-row"><span>Delivery</span><strong><?= $deliveryFee > 0 ? money($deliveryFee) : 'Free' ?></strong></div>
    <div class="summary-row summary-total"><span>Total</span><strong><?= money($total) ?></strong></div>
    <div class="cod-choice"><span class="cod-dot" aria-hidden="true"></span><span><strong>Cash on delivery</strong><small>Pay when your order arrives. No card details needed.</small></span></div>
    <button class="btn checkout-submit">Place order <span aria-hidden="true">→</span></button>
    <p class="secure-note">Your delivery details are protected in your account. We never collect or store card numbers or security codes.</p>
  </aside>
</form>
<?php require __DIR__ . '/includes/footer.php'; ?>
