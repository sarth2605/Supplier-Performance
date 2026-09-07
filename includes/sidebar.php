<?php
/**
 * Supplier Performance Analysis and Management System (SPAS)
 * Master Navigation Sidebar — Complete Enterprise Edition
 * Includes ALL system modules, workflows, operations, reports, and admin consoles
 */

$current_script = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
$current_query  = $_SERVER['QUERY_STRING'] ?? '';
$user_role      = strtolower($_SESSION['user_role'] ?? 'admin');
$user_name      = $_SESSION['user_name'] ?? 'System Administrator';
$brand_name     = $_SESSION['user_company'] ?? ($_SESSION['user_shop'] ?? APP_NAME);

// Role Palette
$role_badge_class = 'bg-danger';
$role_gradient = 'linear-gradient(135deg, #dc2626, #b91c1c)';
$role_icon = 'fa-user-shield';

if ($user_role === 'manufacturer') {
    $role_badge_class = 'bg-primary';
    $role_gradient = 'linear-gradient(135deg, #2563eb, #3b82f6)';
    $role_icon = 'fa-industry';
} elseif ($user_role === 'supplier') {
    $role_badge_class = 'bg-success';
    $role_gradient = 'linear-gradient(135deg, #059669, #10b981)';
    $role_icon = 'fa-truck-ramp-box';
} elseif ($user_role === 'shopkeeper') {
    $role_badge_class = 'bg-purple';
    $role_gradient = 'linear-gradient(135deg, #7c3aed, #8b5cf6)';
    $role_icon = 'fa-store';
}
?>

<aside class="app-sidebar text-white" id="app-sidebar">
    
    <!-- 1. Sidebar Header (Pinned Top) -->
    <div class="sidebar-header d-flex align-items-center justify-content-between">
        <a href="<?= BASE_URL ?>dashboard/index.php" class="text-decoration-none d-flex align-items-center gap-2.5 text-white overflow-hidden">
            <div class="brand-icon-box" style="background: <?= $role_gradient ?>;">
                <i class="fa-solid <?= $role_icon ?> text-white fs-5"></i>
            </div>
            <div class="d-flex flex-column brand-title overflow-hidden">
                <div class="d-flex align-items-center gap-1.5">
                    <span class="fw-extrabold fs-6 tracking-tight text-white"><?= APP_NAME ?></span>
                    <span class="badge rounded-pill extra-small px-1.5 py-0.5 text-white" style="background: rgba(255,255,255,0.15); font-size: 0.62rem;">v2.5</span>
                </div>
                <div class="d-flex align-items-center gap-1 mt-0.5">
                    <span class="badge <?= $role_badge_class ?> extra-small text-uppercase fw-bold px-2 py-0.5" style="<?= $user_role === 'shopkeeper' ? 'background: #8b5cf6 !important;' : '' ?>">
                        <?= ucfirst($user_role) ?> Console
                    </span>
                </div>
            </div>
        </a>
        <button class="btn btn-sm btn-link text-secondary d-lg-none p-0" id="mobile-sidebar-close" title="Close Sidebar">
            <i class="fa-solid fa-xmark fs-5"></i>
        </button>
    </div>

    <!-- 2. Scrollable Navigation Menu (Takes Remaining Height & Always Scrolls) -->
    <div class="sidebar-nav-wrapper custom-scrollbar">
        <ul class="nav nav-pills flex-column gap-0.5">
            
            <!-- SECTION 1: DASHBOARDS -->
            <li class="nav-header">
                <span class="nav-label">Core Command</span>
            </li>
            
            <li class="nav-item">
                <a class="nav-link <?= (strpos($current_script, '/index.php') !== false && strpos($current_script, '/dashboard/') === false) ? 'active' : '' ?>" href="<?= BASE_URL ?>index.php">
                    <i class="fa-solid fa-house nav-icon text-info"></i>
                    <span class="nav-label">Home Gateway</span>
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link <?= (strpos($current_script, '/dashboard/index.php') !== false) ? 'active' : '' ?>" href="<?= BASE_URL ?>dashboard/index.php">
                    <i class="fa-solid fa-gauge-high nav-icon"></i>
                    <span class="nav-label">Executive Dashboard</span>
                </a>
            </li>

            <!-- Stakeholder Dedicated Portals -->
            <?php if ($user_role === 'admin'): ?>
            <li class="nav-item">
                <a class="nav-link <?= strpos($current_script, '/manufacturer/dashboard.php') !== false ? 'active' : '' ?>" href="<?= BASE_URL ?>manufacturer/dashboard.php">
                    <i class="fa-solid fa-industry nav-icon text-primary"></i>
                    <span class="nav-label">Manufacturer Hub</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= strpos($current_script, '/supplier_portal/dashboard.php') !== false ? 'active' : '' ?>" href="<?= BASE_URL ?>supplier_portal/dashboard.php">
                    <i class="fa-solid fa-warehouse nav-icon text-success"></i>
                    <span class="nav-label">Supplier 360 Portal</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= strpos($current_script, '/shopkeeper/dashboard.php') !== false ? 'active' : '' ?>" href="<?= BASE_URL ?>shopkeeper/dashboard.php">
                    <i class="fa-solid fa-shop nav-icon text-purple" style="color: #a855f7;"></i>
                    <span class="nav-label">Shopkeeper Boutique</span>
                </a>
            </li>
            <?php endif; ?>

            <!-- SECTION 2: SUPPLY CHAIN WORKFLOW -->
            <li class="nav-header mt-2">
                <span class="nav-label">Supply Chain Flow</span>
            </li>

            <li class="nav-item">
                <a class="nav-link <?= strpos($current_script, '/products/') !== false ? 'active' : '' ?>" href="<?= BASE_URL ?>products/index.php">
                    <i class="fa-solid fa-boxes-stacked nav-icon"></i>
                    <span class="nav-label">Products Catalog</span>
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link <?= strpos($current_script, '/transfers/') !== false ? 'active' : '' ?>" href="<?= BASE_URL ?>transfers/index.php">
                    <i class="fa-solid fa-arrow-right-arrow-left nav-icon text-warning"></i>
                    <span class="nav-label fw-semibold">Product Transfers</span>
                    <span class="badge bg-warning bg-opacity-20 text-warning border border-warning border-opacity-30 extra-small ms-auto rounded-pill px-2 py-0.5">Flow</span>
                </a>
            </li>

            <!-- SECTION 3: STAKEHOLDERS -->
            <li class="nav-header mt-2">
                <span class="nav-label">Stakeholders</span>
            </li>

            <li class="nav-item">
                <a class="nav-link <?= strpos($current_script, '/manufacturers/') !== false ? 'active' : '' ?>" href="<?= BASE_URL ?>manufacturers/index.php">
                    <i class="fa-solid fa-flask-vial nav-icon"></i>
                    <span class="nav-label">Manufacturers</span>
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link <?= (strpos($current_script, '/suppliers/') !== false && strpos($current_script, '/supplier_portal/') === false) ? 'active' : '' ?>" href="<?= BASE_URL ?>suppliers/index.php">
                    <i class="fa-solid fa-truck-ramp-box nav-icon"></i>
                    <span class="nav-label">Suppliers</span>
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link <?= strpos($current_script, '/shopkeepers/') !== false ? 'active' : '' ?>" href="<?= BASE_URL ?>shopkeepers/index.php">
                    <i class="fa-solid fa-store nav-icon"></i>
                    <span class="nav-label">Shopkeepers</span>
                </a>
            </li>

            <!-- SECTION 4: PROCUREMENT & OPERATIONS -->
            <li class="nav-header mt-2">
                <span class="nav-label">Operations & Quality</span>
            </li>

            <li class="nav-item">
                <a class="nav-link <?= strpos($current_script, '/purchase_orders/') !== false ? 'active' : '' ?>" href="<?= BASE_URL ?>purchase_orders/index.php">
                    <i class="fa-solid fa-file-invoice-dollar nav-icon"></i>
                    <span class="nav-label">Purchase Orders</span>
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link <?= strpos($current_script, '/deliveries/') !== false ? 'active' : '' ?>" href="<?= BASE_URL ?>deliveries/index.php">
                    <i class="fa-solid fa-truck-fast nav-icon"></i>
                    <span class="nav-label">Deliveries Logistics</span>
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link <?= strpos($current_script, '/quality/') !== false ? 'active' : '' ?>" href="<?= BASE_URL ?>quality/index.php">
                    <i class="fa-solid fa-shield-halved nav-icon text-info"></i>
                    <span class="nav-label">Quality Inspections</span>
                </a>
            </li>

            <!-- SECTION 5: ANALYSIS & RANKINGS -->
            <li class="nav-header mt-2">
                <span class="nav-label">Analysis & Ranking</span>
            </li>

            <li class="nav-item">
                <a class="nav-link <?= (strpos($current_script, '/performance/index.php') !== false || strpos($current_script, '/performance/supplier.php') !== false) ? 'active' : '' ?>" href="<?= BASE_URL ?>performance/index.php">
                    <i class="fa-solid fa-chart-simple nav-icon"></i>
                    <span class="nav-label">Supplier Performance</span>
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link <?= strpos($current_script, '/performance/ranking.php') !== false ? 'active' : '' ?>" href="<?= BASE_URL ?>performance/ranking.php">
                    <i class="fa-solid fa-trophy nav-icon text-warning"></i>
                    <span class="nav-label">Supplier Ranking</span>
                    <span class="badge bg-warning text-dark extra-small ms-auto rounded-pill px-1.5 py-0.2">Top</span>
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link <?= (strpos($current_script, '/performance/comparison.php') !== false || strpos($current_script, '/comparison/') !== false) ? 'active' : '' ?>" href="<?= BASE_URL ?>performance/comparison.php">
                    <i class="fa-solid fa-scale-balanced nav-icon"></i>
                    <span class="nav-label">Supplier Comparison</span>
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link <?= strpos($current_script, '/performance/trends.php') !== false ? 'active' : '' ?>" href="<?= BASE_URL ?>performance/trends.php">
                    <i class="fa-solid fa-arrow-trend-up nav-icon text-info"></i>
                    <span class="nav-label">Performance Trends</span>
                </a>
            </li>

            <!-- SECTION 6: REPORTS STUDIO -->
            <li class="nav-header mt-2">
                <span class="nav-label">Reports Studio</span>
            </li>

            <li class="nav-item">
                <a class="nav-link <?= (strpos($current_script, '/reports/') !== false && empty($_GET['report_type'])) ? 'active' : '' ?>" href="<?= BASE_URL ?>reports/index.php">
                    <i class="fa-solid fa-layer-group nav-icon text-primary"></i>
                    <span class="nav-label fw-semibold">Reports Studio</span>
                    <span class="badge bg-primary extra-small ms-auto rounded-pill px-2 py-0.5">12 Reports</span>
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link <?= (strpos($current_script, '/reports/') !== false && ($_GET['report_type'] ?? '') === 'supplier_performance') ? 'active' : '' ?>" href="<?= BASE_URL ?>reports/index.php?report_type=supplier_performance">
                    <i class="fa-solid fa-chart-line nav-icon"></i>
                    <span class="nav-label">Supplier Reports</span>
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link <?= (strpos($current_script, '/reports/') !== false && ($_GET['report_type'] ?? '') === 'product_transfers') ? 'active' : '' ?>" href="<?= BASE_URL ?>reports/index.php?report_type=product_transfers">
                    <i class="fa-solid fa-route nav-icon"></i>
                    <span class="nav-label">Transfer Reports</span>
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link <?= (strpos($current_script, '/reports/') !== false && ($_GET['report_type'] ?? '') === 'quality_report') ? 'active' : '' ?>" href="<?= BASE_URL ?>reports/index.php?report_type=quality_report">
                    <i class="fa-solid fa-microscope nav-icon"></i>
                    <span class="nav-label">Quality Reports</span>
                </a>
            </li>

            <!-- SECTION 7: ADMINISTRATION -->
            <li class="nav-header mt-2">
                <span class="nav-label">Governance & System</span>
            </li>

            <li class="nav-item">
                <a class="nav-link <?= (strpos($current_script, '/users/') !== false && strpos($current_script, '/users/profile.php') === false) ? 'active' : '' ?>" href="<?= BASE_URL ?>users/index.php">
                    <i class="fa-solid fa-users-gear nav-icon"></i>
                    <span class="nav-label">Users Control</span>
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link <?= strpos($current_script, '/settings/') !== false ? 'active' : '' ?>" href="<?= BASE_URL ?>settings/index.php">
                    <i class="fa-solid fa-sliders nav-icon"></i>
                    <span class="nav-label">System Settings</span>
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link <?= strpos($current_script, '/notifications/') !== false ? 'active' : '' ?>" href="<?= BASE_URL ?>notifications/index.php">
                    <i class="fa-solid fa-bell nav-icon"></i>
                    <span class="nav-label">System Alerts</span>
                </a>
            </li>

        </ul>
    </div>

    <!-- 3. Pinned User Profile Card & Quick Sign Out (Pinned Bottom) -->
    <div class="sidebar-user-footer p-2.5 border-top border-secondary border-opacity-25" style="background: #060910; flex-shrink: 0;">
        <div class="sidebar-user-pill d-flex align-items-center justify-content-between gap-2">
            <a href="<?= BASE_URL ?>users/profile.php" class="d-flex align-items-center gap-2 text-decoration-none text-truncate flex-grow-1">
                <div class="avatar-circle rounded-circle text-white d-flex align-items-center justify-content-center fw-bold flex-shrink-0" style="width: 32px; height: 32px; font-size: 0.8rem; background: <?= $role_gradient ?>;">
                    <?= strtoupper(substr($user_name, 0, 1)) ?>
                </div>
                <div class="text-truncate" style="line-height: 1.2;">
                    <div class="text-white fw-semibold small text-truncate"><?= htmlspecialchars($user_name) ?></div>
                    <div class="text-muted extra-small text-truncate"><?= htmlspecialchars($brand_name) ?></div>
                </div>
            </a>
            
            <!-- Quick Logout Action -->
            <a href="<?= BASE_URL ?>auth/logout.php" class="btn btn-sm btn-link text-muted p-1 hover-danger text-decoration-none" title="Sign Out">
                <i class="fa-solid fa-arrow-right-from-bracket text-secondary hover-text-danger fs-6"></i>
            </a>
        </div>
    </div>

</aside>

<!-- Backdrop overlay for mobile sidebar -->
<div class="sidebar-backdrop" id="sidebar-backdrop"></div>
