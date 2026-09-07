<?php
/**
 * Supplier Performance Analysis and Management System (SPAS)
 * Executive Reports Studio & Business Analytics Engine
 * Real MySQL Database Queries across 12 Standard Analytical Reports
 */

$page_title = "Reports Studio — Business Analytics";
require_once __DIR__ . '/../includes/header.php';
require_login();

$db = get_db();
$current_user = get_current_user_data();
$user_id = (int)$current_user['id'];
$user_role = strtolower($current_user['role'] ?? 'admin');

// -------------------------------------------------------------
// FILTER PARAMETERS
// -------------------------------------------------------------
$report_type = trim($_GET['report_type'] ?? 'supplier_performance');
$date_from = trim($_GET['date_from'] ?? '');
$date_to = trim($_GET['date_to'] ?? '');
$filter_supplier = (int)($_GET['supplier_id'] ?? 0);
$filter_category = trim($_GET['category'] ?? '');
$filter_status = trim($_GET['status'] ?? '');
$search = trim($_GET['search'] ?? '');
$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 15;
$offset = ($page - 1) * $limit;

// Export trigger
$is_export = (($_GET['export'] ?? '') === 'csv');

// -------------------------------------------------------------
// REPORT DEFINITIONS REGISTRY
// -------------------------------------------------------------
$report_definitions = [
    'supplier_performance' => ['title' => 'Supplier Performance Report', 'icon' => 'fa-solid fa-chart-line'],
    'supplier_ranking'     => ['title' => 'Supplier Ranking Report', 'icon' => 'fa-solid fa-trophy'],
    'purchase_order'       => ['title' => 'Purchase Order Report', 'icon' => 'fa-solid fa-file-invoice-dollar'],
    'delivery_report'      => ['title' => 'Delivery Punctuality Report', 'icon' => 'fa-solid fa-truck-fast'],
    'quality_report'       => ['title' => 'Quality & Defect Audit Report', 'icon' => 'fa-solid fa-microscope'],
    'product_transfers'    => ['title' => 'Product Transfers & Chain Report', 'icon' => 'fa-solid fa-arrow-right-arrow-left'],
    'traceability_report'  => ['title' => 'Product Traceability & Provenance Report', 'icon' => 'fa-solid fa-route'],
    'manufacturer_report'  => ['title' => 'Manufacturer Activity Report', 'icon' => 'fa-solid fa-industry'],
    'supplier_report'      => ['title' => 'Supplier Hub Distribution Report', 'icon' => 'fa-solid fa-boxes-packing'],
    'shopkeeper_report'    => ['title' => 'Shopkeeper Retail Intake Report', 'icon' => 'fa-solid fa-store'],
    'product_report'       => ['title' => 'Product Master Catalog Report', 'icon' => 'fa-solid fa-tag'],
    'system_overview'      => ['title' => 'Overall Enterprise System Report', 'icon' => 'fa-solid fa-sitemap']
];

if (!array_key_exists($report_type, $report_definitions)) {
    $report_type = 'supplier_performance';
}

// -------------------------------------------------------------
// EXECUTIVE SUMMARY METRICS (TOP CARDS)
// -------------------------------------------------------------
$total_suppliers = (int)($db->query("SELECT COUNT(*) FROM suppliers WHERE status = 'Active'")->fetchColumn() ?: 0);
$total_manufacturers = (int)($db->query("SELECT COUNT(*) FROM users WHERE role = 'manufacturer'")->fetchColumn() ?: 0);
$total_shopkeepers = (int)($db->query("SELECT COUNT(*) FROM users WHERE role = 'shopkeeper'")->fetchColumn() ?: 0);
$total_products = (int)($db->query("SELECT COUNT(*) FROM products")->fetchColumn() ?: 0);
$total_orders = (int)($db->query("SELECT COUNT(*) FROM purchase_orders")->fetchColumn() ?: 0);
$total_deliveries = (int)($db->query("SELECT COUNT(*) FROM deliveries")->fetchColumn() ?: 0);
$total_transfers = (int)($db->query("SELECT COUNT(*) FROM product_transfers")->fetchColumn() ?: 0);
$avg_supplier_performance = (float)($db->query("SELECT COALESCE(ROUND(AVG(overall_score), 1), 0) FROM performance_scores")->fetchColumn() ?: 0);

// Filter Dropdown Options
$all_suppliers = $db->query("SELECT id, supplier_name, supplier_code FROM suppliers ORDER BY supplier_name ASC")->fetchAll();
$all_categories = $db->query("SELECT DISTINCT category FROM products WHERE category IS NOT NULL AND category != '' ORDER BY category ASC")->fetchAll();

// -------------------------------------------------------------
// DYNAMIC SQL BUILDER PER REPORT
// -------------------------------------------------------------
$report_rows = [];
$total_records = 0;
$summary_data = [];
$chart_data = ['labels' => [], 'datasets' => []];

switch ($report_type) {

    // 1. SUPPLIER PERFORMANCE REPORT
    case 'supplier_performance':
        $where = ["1=1"];
        $params = [];
        if ($filter_supplier > 0) { $where[] = "s.id = ?"; $params[] = $filter_supplier; }
        if (!empty($filter_category)) { $where[] = "s.category = ?"; $params[] = $filter_category; }
        if (!empty($filter_status)) { $where[] = "s.status = ?"; $params[] = $filter_status; }
        if (!empty($search)) { $where[] = "(s.supplier_name LIKE ? OR s.supplier_code LIKE ?)"; $params[] = "%$search%"; $params[] = "%$search%"; }

        $where_sql = implode(' AND ', $where);

        $count_query = "SELECT COUNT(*) FROM suppliers s WHERE $where_sql";
        $c_stmt = $db->prepare($count_query);
        $c_stmt->execute($params);
        $total_records = (int)$c_stmt->fetchColumn();

        $query = "
            SELECT s.id, s.supplier_code, s.supplier_name, s.category, s.status,
                   COALESCE(ps.delivery_score, 90.0) as delivery_score,
                   COALESCE(ps.quality_score, 92.0) as quality_score,
                   COALESCE(ps.cost_score, 88.0) as cost_score,
                   COALESCE(ps.fulfillment_score, 91.0) as reliability_score,
                   COALESCE(ps.overall_score, 90.5) as overall_score,
                   COALESCE(ps.grade, 'A') as grade,
                   (SELECT COUNT(*) FROM purchase_orders po WHERE po.supplier_id = s.id) as total_orders,
                   (SELECT COUNT(*) FROM purchase_orders po WHERE po.supplier_id = s.id AND po.status = 'Delivered') as completed_orders,
                   (SELECT COUNT(*) FROM deliveries d JOIN purchase_orders po ON d.purchase_order_id = po.id WHERE po.supplier_id = s.id AND d.delivery_status = 'Delayed') as delayed_orders
            FROM suppliers s
            LEFT JOIN performance_scores ps ON s.id = ps.supplier_id
            WHERE $where_sql
            ORDER BY overall_score DESC
            " . ($is_export ? "" : "LIMIT $limit OFFSET $offset");
        $stmt = $db->prepare($query);
        $stmt->execute($params);
        $report_rows = $stmt->fetchAll();

        // Chart Data (Top 8 for bar chart)
        $top_perf = array_slice($report_rows, 0, 8);
        $chart_data['labels'] = array_map(fn($r) => $r['supplier_name'], $top_perf);
        $chart_data['datasets'] = [
            [
                'label' => 'Overall Score (%)',
                'data' => array_map(fn($r) => (float)$r['overall_score'], $top_perf),
                'backgroundColor' => '#2563eb'
            ],
            [
                'label' => 'Delivery Score (%)',
                'data' => array_map(fn($r) => (float)$r['delivery_score'], $top_perf),
                'backgroundColor' => '#10b981'
            ],
            [
                'label' => 'Quality Score (%)',
                'data' => array_map(fn($r) => (float)$r['quality_score'], $top_perf),
                'backgroundColor' => '#8b5cf6'
            ]
        ];
        break;

    // 2. SUPPLIER RANKING REPORT
    case 'supplier_ranking':
        $query = "
            SELECT s.supplier_code, s.supplier_name, s.category,
                   COALESCE(ps.delivery_score, 90) as delivery_score,
                   COALESCE(ps.quality_score, 92) as quality_score,
                   COALESCE(ps.cost_score, 88) as cost_score,
                   COALESCE(ps.fulfillment_score, 91) as reliability_score,
                   COALESCE(ps.overall_score, 90.5) as overall_score,
                   COALESCE(ps.grade, 'A') as grade
            FROM suppliers s
            LEFT JOIN performance_scores ps ON s.id = ps.supplier_id
            ORDER BY ps.overall_score DESC
            " . ($is_export ? "" : "LIMIT $limit OFFSET $offset");
        $report_rows = $db->query($query)->fetchAll();
        $total_records = count($report_rows);
        break;

    // 3. PURCHASE ORDER REPORT
    case 'purchase_order':
        $where = ["1=1"];
        $params = [];
        if ($filter_supplier > 0) { $where[] = "po.supplier_id = ?"; $params[] = $filter_supplier; }
        if (!empty($filter_status)) { $where[] = "po.status = ?"; $params[] = $filter_status; }
        if (!empty($date_from)) { $where[] = "po.order_date >= ?"; $params[] = $date_from; }
        if (!empty($date_to)) { $where[] = "po.order_date <= ?"; $params[] = $date_to; }
        if (!empty($search)) { $where[] = "(po.po_number LIKE ? OR s.supplier_name LIKE ?)"; $params[] = "%$search%"; $params[] = "%$search%"; }

        $where_sql = implode(' AND ', $where);

        $count_query = "SELECT COUNT(*) FROM purchase_orders po JOIN suppliers s ON po.supplier_id = s.id WHERE $where_sql";
        $c_stmt = $db->prepare($count_query);
        $c_stmt->execute($params);
        $total_records = (int)$c_stmt->fetchColumn();

        $query = "
            SELECT po.id, po.po_number, po.order_date, po.expected_date, po.total_amount, po.status,
                   s.supplier_name, s.supplier_code
            FROM purchase_orders po
            JOIN suppliers s ON po.supplier_id = s.id
            WHERE $where_sql
            ORDER BY po.order_date DESC
            " . ($is_export ? "" : "LIMIT $limit OFFSET $offset");
        $stmt = $db->prepare($query);
        $stmt->execute($params);
        $report_rows = $stmt->fetchAll();

        // Summary calculations
        $sum_stmt = $db->prepare("
            SELECT 
                COUNT(*) as total_orders,
                COALESCE(SUM(total_amount), 0) as total_val,
                SUM(CASE WHEN status = 'Pending' THEN 1 ELSE 0 END) as pending_cnt,
                SUM(CASE WHEN status = 'Delivered' THEN 1 ELSE 0 END) as delivered_cnt,
                SUM(CASE WHEN status = 'Cancelled' THEN 1 ELSE 0 END) as cancelled_cnt
            FROM purchase_orders po
            JOIN suppliers s ON po.supplier_id = s.id
            WHERE $where_sql
        ");
        $sum_stmt->execute($params);
        $summary_data = $sum_stmt->fetch();
        break;

    // 4. DELIVERY REPORT
    case 'delivery_report':
        $where = ["1=1"];
        $params = [];
        if ($filter_supplier > 0) { $where[] = "po.supplier_id = ?"; $params[] = $filter_supplier; }
        if (!empty($filter_status)) { $where[] = "d.delivery_status = ?"; $params[] = $filter_status; }
        if (!empty($date_from)) { $where[] = "d.delivery_date >= ?"; $params[] = $date_from; }
        if (!empty($date_to)) { $where[] = "d.delivery_date <= ?"; $params[] = $date_to; }

        $where_sql = implode(' AND ', $where);

        $query = "
            SELECT d.id, d.delivery_date, d.quantity_received, d.delivery_status, d.delay_days, d.remarks,
                   po.po_number, po.expected_date, s.supplier_name
            FROM deliveries d
            JOIN purchase_orders po ON d.purchase_order_id = po.id
            JOIN suppliers s ON po.supplier_id = s.id
            WHERE $where_sql
            ORDER BY d.delivery_date DESC
            " . ($is_export ? "" : "LIMIT $limit OFFSET $offset");
        $stmt = $db->prepare($query);
        $stmt->execute($params);
        $report_rows = $stmt->fetchAll();
        $total_records = count($report_rows);

        // Deliveries summary
        $on_time = 0; $delayed = 0; $partial = 0;
        foreach ($report_rows as $r) {
            if ($r['delivery_status'] === 'On Time') $on_time++;
            elseif ($r['delivery_status'] === 'Delayed') $delayed++;
            elseif ($r['delivery_status'] === 'Partial') $partial++;
        }
        $summary_data = [
            'total' => count($report_rows),
            'on_time' => $on_time,
            'delayed' => $delayed,
            'partial' => $partial,
            'rate' => count($report_rows) > 0 ? round(($on_time / count($report_rows)) * 100, 1) : 100
        ];
        break;

    // 5. QUALITY REPORT
    case 'quality_report':
        $query = "
            SELECT qi.*, po.po_number, s.supplier_name, p.product_name
            FROM quality_inspections qi
            JOIN deliveries d ON qi.delivery_id = d.id
            JOIN purchase_orders po ON d.purchase_order_id = po.id
            JOIN suppliers s ON po.supplier_id = s.id
            LEFT JOIN products p ON s.id = p.supplier_id
            ORDER BY qi.inspection_date DESC
            " . ($is_export ? "" : "LIMIT $limit OFFSET $offset");
        $report_rows = $db->query($query)->fetchAll();
        $total_records = count($report_rows);

        $tot_inspected = 0; $tot_defective = 0; $tot_score = 0;
        foreach ($report_rows as $r) {
            $tot_inspected += (int)$r['quantity_received'];
            $tot_defective += (int)$r['quantity_defective'];
            $tot_score += (float)$r['quality_score'];
        }
        $summary_data = [
            'inspections_count' => count($report_rows),
            'total_units' => $tot_inspected,
            'defective_units' => $tot_defective,
            'avg_score' => count($report_rows) > 0 ? round($tot_score / count($report_rows), 1) : 100
        ];
        break;

    // 6. PRODUCT TRANSFERS REPORT
    case 'product_transfers':
        $where = ["1=1"];
        $params = [];
        if (!empty($filter_status)) { $where[] = "pt.status = ?"; $params[] = $filter_status; }
        if (!empty($search)) { $where[] = "(pt.transfer_ref LIKE ? OR p.product_name LIKE ?)"; $params[] = "%$search%"; $params[] = "%$search%"; }

        $where_sql = implode(' AND ', $where);

        $query = "
            SELECT pt.*, p.product_name, p.product_code,
                   u_s.name as sender_name, u_s.company_name as sender_company, u_s.role as sender_role_name,
                   u_r.name as receiver_name, u_r.company_name as receiver_company, u_r.shop_name as receiver_shop, u_r.role as receiver_role_name
            FROM product_transfers pt
            JOIN products p ON pt.product_id = p.id
            JOIN users u_s ON pt.sender_id = u_s.id
            JOIN users u_r ON pt.receiver_id = u_r.id
            WHERE $where_sql
            ORDER BY pt.transfer_date DESC
            " . ($is_export ? "" : "LIMIT $limit OFFSET $offset");
        $stmt = $db->prepare($query);
        $stmt->execute($params);
        $report_rows = $stmt->fetchAll();
        $total_records = count($report_rows);
        break;

    // 7. PRODUCT TRACEABILITY & PROVENANCE
    case 'traceability_report':
        $query = "
            SELECT p.id as product_id, p.product_name, p.product_code, p.category, p.brand,
                   u_mfr.company_name as manufacturer_name,
                   (SELECT COUNT(*) FROM product_transfers WHERE product_id = p.id) as transfer_hops,
                   (SELECT COALESCE(SUM(quantity), 0) FROM product_transfers WHERE product_id = p.id) as volume_moved,
                   (SELECT status FROM product_transfers WHERE product_id = p.id ORDER BY id DESC LIMIT 1) as latest_status,
                   (SELECT u2.name FROM product_transfers pt2 JOIN users u2 ON pt2.receiver_id = u2.id WHERE pt2.product_id = p.id ORDER BY pt2.id DESC LIMIT 1) as current_custodian
            FROM products p
            LEFT JOIN users u_mfr ON p.manufacturer_id = u_mfr.id
            ORDER BY p.id ASC
            " . ($is_export ? "" : "LIMIT $limit OFFSET $offset");
        $report_rows = $db->query($query)->fetchAll();
        $total_records = count($report_rows);
        break;

    // 8. MANUFACTURER ACTIVITY REPORT
    case 'manufacturer_report':
        $query = "
            SELECT u.id, u.name, u.company_name, u.city, u.email,
                   (SELECT COUNT(*) FROM products WHERE manufacturer_id = u.id) as catalog_products,
                   (SELECT COALESCE(SUM(quantity), 0) FROM product_transfers WHERE sender_id = u.id) as units_transferred,
                   (SELECT COUNT(DISTINCT receiver_id) FROM product_transfers WHERE sender_id = u.id) as connected_suppliers,
                   (SELECT COUNT(*) FROM product_transfers WHERE sender_id = u.id AND status = 'In Transit') as in_transit_shipments
            FROM users u
            WHERE u.role = 'manufacturer'
        ";
        $report_rows = $db->query($query)->fetchAll();
        $total_records = count($report_rows);
        break;

    // 9. SUPPLIER DISTRIBUTION REPORT
    case 'supplier_report':
        $query = "
            SELECT s.id, s.supplier_code, s.supplier_name, s.category, s.city,
                   COALESCE(ps.overall_score, 90.0) as overall_score,
                   COALESCE(ps.grade, 'A') as grade,
                   (SELECT COALESCE(SUM(quantity), 0) FROM product_transfers pt JOIN users u ON pt.receiver_id = u.id WHERE u.supplier_id = s.id AND pt.status = 'Received') as units_received,
                   (SELECT COALESCE(SUM(quantity), 0) FROM product_transfers pt JOIN users u ON pt.sender_id = u.id WHERE u.supplier_id = s.id) as units_forwarded
            FROM suppliers s
            LEFT JOIN performance_scores ps ON s.id = ps.supplier_id
            ORDER BY s.supplier_name ASC
        ";
        $report_rows = $db->query($query)->fetchAll();
        $total_records = count($report_rows);
        break;

    // 10. SHOPKEEPER INTAKE REPORT
    case 'shopkeeper_report':
        $query = "
            SELECT u.id, u.name, u.shop_name, u.city, u.phone,
                   (SELECT COUNT(*) FROM product_transfers WHERE receiver_id = u.id AND status = 'Received') as shipments_received,
                   (SELECT COALESCE(SUM(quantity), 0) FROM product_transfers WHERE receiver_id = u.id AND status = 'Received') as units_received,
                   (SELECT COALESCE(SUM(quantity), 0) FROM user_inventory WHERE user_id = u.id) as current_shelf_stock
            FROM users u
            WHERE u.role = 'shopkeeper'
        ";
        $report_rows = $db->query($query)->fetchAll();
        $total_records = count($report_rows);
        break;

    // 11. PRODUCT REPORT
    case 'product_report':
        $where = ["1=1"];
        $params = [];
        if (!empty($filter_category)) { $where[] = "p.category = ?"; $params[] = $filter_category; }
        if (!empty($search)) { $where[] = "(p.product_name LIKE ? OR p.product_code LIKE ?)"; $params[] = "%$search%"; $params[] = "%$search%"; }
        $where_sql = implode(' AND ', $where);

        $query = "
            SELECT p.*, s.supplier_name, u_mfr.company_name as manufacturer_name
            FROM products p
            LEFT JOIN suppliers s ON p.supplier_id = s.id
            LEFT JOIN users u_mfr ON p.manufacturer_id = u_mfr.id
            WHERE $where_sql
            ORDER BY p.product_name ASC
            " . ($is_export ? "" : "LIMIT $limit OFFSET $offset");
        $stmt = $db->prepare($query);
        $stmt->execute($params);
        $report_rows = $stmt->fetchAll();
        $total_records = count($report_rows);
        break;

    // 12. OVERALL SYSTEM REPORT
    case 'system_overview':
    default:
        $report_rows = [
            ['metric' => 'Total Registered Manufacturers', 'value' => $total_manufacturers, 'status' => 'Active R&D Labs'],
            ['metric' => 'Total Cosmetics Supplier Hubs', 'value' => $total_suppliers, 'status' => 'Audited & Ranked'],
            ['metric' => 'Total Retail Shopkeeper Stores', 'value' => $total_shopkeepers, 'status' => 'Retail Boutiques'],
            ['metric' => 'Active Cosmetics Product Lines', 'value' => $total_products, 'status' => 'SKUs Across 8 Categories'],
            ['metric' => 'Total Procurement Purchase Orders', 'value' => $total_orders, 'status' => 'Commercial POs'],
            ['metric' => 'Total Logistics Deliveries Audited', 'value' => $total_deliveries, 'status' => 'Punctuality Tracked'],
            ['metric' => 'Total Supply Chain Transfers', 'value' => $total_transfers, 'status' => 'Batch Movement Audited'],
            ['metric' => 'Average Supplier Evaluation Score', 'value' => $avg_supplier_performance . '%', 'status' => 'System Baseline']
        ];
        $total_records = count($report_rows);
        break;
}

// -------------------------------------------------------------
// CSV EXPORT ENGINE
// -------------------------------------------------------------
if ($is_export && !empty($report_rows)) {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $report_type . '_' . date('Ymd_His') . '.csv"');
    $output = fopen('php://output', 'w');

    // Headers
    $first_row = (array)$report_rows[0];
    fputcsv($output, array_keys($first_row));

    // Data rows
    foreach ($report_rows as $row) {
        fputcsv($output, array_values((array)$row));
    }
    fclose($output);
    exit;
}
?>

<!-- =============================================================
     PAGE HEADER & TITLE
     ============================================================= -->
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4 animate-slide-up">
    <div>
        <div class="d-flex align-items-center gap-2 mb-1">
            <h1 class="h3 fw-extrabold text-dark mb-0">Reports Studio</h1>
            <span class="badge bg-primary bg-opacity-10 text-primary font-monospace extra-small px-2.5 py-1">Enterprise Analytics</span>
        </div>
        <p class="text-muted small mb-0">Generate, analyze and export business, supplier performance, and multi-tier supply chain audits.</p>
    </div>

    <!-- Quick Export Controls -->
    <div class="d-flex align-items-center gap-2">
        <a href="<?= $_SERVER['REQUEST_URI'] . (strpos($_SERVER['REQUEST_URI'], '?') !== false ? '&' : '?') ?>export=csv" class="btn btn-outline-success btn-sm rounded-3 shadow-xs fw-semibold">
            <i class="fa-solid fa-file-csv me-1.5"></i> Export CSV
        </a>
        <button onclick="window.print()" class="btn btn-light border btn-sm rounded-3 fw-semibold">
            <i class="fa-solid fa-print me-1.5"></i> Print / PDF
        </button>
    </div>
</div>

<!-- =============================================================
     SECTION 3: REPORT SUMMARY CARDS (REAL DATABASE QUERIES)
     ============================================================= -->
<div class="row g-3 mb-4">
    
    <div class="col-6 col-md-3 col-xl-1-5 col-lg-3 animate-slide-up" style="animation-delay: 20ms;">
        <div class="kpi-card" style="--kpi-color: #2563eb;">
            <span class="kpi-title">Total Suppliers</span>
            <div class="kpi-value counter-value" data-target="<?= $total_suppliers ?>">0</div>
            <div class="kpi-subtext">Active Partners</div>
        </div>
    </div>

    <div class="col-6 col-md-3 col-xl-1-5 col-lg-3 animate-slide-up" style="animation-delay: 40ms;">
        <div class="kpi-card" style="--kpi-color: #06b6d4;">
            <span class="kpi-title">Manufacturers</span>
            <div class="kpi-value counter-value" data-target="<?= $total_manufacturers ?>">0</div>
            <div class="kpi-subtext">Formulation Labs</div>
        </div>
    </div>

    <div class="col-6 col-md-3 col-xl-1-5 col-lg-3 animate-slide-up" style="animation-delay: 60ms;">
        <div class="kpi-card" style="--kpi-color: #ec4899;">
            <span class="kpi-title">Shopkeepers</span>
            <div class="kpi-value counter-value" data-target="<?= $total_shopkeepers ?>">0</div>
            <div class="kpi-subtext">Retail Boutiques</div>
        </div>
    </div>

    <div class="col-6 col-md-3 col-xl-1-5 col-lg-3 animate-slide-up" style="animation-delay: 80ms;">
        <div class="kpi-card" style="--kpi-color: #10b981;">
            <span class="kpi-title">Total Products</span>
            <div class="kpi-value counter-value" data-target="<?= $total_products ?>">0</div>
            <div class="kpi-subtext">Catalog Formulations</div>
        </div>
    </div>

    <div class="col-6 col-md-3 col-xl-1-5 col-lg-3 animate-slide-up" style="animation-delay: 100ms;">
        <div class="kpi-card" style="--kpi-color: #f59e0b;">
            <span class="kpi-title">Purchase Orders</span>
            <div class="kpi-value counter-value" data-target="<?= $total_orders ?>">0</div>
            <div class="kpi-subtext">Procurement POs</div>
        </div>
    </div>

    <div class="col-6 col-md-3 col-xl-1-5 col-lg-3 animate-slide-up" style="animation-delay: 120ms;">
        <div class="kpi-card" style="--kpi-color: #3b82f6;">
            <span class="kpi-title">Deliveries</span>
            <div class="kpi-value counter-value" data-target="<?= $total_deliveries ?>">0</div>
            <div class="kpi-subtext">Audited Shipments</div>
        </div>
    </div>

    <div class="col-6 col-md-3 col-xl-1-5 col-lg-3 animate-slide-up" style="animation-delay: 140ms;">
        <div class="kpi-card" style="--kpi-color: #8b5cf6;">
            <span class="kpi-title">Product Transfers</span>
            <div class="kpi-value counter-value" data-target="<?= $total_transfers ?>">0</div>
            <div class="kpi-subtext">Custody Hops</div>
        </div>
    </div>

    <div class="col-6 col-md-3 col-xl-1-5 col-lg-3 animate-slide-up" style="animation-delay: 160ms;">
        <div class="kpi-card" style="--kpi-color: #14b8a6;">
            <span class="kpi-title">Avg Performance</span>
            <div class="kpi-value counter-value" data-target="<?= $avg_supplier_performance ?>" data-suffix="%">0%</div>
            <div class="kpi-subtext">Ecosystem Health</div>
        </div>
    </div>

</div>

<!-- =============================================================
     SECTION 2: 12 REPORT SELECTOR TABS
     ============================================================= -->
<div class="card-saas mb-4">
    <div class="card-saas-body p-2">
        <div class="d-flex flex-wrap gap-1">
            <?php foreach ($report_definitions as $r_key => $r_info): ?>
                <?php $is_active = ($r_key === $report_type); ?>
                <a href="?report_type=<?= $r_key ?>" class="btn btn-sm rounded-pill px-3 py-1.5 extra-small fw-semibold <?= $is_active ? 'btn-primary shadow-xs' : 'btn-light text-muted' ?>">
                    <i class="<?= $r_info['icon'] ?> me-1"></i> <?= $r_info['title'] ?>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<!-- =============================================================
     SECTION 1: ADVANCED FILTER PANEL
     ============================================================= -->
<div class="card-saas mb-4 animate-slide-up">
    <div class="card-saas-header">
        <h6 class="section-title"><i class="fa-solid fa-filter text-primary"></i> Report Filter Console</h6>
        <span class="extra-small text-muted">Filter database records dynamically</span>
    </div>
    <div class="card-saas-body">
        <form method="GET" action="<?= htmlspecialchars($_SERVER['PHP_SELF']) ?>">
            <input type="hidden" name="report_type" value="<?= htmlspecialchars($report_type) ?>">
            
            <div class="row g-3">
                <div class="col-12 col-md-3">
                    <label class="form-label extra-small fw-semibold text-muted">Date From</label>
                    <input type="date" name="date_from" class="form-control form-control-sm" value="<?= htmlspecialchars($date_from) ?>">
                </div>
                <div class="col-12 col-md-3">
                    <label class="form-label extra-small fw-semibold text-muted">Date To</label>
                    <input type="date" name="date_to" class="form-control form-control-sm" value="<?= htmlspecialchars($date_to) ?>">
                </div>
                
                <div class="col-12 col-md-3">
                    <label class="form-label extra-small fw-semibold text-muted">Supplier Partner</label>
                    <select name="supplier_id" class="form-select form-select-sm">
                        <option value="">All Suppliers</option>
                        <?php foreach ($all_suppliers as $s): ?>
                            <option value="<?= $s['id'] ?>" <?= $filter_supplier == $s['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($s['supplier_name']) ?> (<?= htmlspecialchars($s['supplier_code']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-12 col-md-3">
                    <label class="form-label extra-small fw-semibold text-muted">Product Category</label>
                    <select name="category" class="form-select form-select-sm">
                        <option value="">All Categories</option>
                        <?php foreach ($all_categories as $c): ?>
                            <option value="<?= htmlspecialchars($c['category']) ?>" <?= $filter_category == $c['category'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($c['category']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-12 col-md-4">
                    <label class="form-label extra-small fw-semibold text-muted">Search Keyword</label>
                    <input type="text" name="search" class="form-control form-control-sm" placeholder="PO code, supplier name, transfer ref..." value="<?= htmlspecialchars($search) ?>">
                </div>

                <div class="col-12 col-md-4">
                    <label class="form-label extra-small fw-semibold text-muted">Lifecycle Status</label>
                    <select name="status" class="form-select form-select-sm">
                        <option value="">All Statuses</option>
                        <option value="Active" <?= $filter_status == 'Active' ? 'selected' : '' ?>>Active</option>
                        <option value="Delivered" <?= $filter_status == 'Delivered' ? 'selected' : '' ?>>Delivered</option>
                        <option value="In Transit" <?= $filter_status == 'In Transit' ? 'selected' : '' ?>>In Transit</option>
                        <option value="Received" <?= $filter_status == 'Received' ? 'selected' : '' ?>>Received</option>
                        <option value="Delayed" <?= $filter_status == 'Delayed' ? 'selected' : '' ?>>Delayed</option>
                    </select>
                </div>

                <div class="col-12 col-md-4 d-flex align-items-end gap-2">
                    <button type="submit" class="btn btn-primary btn-sm flex-grow-1 rounded-3 fw-semibold">
                        <i class="fa-solid fa-magnifying-glass me-1"></i> Apply Filters
                    </button>
                    <a href="?report_type=<?= htmlspecialchars($report_type) ?>" class="btn btn-light border btn-sm px-3 rounded-3">
                        Reset
                    </a>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- =============================================================
     SUMMARY METRIC CALLOUTS (IF AVAILABLE)
     ============================================================= -->
<?php if (!empty($summary_data)): ?>
    <div class="row g-3 mb-4">
        <?php foreach ($summary_data as $metric_label => $metric_val): ?>
            <?php if (is_numeric($metric_val)): ?>
                <div class="col-6 col-md-3">
                    <div class="p-3 bg-white border rounded-3 shadow-xs">
                        <span class="text-uppercase extra-small fw-bold text-muted d-block"><?= ucwords(str_replace('_', ' ', $metric_label)) ?></span>
                        <span class="fs-5 fw-extrabold text-dark font-monospace"><?= is_float($metric_val) ? number_format($metric_val, 2) : number_format($metric_val) ?></span>
                    </div>
                </div>
            <?php endif; ?>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<!-- =============================================================
     ANALYTICAL REPORT DATA TABLE
     ============================================================= -->
<div class="card-saas animate-slide-up">
    <div class="card-saas-header">
        <div>
            <h5 class="section-title"><i class="<?= $report_definitions[$report_type]['icon'] ?> text-primary"></i> <?= $report_definitions[$report_type]['title'] ?></h5>
            <span class="extra-small text-muted">Showing <?= count($report_rows) ?> records matching selected parameters</span>
        </div>
        <span class="badge bg-light text-dark font-monospace border extra-small">Live MySQL Stream</span>
    </div>

    <div class="table-responsive">
        <?php if (empty($report_rows)): ?>
            <div class="text-center py-5">
                <i class="fa-solid fa-file-circle-question fs-1 text-muted opacity-50 mb-3 d-block"></i>
                <h6 class="fw-bold text-dark mb-1">No data available for the selected filters.</h6>
                <p class="text-muted small mb-3">Adjust your date range, supplier filter, or category search criteria.</p>
                <a href="?report_type=<?= htmlspecialchars($report_type) ?>" class="btn btn-outline-primary btn-sm px-3 rounded-3">
                    <i class="fa-solid fa-rotate-left me-1"></i> Reset Filters
                </a>
            </div>
        <?php else: ?>
            <table class="table table-custom table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <?php if ($report_type === 'supplier_performance'): ?>
                            <th class="ps-3">Code</th>
                            <th>Supplier Name</th>
                            <th>Category</th>
                            <th class="text-center">Orders (Delivered)</th>
                            <th class="text-center">Delivery</th>
                            <th class="text-center">Quality</th>
                            <th class="text-center">Cost</th>
                            <th class="text-center">Reliability</th>
                            <th class="text-center">Overall</th>
                            <th class="text-center">Grade</th>
                            <th class="text-end pe-3">Profile</th>

                        <?php elseif ($report_type === 'supplier_ranking'): ?>
                            <th class="ps-3">Rank</th>
                            <th>Supplier Partner</th>
                            <th>Category</th>
                            <th class="text-center">Delivery Score</th>
                            <th class="text-center">Quality Score</th>
                            <th class="text-center">Cost Score</th>
                            <th class="text-center">Reliability</th>
                            <th class="text-center">Overall Score</th>
                            <th class="text-end pe-3">Grade</th>

                        <?php elseif ($report_type === 'purchase_order'): ?>
                            <th class="ps-3">PO Number</th>
                            <th>Supplier</th>
                            <th>Order Date</th>
                            <th>Expected Delivery</th>
                            <th class="text-end">Amount</th>
                            <th class="text-end pe-3">Status</th>

                        <?php elseif ($report_type === 'delivery_report'): ?>
                            <th class="ps-3">PO Number</th>
                            <th>Supplier</th>
                            <th>Expected Date</th>
                            <th>Delivered Date</th>
                            <th class="text-center">Qty Received</th>
                            <th class="text-center">Delay</th>
                            <th class="text-end pe-3">Punctuality</th>

                        <?php elseif ($report_type === 'quality_report'): ?>
                            <th class="ps-3">PO Reference</th>
                            <th>Supplier Partner</th>
                            <th>Inspection Date</th>
                            <th class="text-center">Units Inspected</th>
                            <th class="text-center">Defects</th>
                            <th class="text-center">Defect Rate</th>
                            <th class="text-center">Quality Score</th>
                            <th class="text-end pe-3">Status</th>

                        <?php elseif ($report_type === 'product_transfers'): ?>
                            <th class="ps-3">Transfer Ref</th>
                            <th>Product Name</th>
                            <th>Stage & Direction</th>
                            <th>From &rarr; To</th>
                            <th class="text-center">Quantity</th>
                            <th>Transfer Date</th>
                            <th class="text-end pe-3">Status</th>

                        <?php elseif ($report_type === 'traceability_report'): ?>
                            <th class="ps-3">SKU Code</th>
                            <th>Product Name</th>
                            <th>Category</th>
                            <th>Manufacturer Lab</th>
                            <th class="text-center">Custody Hops</th>
                            <th class="text-center">Total Moved</th>
                            <th class="text-end pe-3">Current Custodian</th>

                        <?php elseif ($report_type === 'manufacturer_report'): ?>
                            <th class="ps-3">Manufacturer</th>
                            <th>Company</th>
                            <th>City</th>
                            <th class="text-center">Formulations</th>
                            <th class="text-center">Units Dispatched</th>
                            <th class="text-center">Suppliers Supplied</th>
                            <th class="text-end pe-3">In Transit</th>

                        <?php elseif ($report_type === 'supplier_report'): ?>
                            <th class="ps-3">Code</th>
                            <th>Supplier Partner</th>
                            <th>Category</th>
                            <th>Hub Location</th>
                            <th class="text-center">Received</th>
                            <th class="text-center">Forwarded</th>
                            <th class="text-end pe-3">Score & Grade</th>

                        <?php elseif ($report_type === 'shopkeeper_report'): ?>
                            <th class="ps-3">Store Name</th>
                            <th>Proprietor</th>
                            <th>City</th>
                            <th class="text-center">Deliveries Received</th>
                            <th class="text-center">Total Units Received</th>
                            <th class="text-end pe-3">Current Shelf Stock</th>

                        <?php elseif ($report_type === 'product_report'): ?>
                            <th class="ps-3">Code</th>
                            <th>Product Name</th>
                            <th>Category</th>
                            <th>Brand</th>
                            <th>Manufacturer</th>
                            <th class="text-end">Standard Price</th>
                            <th class="text-end pe-3">Status</th>

                        <?php else: ?>
                            <th class="ps-3">System Key Metric</th>
                            <th class="text-center">Database Aggregation</th>
                            <th class="text-end pe-3">Operational Scope</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $rank = $offset + 1;
                    foreach ($report_rows as $row): 
                    ?>
                        <tr>
                            <?php if ($report_type === 'supplier_performance'): ?>
                                <td class="ps-3 font-monospace fw-bold text-dark"><?= htmlspecialchars($row['supplier_code']) ?></td>
                                <td>
                                    <div class="fw-semibold text-dark"><?= htmlspecialchars($row['supplier_name']) ?></div>
                                </td>
                                <td><span class="badge bg-light text-dark border extra-small"><?= htmlspecialchars($row['category']) ?></span></td>
                                <td class="text-center font-monospace"><?= $row['total_orders'] ?> (<?= $row['completed_orders'] ?>)</td>
                                <td class="text-center font-monospace"><?= number_format($row['delivery_score'], 1) ?>%</td>
                                <td class="text-center font-monospace"><?= number_format($row['quality_score'], 1) ?>%</td>
                                <td class="text-center font-monospace"><?= number_format($row['cost_score'], 1) ?>%</td>
                                <td class="text-center font-monospace"><?= number_format($row['reliability_score'], 1) ?>%</td>
                                <td class="text-center font-monospace fw-bold text-success fs-6"><?= number_format($row['overall_score'], 1) ?>%</td>
                                <td class="text-center"><?= get_grade_badge($row['grade']) ?></td>
                                <td class="text-end pe-3">
                                    <a href="<?= BASE_URL ?>suppliers/view.php?id=<?= $row['id'] ?>" class="btn btn-outline-secondary btn-sm btn-action-sm">
                                        View 360
                                    </a>
                                </td>

                            <?php elseif ($report_type === 'supplier_ranking'): ?>
                                <td class="ps-3">
                                    <?php if ($rank === 1): ?><span class="badge bg-warning text-dark px-2 py-1"><i class="fa-solid fa-crown me-1"></i>#1</span>
                                    <?php elseif ($rank === 2): ?><span class="badge bg-secondary text-white px-2 py-1">#2</span>
                                    <?php elseif ($rank === 3): ?><span class="badge bg-danger bg-opacity-75 text-white px-2 py-1">#3</span>
                                    <?php else: ?><span class="fw-bold font-monospace text-muted">#<?= $rank ?></span><?php endif; ?>
                                </td>
                                <td>
                                    <div class="fw-bold text-dark"><?= htmlspecialchars($row['supplier_name']) ?></div>
                                    <div class="extra-small text-muted font-monospace"><?= htmlspecialchars($row['supplier_code']) ?></div>
                                </td>
                                <td><span class="badge bg-light text-dark border extra-small"><?= htmlspecialchars($row['category']) ?></span></td>
                                <td class="text-center font-monospace"><?= number_format($row['delivery_score'], 1) ?>%</td>
                                <td class="text-center font-monospace"><?= number_format($row['quality_score'], 1) ?>%</td>
                                <td class="text-center font-monospace"><?= number_format($row['cost_score'], 1) ?>%</td>
                                <td class="text-center font-monospace"><?= number_format($row['reliability_score'], 1) ?>%</td>
                                <td class="text-center font-monospace fw-bold text-success fs-6"><?= number_format($row['overall_score'], 1) ?>%</td>
                                <td class="text-end pe-3"><?= get_grade_badge($row['grade']) ?></td>

                            <?php elseif ($report_type === 'purchase_order'): ?>
                                <td class="ps-3 font-monospace fw-bold text-dark"><?= htmlspecialchars($row['po_number']) ?></td>
                                <td><?= htmlspecialchars($row['supplier_name']) ?></td>
                                <td><?= date('d M Y', strtotime($row['order_date'])) ?></td>
                                <td><?= date('d M Y', strtotime($row['expected_date'])) ?></td>
                                <td class="text-end font-monospace fw-bold">₹<?= number_format($row['total_amount'], 2) ?></td>
                                <td class="text-end pe-3">
                                    <span class="badge bg-light text-dark border extra-small"><?= htmlspecialchars($row['status']) ?></span>
                                </td>

                            <?php elseif ($report_type === 'delivery_report'): ?>
                                <td class="ps-3 font-monospace fw-bold text-dark"><?= htmlspecialchars($row['po_number']) ?></td>
                                <td><?= htmlspecialchars($row['supplier_name']) ?></td>
                                <td><?= date('d M Y', strtotime($row['expected_date'])) ?></td>
                                <td><?= date('d M Y', strtotime($row['delivery_date'])) ?></td>
                                <td class="text-center font-monospace fw-bold"><?= number_format($row['quantity_received']) ?> pcs</td>
                                <td class="text-center">
                                    <?php if ($row['delay_days'] > 0): ?>
                                        <span class="text-danger fw-bold">+<?= $row['delay_days'] ?> days</span>
                                    <?php else: ?>
                                        <span class="text-success fw-bold">0 days</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end pe-3">
                                    <span class="badge <?= $row['delivery_status'] === 'On Time' ? 'badge-status-received' : 'badge-status-in-transit' ?> extra-small">
                                        <?= htmlspecialchars($row['delivery_status']) ?>
                                    </span>
                                </td>

                            <?php elseif ($report_type === 'quality_report'): ?>
                                <td class="ps-3 font-monospace fw-bold text-dark"><?= htmlspecialchars($row['po_number'] ?? 'QI-' . $row['id']) ?></td>
                                <td><?= htmlspecialchars($row['supplier_name'] ?? 'Formulation Vendor') ?></td>
                                <td><?= date('d M Y', strtotime($row['inspection_date'])) ?></td>
                                <td class="text-center font-monospace"><?= number_format($row['quantity_received']) ?></td>
                                <td class="text-center font-monospace text-danger fw-bold"><?= number_format($row['quantity_defective']) ?></td>
                                <td class="text-center font-monospace"><?= number_format($row['defect_rate'], 2) ?>%</td>
                                <td class="text-center font-monospace fw-bold text-success"><?= number_format($row['quality_score'], 1) ?>%</td>
                                <td class="text-end pe-3">
                                    <span class="badge <?= $row['quality_status'] === 'Passed' ? 'badge-status-received' : 'badge-status-cancelled' ?> extra-small">
                                        <?= htmlspecialchars($row['quality_status']) ?>
                                    </span>
                                </td>

                            <?php elseif ($report_type === 'product_transfers'): ?>
                                <td class="ps-3 font-monospace fw-bold text-dark"><?= htmlspecialchars($row['transfer_ref']) ?></td>
                                <td>
                                    <div class="fw-semibold text-dark"><?= htmlspecialchars($row['product_name']) ?></div>
                                    <div class="extra-small text-muted font-monospace"><?= htmlspecialchars($row['product_code']) ?></div>
                                </td>
                                <td>
                                    <?php if ($row['stage'] === 'manufacturer_to_supplier'): ?>
                                        <span class="badge bg-primary bg-opacity-10 text-primary border border-primary-subtle extra-small">MFR &rarr; SUP</span>
                                    <?php else: ?>
                                        <span class="badge bg-success bg-opacity-10 text-success border border-success-subtle extra-small">SUP &rarr; SHOP</span>
                                    <?php endif; ?>
                                </td>
                                <td class="extra-small">
                                    <div><strong>From:</strong> <?= htmlspecialchars($row['sender_company'] ?: $row['sender_name']) ?></div>
                                    <div><strong>To:</strong> <?= htmlspecialchars($row['receiver_shop'] ?: ($row['receiver_company'] ?: $row['receiver_name'])) ?></div>
                                </td>
                                <td class="text-center font-monospace fw-bold"><?= number_format($row['quantity']) ?> pcs</td>
                                <td><?= date('d M Y', strtotime($row['transfer_date'])) ?></td>
                                <td class="text-end pe-3">
                                    <span class="badge <?= $row['status'] === 'Received' ? 'badge-status-received' : 'badge-status-in-transit' ?> extra-small">
                                        <?= htmlspecialchars($row['status']) ?>
                                    </span>
                                </td>

                            <?php elseif ($report_type === 'traceability_report'): ?>
                                <td class="ps-3 font-monospace fw-bold text-dark"><?= htmlspecialchars($row['product_code']) ?></td>
                                <td>
                                    <div class="fw-bold text-dark"><?= htmlspecialchars($row['product_name']) ?></div>
                                    <div class="extra-small text-muted"><?= htmlspecialchars($row['brand']) ?></div>
                                </td>
                                <td><?= htmlspecialchars($row['category']) ?></td>
                                <td><?= htmlspecialchars($row['manufacturer_name'] ?? 'Production Lab') ?></td>
                                <td class="text-center font-monospace fw-bold"><?= $row['transfer_hops'] ?> hops</td>
                                <td class="text-center font-monospace"><?= number_format($row['volume_moved']) ?> pcs</td>
                                <td class="text-end pe-3">
                                    <span class="badge bg-light text-dark border extra-small">
                                        <i class="fa-solid fa-user-shield text-success me-1"></i><?= htmlspecialchars($row['current_custodian'] ?? 'In Transit') ?>
                                    </span>
                                </td>

                            <?php elseif ($report_type === 'manufacturer_report'): ?>
                                <td class="ps-3 fw-bold text-dark"><?= htmlspecialchars($row['name']) ?></td>
                                <td><?= htmlspecialchars($row['company_name']) ?></td>
                                <td><?= htmlspecialchars($row['city']) ?></td>
                                <td class="text-center font-monospace"><?= $row['catalog_products'] ?></td>
                                <td class="text-center font-monospace fw-bold"><?= number_format($row['units_transferred']) ?> pcs</td>
                                <td class="text-center font-monospace"><?= $row['connected_suppliers'] ?></td>
                                <td class="text-end pe-3">
                                    <span class="badge bg-warning bg-opacity-10 text-warning border extra-small"><?= $row['in_transit_shipments'] ?> in transit</span>
                                </td>

                            <?php elseif ($report_type === 'supplier_report'): ?>
                                <td class="ps-3 font-monospace fw-bold text-dark"><?= htmlspecialchars($row['supplier_code']) ?></td>
                                <td><?= htmlspecialchars($row['supplier_name']) ?></td>
                                <td><?= htmlspecialchars($row['category']) ?></td>
                                <td><?= htmlspecialchars($row['city']) ?></td>
                                <td class="text-center font-monospace"><?= number_format($row['units_received']) ?> pcs</td>
                                <td class="text-center font-monospace"><?= number_format($row['units_forwarded']) ?> pcs</td>
                                <td class="text-end pe-3">
                                    <span class="fw-bold font-monospace text-success me-1"><?= number_format($row['overall_score'], 1) ?>%</span>
                                    <?= get_grade_badge($row['grade']) ?>
                                </td>

                            <?php elseif ($report_type === 'shopkeeper_report'): ?>
                                <td class="ps-3 fw-bold text-dark"><?= htmlspecialchars($row['shop_name'] ?: 'Retail Boutique') ?></td>
                                <td><?= htmlspecialchars($row['name']) ?></td>
                                <td><?= htmlspecialchars($row['city']) ?></td>
                                <td class="text-center font-monospace"><?= $row['shipments_received'] ?></td>
                                <td class="text-center font-monospace fw-bold"><?= number_format($row['units_received']) ?> pcs</td>
                                <td class="text-end pe-3">
                                    <span class="badge bg-success-subtle text-success font-monospace fw-bold"><?= number_format($row['current_shelf_stock']) ?> pcs</span>
                                </td>

                            <?php elseif ($report_type === 'product_report'): ?>
                                <td class="ps-3 font-monospace fw-bold text-dark"><?= htmlspecialchars($row['product_code']) ?></td>
                                <td><?= htmlspecialchars($row['product_name']) ?></td>
                                <td><?= htmlspecialchars($row['category']) ?></td>
                                <td><?= htmlspecialchars($row['brand']) ?></td>
                                <td><?= htmlspecialchars($row['manufacturer_name'] ?? $row['supplier_name']) ?></td>
                                <td class="text-end font-monospace">₹<?= number_format($row['standard_price'], 2) ?></td>
                                <td class="text-end pe-3">
                                    <span class="badge bg-light text-dark border extra-small"><?= htmlspecialchars($row['status']) ?></span>
                                </td>

                            <?php else: ?>
                                <td class="ps-3 fw-bold text-dark"><?= htmlspecialchars($row['metric']) ?></td>
                                <td class="text-center font-monospace fw-bold fs-6 text-primary"><?= htmlspecialchars($row['value']) ?></td>
                                <td class="text-end pe-3"><span class="badge bg-light text-dark border"><?= htmlspecialchars($row['status']) ?></span></td>
                            <?php endif; ?>
                        </tr>
                    <?php 
                    $rank++;
                    endforeach; 
                    ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
