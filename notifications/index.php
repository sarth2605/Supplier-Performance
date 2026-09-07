<?php
/**
 * Supplier Performance Analysis System
 * Notifications & Risk Alert Center
 */

$page_title = "Notification Center";
require_once __DIR__ . '/../includes/header.php';

$db = get_db();

// Handle Mark Single as Read
if (isset($_GET['mark_read']) && (int)$_GET['mark_read'] > 0) {
    $nid = (int)$_GET['mark_read'];
    $stmt = $db->prepare("UPDATE notifications SET is_read = 1 WHERE id = ?");
    $stmt->execute([$nid]);
    set_flash('success', 'Notification marked as read.');
    header("Location: " . BASE_URL . "notifications/index.php");
    exit;
}

// Handle Mark All as Read
if (isset($_GET['mark_all_read']) && $_GET['mark_all_read'] === '1') {
    $db->query("UPDATE notifications SET is_read = 1");
    set_flash('success', 'All notifications marked as read.');
    header("Location: " . BASE_URL . "notifications/index.php");
    exit;
}

// Fetch all notifications
$notifications_stmt = $db->query("
    SELECT n.*, u.name as user_name
    FROM notifications n
    LEFT JOIN users u ON n.user_id = u.id
    ORDER BY n.is_read ASC, n.created_at DESC
");
$notifications = $notifications_stmt->fetchAll();
?>

<!-- Header -->
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
    <div>
        <h1 class="h3 fw-bold text-dark mb-1">Notifications & Alert Center</h1>
        <p class="text-muted small mb-0">Review operational risk triggers, compliance breaches, and automated supplier status updates.</p>
    </div>
    <div>
        <a href="?mark_all_read=1" class="btn btn-outline-secondary btn-sm rounded-pill d-inline-flex align-items-center gap-2">
            <i class="fa-solid fa-check-double text-primary"></i> Mark All as Read
        </a>
    </div>
</div>

<div class="card-saas">
    <div class="card-saas-header">
        <h6 class="fw-bold mb-0 text-dark">System Alerts & Notifications (<?= count($notifications) ?>)</h6>
    </div>
    
    <div class="card-saas-body p-0">
        <div class="list-group list-group-flush">
            <?php if (empty($notifications)): ?>
                <div class="text-center py-5 text-muted">
                    <i class="fa-regular fa-bell-slash fs-1 text-secondary opacity-50 mb-2 d-block"></i>
                    <p class="mb-0 fw-semibold">No notifications or risk alerts at this time.</p>
                </div>
            <?php else: ?>
                <?php foreach ($notifications as $n): ?>
                    <?php
                    $icon = 'fa-solid fa-circle-info text-info';
                    $bg = 'bg-info-subtle';
                    if ($n['type'] === 'Critical') {
                        $icon = 'fa-solid fa-circle-xmark text-danger';
                        $bg = 'bg-danger-subtle';
                    } elseif ($n['type'] === 'Warning') {
                        $icon = 'fa-solid fa-triangle-exclamation text-warning';
                        $bg = 'bg-warning-subtle';
                    } elseif ($n['type'] === 'Success') {
                        $icon = 'fa-solid fa-circle-check text-success';
                        $bg = 'bg-success-subtle';
                    }
                    ?>
                    <div class="list-group-item p-3 d-flex align-items-start gap-3 <?= !$n['is_read'] ? 'bg-light' : '' ?>">
                        <div class="avatar-circle <?= $bg ?>" style="width: 40px; height: 40px; font-size: 1.1rem; flex-shrink: 0;">
                            <i class="<?= $icon ?>"></i>
                        </div>
                        <div class="flex-grow-1">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <h6 class="fw-bold text-dark mb-0 <?= !$n['is_read'] ? 'text-primary' : '' ?>">
                                    <?= htmlspecialchars($n['title']) ?>
                                    <?php if (!$n['is_read']): ?>
                                        <span class="badge bg-primary extra-small ms-2">New</span>
                                    <?php endif; ?>
                                </h6>
                                <span class="text-muted extra-small"><i class="fa-regular fa-clock me-1"></i> <?= format_date($n['created_at']) ?></span>
                            </div>
                            <p class="text-secondary small mb-1"><?= htmlspecialchars($n['message']) ?></p>
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="extra-small text-muted">Triggered for: <?= htmlspecialchars($n['user_name'] ?? 'All Users') ?></span>
                                <?php if (!$n['is_read']): ?>
                                    <a href="?mark_read=<?= $n['id'] ?>" class="btn btn-sm btn-link text-decoration-none extra-small p-0 text-primary">
                                        <i class="fa-solid fa-check me-1"></i> Mark as Read
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
