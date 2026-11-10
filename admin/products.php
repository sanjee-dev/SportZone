<?php
require_once __DIR__ . '/../config/bootstrap.php';
require_admin();
if (!can_manage_products()) {
    http_response_code(403);
    exit('Your role does not have permission to manage products.');
}
$pdo = db();
$pageTitle = 'Products';
$errors = [];
$form = ['id' => 0, 'name' => '', 'sku' => '', 'brand' => '', 'description' => '', 'category_id' => '',
         'price' => '', 'discount_price' => '', 'stock' => 0, 'is_featured' => 0, 'is_active' => 1, 'image' => null];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_valid()) {
        flash('error', 'Your session expired. Please try again.');
        redirect('admin/products.php');
    }
    $action = $_POST['action'] ?? '';

    if ($action === 'toggle') {
        $pdo->prepare('UPDATE products SET is_active = 1 - is_active WHERE id = ?')->execute([(int)($_POST['id'] ?? 0)]);
        flash('success', 'Product status updated.');
        redirect('admin/products.php');
    }

    if ($action === 'save') {
        $id = (int)($_POST['id'] ?? 0);
        $form = [
            'id' => $id,
            'name' => trim($_POST['name'] ?? ''),
            'sku' => strtoupper(trim($_POST['sku'] ?? '')),
            'brand' => trim($_POST['brand'] ?? ''),
            'description' => trim($_POST['description'] ?? ''),
            'category_id' => (int)($_POST['category_id'] ?? 0) ?: null,
            'price' => trim($_POST['price'] ?? ''),
            'discount_price' => trim($_POST['discount_price'] ?? ''),
            'stock' => trim($_POST['stock'] ?? ''),
            'is_featured' => isset($_POST['is_featured']) ? 1 : 0,
            'is_active' => isset($_POST['is_active']) ? 1 : 0,
            'image' => null,
        ];
        $price = filter_var($form['price'], FILTER_VALIDATE_FLOAT);
        $stock = filter_var($form['stock'], FILTER_VALIDATE_INT);
        $discount = $form['discount_price'] === '' ? null : filter_var($form['discount_price'], FILTER_VALIDATE_FLOAT);

        if ($form['name'] === '' || mb_strlen($form['name']) > 150) {
            $errors[] = 'Product name is required (max 150 characters).';
        }
        if (!preg_match('/^[A-Z0-9-]{3,50}$/', $form['sku'])) {
            $errors[] = 'SKU must be 3–50 characters: letters, numbers or hyphens.';
        }
        if ($form['category_id'] === null) {
            $errors[] = 'Choose a category.';
        }
        if ($price === false || $price < 0) {
            $errors[] = 'Enter a valid regular price.';
        }
        if ($discount !== null && ($discount === false || $discount < 0 || ($price !== false && $discount >= $price))) {
            $errors[] = 'Discount price must be lower than the regular price.';
        }
        if ($stock === false || $stock < 0) {
            $errors[] = 'Stock must be a whole number, zero or more.';
        }

        $newImage = null;
        if (!$errors) {
            try {
                $newImage = save_product_image($_FILES['image'] ?? null);
            } catch (RuntimeException $ex) {
                $errors[] = $ex->getMessage();
            }
        }

        if (!$errors) {
            $data = [$form['category_id'], $form['name'], slugify($form['name'] . '-' . $form['sku']),
                     $form['sku'], $form['brand'], $form['description'], $price, $discount, $stock,
                     $form['is_featured'], $form['is_active']];
            try {
                if ($id) {
                    $old = $pdo->prepare('SELECT image FROM products WHERE id = ?');
                    $old->execute([$id]);
                    $oldImage = $old->fetchColumn();
                    $sql = 'UPDATE products SET category_id=?, name=?, slug=?, sku=?, brand=?, description=?, price=?, discount_price=?, stock=?, is_featured=?, is_active=?';
                    if ($newImage) {
                        $sql .= ', image=?';
                        $data[] = $newImage;
                    }
                    $data[] = $id;
                    $pdo->prepare($sql . ' WHERE id=?')->execute($data);
                    if ($newImage) {
                        delete_product_image($oldImage);
                    }
                    flash('success', 'Product updated. Changes are live on the storefront.');
                } else {
                    $data[] = $newImage;
                    $pdo->prepare('INSERT INTO products (category_id, name, slug, sku, brand, description, price, discount_price, stock, is_featured, is_active, image) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)')
                        ->execute($data);
                    flash('success', 'Product added.');
                }
                redirect('admin/products.php');
            } catch (PDOException $ex) {
                delete_product_image($newImage);
                if ($ex->getCode() === '23000') {
                    $errors[] = 'That SKU already exists, or the category is invalid.';
                } else {
                    throw $ex;
                }
            }
        }
    }
} elseif (isset($_GET['edit'])) {
    $st = $pdo->prepare('SELECT * FROM products WHERE id = ?');
    $st->execute([(int)$_GET['edit']]);
    if ($row = $st->fetch()) {
        $form = array_merge($form, $row, ['discount_price' => $row['discount_price'] ?? '', 'category_id' => $row['category_id'] ?? '']);
    }
}

$q = trim($_GET['q'] ?? '');
$catFilter = (int)($_GET['category'] ?? 0);
$where = ['1=1'];
$params = [];
if ($q !== '') {
    $where[] = '(p.name LIKE ? OR p.sku LIKE ? OR p.brand LIKE ?)';
    array_push($params, "%$q%", "%$q%", "%$q%");
}
if ($catFilter) {
    $where[] = 'p.category_id = ?';
    $params[] = $catFilter;
}
$list = $pdo->prepare('SELECT p.*, c.name AS category FROM products p LEFT JOIN categories c ON c.id = p.category_id
    WHERE ' . implode(' AND ', $where) . ' ORDER BY p.id DESC LIMIT 200');
$list->execute($params);
$products = $list->fetchAll();
$categories = $pdo->query('SELECT id, name FROM categories WHERE is_active = 1 ORDER BY name')->fetchAll();

require __DIR__ . '/includes/header.php';
?>
<div class="card-box">
  <h2><?= $form['id'] ? 'Edit product' : 'Add product' ?></h2>
  <?php foreach ($errors as $err): ?><div class="alert alert-error"><?= e($err) ?></div><?php endforeach; ?>
  <?php if ($form['image']): ?><p><img class="thumb" src="<?= e(image_url($form['image'])) ?>" alt=""> Current image (uploading a new one replaces it)</p><?php endif; ?>
  <form method="post" enctype="multipart/form-data" class="grid-form">
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="save">
    <input type="hidden" name="id" value="<?= (int)$form['id'] ?>">
    <label>Name<input name="name" required maxlength="150" value="<?= e($form['name']) ?>"></label>
    <label>SKU<input name="sku" required maxlength="50" value="<?= e($form['sku']) ?>"></label>
    <label>Brand<input name="brand" maxlength="80" value="<?= e($form['brand']) ?>"></label>
    <label>Category
      <select name="category_id" required>
        <option value="">Select…</option>
        <?php foreach ($categories as $c): ?>
          <option value="<?= (int)$c['id'] ?>" <?= (string)$form['category_id'] === (string)$c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option>
        <?php endforeach; ?>
      </select>
    </label>
    <label>Regular price (LKR)<input name="price" type="number" step="0.01" min="0" required value="<?= e($form['price']) ?>"></label>
    <label>Discount price (optional)<input name="discount_price" type="number" step="0.01" min="0" value="<?= e($form['discount_price']) ?>"></label>
    <label>Stock quantity<input name="stock" type="number" min="0" required value="<?= e($form['stock']) ?>"></label>
    <label>Image (JPEG, PNG or WebP, max 3 MB)<input type="file" name="image" accept="image/jpeg,image/png,image/webp"></label>
    <label class="full">Description<textarea name="description" rows="4"><?= e($form['description']) ?></textarea></label>
    <label class="check"><input type="checkbox" name="is_featured" <?= $form['is_featured'] ? 'checked' : '' ?>> Featured product</label>
    <label class="check"><input type="checkbox" name="is_active" <?= $form['is_active'] ? 'checked' : '' ?>> Active (visible on website)</label>
    <div class="full">
      <button class="btn">Save product</button>
      <?php if ($form['id']): ?><a href="products.php">Cancel</a><?php endif; ?>
    </div>
  </form>
</div>

<div class="card-box">
  <form class="filters" method="get">
    <input name="q" placeholder="Search name, SKU, brand" value="<?= e($q) ?>">
    <select name="category">
      <option value="0">All categories</option>
      <?php foreach ($categories as $c): ?>
        <option value="<?= (int)$c['id'] ?>" <?= $catFilter === (int)$c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option>
      <?php endforeach; ?>
    </select>
    <button class="btn">Filter</button>
  </form>
  <div class="table-wrap"><table>
    <thead><tr><th>Image</th><th>Product</th><th>SKU</th><th>Price</th><th>Stock</th><th>Status</th><th>Actions</th></tr></thead>
    <tbody>
    <?php if (!$products): ?><tr><td colspan="7" class="empty">No products found.</td></tr><?php endif; ?>
    <?php foreach ($products as $p): ?>
      <tr>
        <td><img class="thumb" src="<?= e(image_url($p['image'])) ?>" alt=""></td>
        <td><strong><?= e($p['name']) ?></strong><br><small><?= e($p['category'] ?? 'Uncategorised') ?> · <?= e($p['brand']) ?></small></td>
        <td><?= e($p['sku']) ?></td>
        <td><?= money($p['discount_price'] ?? $p['price']) ?><?php if ($p['discount_price'] !== null): ?><br><s><?= money($p['price']) ?></s><?php endif; ?></td>
        <td><?= (int)$p['stock'] ?></td>
        <td>
          <span class="badge <?= $p['is_active'] ? 'ok' : 'off' ?>"><?= $p['is_active'] ? 'Active' : 'Inactive' ?></span>
          <?= $p['is_featured'] ? ' <span class="badge feat">Featured</span>' : '' ?>
        </td>
        <td class="actions">
          <a href="?edit=<?= (int)$p['id'] ?>">Edit</a>
          <form method="post" action="products.php">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="toggle">
            <input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
            <button class="link-btn"><?= $p['is_active'] ? 'Deactivate' : 'Activate' ?></button>
          </form>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table></div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
