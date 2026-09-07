<?php
/**
 * Supplier Performance Analysis and Management System
 * Comprehensive Performance Scorecard Audit Report
 */

$page_title = "Performance Scorecard Report";
require_once __DIR__ . '/../config/config.php';
require_login();

$db = get_db();

// Handle CSV Export
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=Supplier_Performance_Scorecard_Report_' . date('Y-m-d') . '.csv');
    $output = fopen('php://output', 'w');
    fputcsv($output, ['Supplier Code', 'Supplier Name', 'Period', 'Delivery Score (30%)', 'Quality Score (30%)', 'Cost Score (20%)', 'Reliability Score (20%)', 'Overall Weighted Score (%)', 'Grade']);

    $q = $db->query("
        SELECT ps.*, s.supplier_name, s.supplier_code
        FROM performance_scores ps
        INNER JOIN suppliers s ON ps.supplier_id = s.id
        ORDER BY ps.overall_score DESC
    ");
    while ($row = $q->fetch()) {
        fputcsv($output, [
            $row['supplier_code'],
            $row['supplier_name'],
            $row['period'],
            $row['delivery_score'],
            $row['quality_score'],
            $row['cost_score'],
            $row['reliability_score'],
            $row['overall_score'],
            $row['grade']
        ]);
    }
    fclose($output);
    exit;
}

require_once __DIR__ . '/../includes/header.php';

$stmt = $db->query("
    SELECT ps.*, s.supplier_name, s.supplier_code, s.category
    FROM performance_scores ps
    INNER JOIN suppliers s ON ps.supplier_id = s.id
    ORDER BY ps.period DESC, ps.overall_score DESC
");
$scores = $stmt->fetchAll();
?>

<!-- Header -->
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4 no-print">
    <div>
        <h1 class="h3 fw-bold text-dark mb-1">Performance Scorecard Audit Report</h1>
        <p class="text-muted small mb-0">Multi-criteria vendor evaluation records computed via the standard 30-30-20-20 weighted formula.</p>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= BASE_URL ?>reports/performance_report.php?export=csv" class="btn btn-outline-success btn-sm rounded-pill">
            <i class="fa-solid fa-file-csv me-1"></i> Export CSV
        </a>
        <button onclick="window.print()" class="btn btn-outline-secondary btn-sm rounded-pill">
            <i class="fa-solid fa-print me-1"></i> Print
        </button>
    </div>
</div>

<!-- Table Card -->
<div class="card-saas">
    <div class="card-saas-header d-flex justify-content-between align-items-center">
        <h6 class="fw-bold mb-0 text-dark">Scorecard Audit Ledger (<?= count($scores) ?>)</h6>
        <span class="badge bg-light text-dark border">Generated: <?= date('d M Y') ?></span>
    </div>
    <div class="table-responsive">
        <table class="table table-custom mb-0">
            <thead>
                <tr>
                    <th>Supplier Details</th>
                    <th>Evaluation Period</th>
                    <th>Delivery (30%)</th>
                    <th>Quality (30%)</th>
                    <th>Cost (20%)</th>
                    <th>Reliability (20%)</th>
                    <th>Overall Score</th>
                    <th>Grade</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($scores as $s): ?>
                    <tr>
                        <td>
                            <span class="fw-bold text-dark"><?= htmlspecialchars($s['supplier_name']) ?></span>
                            <code class="extra-small text-muted d-block"><?= htmlspecialchars($s['supplier_code']) ?> &bull; <?= htmlspecialchars($s['category']) ?></code>
                        </td>
                        <td><span class="badge bg-light text-dark border font-monospace"><?= htmlspecialchars($s['period']) ?></span></td>
                        <td><?= $s['delivery_score'] ?>%</td>
                        <td><?= $s['quality_score'] ?>%</td>
                        <td><?= $s['cost_score'] ?>%</td>
                        <td><?= $s['reliability_score'] ?>%</td>
                        <td>
                            <strong class="font-monospace fs-6" style="color: <?= get_supplier_grade($s['overall_score'])['color'] ?>;">
                                <?= $s['overall_score'] ?>%
                            </strong>
                        </td>
                        <td><?= get_grade_badge($s['grade']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
