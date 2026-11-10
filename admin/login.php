<?php
require_once __DIR__ . '/../config/bootstrap.php';
if (!empty($_SESSION['admin'])) {
    redirect('admin/index.php');
}
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_valid()) {
        $error = 'Your session expired. Please try again.';
    } else {
        $st = db()->prepare('SELECT id, username, role, password_hash, is_active FROM admin_users WHERE username = ? LIMIT 1');
        $st->execute([trim($_POST['username'] ?? '')]);
        $admin = $st->fetch();
        if ($admin && $admin['is_active'] && password_verify($_POST['password'] ?? '', $admin['password_hash'])) {
            session_regenerate_id(true);
            $_SESSION['admin'] = ['id' => (int)$admin['id'], 'username' => $admin['username'], 'role' => $admin['role']];
            redirect('admin/index.php');
        }
        usleep(800000); // slows down brute-force attempts
        $error = 'Invalid username or password.';
    }
}
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Admin Login | SportZone</title>
<link rel="stylesheet" href="<?= e(url('assets/css/style.css')) ?>">
</head>
<body class="admin">
<div class="login-box">
  <div class="logo" style="color:var(--navy)">Sport<span>Zone</span> Admin</div>
  <?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>
  <form method="post">
    <?= csrf_field() ?>
    <label>Username<input name="username" required maxlength="50" autofocus></label>
    <label>Password<input type="password" name="password" required></label>
    <button class="btn">Log in</button>
  </form>
</div>
</body>
</html>
