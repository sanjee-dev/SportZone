<?php
function e($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function url(string $path): string
{
    return BASE_URL . '/' . ltrim($path, '/');
}

function money($amount): string
{
    return CURRENCY . number_format((float)$amount, 2);
}

function redirect(string $path): void
{
    header('Location: ' . url($path));
    exit;
}

function slugify(string $text): string
{
    $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9]+/', '-', $text), '-'));
    return $slug !== '' ? $slug : 'item';
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">';
}

function csrf_valid(): bool
{
    return isset($_POST['csrf'], $_SESSION['csrf']) && hash_equals($_SESSION['csrf'], (string)$_POST['csrf']);
}

function flash(string $type, string $message): void
{
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function flash_html(): string
{
    if (empty($_SESSION['flash'])) {
        return '';
    }
    $f = $_SESSION['flash'];
    unset($_SESSION['flash']);
    return '<div class="alert alert-' . e($f['type']) . '">' . e($f['message']) . '</div>';
}

function image_url(?string $file): string
{
    return url($file ? 'assets/images/products/' . basename($file) : 'assets/images/placeholder.svg');
}

function require_admin(): array
{
    if (empty($_SESSION['admin'])) {
        redirect('admin/login.php');
    }
    return $_SESSION['admin'];
}

function can_manage_products(): bool
{
    return in_array($_SESSION['admin']['role'] ?? '', ['super_admin', 'product_manager'], true);
}

function current_customer(): ?array
{
    return isset($_SESSION['customer']) && is_array($_SESSION['customer'])
        ? $_SESSION['customer']
        : null;
}

function require_customer(string $next = 'account.php'): array
{
    $customer = current_customer();
    if (!$customer) {
        $next = $next === 'checkout.php' ? 'checkout.php' : 'account.php';
        redirect('login.php?next=' . rawurlencode($next));
    }
    return $customer;
}

function customer_next_path(): string
{
    return ($_POST['next'] ?? $_GET['next'] ?? '') === 'checkout.php'
        ? 'checkout.php'
        : 'account.php';
}

function load_customer_cart(): void
{
    $customer = current_customer();
    if (!$customer || (int)($_SESSION['cart_owner_loaded'] ?? 0) === (int)$customer['id']) {
        return;
    }

    $_SESSION['cart'] = $_SESSION['cart'] ?? [];
    $statement = db()->prepare('SELECT product_id, quantity FROM customer_cart_items WHERE customer_id = ?');
    $statement->execute([(int)$customer['id']]);
    $saved = [];
    foreach ($statement->fetchAll() as $row) {
        $saved[(int)$row['product_id']] = (int)$row['quantity'];
    }
    $quantities = $_SESSION['cart'];
    foreach ($saved as $productId => $quantity) {
        $quantities[$productId] = (int)($quantities[$productId] ?? 0) + $quantity;
    }

    $ids = array_values(array_filter(array_map('intval', array_keys($quantities)), static fn($id) => $id > 0));
    $cart = [];
    if ($ids) {
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stockStatement = db()->prepare("SELECT id, stock FROM products WHERE is_active = 1 AND id IN ($placeholders)");
        $stockStatement->execute($ids);
        foreach ($stockStatement->fetchAll() as $product) {
            $id = (int)$product['id'];
            $quantity = min((int)($quantities[$id] ?? 0), (int)$product['stock']);
            if ($quantity > 0) {
                $cart[$id] = $quantity;
            }
        }
    }

    $_SESSION['cart'] = $cart;
    $_SESSION['cart_owner_loaded'] = (int)$customer['id'];
    sync_customer_cart();
}

function sync_customer_cart(): void
{
    $customer = current_customer();
    if (!$customer) {
        return;
    }

    $customerId = (int)$customer['id'];
    $cart = $_SESSION['cart'] ?? [];
    if (!$cart) {
        db()->prepare('DELETE FROM customer_cart_items WHERE customer_id = ?')->execute([$customerId]);
        return;
    }

    $upsert = db()->prepare(
        'INSERT INTO customer_cart_items (customer_id, product_id, quantity) VALUES (?, ?, ?)
         ON DUPLICATE KEY UPDATE quantity = VALUES(quantity), updated_at = CURRENT_TIMESTAMP'
    );
    $ids = [];
    foreach ($cart as $productId => $quantity) {
        $productId = (int)$productId;
        $quantity = (int)$quantity;
        if ($productId > 0 && $quantity > 0) {
            $upsert->execute([$customerId, $productId, $quantity]);
            $ids[] = $productId;
        }
    }

    if (!$ids) {
        db()->prepare('DELETE FROM customer_cart_items WHERE customer_id = ?')->execute([$customerId]);
        return;
    }

    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    db()->prepare("DELETE FROM customer_cart_items WHERE customer_id = ? AND product_id NOT IN ($placeholders)")
        ->execute(array_merge([$customerId], $ids));
}

function can_manage_orders(): bool
{
    return in_array($_SESSION['admin']['role'] ?? '', ['super_admin', 'order_manager'], true);
}

/** Validates and stores an uploaded product image. Returns the new filename, or null if no file was sent. */
function save_product_image(?array $file): ?string
{
    if (!$file || $file['error'] === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Image upload failed.');
    }
    if ($file['size'] > UPLOAD_MAX_BYTES) {
        throw new RuntimeException('Image must be 3 MB or smaller.');
    }
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
    $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    if (!isset($allowed[$mime]) || !getimagesize($file['tmp_name'])) {
        throw new RuntimeException('Only real JPEG, PNG or WebP images are allowed.');
    }
    $dir = __DIR__ . '/../assets/images/products/';
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    $name = bin2hex(random_bytes(16)) . '.' . $allowed[$mime];
    if (!move_uploaded_file($file['tmp_name'], $dir . $name)) {
        throw new RuntimeException('Could not save image. Check folder permissions.');
    }
    return $name;
}

function delete_product_image(?string $file): void
{
    if ($file) {
        @unlink(__DIR__ . '/../assets/images/products/' . basename($file));
    }
}

function product_card(array $p): string
{
    $hasDiscount = $p['discount_price'] !== null;
    $price = $hasDiscount
        ? '<s>' . money($p['price']) . '</s> <strong>' . money($p['discount_price']) . '</strong>'
        : '<strong>' . money($p['price']) . '</strong>';
    $badge = $p['stock'] < 1 ? '<span class="badge out">Out of stock</span>' : '';
    return '<a class="card" href="' . e(url('product.php?slug=' . urlencode($p['slug']))) . '">'
        . '<div class="card-img"><img src="' . e(image_url($p['image'])) . '" alt="' . e($p['name']) . '">' . $badge . '</div>'
        . '<div class="card-body"><small>' . e($p['brand']) . '</small><h3>' . e($p['name']) . '</h3>'
        . '<p class="price">' . $price . '</p></div></a>';
}
