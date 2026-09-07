<?php
/**
 * Supplier Performance Analysis and Management System
 * Shopkeeper Wholesale Beauty Catalog & 1-Click Ordering
 */

$page_title = "Wholesale Cosmetics Catalog";
require_once __DIR__ . '/../includes/header.php';
require_role(['shopkeeper', 'admin', 'manufacturer']);

$db = get_db();
$user_id = (int)$_SESSION['user_id'];
$selected_category = $_GET['category'] ?? '';

// Build Query
$where = ["p.status = 'Active'"];
$params = [];

if (!empty($selected_category)) {
    $where[] = "p.category = ?";
    $params[] = $selected_category;
}

$query = "
    SELECT p.*, s.supplier_name, s.supplier_code 
    FROM products p
    LEFT JOIN suppliers s ON p.supplier_id = s.id
    WHERE " . implode(" AND ", $where) . "
    ORDER BY p.category ASC, p.product_name ASC
";
$stmt = $db->prepare($query);
$stmt->execute($params);
$products = $stmt->fetchAll();

// Fetch unique categories for filter
$categories = $db->query("SELECT DISTINCT category FROM products ORDER BY category ASC")->fetchAll(PDO::FETCH_COLUMN);

// Handle 1-Click Quick Order submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'place_order') {
    $token = $_POST['csrf_token'] ?? '';
    if (!verify_csrf_token($token)) {
        set_flash('danger', 'Security session expired.');
    } else {
        $product_id = (int)($_POST['product_id'] ?? 0);
        $qty = (int)($_POST['quantity'] ?? 10);
        $shipping_address = trim($_POST['shipping_address'] ?? 'Shop Location');

        $p_stmt = $db->prepare("SELECT * FROM products WHERE id = ?");
        $p_stmt->execute([$product_id]);
        $prod = $p_stmt->fetch();

        if ($prod && $qty >= 1) {
            $unit_price = (float)$prod['wholesale_price'];
            $total_amount = $unit_price * $qty;
            $supplier_id = (int)($prod['supplier_id'] ?? 1);

            // Generate PO Number
            $last_po = $db->query("SELECT id FROM purchase_orders ORDER BY id DESC LIMIT 1")->fetch();
            $next_id = ($last_po['id'] ?? 1000) + 1;
            $po_number = 'ORD' . str_pad($next_id, 4, '0', STR_PAD_LEFT);

            $order_date = date('Y-m-d');
            $expected_date = date('Y-m-d', strtotime('+5 days'));

            $ins_po = $db->prepare("
                INSERT INTO purchase_orders (po_number, user_id, supplier_id, order_type, order_date, expected_date, total_amount, status, shipping_address)
                VALUES (?, ?, ?, 'wholesale_shopkeeper', ?, ?, ?, 'Pending', ?)
            ");
            $ins_po->execute([$po_number, $user_id, $supplier_id, $order_date, $expected_date, $total_amount, $shipping_address]);
            $po_id = $db->lastInsertId();

            $ins_item = $db->prepare("
                INSERT INTO order_items (purchase_order_id, product_id, quantity, unit_price, total_price)
                VALUES (?, ?, ?, ?, ?)
            ");
            $ins_item->execute([$po_id, $product_id, $qty, $unit_price, $total_amount]);

            log_activity($user_id, 'Shopkeeper Placed Order', 'Orders', $po_id, 'Placed wholesale order ' . $po_number . ' for ' . $prod['product_name'] . ' (₹' . number_format($total_amount, 2) . ')');
            set_flash('success', 'Wholesale Order ' . $po_number . ' placed successfully! Expected delivery by ' . $expected_date . '.');
            header('Location: ' . BASE_URL . 'shopkeeper/my_orders.php');
            exit;
        } else {
            set_flash('danger', 'Invalid product or quantity.');
        }
    }
}
?>

<!-- Header -->
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
    <div>
        <h1 class="h3 fw-bold text-dark mb-1">Wholesale Cosmetics & Beauty Catalog</h1>
        <p class="text-muted small mb-0">Direct manufacturer & supplier wholesale pricing in Indian Rupees (₹) for retail store replenishment.</p>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= BASE_URL ?>shopkeeper/my_orders.php" class="btn btn-outline-secondary btn-sm rounded-3">
            <i class="fa-solid fa-list-check me-1"></i> My Orders
        </a>
        <a href="<?= BASE_URL ?>shopkeeper/dashboard.php" class="btn btn-light btn-sm rounded-3 border">
            <i class="fa-solid fa-arrow-left me-1"></i> Dashboard
        </a>
    </div>
</div>

<!-- Category Filter Pills -->
<div class="card-saas p-3 mb-4">
    <div class="d-flex align-items-center gap-2 flex-wrap">
        <span class="small fw-bold text-secondary me-2"><i class="fa-solid fa-filter me-1"></i> Category:</span>
        <a href="<?= BASE_URL ?>shopkeeper/catalog.php" class="btn btn-sm <?= empty($selected_category) ? 'btn-purple text-white' : 'btn-light border' ?> rounded-pill extra-small px-3 py-1" style="<?= empty($selected_category) ? 'background-color: #8b5cf6;' : '' ?>">
            All Categories (<?= count($products) ?>)
        </a>
        <?php foreach ($categories as $cat): ?>
            <a href="<?= BASE_URL ?>shopkeeper/catalog.php?category=<?= urlencode($cat) ?>" class="btn btn-sm <?= $selected_category === $cat ? 'btn-purple text-white' : 'btn-light border' ?> rounded-pill extra-small px-3 py-1" style="<?= $selected_category === $cat ? 'background-color: #8b5cf6;' : '' ?>">
                <?= htmlspecialchars($cat) ?>
            </a>
        <?php endforeach; ?>
    </div>
</div>

<!-- Products Grid -->
<div class="row g-4">
    <?php if (empty($products)): ?>
        <div class="col-12">
            <div class="card-saas p-5 text-center text-muted">
                <i class="fa-solid fa-boxes-stacked fs-1 text-secondary mb-3 d-block"></i>
                <h5>No cosmetics products found in this category.</h5>
                <a href="<?= BASE_URL ?>shopkeeper/catalog.php" class="btn btn-outline-primary btn-sm mt-2">View All Categories</a>
            </div>
        </div>
    <?php else: ?>
        <?php foreach ($products as $p): ?>
            <div class="col-12 col-md-6 col-lg-4 col-xl-3">
                <div class="card-saas h-100 d-flex flex-column justify-content-between p-3 border hover-shadow transition">
                    <div>
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <span class="badge bg-light text-dark border extra-small font-monospace"><?= htmlspecialchars($p['product_code']) ?></span>
                            <span class="badge bg-purple-subtle text-purple border extra-small"><?= htmlspecialchars($p['sub_category']) ?></span>
                        </div>
                        <h6 class="fw-bold text-dark mb-1"><?= htmlspecialchars($p['product_name']) ?></h6>
                        <div class="extra-small text-muted mb-2">
                            Brand: <strong><?= htmlspecialchars($p['brand']) ?></strong> &bull; By: <?= htmlspecialchars($p['supplier_name'] ?? 'Formulation Lab') ?>
                        </div>
                        <p class="extra-small text-secondary mb-3" style="min-height: 38px;"><?= htmlspecialchars(substr($p['description'] ?? '', 0, 90)) ?>...</p>
                    </div>

                    <div class="pt-3 border-top">
                        <div class="d-flex justify-content-between align-items-baseline mb-3">
                            <div>
                                <span class="extra-small text-muted text-uppercase d-block">Wholesale Price</span>
                                <span class="fw-bold fs-5 text-success"><?= format_currency($p['wholesale_price']) ?></span>
                                <span class="extra-small text-muted text-decoration-line-through ms-1"><?= format_currency($p['standard_price']) ?></span>
                            </div>
                            <div class="text-end">
                                <span class="badge bg-success-subtle text-success extra-small">In Stock (<?= $p['stock_quantity'] ?>)</span>
                            </div>
                        </div>

                        <button type="button" class="btn btn-purple w-100 btn-sm rounded-3 fw-semibold text-white d-flex align-items-center justify-content-center gap-1.5" style="background-color: #8b5cf6;" onclick="openOrderModal(<?= htmlspecialchars(json_encode($p)) ?>)">
                            <i class="fa-solid fa-cart-plus"></i>
                            <span>Quick Wholesale Order</span>
                        </button>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<!-- Quick Order Modal -->
<div class="modal fade" id="orderModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-purple text-white" style="background-color: #8b5cf6;">
                <h5 class="modal-title fw-bold fs-6"><i class="fa-solid fa-cart-shopping me-2"></i> Place Wholesale Purchase Order</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="<?= htmlspecialchars($_SERVER['PHP_SELF']) ?>" method="POST">
                <?= csrf_input() ?>
                <input type="hidden" name="action" value="place_order">
                <input type="hidden" name="product_id" id="modal-product-id">

                <div class="modal-body p-4">
                    <div class="p-3 bg-light rounded-3 mb-3 border">
                        <div class="fw-bold text-dark fs-6" id="modal-product-name">Product Name</div>
                        <div class="extra-small text-muted" id="modal-product-details">Code & Category</div>
                        <div class="d-flex justify-content-between align-items-center mt-2 pt-2 border-top">
                            <span class="small text-secondary">Wholesale Rate:</span>
                            <span class="fw-bold text-success" id="modal-product-price">₹0.00</span>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-secondary">Order Quantity (Units) *</label>
                        <input type="number" name="quantity" id="modal-qty-input" class="form-control" value="25" min="5" max="1000" oninput="calculateModalTotal()" required>
                        <div class="extra-small text-muted mt-1">Minimum wholesale order quantity: 5 units.</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-secondary">Delivery Shipping Address *</label>
                        <textarea name="shipping_address" class="form-control" rows="2" placeholder="Enter your shop / salon address" required><?= htmlspecialchars($_SESSION['user_shop'] ?? 'Retail Store') ?>, Retail Hub</textarea>
                    </div>

                    <div class="p-3 bg-purple-subtle rounded-3 d-flex justify-content-between align-items-center">
                        <span class="fw-bold text-purple small">Total Invoice Amount:</span>
                        <span class="fw-extrabold fs-5 text-purple font-monospace" id="modal-total-calc">₹0.00</span>
                    </div>
                </div>

                <div class="modal-footer bg-light border-0">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-purple btn-sm text-white px-4 fw-semibold" style="background-color: #8b5cf6;">
                        <i class="fa-solid fa-check me-1"></i> Confirm & Issue PO
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
let currentProduct = null;
const orderModal = new bootstrap.Modal(document.getElementById('orderModal'));

function openOrderModal(prod) {
    currentProduct = prod;
    document.getElementById('modal-product-id').value = prod.id;
    document.getElementById('modal-product-name').textContent = prod.product_name;
    document.getElementById('modal-product-details').textContent = `${prod.product_code} • ${prod.category} • Brand: ${prod.brand}`;
    document.getElementById('modal-product-price').textContent = `₹${parseFloat(prod.wholesale_price).toFixed(2)} / ${prod.unit}`;
    document.getElementById('modal-qty-input').value = prod.min_order_qty || 20;
    calculateModalTotal();
    orderModal.show();
}

function calculateModalTotal() {
    if (!currentProduct) return;
    const qty = parseInt(document.getElementById('modal-qty-input').value) || 0;
    const price = parseFloat(currentProduct.wholesale_price) || 0;
    const total = qty * price;
    document.getElementById('modal-total-calc').textContent = `₹${total.toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2})}`;
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
