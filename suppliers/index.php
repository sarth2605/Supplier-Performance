<?php
/**
 * Supplier Performance Analysis and Management System
 * Domain: Beauty, Cosmetics, Skincare & Personal Care Supply Chain
 * Supplier Management Directory & List View
 */

$page_title = "Suppliers Directory";
require_once __DIR__ . '/../includes/header.php';

$db = get_db();

// Handle CSV Export
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=Cosmetics_Suppliers_Directory_' . date('Y-m-d') . '.csv');
    $output = fopen('php://output', 'w');
    fputcsv($output, ['Supplier Code', 'Supplier Name', 'Contact Person', 'Email', 'Phone', 'Category', 'Supplier Type', 'City', 'State', 'Payment Terms', 'Status', 'Overall Score', 'Grade']);

    $export_stmt = $db->query("
        SELECT s.supplier_code, s.supplier_name, s.contact_person, s.email, s.phone, s.category, s.supplier_type,
               s.city, s.state, s.payment_terms, s.status,
               COALESCE(ps.overall_score, 0) as overall_score, COALESCE(ps.grade, 'Unrated') as grade
        FROM suppliers s
        LEFT JOIN (
            SELECT p1.* FROM performance_scores p1
            INNER JOIN (
                SELECT supplier_id, MAX(id) as max_id FROM performance_scores GROUP BY supplier_id
            ) p2 ON p1.id = p2.max_id
        ) ps ON s.id = ps.supplier_id
        ORDER BY s.id DESC
    ");
    while ($row = $export_stmt->fetch(PDO::FETCH_ASSOC)) {
        fputcsv($output, $row);
    }
    fclose($output);
    exit;
}

// Search and Filter Params
$search = trim($_GET['search'] ?? '');
$category = trim($_GET['category'] ?? 'All');
$grade = trim($_GET['grade'] ?? 'All');
$status = trim($_GET['status'] ?? 'All');
$sort = trim($_GET['sort'] ?? 'score_desc');

// Pagination Params
$page = max(1, (int)($_GET['page'] ?? 1));
$per_page = 12;
$offset = ($page - 1) * $per_page;

// Build Dynamic Query
$where_clauses = ["1=1"];
$params = [];

if (!empty($search)) {
    $where_clauses[] = "(s.supplier_name LIKE ? OR s.supplier_code LIKE ? OR s.contact_person LIKE ? OR s.city LIKE ?)";
    $search_term = "%$search%";
    $params = array_merge($params, [$search_term, $search_term, $search_term, $search_term]);
}

if ($category !== 'All' && !empty($category)) {
    $where_clauses[] = "s.category = ?";
    $params[] = $category;
}

if ($status !== 'All' && !empty($status)) {
    $where_clauses[] = "s.status = ?";
    $params[] = $status;
}

$where_sql = implode(" AND ", $where_clauses);

// Grade filter
$having_sql = "";
if ($grade !== 'All' && !empty($grade)) {
    $having_sql = "HAVING grade = " . $db->quote($grade);
}

// Sorting SQL
$order_sql = "ORDER BY ps.overall_score DESC";
if ($sort === 'score_asc') $order_sql = "ORDER BY ps.overall_score ASC";
elseif ($sort === 'name_asc') $order_sql = "ORDER BY s.supplier_name ASC";
elseif ($sort === 'name_desc') $order_sql = "ORDER BY s.supplier_name DESC";
elseif ($sort === 'newest') $order_sql = "ORDER BY s.id DESC";

// Count total matching records
$count_query = "
    SELECT COUNT(*) FROM (
        SELECT s.id, COALESCE(ps.grade, 'Unrated') as grade
        FROM suppliers s
        LEFT JOIN (
            SELECT p1.* FROM performance_scores p1
            INNER JOIN (
                SELECT supplier_id, MAX(id) as max_id FROM performance_scores GROUP BY supplier_id
            ) p2 ON p1.id = p2.max_id
        ) ps ON s.id = ps.supplier_id
        WHERE $where_sql
        $having_sql
    ) as count_table
";
$count_stmt = $db->prepare($count_query);
$count_stmt->execute($params);
$total_records = (int)$count_stmt->fetchColumn();
$total_pages = ceil($total_records / $per_page);

// Fetch paginated suppliers
$data_query = "
    SELECT s.*, 
           ps.id as score_id, ps.overall_score, ps.grade, ps.delivery_score, ps.quality_score, ps.fulfillment_score, ps.cost_score, ps.return_score
    FROM suppliers s
    LEFT JOIN (
        SELECT p1.* FROM performance_scores p1
        INNER JOIN (
            SELECT supplier_id, MAX(id) as max_id FROM performance_scores GROUP BY supplier_id
        ) p2 ON p1.id = p2.max_id
    ) ps ON s.id = ps.supplier_id
    WHERE $where_sql
    $having_sql
    $order_sql
    LIMIT $per_page OFFSET $offset
";
$data_stmt = $db->prepare($data_query);
$data_stmt->execute($params);
$suppliers = $data_stmt->fetchAll();

$categories = [
    'Face Products',
    'Eye Products',
    'Lip Products',
    'Skincare Products',
    'Nail Products',
    'Hair Beauty Products',
    'Body Care Products',
    'Beauty Tools & Accessories'
];
?>

<!-- Header Toolbar -->
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
    <div>
        <h1 class="h3 fw-bold text-dark mb-1">Cosmetics Supplier Directory</h1>
        <p class="text-muted small mb-0">Manage registered formulation labs, contract manufacturers, packaging suppliers, and tier rankings.</p>
    </div>
    <div class="d-flex gap-2">
        <a href="?export=csv<?= !empty($_SERVER['QUERY_STRING']) ? '&' . htmlspecialchars($_SERVER['QUERY_STRING']) : '' ?>" class="btn btn-outline-secondary btn-sm rounded-pill d-inline-flex align-items-center gap-2">
            <i class="fa-solid fa-file-csv"></i> Export CSV
        </a>
        <?php if ($_SESSION['user_role'] !== 'staff'): ?>
        <a href="<?= BASE_URL ?>suppliers/add.php" class="btn btn-primary btn-sm rounded-pill d-inline-flex align-items-center gap-2 shadow-sm">
            <i class="fa-solid fa-plus"></i> Add New Supplier
        </a>
        <?php endif; ?>
    </div>
</div>

<!-- Search & Filter Controls Card -->
<div class="card-saas mb-4">
    <div class="card-saas-body">
        <form method="GET" action="<?= htmlspecialchars($_SERVER['PHP_SELF']) ?>" class="row g-2 align-items-center">
            
            <div class="col-12 col-md-4">
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-light border-end-0 text-muted"><i class="fa-solid fa-magnifying-glass"></i></span>
                    <input type="text" name="search" class="form-control border-start-0" placeholder="Search supplier, contact, city..." value="<?= htmlspecialchars($search) ?>">
                </div>
            </div>

            <div class="col-6 col-md-3">
                <select name="category" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="All">All 8 Beauty Categories</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= htmlspecialchars($cat) ?>" <?= $category === $cat ? 'selected' : '' ?>><?= htmlspecialchars($cat) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-6 col-md-2">
                <select name="grade" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="All">All Grades</option>
                    <option value="A+" <?= $grade === 'A+' ? 'selected' : '' ?>>A+ (90-100% Excellent)</option>
                    <option value="A" <?= $grade === 'A' ? 'selected' : '' ?>>A (80-89% Very Good)</option>
                    <option value="B" <?= $grade === 'B' ? 'selected' : '' ?>>B (70-79% Good)</option>
                    <option value="C" <?= $grade === 'C' ? 'selected' : '' ?>>C (60-69% Average)</option>
                    <option value="D" <?= $grade === 'D' ? 'selected' : '' ?>>D (&lt;60% Poor)</option>
                </select>
            </div>

            <div class="col-6 col-md-1">
                <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="All">Status</option>
                    <option value="Active" <?= $status === 'Active' ? 'selected' : '' ?>>Active</option>
                    <option value="Inactive" <?= $status === 'Inactive' ? 'selected' : '' ?>>Inactive</option>
                </select>
            </div>

            <div class="col-6 col-md-2 d-flex gap-1">
                <button type="submit" class="btn btn-primary btn-sm flex-grow-1">Filter</button>
                <a href="<?= BASE_URL ?>suppliers/index.php" class="btn btn-light btn-sm border" title="Reset Filters"><i class="fa-solid fa-rotate-left"></i></a>
            </div>

        </form>
    </div>
</div>

<!-- Supplier Data Table Card -->
<div class="card-saas">
    <div class="card-saas-header d-flex justify-content-between align-items-center">
        <div class="fw-bold text-dark small">
            Showing <?= count($suppliers) ?> of <?= $total_records ?> Beauty Suppliers
        </div>
        <div class="d-flex align-items-center gap-2">
            <span class="text-muted extra-small">Sort by:</span>
            <select class="form-select form-select-sm py-0 ps-2 pe-4" style="width: auto; font-size: 0.8rem;" onchange="location.href='?<?= http_build_query(array_merge($_GET, ['sort' => ''])) ?>' + this.value">
                <option value="score_desc" <?= $sort === 'score_desc' ? 'selected' : '' ?>>Highest Score</option>
                <option value="score_asc" <?= $sort === 'score_asc' ? 'selected' : '' ?>>Lowest Score</option>
                <option value="name_asc" <?= $sort === 'name_asc' ? 'selected' : '' ?>>Name A-Z</option>
                <option value="newest" <?= $sort === 'newest' ? 'selected' : '' ?>>Newest Added</option>
            </select>
        </div>
    </div>
    
    <div class="table-responsive">
        <table class="table table-custom mb-0">
            <thead>
                <tr>
                    <th>Supplier ID</th>
                    <th>Supplier Partner & Type</th>
                    <th>Category</th>
                    <th>Contact & Location</th>
                    <th>Payment Terms</th>
                    <th>Overall Score</th>
                    <th>Grade</th>
                    <th>Status</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($suppliers)): ?>
                    <tr>
                        <td colspan="9" class="text-center py-5 text-muted">
                            <i class="fa-solid fa-building fs-1 text-secondary opacity-50 mb-2 d-block"></i>
                            <p class="mb-0 fw-semibold">No cosmetics suppliers found matching filter criteria.</p>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($suppliers as $s): ?>
                        <tr>
                            <td><code class="fw-bold text-primary"><?= htmlspecialchars($s['supplier_code']) ?></code></td>
                            <td>
                                <a href="<?= BASE_URL ?>suppliers/view.php?id=<?= $s['id'] ?>" class="text-decoration-none fw-bold text-dark d-block">
                                    <?= htmlspecialchars($s['supplier_name']) ?>
                                </a>
                                <span class="badge bg-light text-muted border extra-small"><?= htmlspecialchars($s['supplier_type'] ?? 'Manufacturer') ?></span>
                            </td>
                            <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($s['category']) ?></span></td>
                            <td>
                                <div class="fw-semibold text-dark extra-small"><?= htmlspecialchars($s['contact_person']) ?></div>
                                <div class="extra-small text-muted"><i class="fa-solid fa-location-dot me-1 text-secondary"></i> <?= htmlspecialchars($s['city']) ?>, <?= htmlspecialchars($s['state']) ?></div>
                            </td>
                            <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($s['payment_terms'] ?? 'Net 30') ?></span></td>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <div class="score-progress flex-grow-1" style="width: 60px;">
                                        <div class="score-progress-bar" style="width: <?= $s['overall_score'] ?? 0 ?>%; background-color: <?= get_supplier_grade($s['overall_score'] ?? 0)['color'] ?>;"></div>
                                    </div>
                                    <span class="fw-bold font-monospace" style="color: <?= get_supplier_grade($s['overall_score'] ?? 0)['color'] ?>;">
                                        <?= !empty($s['overall_score']) ? $s['overall_score'] . '%' : 'Unrated' ?>
                                    </span>
                                </div>
                            </td>
                            <td><?= !empty($s['grade']) ? get_grade_badge($s['grade']) : '<span class="badge bg-secondary">Unrated</span>' ?></td>
                            <td><?= get_status_badge($s['status']) ?></td>
                            <td class="text-end">
                                <div class="btn-group btn-group-sm">
                                    <a href="<?= BASE_URL ?>suppliers/view.php?id=<?= $s['id'] ?>" class="btn btn-light border" title="360 Vendor Profile">
                                        <i class="fa-solid fa-eye text-primary"></i>
                                    </a>
                                    <a href="<?= BASE_URL ?>performance/supplier.php?id=<?= $s['id'] ?>" class="btn btn-light border" title="Performance Scorecard">
                                        <i class="fa-solid fa-calculator text-success"></i>
                                    </a>
                                    <?php if ($_SESSION['user_role'] !== 'staff'): ?>
                                    <a href="<?= BASE_URL ?>suppliers/edit.php?id=<?= $s['id'] ?>" class="btn btn-light border" title="Edit Supplier">
                                        <i class="fa-solid fa-pen text-secondary"></i>
                                    </a>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination Footer -->
    <?php if ($total_pages > 1): ?>
    <div class="card-saas-header border-top py-2 d-flex justify-content-between align-items-center">
        <div class="extra-small text-muted">
            Page <?= $page ?> of <?= $total_pages ?>
        </div>
        <nav aria-label="Suppliers list pagination">
            <ul class="pagination pagination-sm mb-0">
                <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                    <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>">Previous</a>
                </li>
                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <li class="page-item <?= $page === $i ? 'active' : '' ?>">
                        <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>"><?= $i ?></a>
                    </li>
                <?php endfor; ?>
                <li class="page-item <?= $page >= $total_pages ? 'disabled' : '' ?>">
                    <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>">Next</a>
                </li>
            </ul>
        </nav>
    </div>
    <?php endif; ?>

</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
