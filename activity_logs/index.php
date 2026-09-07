<?php
/**
 * Supplier Performance Analysis and Management System
 * System Activity Logs & Security Audit Trail
 */

$page_title = "Activity Logs";
require_once __DIR__ . '/../includes/header.php';
require_role(['admin', 'manager']);

$db = get_db();

$module = trim($_GET['module'] ?? 'All');
$search = trim($_GET['search'] ?? '');

$where = ["1=1"];
$params = [];

if ($module !== 'All' && !empty($module)) {
    $where[] = "al.module = ?";
    $params[] = $module;
}

if (!empty($search)) {
    $where[] = "(al.action LIKE ? OR al.details LIKE ? OR u.name LIKE ?)";
    $st = "%$search%";
    $params = array_merge($params, [$st, $st, $st]);
}

$where_sql = implode(" AND ", $where);

// Fetch modules for filter
$modules = $db->query("SELECT DISTINCT module FROM activity_logs ORDER BY module ASC")->fetchAll(PDO::FETCH_COLUMN);

// Fetch logs
$stmt = $db->prepare("
    SELECT al.*, u.name as user_name, u.email as user_email, u.role as user_role
    FROM activity_logs al
    LEFT JOIN users u ON al.user_id = u.id
    WHERE $where_sql
    ORDER BY al.id DESC
    LIMIT 100
");
$stmt->execute($params);
$logs = $stmt->fetchAll();
?>

<!-- Header -->
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
    <div>
        <h1 class="h3 fw-bold text-dark mb-1">System Audit & Activity Logs</h1>
        <p class="text-muted small mb-0">Immutable compliance audit trail recording all user CRUD actions, logins, and algorithmic calculations.</p>
    </div>
</div>

<!-- Filters -->
<div class="card-saas mb-4">
    <div class="card-saas-body">
        <form method="GET" action="<?= htmlspecialchars($_SERVER['PHP_SELF']) ?>" class="row g-2 align-items-center">
            
            <div class="col-12 col-md-5">
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-light border-end-0 text-muted"><i class="fa-solid fa-magnifying-glass"></i></span>
                    <input type="text" name="search" class="form-control border-start-0" placeholder="Search actions, details, users..." value="<?= htmlspecialchars($search) ?>">
                </div>
            </div>

            <div class="col-6 col-md-4">
                <select name="module" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="All">All Functional Modules</option>
                    <?php foreach ($modules as $m): ?>
                        <option value="<?= htmlspecialchars($m) ?>" <?= $module === $m ? 'selected' : '' ?>><?= htmlspecialchars($m) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-6 col-md-3 d-flex gap-1">
                <button type="submit" class="btn btn-primary btn-sm flex-grow-1">Filter Logs</button>
                <a href="<?= BASE_URL ?>activity_logs/index.php" class="btn btn-light btn-sm border" title="Reset"><i class="fa-solid fa-rotate-left"></i></a>
            </div>

        </form>
    </div>
</div>

<!-- Table Card -->
<div class="card-saas">
    <div class="card-saas-header d-flex justify-content-between align-items-center">
        <h6 class="fw-bold mb-0 text-dark">Audit Event Ledger (<?= count($logs) ?> Events)</h6>
        <span class="badge bg-light text-dark border">Max 100 Recent Entries</span>
    </div>
    <div class="table-responsive">
        <table class="table table-custom mb-0">
            <thead>
                <tr>
                    <th style="width: 170px;">Timestamp</th>
                    <th>User Operator</th>
                    <th>Module</th>
                    <th>Action</th>
                    <th>Audit Details</th>
                    <th>IP Address</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($logs)): ?>
                    <tr><td colspan="6" class="text-center py-5 text-muted">No audit log records found matching criteria.</td></tr>
                <?php else: ?>
                    <?php foreach ($logs as $l): ?>
                        <tr>
                            <td class="extra-small text-muted font-monospace"><?= date('d M Y, H:i:s', strtotime($l['created_at'])) ?></td>
                            <td>
                                <span class="fw-bold text-dark small"><?= htmlspecialchars($l['user_name'] ?: 'System Process') ?></span>
                                <?php if ($l['user_role']): ?>
                                    <span class="extra-small text-muted d-block"><?= htmlspecialchars($l['user_role']) ?></span>
                                <?php endif; ?>
                            </td>
                            <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($l['module']) ?></span></td>
                            <td class="fw-semibold text-dark small"><?= htmlspecialchars($l['action']) ?></td>
                            <td class="small text-secondary"><?= htmlspecialchars($l['details']) ?></td>
                            <td><code class="extra-small"><?= htmlspecialchars($l['ip_address'] ?? '127.0.0.1') ?></code></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
