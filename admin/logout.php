<?php
require_once __DIR__ . '/../config/bootstrap.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_valid()) {
    $_SESSION = [];
    setcookie(session_name(), '', time() - 3600, '/');
    session_destroy();
}
redirect('admin/login.php');
