<?php
/**
 * Supplier Performance Analysis System
 * Edit Supplier Details Form & Validation
 */

$page_title = "Edit Supplier";
require_once __DIR__ . '/../includes/header.php';
require_role(['Admin', 'Manager']);

$db = get_db();
$id = (int)($_GET['id'] ?? 0);

// Fetch existing supplier record
$stmt = $db->prepare("SELECT * FROM suppliers WHERE id = ?");
$stmt->execute([$id]);
$supplier = $stmt->fetch();

if (!$supplier) {
    set_flash('danger', 'Supplier not found or already deleted.');
    header("Location: " . BASE_URL . "suppliers/index.php");
    exit;
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf_token = $_POST['csrf_token'] ?? '';
    if (!verify_csrf_token($csrf_token)) {
        $errors[] = "Security token validation failed. Please try again.";
    } else {
        $supplier_code     = trim($_POST['supplier_code'] ?? '');
        $supplier_name     = trim($_POST['supplier_name'] ?? '');
        $company_name      = trim($_POST['company_name'] ?? '');
        $email             = trim($_POST['email'] ?? '');
        $phone             = trim($_POST['phone'] ?? '');
        $address           = trim($_POST['address'] ?? '');
        $city              = trim($_POST['city'] ?? '');
        $state             = trim($_POST['state'] ?? '');
        $country           = trim($_POST['country'] ?? 'India');
        $category          = trim($_POST['category'] ?? 'Electronics');
        $products_supplied = trim($_POST['products_supplied'] ?? '');
        $registration_num  = trim($_POST['registration_number'] ?? '');
        $contract_start    = trim($_POST['contract_start_date'] ?? '');
        $contract_end      = trim($_POST['contract_end_date'] ?? '');
        $status            = trim($_POST['status'] ?? 'Active');

        if (empty($supplier_code)) $errors[] = "Supplier Code is required.";
        if (empty($supplier_name)) $errors[] = "Supplier Name is required.";
        if (empty($company_name)) $errors[] = "Company Legal Name is required.";
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "A valid Email is required.";
        if (empty($phone)) $errors[] = "Phone is required.";
        if (empty($city)) $errors[] = "City is required.";
        if (empty($state)) $errors[] = "State is required.";
        if (empty($contract_start) || empty($contract_end)) $errors[] = "Contract dates are required.";

        // Check duplicate code (excluding current record)
        if (empty($errors)) {
            $dup_stmt = $db->prepare("SELECT COUNT(*) FROM suppliers WHERE supplier_code = ? AND id != ?");
            $dup_stmt->execute([$supplier_code, $id]);
            if ($dup_stmt->fetchColumn() > 0) {
                $errors[] = "Supplier Code '{$supplier_code}' is already assigned to another vendor.";
            }
        }

        if (empty($errors)) {
            try {
                $update_stmt = $db->prepare("
                    UPDATE suppliers SET
                        supplier_code = ?, supplier_name = ?, company_name = ?, email = ?,
                        phone = ?, address = ?, city = ?, state = ?, country = ?,
                        category = ?, products_supplied = ?, registration_number = ?,
                        contract_start_date = ?, contract_end_date = ?, status = ?
                    WHERE id = ?
                ");

                $update_stmt->execute([
                    $supplier_code, $supplier_name, $company_name, $email,
                    $phone, $address, $city, $state, $country,
                    $category, $products_supplied, $registration_num,
                    $contract_start, $contract_end, $status,
                    $id
                ]);

                log_activity($_SESSION['user_id'], 'Supplier Updated', "Modified details for supplier: $supplier_name ($supplier_code)");
                set_flash('success', "Supplier '{$supplier_name}' was successfully updated.");
                header("Location: " . BASE_URL . "suppliers/view.php?id=" . $id);
                exit;

            } catch (Exception $e) {
                $errors[] = "Database update failed: " . $e->getMessage();
            }
        }
    }
}
?>

<!-- Header -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 fw-bold text-dark mb-1">Edit Supplier</h1>
        <p class="text-muted small mb-0">Modify information, contact records, or operational status for <?= htmlspecialchars($supplier['supplier_name']) ?>.</p>
    </div>
    <a href="<?= BASE_URL ?>suppliers/view.php?id=<?= $id ?>" class="btn btn-outline-secondary btn-sm rounded-pill">
        <i class="fa-solid fa-arrow-left me-1"></i> Back to Profile
    </a>
</div>

<?php if (!empty($errors)): ?>
    <div class="alert alert-danger alert-dismissible fade show shadow-sm" role="alert">
        <h6 class="fw-bold mb-1"><i class="fa-solid fa-triangle-exclamation me-2"></i> Please fix the following errors:</h6>
        <ul class="mb-0 small ps-3">
            <?php foreach ($errors as $err): ?>
                <li><?= htmlspecialchars($err) ?></li>
            <?php endforeach; ?>
        </ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<form action="<?= htmlspecialchars($_SERVER['REQUEST_URI']) ?>" method="POST">
    <?= csrf_input() ?>

    <div class="row g-4">
        
        <!-- Left: Basic Info -->
        <div class="col-12 col-lg-7">
            <div class="card-saas mb-4">
                <div class="card-saas-header">
                    <h6 class="fw-bold mb-0 text-dark"><i class="fa-solid fa-id-card text-primary me-2"></i> Basic Details</h6>
                </div>
                <div class="card-saas-body">
                    <div class="row g-3">
                        <div class="col-12 col-md-4">
                            <label class="form-label small fw-semibold text-secondary">Supplier Code *</label>
                            <input type="text" name="supplier_code" class="form-control form-control-sm font-monospace" value="<?= htmlspecialchars($_POST['supplier_code'] ?? $supplier['supplier_code']) ?>" required>
                        </div>
                        <div class="col-12 col-md-8">
                            <label class="form-label small fw-semibold text-secondary">Supplier Trade Name *</label>
                            <input type="text" name="supplier_name" class="form-control form-control-sm" value="<?= htmlspecialchars($_POST['supplier_name'] ?? $supplier['supplier_name']) ?>" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-semibold text-secondary">Registered Company Legal Name *</label>
                            <input type="text" name="company_name" class="form-control form-control-sm" value="<?= htmlspecialchars($_POST['company_name'] ?? $supplier['company_name']) ?>" required>
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label small fw-semibold text-secondary">Official Email Address *</label>
                            <input type="email" name="email" class="form-control form-control-sm" value="<?= htmlspecialchars($_POST['email'] ?? $supplier['email']) ?>" required>
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label small fw-semibold text-secondary">Phone Number *</label>
                            <input type="text" name="phone" class="form-control form-control-sm" value="<?= htmlspecialchars($_POST['phone'] ?? $supplier['phone']) ?>" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-semibold text-secondary">Physical Address</label>
                            <textarea name="address" class="form-control form-control-sm" rows="2"><?= htmlspecialchars($_POST['address'] ?? $supplier['address']) ?></textarea>
                        </div>
                        <div class="col-12 col-md-4">
                            <label class="form-label small fw-semibold text-secondary">City *</label>
                            <input type="text" name="city" class="form-control form-control-sm" value="<?= htmlspecialchars($_POST['city'] ?? $supplier['city']) ?>" required>
                        </div>
                        <div class="col-12 col-md-4">
                            <label class="form-label small fw-semibold text-secondary">State *</label>
                            <input type="text" name="state" class="form-control form-control-sm" value="<?= htmlspecialchars($_POST['state'] ?? $supplier['state']) ?>" required>
                        </div>
                        <div class="col-12 col-md-4">
                            <label class="form-label small fw-semibold text-secondary">Country *</label>
                            <input type="text" name="country" class="form-control form-control-sm" value="<?= htmlspecialchars($_POST['country'] ?? $supplier['country']) ?>" required>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Products Supplied -->
            <div class="card-saas">
                <div class="card-saas-header">
                    <h6 class="fw-bold mb-0 text-dark"><i class="fa-solid fa-boxes-stacked text-primary me-2"></i> Supplied Products List</h6>
                </div>
                <div class="card-saas-body">
                    <textarea name="products_supplied" class="form-control form-control-sm" rows="3"><?= htmlspecialchars($_POST['products_supplied'] ?? $supplier['products_supplied']) ?></textarea>
                </div>
            </div>
        </div>

        <!-- Right: Business & Contract -->
        <div class="col-12 col-lg-5">
            <div class="card-saas mb-4">
                <div class="card-saas-header">
                    <h6 class="fw-bold mb-0 text-dark"><i class="fa-solid fa-briefcase text-primary me-2"></i> Business & Contract Terms</h6>
                </div>
                <div class="card-saas-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label small fw-semibold text-secondary">Category *</label>
                            <?php $cat = $_POST['category'] ?? $supplier['category']; ?>
                            <select name="category" class="form-select form-select-sm" required>
                                <option value="Electronics" <?= $cat === 'Electronics' ? 'selected' : '' ?>>Electronics</option>
                                <option value="Raw Materials" <?= $cat === 'Raw Materials' ? 'selected' : '' ?>>Raw Materials</option>
                                <option value="Packaging" <?= $cat === 'Packaging' ? 'selected' : '' ?>>Packaging</option>
                                <option value="Logistics" <?= $cat === 'Logistics' ? 'selected' : '' ?>>Logistics</option>
                                <option value="Machinery" <?= $cat === 'Machinery' ? 'selected' : '' ?>>Machinery</option>
                                <option value="IT Services" <?= $cat === 'IT Services' ? 'selected' : '' ?>>IT Services</option>
                                <option value="Chemicals" <?= $cat === 'Chemicals' ? 'selected' : '' ?>>Chemicals</option>
                                <option value="Other" <?= $cat === 'Other' ? 'selected' : '' ?>>Other</option>
                            </select>
                        </div>

                        <div class="col-12">
                            <label class="form-label small fw-semibold text-secondary">Registration / Tax ID</label>
                            <input type="text" name="registration_number" class="form-control form-control-sm font-monospace" value="<?= htmlspecialchars($_POST['registration_number'] ?? $supplier['registration_number']) ?>">
                        </div>

                        <div class="col-12 col-md-6">
                            <label class="form-label small fw-semibold text-secondary">Contract Start Date *</label>
                            <input type="date" name="contract_start_date" class="form-control form-control-sm" value="<?= htmlspecialchars($_POST['contract_start_date'] ?? $supplier['contract_start_date']) ?>" required>
                        </div>

                        <div class="col-12 col-md-6">
                            <label class="form-label small fw-semibold text-secondary">Contract End Date *</label>
                            <input type="date" name="contract_end_date" class="form-control form-control-sm" value="<?= htmlspecialchars($_POST['contract_end_date'] ?? $supplier['contract_end_date']) ?>" required>
                        </div>

                        <div class="col-12">
                            <label class="form-label small fw-semibold text-secondary">Operational Status</label>
                            <?php $st = $_POST['status'] ?? $supplier['status']; ?>
                            <select name="status" class="form-select form-select-sm">
                                <option value="Active" <?= $st === 'Active' ? 'selected' : '' ?>>Active</option>
                                <option value="Inactive" <?= $st === 'Inactive' ? 'selected' : '' ?>>Inactive</option>
                                <option value="Suspended" <?= $st === 'Suspended' ? 'selected' : '' ?>>Suspended</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Submit Button -->
            <div class="d-flex gap-2">
                <a href="<?= BASE_URL ?>suppliers/view.php?id=<?= $id ?>" class="btn btn-light border flex-grow-1">Cancel</a>
                <button type="submit" class="btn btn-primary flex-grow-1 shadow-sm d-flex align-items-center justify-content-center gap-2">
                    <i class="fa-solid fa-floppy-disk"></i>
                    <span>Save Changes</span>
                </button>
            </div>
        </div>

    </div>
</form>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
