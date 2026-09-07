<?php
/**
 * Supplier Performance Analysis and Management System (SPAS)
 * Product Transfer Provenance & Full Supply Chain History
 * Sequential Animated Timeline: Manufacturer -> Supplier -> Shopkeeper
 */

$page_title = 'Supply Chain Provenance & Transfer Chain';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/transfer_functions.php';

$transfer_id = (int)($_GET['id'] ?? 0);
if ($transfer_id <= 0) {
    set_flash('danger', 'Invalid transfer identifier.');
    header('Location: ' . BASE_URL . 'transfers/index.php');
    exit;
}

$chain_data = get_transfer_chain_details($transfer_id);
if (empty($chain_data['current'])) {
    set_flash('danger', 'Transfer record not found.');
    header('Location: ' . BASE_URL . 'transfers/index.php');
    exit;
}

$current = $chain_data['current'];
$parent = $chain_data['parent'];
$children = $chain_data['children'];

// Determine initial origin manufacturer
$manufacturer_info = null;
$supplier_info = null;
$shopkeeper_info = null;

if ($current['stage'] === 'manufacturer_to_supplier') {
    $manufacturer_info = [
        'name' => $current['sender_company'] ?: $current['sender_name'],
        'city' => $current['sender_city'],
        'date' => $current['transfer_date'],
        'quantity' => $current['quantity'],
        'status' => 'Dispatched'
    ];
    $supplier_info = [
        'name' => $current['receiver_company'] ?: $current['receiver_name'],
        'city' => $current['receiver_city'],
        'date' => $current['received_date'],
        'quantity' => $current['quantity'],
        'status' => $current['status']
    ];
    if (!empty($children)) {
        $first_child = $children[0];
        $shopkeeper_info = [
            'name' => $first_child['receiver_shop'] ?: $first_child['receiver_name'],
            'city' => $first_child['receiver_city'],
            'date' => $first_child['received_date'],
            'quantity' => $first_child['quantity'],
            'status' => $first_child['status']
        ];
    }
} elseif ($current['stage'] === 'supplier_to_shopkeeper') {
    if ($parent) {
        $manufacturer_info = [
            'name' => $parent['sender_company'] ?: $parent['sender_name'],
            'city' => $parent['sender_city'],
            'date' => $parent['transfer_date'],
            'quantity' => $parent['quantity'],
            'status' => 'Dispatched'
        ];
    } else {
        $manufacturer_info = [
            'name' => 'Original Formulation Lab',
            'city' => 'Origin Lab',
            'date' => $current['transfer_date'],
            'quantity' => $current['quantity'],
            'status' => 'Formulated'
        ];
    }
    $supplier_info = [
        'name' => $current['sender_company'] ?: $current['sender_name'],
        'city' => $current['sender_city'],
        'date' => $current['transfer_date'],
        'quantity' => $current['quantity'],
        'status' => 'Dispatched'
    ];
    $shopkeeper_info = [
        'name' => $current['receiver_shop'] ?: $current['receiver_name'],
        'city' => $current['receiver_city'],
        'date' => $current['received_date'],
        'quantity' => $current['quantity'],
        'status' => $current['status']
    ];
}

// Determine Current Physical Location and Owner
$current_owner = '';
$current_owner_role = '';
if ($current['status'] === 'Received') {
    $current_owner = $current['receiver_company'] ?: ($current['receiver_shop'] ?: $current['receiver_name']);
    $current_owner_role = ucfirst($current['receiver_role']);
} else {
    $current_owner = 'In Transit Carrier (En route from ' . ($current['sender_company'] ?: $current['sender_name']) . ' → ' . ($current['receiver_company'] ?: ($current['receiver_shop'] ?: $current['receiver_name'])) . ')';
    $current_owner_role = 'Logistics Escrow';
}
?>

<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 mb-4 animate-slide-up">
    <div>
        <a href="<?= BASE_URL ?>transfers/index.php" class="text-decoration-none text-muted small d-inline-flex align-items-center gap-1.5 mb-1">
            <i class="fa-solid fa-arrow-left extra-small"></i> Back to All Transfers
        </a>
        <h1 class="h4 fw-extrabold text-dark mb-0">Supply Chain Provenance & Transfer Lineage</h1>
    </div>
    <div class="d-flex align-items-center gap-2">
        <span class="badge bg-white text-dark border px-3 py-2 font-monospace fs-6 shadow-xs">
            Ref: <?= htmlspecialchars($current['transfer_ref']) ?>
        </span>
    </div>
</div>

<!-- Product & Current Ownership Banner -->
<div class="card-saas mb-4 animate-slide-up">
    <div class="card-saas-body p-4">
        <div class="row g-3 align-items-center">
            
            <div class="col-12 col-md-7">
                <span class="text-uppercase extra-small fw-bold text-muted d-block mb-1">Authenticated Product Batch</span>
                <h3 class="h4 fw-extrabold text-dark mb-1">
                    <?= htmlspecialchars($current['product_name']) ?>
                    <span class="badge bg-primary bg-opacity-10 text-primary font-monospace ms-2 fs-6 border border-primary border-opacity-25">
                        <?= htmlspecialchars($current['product_code']) ?>
                    </span>
                </h3>
                <div class="text-muted small mt-1">
                    Batch: <span class="fw-bold font-monospace text-dark"><?= htmlspecialchars($current['batch_number'] ?? 'N/A') ?></span> &bull; 
                    Category: <span class="badge bg-light text-dark border"><?= htmlspecialchars($current['category']) ?></span> &bull; 
                    Brand: <strong><?= htmlspecialchars($current['brand']) ?></strong>
                </div>
            </div>

            <!-- Current Owner Alert Box -->
            <div class="col-12 col-md-5">
                <div class="p-3 rounded-3 border bg-light bg-opacity-50">
                    <span class="extra-small text-muted text-uppercase fw-bold d-block mb-1">Current Physical Location & Owner</span>
                    <div class="fw-bold text-dark fs-6 d-flex align-items-center gap-2">
                        <i class="fa-solid fa-shield-halved text-success"></i>
                        <span><?= htmlspecialchars($current_owner) ?></span>
                    </div>
                    <div class="d-flex align-items-center justify-content-between mt-2 pt-2 border-top extra-small">
                        <span class="text-muted">Custody Stage: <strong><?= $current_owner_role ?></strong></span>
                        <span class="badge <?= $current['status'] === 'Received' ? 'badge-status-received' : 'badge-status-in-transit' ?> px-2.5 py-1 rounded-pill">
                            <?= htmlspecialchars($current['status']) ?>
                        </span>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<!-- ==========================================
     SECTION 13: ANIMATED 4-STEP TIMELINE
     Step 1: Product Formulation
     Step 2: Transferred to Supplier
     Step 3: Supplier Received in Hub
     Step 4: Shopkeeper Received in Store
     ========================================== -->
<div class="card-saas mb-4">
    <div class="card-saas-header">
        <div>
            <h5 class="section-title"><i class="fa-solid fa-timeline text-primary"></i> 4-Stage Sequential Transfer Timeline</h5>
            <span class="text-muted extra-small">Animated custody transfer progression: Manufacturer &rarr; Supplier &rarr; Shopkeeper</span>
        </div>
        <span class="badge bg-light text-dark border extra-small">Auto-Verified Chain</span>
    </div>

    <div class="card-saas-body p-4 p-md-5">
        <div class="row g-4 position-relative">
            
            <!-- STEP 1: Formulation & Batch Creation (Manufacturer) -->
            <div class="col-12 col-md-6 col-lg-3 animate-slide-up" style="animation-delay: 80ms;">
                <div class="kpi-card h-100" style="--kpi-color: #2563eb;">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="badge bg-primary text-white rounded-pill px-2.5 py-1 extra-small fw-bold">STEP 1</span>
                        <i class="fa-solid fa-flask-vial fs-4 text-primary"></i>
                    </div>
                    <h6 class="fw-bold text-dark mb-1">1. Product Formulation</h6>
                    <div class="small text-primary fw-semibold"><?= htmlspecialchars($manufacturer_info['name'] ?? 'Production Laboratory') ?></div>
                    <div class="extra-small text-muted mb-3"><i class="fa-solid fa-location-dot me-1"></i><?= htmlspecialchars($manufacturer_info['city'] ?? 'Facility') ?></div>
                    
                    <div class="pt-2 border-top extra-small mt-auto">
                        <div><strong>Batch Created:</strong> <?= $manufacturer_info ? date('d M Y', strtotime($manufacturer_info['date'])) : 'Pending' ?></div>
                        <div><strong>Origin Qty:</strong> <?= $manufacturer_info ? number_format($manufacturer_info['quantity']) . ' pcs' : '—' ?></div>
                    </div>
                </div>
            </div>

            <!-- STEP 2: Dispatched to Supplier Hub -->
            <div class="col-12 col-md-6 col-lg-3 animate-slide-up" style="animation-delay: 200ms;">
                <div class="kpi-card h-100" style="--kpi-color: #f59e0b;">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="badge bg-warning text-dark rounded-pill px-2.5 py-1 extra-small fw-bold">STEP 2</span>
                        <i class="fa-solid fa-truck-arrow-right fs-4 text-warning"></i>
                    </div>
                    <h6 class="fw-bold text-dark mb-1">2. Dispatched to Supplier</h6>
                    <div class="small text-warning fw-semibold"><?= htmlspecialchars($supplier_info['name'] ?? 'Supplier Hub') ?></div>
                    <div class="extra-small text-muted mb-3"><i class="fa-solid fa-location-dot me-1"></i>Destination: <?= htmlspecialchars($supplier_info['city'] ?? 'Hub') ?></div>
                    
                    <div class="pt-2 border-top extra-small mt-auto">
                        <div><strong>Ship Date:</strong> <?= $manufacturer_info ? date('d M Y', strtotime($manufacturer_info['date'])) : '—' ?></div>
                        <div><strong>In-Transit Qty:</strong> <?= $manufacturer_info ? number_format($manufacturer_info['quantity']) . ' pcs' : '—' ?></div>
                    </div>
                </div>
            </div>

            <!-- STEP 3: Supplier Received in Hub -->
            <div class="col-12 col-md-6 col-lg-3 animate-slide-up" style="animation-delay: 320ms;">
                <div class="kpi-card h-100 <?= $supplier_info['status'] === 'Received' ? '' : 'opacity-75' ?>" style="--kpi-color: #10b981;">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="badge bg-success text-white rounded-pill px-2.5 py-1 extra-small fw-bold">STEP 3</span>
                        <i class="fa-solid fa-truck-ramp-box fs-4 text-success"></i>
                    </div>
                    <h6 class="fw-bold text-dark mb-1">3. Supplier Hub Intake</h6>
                    <div class="small text-success fw-semibold"><?= htmlspecialchars($supplier_info['name'] ?? 'Distribution Hub') ?></div>
                    <div class="extra-small text-muted mb-3"><i class="fa-solid fa-boxes-packing me-1"></i>Central Distribution Stock</div>
                    
                    <div class="pt-2 border-top extra-small mt-auto">
                        <div><strong>Hub Intake Date:</strong> <?= ($supplier_info && !empty($supplier_info['date'])) ? date('d M Y', strtotime($supplier_info['date'])) : ($supplier_info['status'] === 'In Transit' ? '<span class="text-warning">In Transit</span>' : 'Pending') ?></div>
                        <div><strong>Stock Level:</strong> <?= $supplier_info ? number_format($supplier_info['quantity']) . ' pcs' : '—' ?></div>
                    </div>
                </div>
            </div>

            <!-- STEP 4: Retail Delivery to Shopkeeper -->
            <div class="col-12 col-md-6 col-lg-3 animate-slide-up" style="animation-delay: 440ms;">
                <div class="kpi-card h-100 <?= $shopkeeper_info ? '' : 'opacity-75' ?>" style="--kpi-color: #8b5cf6;">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="badge text-white rounded-pill px-2.5 py-1 extra-small fw-bold" style="background: #8b5cf6;">STEP 4</span>
                        <i class="fa-solid fa-store fs-4" style="color: #8b5cf6;"></i>
                    </div>
                    <h6 class="fw-bold text-dark mb-1">4. Shopkeeper Store Intake</h6>
                    <div class="small fw-semibold" style="color: #7c3aed;"><?= htmlspecialchars($shopkeeper_info['name'] ?? 'Retail Storefront') ?></div>
                    <div class="extra-small text-muted mb-3"><i class="fa-solid fa-location-dot me-1"></i><?= htmlspecialchars($shopkeeper_info['city'] ?? 'Retail Boutique') ?></div>
                    
                    <div class="pt-2 border-top extra-small mt-auto">
                        <div><strong>Store Delivery:</strong> <?= ($shopkeeper_info && !empty($shopkeeper_info['date'])) ? date('d M Y', strtotime($shopkeeper_info['date'])) : ($shopkeeper_info && $shopkeeper_info['status'] === 'In Transit' ? '<span class="text-warning">In Transit to Store</span>' : 'Pending Dispatch') ?></div>
                        <div><strong>Retail Stock:</strong> <?= $shopkeeper_info ? number_format($shopkeeper_info['quantity']) . ' pcs' : 'Awaiting dispatch' ?></div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<!-- ==========================================
     TRANSFER AUDIT TRAIL SPECIFICATIONS
     ========================================== -->
<div class="card-saas animate-slide-up">
    <div class="card-saas-header">
        <h5 class="section-title"><i class="fa-solid fa-file-lines text-secondary"></i> Transfer Audit Trail & Invoicing Specifications</h5>
    </div>
    <div class="card-saas-body">
        <div class="row g-3 small">
            <div class="col-md-3">
                <span class="text-muted extra-small d-block">Transfer Reference</span>
                <span class="font-monospace fw-bold text-dark fs-6"><?= htmlspecialchars($current['transfer_ref']) ?></span>
            </div>
            <div class="col-md-3">
                <span class="text-muted extra-small d-block">Pipeline Stage</span>
                <span class="fw-bold text-dark"><?= $current['stage'] === 'manufacturer_to_supplier' ? 'Stage 1 (Manufacturer → Supplier)' : 'Stage 2 (Supplier → Shopkeeper)' ?></span>
            </div>
            <div class="col-md-3">
                <span class="text-muted extra-small d-block">Dispatch Date & Time</span>
                <span class="fw-semibold text-dark"><?= date('d M Y, h:i A', strtotime($current['transfer_date'])) ?></span>
            </div>
            <div class="col-md-3">
                <span class="text-muted extra-small d-block">Receipt Confirmation Date</span>
                <span class="fw-semibold text-dark"><?= !empty($current['received_date']) ? date('d M Y, h:i A', strtotime($current['received_date'])) : '<span class="text-muted">Awaiting recipient signature</span>' ?></span>
            </div>
            
            <div class="col-md-3">
                <span class="text-muted extra-small d-block">Quantity Transferred</span>
                <span class="fw-bold text-dark font-monospace fs-6"><?= number_format($current['quantity']) ?> pcs</span>
            </div>
            <div class="col-md-3">
                <span class="text-muted extra-small d-block">Unit Transfer Rate</span>
                <span class="fw-bold text-dark font-monospace">₹<?= number_format($current['unit_price'], 2) ?></span>
            </div>
            <div class="col-md-3">
                <span class="text-muted extra-small d-block">Total Shipment Value</span>
                <span class="fw-extrabold text-success font-monospace fs-6">₹<?= number_format($current['total_amount'], 2) ?></span>
            </div>
            <div class="col-md-3">
                <span class="text-muted extra-small d-block">Batch Allocation</span>
                <span class="badge bg-light text-dark font-monospace border"><?= htmlspecialchars($current['batch_number'] ?? 'N/A') ?></span>
            </div>
            
            <div class="col-12 pt-2 border-top">
                <span class="text-muted extra-small d-block mb-1">Logistics Handling Notes</span>
                <div class="p-2.5 rounded-3 bg-light text-secondary fst-italic">
                    <?= htmlspecialchars($current['remarks'] ?: 'Standard commercial cosmetics packaging under cold-chain compliance.') ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
