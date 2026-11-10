<?php
require_once __DIR__ . '/config/bootstrap.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrf_valid()) {
    flash('error', 'Your session expired. Please try again.');
    redirect('account.php');
}

$_SESSION['cart'] = [];
unset($_SESSION['customer'], $_SESSION['cart_owner_loaded'], $_SESSION['csrf']);
session_regenerate_id(true);
flash('success', 'You have been signed out.');
redirect('index.php');
