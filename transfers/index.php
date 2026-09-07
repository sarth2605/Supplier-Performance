<?php
/**
 * Supplier Performance Analysis and Management System (SPAS)
 * Product Transfers History & Chain Management Hub
 * Workflow: Manufacturer -> Supplier -> Shopkeeper
 */

$page_title = 'Product Transfers & Supply Chain History';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/transfer_functions.php';

$db = get_db();
$user_data = get_current_user_data();
$user_id = (int)$user_data['id'];
$user_role = strtolower($user_data['role']);

// Filters
$status_filter = trim($_GET['status'] ?? '');
$stage_filter = trim($_GET['stage'] ?? '');
$search_query = trim($_GET['search'] ?? '');

// Base Query
$where_clauses = ["1=1"];
$params = [];

// Role-based visibility:
// Admin: sees all transfers across entire ecosystem
// Manufacturer: sees transfers sent by them (or received if any)
// Supplier: sees transfers received from manufacturers OR transfers sent to shopkeepers
// Shopkeeper: sees transfers received from suppliers
if ($user_role === 'manufacturer') {
    $where_clauses[] = "(t.sender_id = ?)";
    $params[] = $user_id;
} elseif ($user_role === 'supplier') {
    $where_clauses[] = "(t.sender_id = ? OR t.receiver_id = ?)";
    $params[] = $user_id;
    $params[] = $user_id;
} elseif ($user_role === 'shopkeeper') {
    $where_clauses[] = "(t.receiver_id = ?)";
    $params[] = $user_id;
}

if (!empty($status_filter)) {
    $where_clauses[] = "t.status = ?";
    $params[] = $status_filter;
}

if (!empty($stage_filter)) {
    $where_clauses[] = "t.stage = ?";
    $params[] = $stage_filter;
}

if (!empty($search_query)) {
    $where_clauses[] = "(t.transfer_ref LIKE ? OR p.product_name LIKE ? OR p.product_code LIKE ? OR s.name LIKE ? OR s.company_name LIKE ? OR r.name LIKE ? OR r.company_name LIKE ? OR r.shop_name LIKE ?)";
    $term = "%{$search_query}%";
    for ($i = 0; $i < 8; $i++) {
        $params[] = $term;
    }
}

$where_sql = implode(' AND ', $where_clauses);

// Fetch Transfers
$query = "
    SELECT t.*, 
           p.product_name, p.product_code, p.category, p.brand,
           s.name as sender_name, s.company_name as sender_company, s.city as sender_city,
           r.name as receiver_name, r.company_name as receiver_company, r.shop_name as receiver_shop, r.city as receiver_city
    FROM product_transfers t
    JOIN products p ON t.product_id = p.id
    JOIN users s ON t.sender_id = s.id
    JOIN users r ON t.receiver_id = r.id
    WHERE {$where_sql}
    ORDER BY t.created_at DESC
";
$stmt = $db->prepare($query);
$stmt->execute($params);
$transfers = $stmt->fetchAll();

// KPI Metrics
$total_count = count($transfers);
$in_transit_count = 0;
$received_count = 0;
$total_units = 0;
$total_value = 0.0;
$pending_action_count = 0;

foreach ($transfers as $t) {
    if ($t['status'] === 'In Transit') $in_transit_count++;
    if ($t['status'] === 'Received') $received_count++;
    $total_units += (int)$t['quantity'];
    $total_value += (float)$t['total_amount'];

    // If incoming transfer for current user that is In Transit
    if ((int)$t['receiver_id'] === $user_id && $t['status'] === 'In Transit') {
        $pending_action_count++;
    }
}
?>

<div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
    <div>
        <h3 class="fw-bold text-dark mb-1">
            <i class="fa-solid fa-arrow-right-arrow-left text-primary me-2"></i>Product Transfers & Chain History
        </h3>
        <p class="text-muted small mb-0">
            End-to-end multi-tier supply chain movement: 
            <span class="badge bg-primary-subtle text-primary">Manufacturer</span> 
            <i class="fa-solid fa-arrow-right extra-small text-muted"></i>
            <span class="badge bg-success-subtle text-success">Supplier</span> 
            <i class="fa-solid fa-arrow-right extra-small text-muted"></i>
            <span class="badge bg-purple-subtle text-purple" style="background: rgba(139, 92, 246, 0.15); color: #7c3aed;">Shopkeeper</span>
        </p>
    </div>
    
    <div class="d-flex align-items-center gap-2">
        <?php if ($user_role === 'manufacturer' || $user_role === 'supplier' || $user_role === 'admin'): ?>
            <a href="<?= BASE_URL ?>transfers/create.php" class="btn btn-primary btn-sm px-3 py-2 rounded-3 fw-semibold shadow-sm d-flex align-items-center gap-2">
                <i class="fa-solid fa-paper-plane"></i>
                <span>Initiate New Transfer</span>
            </a>
        <?php endif; ?>
    </div>
</div>

<!-- Pending Inbound Attention Banner -->
<?php if ($pending_action_count > 0): ?>
    <div class="alert alert-warning border-0 shadow-sm d-flex align-items-center justify-content-between p-3 mb-4 rounded-3">
        <div class="d-flex align-items-center gap-2">
            <i class="fa-solid fa-truck-fast fs-4 text-warning"></i>
            <div>
                <div class="fw-bold text-dark">You have <?= $pending_action_count ?> inbound transfer(s) awaiting your receipt confirmation!</div>
                <div class="small text-muted">Review incoming stock and click "Accept & Receive" to add items to your local inventory.</div>
            </div>
        </div>
        <a href="#transfers-table" class="btn btn-warning btn-sm fw-bold px-3 py-1.5 rounded-3">Review Inbound Items</a>
    </div>
<?php endif; ?>

<!-- KPI Overview Cards -->
<div class="row g-3 mb-4">
    <div class="col-6 col-lg-3">
        <div class="card border-0 shadow-sm p-3 rounded-4 bg-white">
            <div class="d-flex align-items-center justify-content-between mb-2">
                <span class="text-muted extra-small fw-bold text-uppercase">Total Transfers</span>
                <span class="p-2 rounded-3 bg-light text-primary"><i class="fa-solid fa-list-check"></i></span>
            </div>
            <h4 class="fw-extrabold text-dark mb-0"><?= number_format($total_count) ?></h4>
            <span class="extra-small text-muted">Recorded movements</span>
        </div>
    </div>

    <div class="col-6 col-lg-3">
        <div class="card border-0 shadow-sm p-3 rounded-4 bg-white">
            <div class="d-flex align-items-center justify-content-between mb-2">
                <span class="text-muted extra-small fw-bold text-uppercase">In Transit</span>
                <span class="p-2 rounded-3 bg-warning bg-opacity-10 text-warning"><i class="fa-solid fa-truck-moving"></i></span>
            </div>
            <h4 class="fw-extrabold text-warning mb-0"><?= number_format($in_transit_count) ?></h4>
            <span class="extra-small text-muted">On the road / Dispatched</span>
        </div>
    </div>

    <div class="col-6 col-lg-3">
        <div class="card border-0 shadow-sm p-3 rounded-4 bg-white">
            <div class="d-flex align-items-center justify-content-between mb-2">
                <span class="text-muted extra-small fw-bold text-uppercase">Completed & Received</span>
                <span class="p-2 rounded-3 bg-success bg-opacity-10 text-success"><i class="fa-solid fa-circle-check"></i></span>
            </div>
            <h4 class="fw-extrabold text-success mb-0"><?= number_format($received_count) ?></h4>
            <span class="extra-small text-muted">Stock received in inventory</span>
        </div>
    </div>

    <div class="col-6 col-lg-3">
        <div class="card border-0 shadow-sm p-3 rounded-4 bg-white">
            <div class="d-flex align-items-center justify-content-between mb-2">
                <span class="text-muted extra-small fw-bold text-uppercase">Total Value</span>
                <span class="p-2 rounded-3 bg-primary bg-opacity-10 text-primary"><i class="fa-solid fa-indian-rupee-sign"></i></span>
            </div>
            <h4 class="fw-extrabold text-dark mb-0">₹<?= number_format($total_value, 2) ?></h4>
            <span class="extra-small text-muted"><?= number_format($total_units) ?> units in transit/stock</span>
        </div>
    </div>
</div>

<!-- Filters Bar -->
<div class="card border-0 shadow-sm p-3 mb-4 rounded-4 bg-white">
    <form method="GET" action="<?= BASE_URL ?>transfers/index.php" class="row g-2 align-items-center">
        <div class="col-md-4">
            <div class="input-group input-group-sm">
                <span class="input-group-text bg-light border-end-0"><i class="fa-solid fa-magnifying-glass text-muted"></i></span>
                <input type="text" name="search" class="form-control border-start-0" placeholder="Search Ref, Product, Sender, Receiver..." value="<?= htmlspecialchars($search_query) ?>">
            </div>
        </div>
        <div class="col-6 col-md-3">
            <select name="stage" class="form-select form-select-sm">
                <option value="">All Supply Chain Stages</option>
                <option value="manufacturer_to_supplier" <?= $stage_filter === 'manufacturer_to_supplier' ? 'selected' : '' ?>>Stage 1: Manufacturer → Supplier</option>
                <option value="supplier_to_shopkeeper" <?= $stage_filter === 'supplier_to_shopkeeper' ? 'selected' : '' ?>>Stage 2: Supplier → Shopkeeper</option>
            </select>
        </div>
        <div class="col-6 col-md-3">
            <select name="status" class="form-select form-select-sm">
                <option value="">All Statuses</option>
                <option value="In Transit" <?= $status_filter === 'In Transit' ? 'selected' : '' ?>>In Transit</option>
                <option value="Received" <?= $status_filter === 'Received' ? 'selected' : '' ?>>Received</option>
                <option value="Pending" <?= $status_filter === 'Pending' ? 'selected' : '' ?>>Pending</option>
                <option value="Cancelled" <?= $status_filter === 'Cancelled' ? 'selected' : '' ?>>Cancelled</option>
            </select>
        </div>
        <div class="col-md-2 d-flex gap-1">
            <button type="submit" class="btn btn-primary btn-sm w-100 fw-semibold">Filter</button>
            <?php if (!empty($search_query) || !empty($stage_filter) || !empty($status_filter)): ?>
                <a href="<?= BASE_URL ?>transfers/index.php" class="btn btn-light btn-sm"><i class="fa-solid fa-rotate-left"></i></a>
            <?php endif; ?>
        </div>
    </form>
</div>

<!-- Transfers Table -->
<div class="card border-0 shadow-sm rounded-4 bg-white overflow-hidden" id="transfers-table">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="bg-light text-secondary extra-small text-uppercase">
                <tr>
                    <th class="ps-3 py-3">Transfer Ref & Date</th>
                    <th>Product Details</th>
                    <th>Supply Chain Stage</th>
                    <th>Sender (From)</th>
                    <th>Receiver (To)</th>
                    <th class="text-center">Quantity</th>
                    <th>Total Value</th>
                    <th>Status</th>
                    <th class="text-end pe-3">Actions</th>
                </tr>
            </thead>
            <tbody class="small">
                <?php if (empty($transfers)): ?>
                    <tr>
                        <td colspan="9" class="text-center py-5 text-muted">
                            <i class="fa-solid fa-box-open fs-1 text-secondary opacity-50 mb-3 d-block"></i>
                            <h6 class="fw-bold mb-1">No Product Transfers Found</h6>
                            <p class="small text-muted mb-3">No transfer records match your current filter or role scope.</p>
                            <?php if ($user_role === 'manufacturer' || $user_role === 'supplier'): ?>
                                <a href="<?= BASE_URL ?>transfers/create.php" class="btn btn-primary btn-sm px-3 py-1.5 rounded-3">
                                    <i class="fa-solid fa-paper-plane me-1"></i> Initiate First Transfer
                                </a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($transfers as $t): ?>
                        <?php 
                            $is_recipient = ((int)$t['receiver_id'] === $user_id);
                            $can_receive = ($is_recipient && $t['status'] === 'In Transit');
                            
                            $status_class = 'bg-secondary';
                            if ($t['status'] === 'Received') $status_class = 'bg-success';
                            elseif ($t['status'] === 'In Transit') $status_class = 'bg-warning text-dark';
                            elseif ($t['status'] === 'Cancelled') $status_class = 'bg-danger';
                        ?>
                        <tr>
                            <td class="ps-3 py-3 font-monospace">
                                <div class="fw-bold text-dark"><?= htmlspecialchars($t['transfer_ref']) ?></div>
                                <div class="extra-small text-muted"><?= date('d M Y, h:i A', strtotime($t['transfer_date'])) ?></div>
                            </td>
                            <td>
                                <div class="fw-bold text-dark"><?= htmlspecialchars($t['product_name']) ?></div>
                                <div class="extra-small text-muted">
                                    <span class="badge bg-light text-dark font-monospace"><?= htmlspecialchars($t['product_code']) ?></span>
                                    &bull; <?= htmlspecialchars($t['category']) ?>
                                    <?php if (!empty($t['batch_number'])): ?>
                                        &bull; Batch: <span class="font-monospace text-primary"><?= htmlspecialchars($t['batch_number']) ?></span>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td>
                                <?php if ($t['stage'] === 'manufacturer_to_supplier'): ?>
                                    <span class="badge bg-primary bg-opacity-10 text-primary border border-primary-subtle extra-small">
                                        <i class="fa-solid fa-industry me-1"></i> MFR &rarr; <i class="fa-solid fa-truck-ramp-box ms-1 me-1"></i> SUP
                                    </span>
                                <?php else: ?>
                                    <span class="badge bg-success bg-opacity-10 text-success border border-success-subtle extra-small">
                                        <i class="fa-solid fa-truck-ramp-box me-1"></i> SUP &rarr; <i class="fa-solid fa-store ms-1 me-1"></i> SHOP
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="fw-semibold text-dark"><?= htmlspecialchars($t['sender_company'] ?: $t['sender_name']) ?></div>
                                <div class="extra-small text-muted"><i class="fa-solid fa-location-dot me-1"></i><?= htmlspecialchars($t['sender_city'] ?: 'India') ?> (<?= ucfirst($t['sender_role']) ?>)</div>
                            </td>
                            <td>
                                <div class="fw-semibold text-dark"><?= htmlspecialchars($t['receiver_company'] ?: ($t['receiver_shop'] ?: $t['receiver_name'])) ?></div>
                                <div class="extra-small text-muted"><i class="fa-solid fa-location-dot me-1"></i><?= htmlspecialchars($t['receiver_city'] ?: 'India') ?> (<?= ucfirst($t['receiver_role']) ?>)</div>
                            </td>
                            <td class="text-center font-monospace fw-bold fs-6 text-dark">
                                <?= number_format($t['quantity']) ?> <span class="extra-small fw-normal text-muted">pcs</span>
                            </td>
                            <td class="font-monospace">
                                <div class="fw-bold text-dark">₹<?= number_format($t['total_amount'], 2) ?></div>
                                <div class="extra-small text-muted">@ ₹<?= number_format($t['unit_price'], 2) ?>/pc</div>
                            </td>
                            <td>
                                <span class="badge <?= $status_class ?> extra-small px-2 py-1">
                                    <?php if ($t['status'] === 'In Transit'): ?>
                                        <i class="fa-solid fa-truck-moving me-1"></i>
                                    <?php elseif ($t['status'] === 'Received'): ?>
                                        <i class="fa-solid fa-circle-check me-1"></i>
                                    <?php endif; ?>
                                    <?= htmlspecialchars($t['status']) ?>
                                </span>
                                <?php if (!empty($t['received_date'])): ?>
                                    <div class="extra-small text-muted mt-0.5"><?= date('d M Y', strtotime($t['received_date'])) ?></div>
                                <?php endif; ?>
                            </td>
                            <td class="text-end pe-3">
                                <div class="btn-group btn-group-sm">
                                    <?php if ($can_receive): ?>
                                        <form method="POST" action="<?= BASE_URL ?>transfers/receive.php" class="d-inline" onsubmit="return confirm('Confirm receipt of <?= (int)$t['quantity'] ?> units of <?= htmlspecialchars($t['product_name']) ?> into your inventory?');">
                                            <?= csrf_input() ?>
                                            <input type="hidden" name="transfer_id" value="<?= (int)$t['id'] ?>">
                                            <button type="submit" class="btn btn-success btn-sm px-2.5 py-1 d-inline-flex align-items-center gap-1">
                                                <i class="fa-solid fa-circle-check"></i>
                                                <span>Accept & Receive</span>
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                    <a href="<?= BASE_URL ?>transfers/chain.php?id=<?= (int)$t['id'] ?>" class="btn btn-outline-secondary btn-sm px-2.5 py-1 d-inline-flex align-items-center gap-1" title="View Full Transfer Chain">
                                        <i class="fa-solid fa-network-wired text-primary"></i>
                                        <span>Chain</span>
                                    </a>
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
