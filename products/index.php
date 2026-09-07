<?php
/**
 * Supplier Performance Analysis and Management System
 * Beauty, Cosmetics & Skincare Products Catalog Directory
 */

$page_title = "Products Catalog";
require_once __DIR__ . '/../includes/header.php';

$db = get_db();

// Handle Search and Filters
$search = trim($_GET['search'] ?? '');
$category = trim($_GET['category'] ?? 'All');
$status = trim($_GET['status'] ?? 'All');

$where_clauses = ["1=1"];
$params = [];

if (!empty($search)) {
    $where_clauses[] = "(p.product_name LIKE ? OR p.product_code LIKE ? OR p.sub_category LIKE ? OR p.brand LIKE ? OR p.description LIKE ?)";
    $st = "%$search%";
    $params = array_merge($params, [$st, $st, $st, $st, $st]);
}

if ($category !== 'All' && !empty($category)) {
    $where_clauses[] = "p.category = ?";
    $params[] = $category;
}

if ($status !== 'All' && !empty($status)) {
    $where_clauses[] = "p.status = ?";
    $params[] = $status;
}

$where_sql = implode(" AND ", $where_clauses);

// Fetch categories for filter dropdown
$categories = [
    'Face Products',
    'Eye Products',
    'Lip Products',
    'Skincare Products',
    'Nail Products',
    'Hair Beauty Products',
    'Body Care Products',
    'Beauty Tools & Accessories'
];

// Fetch products with supplier names
$stmt = $db->prepare("
    SELECT p.*, s.supplier_name, s.supplier_code
    FROM products p
    LEFT JOIN suppliers s ON p.supplier_id = s.id
    WHERE $where_sql
    ORDER BY p.category ASC, p.product_name ASC
");
$stmt->execute($params);
$products = $stmt->fetchAll();
?>

<!-- Header -->
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
    <div>
        <h1 class="h3 fw-bold text-dark mb-1">Cosmetics & Beauty Catalog</h1>
        <p class="text-muted small mb-0">Manage Face, Eye, Lip, Skincare, Hair, Body, and Beauty Tool products and standard price benchmarks.</p>
    </div>
    <?php if ($_SESSION['user_role'] !== 'staff'): ?>
    <div>
        <a href="<?= BASE_URL ?>products/add.php" class="btn btn-primary btn-sm rounded-pill d-inline-flex align-items-center gap-2 shadow-sm">
            <i class="fa-solid fa-plus"></i> Add New Product
        </a>
    </div>
    <?php endif; ?>
</div>

<!-- Filters -->
<div class="card-saas mb-4">
    <div class="card-saas-body">
        <form method="GET" action="<?= htmlspecialchars($_SERVER['PHP_SELF']) ?>" class="row g-2 align-items-center">
            
            <div class="col-12 col-md-5">
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-light border-end-0 text-muted"><i class="fa-solid fa-magnifying-glass"></i></span>
                    <input type="text" name="search" class="form-control border-start-0" placeholder="Search product name, brand, SKU..." value="<?= htmlspecialchars($search) ?>">
                </div>
            </div>

            <div class="col-6 col-md-3">
                <select name="category" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="All">All 8 Beauty Categories</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= htmlspecialchars($cat) ?>" <?= $category === $cat ? 'selected' : '' ?>><?= htmlspecialchars($cat) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-6 col-md-2">
                <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="All">All Statuses</option>
                    <option value="Active" <?= $status === 'Active' ? 'selected' : '' ?>>Active</option>
                    <option value="Inactive" <?= $status === 'Inactive' ? 'selected' : '' ?>>Inactive</option>
                </select>
            </div>

            <div class="col-12 col-md-2 d-flex gap-1">
                <button type="submit" class="btn btn-primary btn-sm flex-grow-1">Filter</button>
                <a href="<?= BASE_URL ?>products/index.php" class="btn btn-light btn-sm border" title="Reset"><i class="fa-solid fa-rotate-left"></i></a>
            </div>

        </form>
    </div>
</div>

<!-- Products Table Card -->
<div class="card-saas">
    <div class="card-saas-header d-flex justify-content-between align-items-center">
        <h6 class="fw-bold mb-0 text-dark">Beauty Catalog Items (<?= count($products) ?>)</h6>
        <span class="badge bg-light text-dark border">Standard Benchmark Pricing (₹)</span>
    </div>
    
    <div class="table-responsive">
        <table class="table table-custom mb-0">
            <thead>
                <tr>
                    <th>Product Code</th>
                    <th>Product Name & Brand</th>
                    <th>Category & Subcategory</th>
                    <th>Supplier Partner</th>
                    <th>Stock / Reorder</th>
                    <th>Standard Price</th>
                    <th>Status</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($products)): ?>
                    <tr>
                        <td colspan="8" class="text-center py-5 text-muted">
                            <i class="fa-solid fa-box-open fs-1 text-secondary opacity-50 mb-2 d-block"></i>
                            <p class="mb-0 fw-semibold">No cosmetics products found matching criteria.</p>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($products as $p): ?>
                        <tr>
                            <td><code class="fw-bold text-primary"><?= htmlspecialchars($p['product_code']) ?></code></td>
                            <td>
                                <a href="<?= BASE_URL ?>products/view.php?id=<?= $p['id'] ?>" class="text-decoration-none fw-bold text-dark d-block">
                                    <?= htmlspecialchars($p['product_name']) ?>
                                </a>
                                <span class="badge bg-purple-subtle text-purple border extra-small me-1"><?= htmlspecialchars($p['brand']) ?></span>
                                <span class="text-muted extra-small"><?= htmlspecialchars(substr($p['description'] ?? '', 0, 50)) ?>...</span>
                            </td>
                            <td>
                                <span class="badge bg-light text-dark border d-block text-start mb-1"><?= htmlspecialchars($p['category']) ?></span>
                                <span class="extra-small text-muted fw-semibold"><?= htmlspecialchars($p['sub_category']) ?></span>
                            </td>
                            <td>
                                <span class="fw-semibold text-dark"><?= htmlspecialchars($p['supplier_name'] ?? 'Primary Vendor') ?></span>
                                <code class="extra-small text-muted d-block"><?= htmlspecialchars($p['supplier_code'] ?? '') ?></code>
                            </td>
                            <td>
                                <span class="fw-bold text-dark font-monospace"><?= number_format($p['stock_quantity']) ?> <?= htmlspecialchars($p['unit']) ?></span>
                                <span class="extra-small text-muted d-block">Reorder: <?= $p['reorder_level'] ?></span>
                            </td>
                            <td class="fw-bold text-dark font-monospace"><?= format_currency($p['standard_price']) ?></td>
                            <td><?= get_status_badge($p['status']) ?></td>
                            <td class="text-end">
                                <div class="btn-group btn-group-sm">
                                    <a href="<?= BASE_URL ?>products/view.php?id=<?= $p['id'] ?>" class="btn btn-light border" title="View Details">
                                        <i class="fa-solid fa-eye text-primary"></i>
                                    </a>
                                    <?php if ($_SESSION['user_role'] !== 'staff'): ?>
                                    <a href="<?= BASE_URL ?>products/edit.php?id=<?= $p['id'] ?>" class="btn btn-light border" title="Edit Product">
                                        <i class="fa-solid fa-pen text-secondary"></i>
                                    </a>
                                    <a href="<?= BASE_URL ?>products/delete.php?id=<?= $p['id'] ?>&csrf_token=<?= generate_csrf_token() ?>" 
                                       class="btn btn-light border text-danger" 
                                       onclick="return confirm('Are you sure you want to delete product <?= htmlspecialchars(addslashes($p['product_name'])) ?>?')"
                                       title="Delete Product">
                                        <i class="fa-solid fa-trash"></i>
                                    </a>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
