<?php
require_once __DIR__ . '/../config/bootstrap.php';
require_admin();
$pageTitle = 'Dashboard';
$pdo = db();
$stats = $pdo->query('SELECT COUNT(*) AS total,
    SUM(is_active = 1) AS active,
    SUM(is_featured = 1) AS featured,
    SUM(is_active = 1 AND stock = 0) AS out_of_stock,
    SUM(is_active = 1 AND stock > 0 AND stock <= ' . (int)LOW_STOCK_THRESHOLD . ') AS low_stock
    FROM products')->fetch();
$categories = (int)$pdo->query('SELECT COUNT(*) FROM categories WHERE is_active = 1')->fetchColumn();
$recent = $pdo->query('SELECT p.name, p.sku, p.stock, p.is_active, c.name AS category
    FROM products p LEFT JOIN categories c ON c.id = p.category_id ORDER BY p.id DESC LIMIT 5')->fetchAll();
require __DIR__ . '/includes/header.php';
?>
<div class="stats">
  <div class="stat"><span>Total products</span><strong><?= (int)$stats['total'] ?></strong></div>
  <div class="stat"><span>Active products</span><strong><?= (int)$stats['active'] ?></strong></div>
  <div class="stat"><span>Featured</span><strong><?= (int)$stats['featured'] ?></strong></div>
  <div class="stat"><span>Active categories</span><strong><?= $categories ?></strong></div>
  <div class="stat"><span>Low stock</span><strong><?= (int)$stats['low_stock'] ?></strong></div>
  <div class="stat"><span>Out of stock</span><strong><?= (int)$stats['out_of_stock'] ?></strong></div>
</div>
<div class="card-box">
  <h2>Latest products</h2>
  <div class="table-wrap"><table>
    <thead><tr><th>Name</th><th>SKU</th><th>Category</th><th>Stock</th><th>Status</th></tr></thead>
    <tbody>
    <?php if (!$recent): ?><tr><td colspan="5" class="empty">No products yet. <a href="products.php">Add one</a>.</td></tr><?php endif; ?>
    <?php foreach ($recent as $r): ?>
      <tr>
        <td><?= e($r['name']) ?></td><td><?= e($r['sku']) ?></td><td><?= e($r['category'] ?? '—') ?></td>
        <td><?= (int)$r['stock'] ?></td>
        <td><span class="badge <?= $r['is_active'] ? 'ok' : 'off' ?>"><?= $r['is_active'] ? 'Active' : 'Inactive' ?></span></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table></div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
