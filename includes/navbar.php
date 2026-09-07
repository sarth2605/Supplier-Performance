<?php
/**
 * Supplier Performance Analysis and Management System
 * Top Navigation Bar Component
 */

$current_user = get_current_user_data();
$db = get_db();

// Fetch unread notifications or delayed deliveries for notification dropdown
$delayed_count = 0;
$defect_alerts_count = 0;
try {
    $delayed_count = (int)$db->query("SELECT COUNT(*) FROM deliveries WHERE delivery_status = 'Delayed'")->fetchColumn();
    $defect_alerts_count = (int)$db->query("SELECT COUNT(*) FROM quality_inspections WHERE defect_rate > 3.0")->fetchColumn();
} catch (Exception $e) {}

$total_alerts = $delayed_count + $defect_alerts_count;
?>

<header class="top-navbar bg-surface border-bottom px-3 px-md-4 d-flex align-items-center justify-content-between sticky-top">
    
    <!-- Left: Mobile Sidebar Toggle & Page Context -->
    <div class="d-flex align-items-center gap-3">
        <button class="btn btn-light btn-icon d-lg-none border" id="mobile-sidebar-toggle" title="Toggle Navigation">
            <i class="fa-solid fa-bars"></i>
        </button>
        <button class="btn btn-light btn-icon d-none d-lg-inline-flex border" id="desktop-sidebar-toggle" title="Collapse Sidebar">
            <i class="fa-solid fa-bars-staggered"></i>
        </button>
        
        <div class="d-none d-sm-block">
            <h5 class="fw-bold mb-0 text-dark"><?= htmlspecialchars($page_title ?? 'Dashboard') ?></h5>
        </div>
    </div>

    <!-- Right: Global Search, Alerts Dropdown, User Profile -->
    <div class="d-flex align-items-center gap-3">
        
        <!-- Quick Search Form -->
        <form class="d-none d-md-block" method="GET" action="<?= BASE_URL ?>suppliers/index.php">
            <div class="input-group input-group-sm" style="width: 240px;">
                <span class="input-group-text bg-light border-end-0 text-muted"><i class="fa-solid fa-magnifying-glass"></i></span>
                <input type="text" name="search" class="form-control bg-light border-start-0 ps-0" placeholder="Search suppliers, POs...">
            </div>
        </form>

        <!-- Alerts & Notifications Dropdown -->
        <div class="dropdown">
            <button class="btn btn-light btn-icon border position-relative" type="button" data-bs-toggle="dropdown" aria-expanded="false" title="System Alerts">
                <i class="fa-regular fa-bell text-secondary"></i>
                <?php if ($total_alerts > 0): ?>
                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="font-size: 0.65rem;">
                        <?= $total_alerts ?>
                    </span>
                <?php endif; ?>
            </button>
            <ul class="dropdown-menu dropdown-menu-end shadow-lg border-0 p-2" style="width: 320px; border-radius: var(--radius-lg);">
                <li class="p-2 border-bottom d-flex justify-content-between align-items-center">
                    <span class="fw-bold small text-dark">System Alerts & Risks</span>
                    <span class="badge bg-danger-subtle text-danger extra-small"><?= $total_alerts ?> Active</span>
                </li>
                <?php if ($delayed_count > 0): ?>
                    <li class="p-2 border-bottom">
                        <a href="<?= BASE_URL ?>deliveries/index.php?status=Delayed" class="text-decoration-none text-dark d-flex gap-2 align-items-start">
                            <i class="fa-solid fa-truck-fast text-warning mt-1"></i>
                            <div>
                                <div class="fw-semibold extra-small"><?= $delayed_count ?> Deliveries Flagged Delayed</div>
                                <div class="text-muted extra-small">Logistics delay detected against expected dates.</div>
                            </div>
                        </a>
                    </li>
                <?php endif; ?>
                <?php if ($defect_alerts_count > 0): ?>
                    <li class="p-2 border-bottom">
                        <a href="<?= BASE_URL ?>quality/index.php" class="text-decoration-none text-dark d-flex gap-2 align-items-start">
                            <i class="fa-solid fa-shield-halved text-danger mt-1"></i>
                            <div>
                                <div class="fw-semibold extra-small"><?= $defect_alerts_count ?> High Defect Rate Inspections</div>
                                <div class="text-muted extra-small">Quality threshold of > 3.0% exceeded.</div>
                            </div>
                        </a>
                    </li>
                <?php endif; ?>
                <?php if ($total_alerts === 0): ?>
                    <li class="p-3 text-center text-muted extra-small">
                        <i class="fa-solid fa-circle-check text-success fs-4 d-block mb-1"></i>
                        All vendor deliveries and inspections in compliance!
                    </li>
                <?php endif; ?>
            </ul>
        </div>

        <!-- User Profile Dropdown -->
        <div class="dropdown">
            <button class="btn btn-light d-flex align-items-center gap-2 border p-1 pe-3 rounded-pill" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                <div class="avatar-circle">
                    <?= strtoupper(substr($current_user['name'] ?? 'U', 0, 1)) ?>
                </div>
                <div class="text-start d-none d-sm-block">
                    <div class="fw-bold extra-small text-dark text-truncate" style="max-width: 120px;"><?= htmlspecialchars($current_user['name'] ?? 'User') ?></div>
                    <div class="extra-small text-muted text-capitalize"><?= htmlspecialchars($current_user['role'] ?? 'Staff') ?></div>
                </div>
                <i class="fa-solid fa-chevron-down text-muted extra-small ms-1"></i>
            </button>
            <ul class="dropdown-menu dropdown-menu-end shadow-lg border-0 p-2" style="width: 220px; border-radius: var(--radius-lg);">
                <li class="p-2 border-bottom">
                    <div class="fw-bold small text-dark"><?= htmlspecialchars($current_user['name'] ?? 'User') ?></div>
                    <div class="text-muted extra-small text-truncate"><?= htmlspecialchars($current_user['email'] ?? '') ?></div>
                    <span class="badge bg-primary extra-small mt-1 text-uppercase"><?= htmlspecialchars($current_user['role'] ?? 'staff') ?></span>
                </li>
                <li><a class="dropdown-item small py-2 rounded-2 mt-1" href="<?= BASE_URL ?>index.php"><i class="fa-solid fa-house me-2 text-info"></i> System Home Page</a></li>
                <li><a class="dropdown-item small py-2 rounded-2" href="<?= BASE_URL ?>settings/index.php"><i class="fa-solid fa-user-gear me-2 text-primary"></i> Account Settings</a></li>
                <?php if ($current_user['role'] === 'admin'): ?>
                    <li><a class="dropdown-item small py-2 rounded-2" href="<?= BASE_URL ?>users/index.php"><i class="fa-solid fa-users-gear me-2 text-info"></i> User Administration</a></li>
                <?php endif; ?>
                <li><hr class="dropdown-divider my-1"></li>
                <li><a class="dropdown-item small py-2 rounded-2 text-danger" href="<?= BASE_URL ?>auth/logout.php"><i class="fa-solid fa-arrow-right-from-bracket me-2"></i> Sign Out</a></li>
            </ul>
        </div>

    </div>

</header>
