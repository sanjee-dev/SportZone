<?php
require_once __DIR__ . '/config/bootstrap.php';
if (current_customer()) {
    redirect(customer_next_path());
}

$pageTitle = 'Create an account';
$error = '';
$form = ['full_name' => '', 'email' => '', 'phone' => ''];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $form = [
        'full_name' => trim($_POST['full_name'] ?? ''),
        'email' => strtolower(trim($_POST['email'] ?? '')),
        'phone' => trim($_POST['phone'] ?? ''),
    ];
    $password = (string)($_POST['password'] ?? '');
    if (!csrf_valid()) {
        $error = 'Your session expired. Please try again.';
    } elseif (mb_strlen($form['full_name']) < 2 || mb_strlen($form['full_name']) > 120) {
        $error = 'Enter your name (2–120 characters).';
    } elseif (!filter_var($form['email'], FILTER_VALIDATE_EMAIL) || strlen($form['email']) > 190) {
        $error = 'Enter a valid email address.';
    } elseif (mb_strlen($form['phone']) < 5 || mb_strlen($form['phone']) > 30) {
        $error = 'Enter a valid phone number.';
    } elseif (strlen($password) < 12) {
        $error = 'Choose a password with at least 12 characters.';
    } elseif ($password !== (string)($_POST['password_confirm'] ?? '')) {
        $error = 'The password confirmation does not match.';
    } else {
        try {
            $statement = db()->prepare('INSERT INTO customers (full_name, email, phone, password_hash) VALUES (?, ?, ?, ?)');
            $statement->execute([$form['full_name'], $form['email'], $form['phone'], password_hash($password, PASSWORD_DEFAULT)]);
            session_regenerate_id(true);
            unset($_SESSION['csrf'], $_SESSION['cart_owner_loaded']);
            $_SESSION['customer'] = [
                'id' => (int)db()->lastInsertId(),
                'full_name' => $form['full_name'],
                'email' => $form['email'],
            ];
            load_customer_cart();
            redirect(customer_next_path());
        } catch (PDOException $exception) {
            if ($exception->getCode() === '23000') {
                $error = 'An account with that email already exists. Sign in instead.';
            } else {
                error_log($exception->getMessage());
                $error = 'We could not create your account right now. Please try again.';
            }
        }
    }
}
require __DIR__ . '/includes/header.php';
?>
<section class="auth-wrap">
  <div class="auth-intro">
    <p class="eyebrow">Join SportZone</p>
    <h1>Make room<br>for more play.</h1>
    <p>Create an account to keep your cart, save delivery details, and follow your orders.</p>
  </div>
  <div class="auth-panel">
    <h2>Create your account</h2>
    <p class="form-intro">Just the essentials to get you started.</p>
    <?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>
    <form method="post" class="stack-form">
      <?= csrf_field() ?>
      <input type="hidden" name="next" value="<?= e(customer_next_path()) ?>">
      <label>Full name<input name="full_name" value="<?= e($form['full_name']) ?>" autocomplete="name" maxlength="120" required></label>
      <label>Email address<input type="email" name="email" value="<?= e($form['email']) ?>" autocomplete="email" maxlength="190" required></label>
      <label>Phone number<input type="tel" name="phone" value="<?= e($form['phone']) ?>" autocomplete="tel" maxlength="30" required></label>
      <label>Password <small>At least 12 characters</small><input type="password" name="password" autocomplete="new-password" minlength="12" required></label>
      <label>Confirm password<input type="password" name="password_confirm" autocomplete="new-password" minlength="12" required></label>
      <button class="btn">Create account <span aria-hidden="true">→</span></button>
    </form>
    <p class="auth-switch">Already have an account? <a href="<?= e(url('login.php' . (customer_next_path() === 'checkout.php' ? '?next=checkout.php' : ''))) ?>">Sign in</a></p>
  </div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
