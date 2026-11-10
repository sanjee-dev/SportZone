<?php
require_once __DIR__ . '/app.php';
require_once __DIR__ . '/database.php';

ini_set('display_errors', '0');
error_reporting(E_ALL);

if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.use_strict_mode', '1');
    session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax', 'path' => '/']);
    session_start();
}
require_once __DIR__ . '/../includes/functions.php';
