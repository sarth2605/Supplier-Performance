<?php
/**
 * Supplier Performance Analysis and Management System
 * Domain: Beauty, Cosmetics, Skincare & Personal Care Supply Chain
 * Core Business Logic & 5-Factor Mathematical Calculation Engine
 */

require_once __DIR__ . '/../config/config.php';

/**
 * Retrieve system performance weights from database or default
 * 1. Delivery Performance: 30%
 * 2. Product Quality: 30%
 * 3. Order Fulfillment: 20%
 * 4. Cost Performance: 10%
 * 5. Return Rate: 10%
 */
function get_system_weights(): array {
    static $weights = null;
    if ($weights !== null) return $weights;

    $weights = [
        'delivery'    => 0.30,
        'quality'     => 0.30,
        'fulfillment' => 0.20,
        'cost'        => 0.10,
        'returns'     => 0.10
    ];

    try {
        $db = get_db();
        $stmt = $db->query("SELECT setting_key, setting_value FROM system_settings WHERE setting_key LIKE 'weight_%'");
        while ($row = $stmt->fetch()) {
            if ($row['setting_key'] === 'weight_delivery')    $weights['delivery'] = floatval($row['setting_value']);
            if ($row['setting_key'] === 'weight_quality')     $weights['quality'] = floatval($row['setting_value']);
            if ($row['setting_key'] === 'weight_fulfillment') $weights['fulfillment'] = floatval($row['setting_value']);
            if ($row['setting_key'] === 'weight_cost')        $weights['cost'] = floatval($row['setting_value']);
            if ($row['setting_key'] === 'weight_returns')     $weights['returns'] = floatval($row['setting_value']);
        }
    } catch (Exception $e) {
        // Fallback to default weights
    }

    return $weights;
}

/**
 * 1. Delivery Performance Score (30%)
 * On-Time Delivery Rate = (On-Time Deliveries / Total Deliveries) * 100
 */
function calculate_delivery_score(int $on_time_deliveries, int $total_deliveries): float {
    if ($total_deliveries <= 0) return 100.00;
    $score = ($on_time_deliveries / $total_deliveries) * 100.0;
    return round(min(100.0, max(0.0, $score)), 2);
}

/**
 * 2. Quality Performance Score (30%)
 * Defect Rate = (Defective Quantity / Received Quantity) * 100
 * Quality Acceptance Rate = (Accepted Quantity / Received Quantity) * 100
 * Quality Score = 100 - Defect Rate
 */
function calculate_quality_score(int $defective_qty, int $received_qty, int $accepted_qty = 0): array {
    if ($received_qty <= 0) {
        return [
            'defect_rate'     => 0.00,
            'acceptance_rate' => 100.00,
            'quality_score'   => 100.00
        ];
    }
    $defect_rate = ($defective_qty / $received_qty) * 100.0;
    $acc_rate = $accepted_qty > 0 ? ($accepted_qty / $received_qty) * 100.0 : max(0.0, 100.0 - $defect_rate);
    $quality_score = 100.0 - $defect_rate;
    return [
        'defect_rate'     => round(max(0.0, $defect_rate), 2),
        'acceptance_rate' => round(min(100.0, max(0.0, $acc_rate)), 2),
        'quality_score'   => round(min(100.0, max(0.0, $quality_score)), 2)
    ];
}

/**
 * 3. Order Fulfillment Rate (20%)
 * Fulfillment Rate = (Delivered Quantity / Ordered Quantity) * 100
 */
function calculate_fulfillment_score(int $delivered_qty, int $ordered_qty): float {
    if ($ordered_qty <= 0) return 100.00;
    $score = ($delivered_qty / $ordered_qty) * 100.0;
    return round(min(100.0, max(0.0, $score)), 2);
}

/**
 * 4. Cost Performance Score (10%)
 * Cost Score = (Standard Benchmark Price / Supplier Contract Price) * 100
 */
function calculate_cost_score(float $standard_price, float $supplier_price): float {
    if ($supplier_price <= 0 || $standard_price <= 0) return 100.00;
    $score = ($standard_price / $supplier_price) * 100.0;
    return round(min(100.0, max(0.0, $score)), 2);
}

/**
 * 5. Return Rate & Penalty Score (10%)
 * Return Rate = (Returned Quantity / Delivered Quantity) * 100
 * Return Performance Score = 100 - (Return Rate * 10)
 */
function calculate_return_score(int $returned_qty, int $delivered_qty): array {
    if ($delivered_qty <= 0) {
        return ['return_rate' => 0.00, 'return_score' => 100.00];
    }
    $return_rate = ($returned_qty / $delivered_qty) * 100.0;
    $return_score = max(0.0, 100.0 - ($return_rate * 5.0)); // Penalty scaling
    return [
        'return_rate'  => round(max(0.0, $return_rate), 2),
        'return_score' => round(min(100.0, max(0.0, $return_score)), 2)
    ];
}

/**
 * Calculate Overall Weighted Performance Score across 5 Dimensions:
 * Overall Score = (Delivery * 0.30) + (Quality * 0.30) + (Fulfillment * 0.20) + (Cost * 0.10) + (Returns * 0.10)
 */
function calculate_overall_score(float $delivery, float $quality, float $fulfillment, float $cost, float $returns, ?array $custom_weights = null): float {
    $w = $custom_weights ?: get_system_weights();
    $score = ($delivery * ($w['delivery'] ?? 0.30)) +
             ($quality * ($w['quality'] ?? 0.30)) +
             ($fulfillment * ($w['fulfillment'] ?? 0.20)) +
             ($cost * ($w['cost'] ?? 0.10)) +
             ($returns * ($w['returns'] ?? 0.10));
    return round(min(100.0, max(0.0, $score)), 2);
}

/**
 * Grade Scale & Classification:
 * 90–100 = 🟢 Excellent (A+)
 * 80–89  = 🟢 Very Good (A)
 * 70–79  = 🟡 Good (B)
 * 60–69  = 🟠 Average (C)
 * Below 60 = 🔴 Poor (D)
 */
function get_supplier_grade(float $overall_score): array {
    if ($overall_score >= 90.0) {
        return [
            'grade'       => 'A+',
            'title'       => 'Excellent',
            'badge_class' => 'bg-success',
            'color'       => '#10b981',
            'bg'          => 'rgba(16, 185, 129, 0.12)',
            'desc'        => 'Category leader; Preferred prime partner.'
        ];
    } elseif ($overall_score >= 80.0) {
        return [
            'grade'       => 'A',
            'title'       => 'Very Good',
            'badge_class' => 'bg-primary',
            'color'       => '#2563eb',
            'bg'          => 'rgba(37, 99, 235, 0.12)',
            'desc'        => 'High consistency; Reliable standard beauty supplier.'
        ];
    } elseif ($overall_score >= 70.0) {
        return [
            'grade'       => 'B',
            'title'       => 'Good',
            'badge_class' => 'bg-warning text-dark',
            'color'       => '#f59e0b',
            'bg'          => 'rgba(245, 158, 11, 0.12)',
            'desc'        => 'Acceptable performance; Periodic monitoring recommended.'
        ];
    } elseif ($overall_score >= 60.0) {
        return [
            'grade'       => 'C',
            'title'       => 'Average',
            'badge_class' => 'bg-orange text-white',
            'color'       => '#f97316',
            'bg'          => 'rgba(249, 115, 22, 0.12)',
            'desc'        => 'Needs improvement; Issue a 30-day Corrective Action Plan (CAP).'
        ];
    } else {
        return [
            'grade'       => 'D',
            'title'       => 'Poor',
            'badge_class' => 'bg-danger',
            'color'       => '#ef4444',
            'bg'          => 'rgba(239, 68, 68, 0.12)',
            'desc'        => 'Critical risk; Suspend orders or re-audit supplier.'
        ];
    }
}

/**
 * Generate HTML badge for supplier grade
 */
function get_grade_badge(string $grade): string {
    $badge_class = 'bg-secondary';
    if ($grade === 'A+') $badge_class = 'bg-success';
    elseif ($grade === 'A') $badge_class = 'bg-primary';
    elseif ($grade === 'B') $badge_class = 'bg-warning text-dark';
    elseif ($grade === 'C') $badge_class = 'bg-orange text-white';
    elseif ($grade === 'D') $badge_class = 'bg-danger';

    return '<span class="badge ' . $badge_class . ' px-2 py-1 font-monospace fw-bold">' . htmlspecialchars($grade) . '</span>';
}

/**
 * Generate HTML status badge
 */
function get_status_badge(string $status): string {
    $class = 'bg-secondary';
    if ($status === 'Active' || $status === 'Delivered' || $status === 'On Time' || $status === 'Approved' || $status === 'Paid' || $status === 'Passed') {
        $class = 'bg-success';
    } elseif ($status === 'Pending' || $status === 'Delayed' || $status === 'Partially Delivered' || $status === 'Early' || $status === 'Partially Paid' || $status === 'Conditional') {
        $class = 'bg-warning text-dark';
    } elseif ($status === 'Suspended' || $status === 'Cancelled' || $status === 'Inactive' || $status === 'Overdue' || $status === 'Rejected' || $status === 'Failed') {
        $class = 'bg-danger';
    }
    return '<span class="badge ' . $class . ' px-2 py-1">' . htmlspecialchars($status) . '</span>';
}

/**
 * Recompute and save comprehensive 5-factor performance score for a supplier and period
 */
function recalculate_supplier_period_score(int $supplier_id, string $period = '2026-Q1'): array {
    $db = get_db();

    // 1. Delivery calculation (On-Time / Total Deliveries * 100)
    $del_stmt = $db->prepare("
        SELECT 
            COUNT(d.id) as total_deliveries,
            SUM(CASE WHEN d.delivery_status IN ('On Time', 'Early') THEN 1 ELSE 0 END) as on_time_deliveries,
            SUM(d.quantity_received) as total_delivered_qty
        FROM deliveries d
        INNER JOIN purchase_orders po ON d.purchase_order_id = po.id
        WHERE po.supplier_id = ?
    ");
    $del_stmt->execute([$supplier_id]);
    $del_data = $del_stmt->fetch();
    $tot_del = (int)($del_data['total_deliveries'] ?? 0);
    $ontime_del = (int)($del_data['on_time_deliveries'] ?? 0);
    $tot_deliv_qty = (int)($del_data['total_delivered_qty'] ?? 0);
    $delivery_score = calculate_delivery_score($ontime_del, $tot_del);

    // 2. Quality calculation (Defect rate & Quality score = 100 - Defect rate)
    $qi_stmt = $db->prepare("
        SELECT 
            COALESCE(SUM(qi.quantity_received), 0) as total_received,
            COALESCE(SUM(qi.quantity_defective), 0) as total_defective,
            COALESCE(SUM(qi.quantity_accepted), 0) as total_accepted
        FROM quality_inspections qi
        INNER JOIN deliveries d ON qi.delivery_id = d.id
        INNER JOIN purchase_orders po ON d.purchase_order_id = po.id
        WHERE po.supplier_id = ?
    ");
    $qi_stmt->execute([$supplier_id]);
    $qi_data = $qi_stmt->fetch();
    $tot_rec = (int)($qi_data['total_received'] ?? 0);
    $tot_def = (int)($qi_data['total_defective'] ?? 0);
    $tot_acc = (int)($qi_data['total_accepted'] ?? 0);
    $q_res = calculate_quality_score($tot_def, $tot_rec, $tot_acc);
    $quality_score = $q_res['quality_score'];

    // 3. Order Fulfillment (Delivered Qty / Ordered Qty * 100)
    $po_stmt = $db->prepare("
        SELECT 
            COALESCE(SUM(oi.quantity), 0) as total_ordered_qty
        FROM order_items oi
        INNER JOIN purchase_orders po ON oi.purchase_order_id = po.id
        WHERE po.supplier_id = ? AND po.status != 'Cancelled'
    ");
    $po_stmt->execute([$supplier_id]);
    $total_ord_qty = (int)$po_stmt->fetchColumn();
    $fulfillment_score = calculate_fulfillment_score($tot_deliv_qty ?: $total_ord_qty, $total_ord_qty ?: 1);

    // 4. Cost calculation from contract unit price vs standard price
    $cost_stmt = $db->prepare("
        SELECT 
            AVG(CASE WHEN oi.unit_price > 0 THEN (p.standard_price / oi.unit_price) * 100 ELSE 100 END) as avg_cost_ratio
        FROM order_items oi
        INNER JOIN products p ON oi.product_id = p.id
        INNER JOIN purchase_orders po ON oi.purchase_order_id = po.id
        WHERE po.supplier_id = ?
    ");
    $cost_stmt->execute([$supplier_id]);
    $cost_ratio = $cost_stmt->fetchColumn();
    $cost_score = $cost_ratio ? round(min(100.0, max(0.0, floatval($cost_ratio))), 2) : 96.00;

    // 5. Return Rate & Score (Returned Qty / Delivered Qty * 100)
    $ret_stmt = $db->prepare("
        SELECT COALESCE(SUM(return_quantity), 0) as total_returned_qty
        FROM returns
        WHERE supplier_id = ? AND return_status = 'Approved'
    ");
    $ret_stmt->execute([$supplier_id]);
    $tot_ret_qty = (int)$ret_stmt->fetchColumn();
    $ret_res = calculate_return_score($tot_ret_qty, $tot_deliv_qty ?: 100);
    $return_score = $ret_res['return_score'];

    // 6. Overall Weighted Score & Academic Grade
    $overall_score = calculate_overall_score($delivery_score, $quality_score, $fulfillment_score, $cost_score, $return_score);
    $grade_info = get_supplier_grade($overall_score);

    // Upsert into performance_scores table
    $upsert_stmt = $db->prepare("
        INSERT INTO performance_scores (
            supplier_id, period, delivery_score, quality_score, fulfillment_score, cost_score, return_score, overall_score, grade, created_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ON DUPLICATE KEY UPDATE
            delivery_score = VALUES(delivery_score),
            quality_score = VALUES(quality_score),
            fulfillment_score = VALUES(fulfillment_score),
            cost_score = VALUES(cost_score),
            return_score = VALUES(return_score),
            overall_score = VALUES(overall_score),
            grade = VALUES(grade)
    ");
    $upsert_stmt->execute([
        $supplier_id, $period, $delivery_score, $quality_score, $fulfillment_score, $cost_score, $return_score, $overall_score, $grade_info['grade']
    ]);

    return [
        'delivery_score'    => $delivery_score,
        'quality_score'     => $quality_score,
        'fulfillment_score' => $fulfillment_score,
        'cost_score'        => $cost_score,
        'return_score'      => $return_score,
        'overall_score'     => $overall_score,
        'grade'             => $grade_info['grade']
    ];
}

/**
 * Log user action into activity_logs
 */
function log_activity(?int $user_id, string $action, string $module = 'General', ?int $record_id = null, string $description = ''): void {
    try {
        $db = get_db();
        $stmt = $db->prepare("
            INSERT INTO activity_logs (user_id, action, module, record_id, details, created_at)
            VALUES (?, ?, ?, ?, ?, NOW())
        ");
        $stmt->execute([$user_id, $action, $module, $record_id, $description]);
    } catch (Exception $e) {
        // Suppress audit logging error
    }
}

/**
 * Format currency (Default: ₹ Indian Rupee)
 */
function format_currency(float $amount, string $symbol = '₹'): string {
    return $symbol . number_format($amount, 2);
}

/**
 * Format date display
 */
function format_date(?string $date_str, string $format = 'd M Y'): string {
    if (empty($date_str)) return '—';
    $time = strtotime($date_str);
    return $time ? date($format, $time) : '—';
}
