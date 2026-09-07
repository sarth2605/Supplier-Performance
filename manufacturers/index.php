<?php
/**
 * Supplier Performance Analysis and Management System (SPAS)
 * Manufacturers Directory & Production Network
 */

$page_title = 'Manufacturers Directory';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/transfer_functions.php';

$db = get_db();
$user_data = get_current_user_data();
$user_role = strtolower($user_data['role']);

// Query all registered manufacturers with stats
$query = "
    SELECT u.id, u.name, u.email, u.company_name, u.phone, u.city, u.state, u.status, u.created_at,
           COUNT(DISTINCT pt.id) as total_dispatches,
           COALESCE(SUM(CASE WHEN pt.status = 'Received' THEN pt.quantity ELSE 0 END), 0) as units_transferred
    FROM users u
    LEFT JOIN product_transfers pt ON u.id = pt.sender_id
    WHERE u.role = 'manufacturer'
    GROUP BY u.id
    ORDER BY u.created_at DESC
";
$manufacturers = $db->query($query)->fetchAll();
?>

<div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
    <div>
        <h3 class="fw-bold text-dark mb-1">
            <i class="fa-solid fa-industry text-primary me-2"></i>Manufacturers & Production Laboratories
        </h3>
        <p class="text-muted small mb-0">Tier 1 cosmetics formulation facilities, R&D labs and primary producers in the supply chain.</p>
    </div>
    
    <div>
        <a href="<?= BASE_URL ?>auth/manufacturer_register.php" class="btn btn-outline-primary btn-sm px-3 py-2 rounded-3 fw-semibold">
            <i class="fa-solid fa-plus me-1"></i> Register New Manufacturer
        </a>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card border-0 shadow-sm p-3 rounded-4 bg-white">
            <div class="d-flex align-items-center justify-content-between mb-2">
                <span class="text-muted extra-small fw-bold text-uppercase">Registered Labs</span>
                <span class="p-2 rounded-3 bg-primary bg-opacity-10 text-primary"><i class="fa-solid fa-industry"></i></span>
            </div>
            <h4 class="fw-extrabold text-dark mb-0"><?= count($manufacturers) ?></h4>
            <span class="extra-small text-muted">Active cosmetics manufacturers</span>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm rounded-4 bg-white overflow-hidden">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="bg-light text-secondary extra-small text-uppercase">
                <tr>
                    <th class="ps-3 py-3">Manufacturing Facility</th>
                    <th>Contact Person</th>
                    <th>Location</th>
                    <th>Phone / Email</th>
                    <th class="text-center">Transfers Sent</th>
                    <th>Units Supplied</th>
                    <th>Status</th>
                    <th class="text-end pe-3">Action</th>
                </tr>
            </thead>
            <tbody class="small">
                <?php if (empty($manufacturers)): ?>
                    <tr>
                        <td colspan="8" class="text-center py-5 text-muted">
                            <i class="fa-solid fa-industry fs-1 text-secondary opacity-50 mb-3 d-block"></i>
                            <h6 class="fw-bold mb-1">No Registered Manufacturers Yet</h6>
                            <p class="small text-muted mb-3">Manufacturers can register via the Manufacturer Registration portal.</p>
                            <a href="<?= BASE_URL ?>auth/manufacturer_register.php" class="btn btn-primary btn-sm px-3 py-1.5 rounded-3">
                                Register First Manufacturer
                            </a>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($manufacturers as $m): ?>
                        <tr>
                            <td class="ps-3 py-3">
                                <div class="fw-bold text-dark"><?= htmlspecialchars($m['company_name'] ?: $m['name']) ?></div>
                                <div class="extra-small text-muted">Joined <?= date('M Y', strtotime($m['created_at'])) ?></div>
                            </td>
                            <td class="fw-semibold text-secondary">
                                <?= htmlspecialchars($m['name']) ?>
                            </td>
                            <td>
                                <i class="fa-solid fa-location-dot text-danger me-1"></i>
                                <?= htmlspecialchars($m['city'] ?: 'Mumbai') ?>, <?= htmlspecialchars($m['state'] ?: 'Maharashtra') ?>
                            </td>
                            <td>
                                <div class="text-dark"><i class="fa-solid fa-envelope extra-small text-muted me-1"></i><?= htmlspecialchars($m['email']) ?></div>
                                <?php if (!empty($m['phone'])): ?>
                                    <div class="extra-small text-muted"><i class="fa-solid fa-phone extra-small text-muted me-1"></i><?= htmlspecialchars($m['phone']) ?></div>
                                <?php endif; ?>
                            </td>
                            <td class="text-center font-monospace fw-bold">
                                <?= number_format($m['total_dispatches']) ?>
                            </td>
                            <td class="font-monospace fw-bold text-success">
                                <?= number_format($m['units_transferred']) ?> pcs
                            </td>
                            <td>
                                <span class="badge <?= $m['status'] === 'Active' ? 'bg-success' : 'bg-secondary' ?> extra-small px-2 py-1">
                                    <?= htmlspecialchars($m['status']) ?>
                                </span>
                            </td>
                            <td class="text-end pe-3">
                                <a href="<?= BASE_URL ?>transfers/index.php?search=<?= urlencode($m['name']) ?>" class="btn btn-outline-secondary btn-sm px-2.5 py-1">
                                    <i class="fa-solid fa-arrow-right-arrow-left me-1"></i> Transfers
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
