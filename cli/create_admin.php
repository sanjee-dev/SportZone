<?php
// Usage: php cli/create_admin.php "admin" "admin@example.com"
// You are prompted for the password. Creates the first Super Admin only.
// Later staff are created from the admin dashboard.
if (PHP_SAPI !== 'cli') {
    exit("Run this script from the command line only.\n");
}
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';

$username = $argv[1] ?? '';
$email = $argv[2] ?? '';
if (!preg_match('/^[A-Za-z0-9_]{3,50}$/', $username)) {
    exit("Username must be 3–50 letters, numbers or underscores.\n");
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    exit("Enter a valid email address.\n");
}

$pdo = db();
if ((int)$pdo->query("SELECT COUNT(*) FROM admin_users WHERE role = 'super_admin'")->fetchColumn() > 0) {
    exit("A Super Admin already exists. Create further staff from the admin dashboard.\n");
}

echo 'Password (min 12 characters): ';
$tty = stream_isatty(STDIN);
if ($tty) { shell_exec('stty -echo'); }
$password = trim((string)fgets(STDIN));
if ($tty) { shell_exec('stty echo'); echo "\n"; }

if (strlen($password) < 12) {
    exit("Password must be at least 12 characters.\n");
}
try {
    $pdo->prepare("INSERT INTO admin_users (username, email, password_hash, role, is_active) VALUES (?, ?, ?, 'super_admin', 1)")
        ->execute([$username, $email, password_hash($password, PASSWORD_DEFAULT)]);
} catch (PDOException $ex) {
    exit($ex->getCode() === '23000' ? "That username or email already exists.\n" : "Database error. Check config/database.php.\n");
}
echo "Super Admin '$username' created. Log in at /sportzone/admin/login.php\n";
