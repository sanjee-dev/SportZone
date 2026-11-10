<?php
require_once __DIR__ . '/../../config/bootstrap.php';
$admin = require_admin();
$current = basename($_SERVER['SCRIPT_NAME']);
$nav = ['index.php' => 'Dashboard', 'products.php' => 'Products'];
if (can_manage_orders()) {
    $nav['orders.php'] = 'Orders';
}
$pageTitle = $pageTitle ?? 'Admin';
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($pageTitle) ?> | SportZone Admin</title>
<link rel="stylesheet" href="<?= e(url('assets/css/style.css')) ?>">
</head>
<body class="admin">
<div class="admin-wrap">
<aside class="sidebar">
  <div class="logo">Sport<span>Zone</span></div>
  <?php foreach ($nav as $file => $label): ?>
    <a class="<?= $current === $file ? 'active' : '' ?>" href="<?= e(url('admin/' . $file)) ?>"><?= e($label) ?></a>
  <?php endforeach; ?>
  <form method="post" action="<?= e(url('admin/logout.php')) ?>"><?= csrf_field() ?><button class="link-btn">Logout</button></form>
</aside>
<section class="admin-main">
  <div class="topbar">
    <h1><?= e($pageTitle) ?></h1>
    <span>Signed in as <?= e($admin['username']) ?> (<?= e(str_replace('_', ' ', $admin['role'])) ?>)</span>
  </div>
  <?= flash_html() ?>
