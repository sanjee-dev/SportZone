<?php
require_once __DIR__ . '/config/bootstrap.php';
$pageTitle = 'Shop';
$q = trim($_GET['q'] ?? '');
$cat = trim($_GET['cat'] ?? '');
$sort = $_GET['sort'] ?? 'newest';
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 12;

$sorts = [
    'newest' => 'p.id DESC',
    'price_asc' => 'COALESCE(p.discount_price, p.price) ASC',
    'price_desc' => 'COALESCE(p.discount_price, p.price) DESC',
    'name' => 'p.name ASC',
];
$orderBy = $sorts[$sort] ?? $sorts['newest'];

$where = ['p.is_active = 1'];
$params = [];
if ($q !== '') {
    $where[] = '(p.name LIKE ? OR p.brand LIKE ? OR p.sku LIKE ?)';
    array_push($params, "%$q%", "%$q%", "%$q%");
}
if ($cat !== '') {
    $where[] = 'c.slug = ?';
    $params[] = $cat;
}
$from = 'FROM products p LEFT JOIN categories c ON c.id = p.category_id WHERE ' . implode(' AND ', $where);

$countSt = db()->prepare("SELECT COUNT(*) $from");
$countSt->execute($params);
$total = (int)$countSt->fetchColumn();
$pages = max(1, (int)ceil($total / $perPage));
$page = min($page, $pages);
$offset = ($page - 1) * $perPage;

$st = db()->prepare("SELECT p.* $from ORDER BY $orderBy LIMIT $perPage OFFSET $offset");
$st->execute($params);
$products = $st->fetchAll();
$categories = db()->query('SELECT name, slug FROM categories WHERE is_active = 1 ORDER BY name')->fetchAll();

require __DIR__ . '/includes/header.php';
?>
<div class="toolbar">
  <div class="chips">
    <a href="shop.php">All</a>
    <?php foreach ($categories as $c): ?>
      <a href="shop.php?cat=<?= e(urlencode($c['slug'])) ?>"><?= e($c['name']) ?></a>
    <?php endforeach; ?>
  </div>
  <form method="get">
    <input type="hidden" name="q" value="<?= e($q) ?>">
    <input type="hidden" name="cat" value="<?= e($cat) ?>">
    <select name="sort" onchange="this.form.submit()">
      <option value="newest" <?= $sort === 'newest' ? 'selected' : '' ?>>Newest</option>
      <option value="price_asc" <?= $sort === 'price_asc' ? 'selected' : '' ?>>Price: low to high</option>
      <option value="price_desc" <?= $sort === 'price_desc' ? 'selected' : '' ?>>Price: high to low</option>
      <option value="name" <?= $sort === 'name' ? 'selected' : '' ?>>Name A–Z</option>
    </select>
  </form>
</div>
<p><?= $total ?> product(s) found<?= $q !== '' ? ' for “' . e($q) . '”' : '' ?>.</p>
<?php if (!$products): ?><p class="empty">No products match your search.</p><?php endif; ?>
<div class="grid">
<?php foreach ($products as $p) { echo product_card($p); } ?>
</div>
<?php if ($pages > 1): ?>
<div class="pager">
<?php for ($i = 1; $i <= $pages; $i++): ?>
  <a class="<?= $i === $page ? 'active' : '' ?>" href="?<?= e(http_build_query(array_merge($_GET, ['page' => $i]))) ?>"><?= $i ?></a>
<?php endfor; ?>
</div>
<?php endif; ?>
<?php require __DIR__ . '/includes/footer.php'; ?>
