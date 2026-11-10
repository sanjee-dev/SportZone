<?php
load_customer_cart();
$cartCount = array_sum($_SESSION['cart'] ?? []);
$pageTitle = $pageTitle ?? 'Home';
$customer = current_customer();
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($pageTitle) ?> | SportZone</title>
<link rel="stylesheet" href="<?= e(url('assets/css/style.css')) ?>">
</head>
<body>
<header class="site-header"><div class="container bar">
  <a class="logo" href="<?= e(url('index.php')) ?>">Sport<span>Zone</span></a>
  <form class="search" action="<?= e(url('shop.php')) ?>" method="get">
    <input name="q" placeholder="Search football, bats, shoes…" value="<?= e($_GET['q'] ?? '') ?>">
    <button>Search</button>
  </form>
  <nav>
    <a href="<?= e(url('index.php#about')) ?>">About</a>
    <a href="<?= e(url('shop.php')) ?>">Shop</a>
    <a href="<?= e(url('cart.php')) ?>">Cart (<?= (int)$cartCount ?>)</a>
    <a href="<?= e(url($customer ? 'account.php' : 'login.php')) ?>"><?= $customer ? 'My account' : 'Sign in' ?></a>
    <a href="<?= e(url('admin/login.php')) ?>">Admin</a>
  </nav>
</div></header>
<main class="container">
<?= flash_html() ?>
