<?php
/**
 * Supplier Performance Analysis and Management System (SPAS)
 * Initiate Product Transfer Workflow
 * Manufacturer -> Supplier OR Supplier -> Shopkeeper
 */

$page_title = 'Initiate Product Transfer';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/transfer_functions.php';

$db = get_db();
$user_data = get_current_user_data();
$user_id = (int)$user_data['id'];
$user_role = strtolower($user_data['role']);

// Role Gate: Shopkeeper cannot dispatch outbound transfers (Shopkeeper is the retail end-node)
if ($user_role === 'shopkeeper') {
    set_flash('warning', 'Shopkeepers are retail recipients and do not dispatch wholesale transfers. You can receive inbound stock from Suppliers.');
    header('Location: ' . BASE_URL . 'transfers/index.php');
    exit;
}

$error = '';
$success = '';

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['dispatch_transfer'])) {
    $token = $_POST['csrf_token'] ?? '';
    if (!verify_csrf_token($token)) {
        $error = 'Security session expired. Please refresh and try again.';
    } else {
        $product_id = (int)($_POST['product_id'] ?? 0);
        $receiver_id = (int)($_POST['receiver_id'] ?? 0);
        $quantity = (int)($_POST['quantity'] ?? 0);
        $unit_price = (float)($_POST['unit_price'] ?? 0.0);
        $batch_number = trim($_POST['batch_number'] ?? '');
        $remarks = trim($_POST['remarks'] ?? '');

        // Determine sender & receiver roles
        $sender_id = $user_id;
        $sender_role = $user_role;
        
        // Admin override capability
        if ($user_role === 'admin' && !empty($_POST['admin_sender_id'])) {
            $sender_id = (int)$_POST['admin_sender_id'];
            $s_stmt = $db->prepare("SELECT role FROM users WHERE id = ?");
            $s_stmt->execute([$sender_id]);
            $s_row = $s_stmt->fetch();
            $sender_role = $s_row ? $s_row['role'] : 'manufacturer';
        }

        // Fetch receiver role
        $r_stmt = $db->prepare("SELECT role, name, company_name, shop_name FROM users WHERE id = ? AND status = 'Active'");
        $r_stmt->execute([$receiver_id]);
        $receiver = $r_stmt->fetch();

        if (!$receiver) {
            $error = 'Selected recipient is invalid or inactive.';
        } elseif ($quantity <= 0) {
            $error = 'Please enter a valid transfer quantity greater than 0.';
        } elseif ($product_id <= 0) {
            $error = 'Please select a valid product to transfer.';
        } else {
            $receiver_role = $receiver['role'];

            // Validate parent transfer provenance if Supplier -> Shopkeeper
            $parent_transfer_id = null;
            if ($sender_role === 'supplier') {
                $p_stmt = $db->prepare("
                    SELECT id FROM product_transfers 
                    WHERE product_id = ? AND receiver_id = ? AND status = 'Received' 
                    ORDER BY id DESC LIMIT 1
                ");
                $p_stmt->execute([$product_id, $sender_id]);
                $parent = $p_stmt->fetch();
                if ($parent) {
                    $parent_transfer_id = (int)$parent['id'];
                }
            }

            // Ensure sender has inventory row in user_inventory if not initialized yet
            $curr_stock = get_user_stock($sender_id, $product_id);
            if ($curr_stock <= 0 && $sender_role === 'manufacturer') {
                // Auto-sync initial stock from products table if manufacturer created it
                $prod_check = $db->prepare("SELECT stock_quantity, standard_price FROM products WHERE id = ?");
                $prod_check->execute([$product_id]);
                $prod_row = $prod_check->fetch();
                if ($prod_row && (int)$prod_row['stock_quantity'] > 0) {
                    adjust_user_stock($sender_id, 'manufacturer', $product_id, (int)$prod_row['stock_quantity'], $batch_number ?: 'BATCH-' . date('Ym'));
                    $curr_stock = (int)$prod_row['stock_quantity'];
                }
            }

            // Dispatch Transfer via helper
            $res = create_product_transfer(
                $sender_id,
                $sender_role,
                $receiver_id,
                $receiver_role,
                $product_id,
                $quantity,
                $unit_price,
                $batch_number,
                $remarks,
                $parent_transfer_id
            );

            if ($res['success']) {
                set_flash('success', $res['message']);
                header('Location: ' . BASE_URL . 'transfers/index.php');
                exit;
            } else {
                $error = $res['message'];
            }
        }
    }
}

// 1. Fetch available products based on role
$available_products = [];
if ($user_role === 'manufacturer') {
    // Manufacturer: can transfer any of their products or catalog formulations
    $p_stmt = $db->prepare("
        SELECT p.id, p.product_code, p.product_name, p.category, p.brand, p.wholesale_price, p.standard_price,
               COALESCE(ui.quantity, p.stock_quantity) as available_stock,
               ui.batch_number
        FROM products p
        LEFT JOIN user_inventory ui ON p.id = ui.product_id AND ui.user_id = ?
        WHERE p.status = 'Active'
        ORDER BY p.product_name ASC
    ");
    $p_stmt->execute([$user_id]);
    $available_products = $p_stmt->fetchAll();
} elseif ($user_role === 'supplier') {
    // Supplier: can ONLY transfer products they have physically received in stock
    $p_stmt = $db->prepare("
        SELECT p.id, p.product_code, p.product_name, p.category, p.brand, p.wholesale_price, p.standard_price,
               ui.quantity as available_stock, ui.batch_number
        FROM user_inventory ui
        JOIN products p ON ui.product_id = p.id
        WHERE ui.user_id = ? AND ui.quantity > 0
        ORDER BY p.product_name ASC
    ");
    $p_stmt->execute([$user_id]);
    $available_products = $p_stmt->fetchAll();
} else {
    // Admin: all products
    $available_products = $db->query("SELECT id, product_code, product_name, category, brand, wholesale_price, standard_price, stock_quantity as available_stock, NULL as batch_number FROM products WHERE status = 'Active'")->fetchAll();
}

// 2. Fetch available recipients based on role
$recipients = [];
if ($user_role === 'manufacturer') {
    // Manufacturer transfers to SUPPLIERS
    $recipients = $db->query("
        SELECT id, name, company_name, city, state, 'supplier' as target_role 
        FROM users 
        WHERE role = 'supplier' AND status = 'Active' 
        ORDER BY company_name, name ASC
    ")->fetchAll();
} elseif ($user_role === 'supplier') {
    // Supplier transfers to SHOPKEEPERS
    $recipients = $db->query("
        SELECT id, name, shop_name as company_name, city, state, 'shopkeeper' as target_role 
        FROM users 
        WHERE role = 'shopkeeper' AND status = 'Active' 
        ORDER BY shop_name, name ASC
    ")->fetchAll();
} else {
    // Admin: all suppliers & shopkeepers
    $recipients = $db->query("
        SELECT id, name, COALESCE(company_name, shop_name) as company_name, city, state, role as target_role 
        FROM users 
        WHERE role IN ('supplier', 'shopkeeper') AND status = 'Active' 
        ORDER BY role, name ASC
    ")->fetchAll();
}
?>

<div class="row justify-content-center">
    <div class="col-lg-8">
        
        <!-- Header -->
        <div class="d-flex align-items-center justify-content-between mb-4">
            <div>
                <a href="<?= BASE_URL ?>transfers/index.php" class="text-decoration-none text-muted small d-inline-flex align-items-center gap-1 mb-2">
                    <i class="fa-solid fa-arrow-left extra-small"></i> Back to Transfers List
                </a>
                <h3 class="fw-bold text-dark mb-0">Initiate Product Transfer</h3>
            </div>
            <div>
                <?php if ($user_role === 'manufacturer'): ?>
                    <span class="badge bg-primary bg-opacity-10 text-primary border border-primary-subtle px-3 py-2 rounded-pill font-monospace">
                        <i class="fa-solid fa-industry me-1"></i> Stage 1: Manufacturer &rarr; Supplier
                    </span>
                <?php elseif ($user_role === 'supplier'): ?>
                    <span class="badge bg-success bg-opacity-10 text-success border border-success-subtle px-3 py-2 rounded-pill font-monospace">
                        <i class="fa-solid fa-truck-ramp-box me-1"></i> Stage 2: Supplier &rarr; Shopkeeper
                    </span>
                <?php endif; ?>
            </div>
        </div>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger alert-dismissible fade show d-flex align-items-center shadow-sm mb-4 rounded-3" role="alert">
                <i class="fa-solid fa-triangle-exclamation me-2 fs-5 flex-shrink-0"></i>
                <div><?= htmlspecialchars($error) ?></div>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <!-- Transfer Dispatch Card -->
        <div class="card border-0 shadow-sm rounded-4 bg-white p-4 p-md-5 mb-4">
            
            <form action="<?= BASE_URL ?>transfers/create.php" method="POST" id="transfer-form">
                <?= csrf_input() ?>
                <input type="hidden" name="dispatch_transfer" value="1">

                <!-- Step 1: Product Selection -->
                <div class="mb-4">
                    <label for="product_id" class="form-label small fw-bold text-secondary text-uppercase tracking-wider">
                        1. Select Product to Transfer <span class="text-danger">*</span>
                    </label>
                    <select name="product_id" id="product_id" class="form-select form-select-lg rounded-3" required onchange="updateProductStockInfo()">
                        <option value="">-- Choose available product from inventory --</option>
                        <?php foreach ($available_products as $prod): ?>
                            <option value="<?= (int)$prod['id'] ?>" 
                                    data-stock="<?= (int)$prod['available_stock'] ?>" 
                                    data-price="<?= (float)$prod['wholesale_price'] ?>"
                                    data-batch="<?= htmlspecialchars($prod['batch_number'] ?? 'BATCH-' . date('Ym')) ?>">
                                <?= htmlspecialchars($prod['product_name']) ?> (<?= htmlspecialchars($prod['product_code']) ?>) &bull; Available Stock: <?= number_format($prod['available_stock']) ?> pcs
                            </option>
                        <?php endforeach; ?>
                    </select>
                    
                    <?php if (empty($available_products) && $user_role === 'supplier'): ?>
                        <div class="alert alert-info mt-2 extra-small mb-0">
                            <i class="fa-solid fa-info-circle me-1"></i> You currently have no received products in stock. You must first accept incoming shipments from Manufacturers before transferring to Shopkeepers.
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Live Stock & Unit Price Pill -->
                <div class="card bg-light border-0 p-3 rounded-3 mb-4 d-none" id="product-stock-banner">
                    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                        <div>
                            <span class="text-muted extra-small d-block text-uppercase fw-bold">Current Available Stock</span>
                            <span class="fs-5 fw-extrabold text-dark" id="display-available-stock">0</span> <span class="small text-muted">pcs in warehouse</span>
                        </div>
                        <div>
                            <span class="text-muted extra-small d-block text-uppercase fw-bold">Standard Wholesale Rate</span>
                            <span class="fs-5 fw-extrabold text-primary" id="display-unit-price">₹0.00</span> <span class="small text-muted">per unit</span>
                        </div>
                    </div>
                </div>

                <!-- Step 2: Target Recipient Selection -->
                <div class="mb-4">
                    <label for="receiver_id" class="form-label small fw-bold text-secondary text-uppercase tracking-wider">
                        2. Select Target Recipient <span class="text-danger">*</span>
                    </label>
                    <select name="receiver_id" id="receiver_id" class="form-select form-select-lg rounded-3" required>
                        <option value="">
                            <?= $user_role === 'manufacturer' ? '-- Choose registered Supplier partner --' : '-- Choose registered Shopkeeper store --' ?>
                        </option>
                        <?php foreach ($recipients as $rec): ?>
                            <option value="<?= (int)$rec['id'] ?>">
                                <?= htmlspecialchars($rec['company_name'] ?: $rec['name']) ?> &bull; <?= htmlspecialchars($rec['city']) ?>, <?= htmlspecialchars($rec['state']) ?> (<?= ucfirst($rec['target_role']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                    
                    <?php if (empty($recipients)): ?>
                        <div class="alert alert-warning mt-2 extra-small mb-0">
                            <i class="fa-solid fa-triangle-exclamation me-1"></i> No registered <?= $user_role === 'manufacturer' ? 'suppliers' : 'shopkeepers' ?> are currently active. They must first register on the portal.
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Step 3: Quantity & Batch Details -->
                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <label for="quantity" class="form-label small fw-bold text-secondary text-uppercase tracking-wider">
                            3. Transfer Quantity (pcs) <span class="text-danger">*</span>
                        </label>
                        <div class="input-group">
                            <span class="input-group-text bg-light text-muted"><i class="fa-solid fa-boxes-stacked"></i></span>
                            <input type="number" name="quantity" id="quantity" class="form-control form-control-lg font-monospace" placeholder="e.g. 50" min="1" required oninput="calculateTotalAmount()">
                        </div>
                    </div>

                    <div class="col-md-6">
                        <label for="unit_price" class="form-label small fw-bold text-secondary text-uppercase tracking-wider">
                            Unit Transfer Price (₹) <span class="text-danger">*</span>
                        </label>
                        <div class="input-group">
                            <span class="input-group-text bg-light text-muted">₹</span>
                            <input type="number" step="0.01" name="unit_price" id="unit_price" class="form-control form-control-lg font-monospace" placeholder="380.00" required oninput="calculateTotalAmount()">
                        </div>
                    </div>
                </div>

                <!-- Batch Number & Total Amount Display -->
                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <label for="batch_number" class="form-label small fw-semibold text-secondary">
                            Batch / Lot Number
                        </label>
                        <div class="input-group">
                            <span class="input-group-text bg-light text-muted"><i class="fa-solid fa-barcode"></i></span>
                            <input type="text" name="batch_number" id="batch_number" class="form-control font-monospace" placeholder="BATCH-<?= date('Ym') ?>-01" value="BATCH-<?= date('Ym') ?>-01">
                        </div>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label small fw-semibold text-secondary">
                            Total Transfer Value
                        </label>
                        <div class="form-control bg-light font-monospace fs-5 fw-bold text-success d-flex align-items-center" id="display-total-value">
                            ₹0.00
                        </div>
                    </div>
                </div>

                <!-- Step 4: Remarks / Dispatch Notes -->
                <div class="mb-4">
                    <label for="remarks" class="form-label small fw-semibold text-secondary">Dispatch Remarks / Shipping Instructions</label>
                    <textarea name="remarks" id="remarks" rows="2" class="form-control" placeholder="e.g. Dispatched via express cold-chain logistics vehicle. Handle with care."></textarea>
                </div>

                <!-- Submit Button -->
                <div class="d-flex align-items-center justify-content-between pt-3 border-top">
                    <a href="<?= BASE_URL ?>transfers/index.php" class="btn btn-outline-secondary px-4 py-2 rounded-3">Cancel</a>
                    <button type="submit" class="btn btn-primary px-4 py-2.5 rounded-3 fw-bold shadow-sm d-flex align-items-center gap-2">
                        <i class="fa-solid fa-paper-plane"></i>
                        <span>Confirm & Dispatch Transfer</span>
                    </button>
                </div>

            </form>

        </div>

    </div>
</div>

<script>
function updateProductStockInfo() {
    const select = document.getElementById('product_id');
    const option = select.options[select.selectedIndex];
    const banner = document.getElementById('product-stock-banner');
    
    if (option && option.value) {
        const stock = parseInt(option.getAttribute('data-stock') || 0);
        const price = parseFloat(option.getAttribute('data-price') || 0.0);
        const batch = option.getAttribute('data-batch') || '';

        document.getElementById('display-available-stock').innerText = stock.toLocaleString();
        document.getElementById('display-unit-price').innerText = '₹' + price.toFixed(2);
        document.getElementById('unit_price').value = price.toFixed(2);
        
        if (batch) {
            document.getElementById('batch_number').value = batch;
        }

        const qtyInput = document.getElementById('quantity');
        qtyInput.max = stock;
        
        banner.classList.remove('d-none');
        calculateTotalAmount();
    } else {
        banner.classList.add('d-none');
    }
}

function calculateTotalAmount() {
    const qty = parseInt(document.getElementById('quantity').value || 0);
    const price = parseFloat(document.getElementById('unit_price').value || 0);
    const total = qty * price;
    document.getElementById('display-total-value').innerText = '₹' + (isNaN(total) ? '0.00' : total.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
