<?php
require_once __DIR__ . '/config/bootstrap.php';
if (current_customer()) {
    redirect(customer_next_path());
}

$pageTitle = 'Customer login';
$error = '';
$email = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = strtolower(trim($_POST['email'] ?? ''));
    if (!csrf_valid()) {
        $error = 'Your session expired. Please try again.';
    } else {
        $statement = db()->prepare('SELECT id, full_name, email, password_hash FROM customers WHERE email = ? LIMIT 1');
        $statement->execute([$email]);
        $customer = $statement->fetch();
        if ($customer && password_verify((string)($_POST['password'] ?? ''), $customer['password_hash'])) {
            session_regenerate_id(true);
            unset($_SESSION['csrf'], $_SESSION['cart_owner_loaded']);
            $_SESSION['customer'] = [
                'id' => (int)$customer['id'],
                'full_name' => $customer['full_name'],
                'email' => $customer['email'],
            ];
            load_customer_cart();
            redirect(customer_next_path());
        }
        usleep(500000);
        $error = 'Email or password is incorrect.';
    }
}
require __DIR__ . '/includes/header.php';
?>
<section class="auth-wrap">
  <div class="auth-intro">
    <p class="eyebrow">Welcome back</p>
    <h1>Your game.<br>Your SportZone.</h1>
    <p>Sign in to pick up your cart, manage delivery details, and check your orders.</p>
  </div>
  <div class="auth-panel">
    <h2>Sign in</h2>
    <p class="form-intro">Use the email and password for your customer account.</p>
    <?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>
    <form method="post" class="stack-form">
      <?= csrf_field() ?>
      <input type="hidden" name="next" value="<?= e(customer_next_path()) ?>">
      <label>Email address<input type="email" name="email" value="<?= e($email) ?>" autocomplete="email" maxlength="190" required></label>
      <label>Password<input type="password" name="password" autocomplete="current-password" required></label>
      <button class="btn">Sign in <span aria-hidden="true">→</span></button>
    </form>
    <p class="auth-switch">New to SportZone? <a href="<?= e(url('register.php' . (customer_next_path() === 'checkout.php' ? '?next=checkout.php' : ''))) ?>">Create an account</a></p>
  </div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
