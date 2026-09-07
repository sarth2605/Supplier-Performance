<?php
/**
 * Supplier Performance Analysis and Management System
 * Add New Cosmetics & Skincare Supplier
 */

$page_title = "Add New Supplier";
require_once __DIR__ . '/../includes/header.php';
require_role(['admin', 'manager']);

$db = get_db();
$errors = [];

$form = [
    'supplier_code'       => 'SUP' . str_pad(rand(13, 999), 3, '0', STR_PAD_LEFT),
    'supplier_name'       => '',
    'contact_person'      => '',
    'email'               => '',
    'phone'               => '',
    'address'             => '',
    'city'                => 'Mumbai',
    'state'               => 'Maharashtra',
    'category'            => 'Face Products',
    'supplier_type'       => 'Manufacturer',
    'contract_start_date' => date('Y-m-d'),
    'payment_terms'       => 'Net 30',
    'status'              => 'Active'
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf = $_POST['csrf_token'] ?? '';
    if (!verify_csrf_token($csrf)) {
        $errors[] = "Security validation failed. Please try again.";
    } else {
        foreach ($form as $k => $v) {
            $form[$k] = trim($_POST[$k] ?? '');
        }

        if (empty($form['supplier_code'])) $errors[] = "Supplier Code is required.";
        if (empty($form['supplier_name'])) $errors[] = "Supplier Name is required.";
        if (empty($form['contact_person'])) $errors[] = "Contact Person is required.";
        if (empty($form['email']) || !filter_var($form['email'], FILTER_VALIDATE_EMAIL)) $errors[] = "Valid email is required.";
        if (empty($form['phone'])) $errors[] = "Phone number is required.";
        if (empty($form['city'])) $errors[] = "City is required.";
        if (empty($form['state'])) $errors[] = "State is required.";

        if (empty($errors)) {
            $dup_stmt = $db->prepare("SELECT COUNT(*) FROM suppliers WHERE supplier_code = ?");
            $dup_stmt->execute([$form['supplier_code']]);
            if ($dup_stmt->fetchColumn() > 0) {
                $errors[] = "Supplier Code '{$form['supplier_code']}' already exists.";
            }
        }

        if (empty($errors)) {
            try {
                $stmt = $db->prepare("
                    INSERT INTO suppliers (
                        supplier_code, supplier_name, contact_person, email, phone,
                        address, city, state, category, supplier_type, contract_start_date, payment_terms, status, created_at
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
                ");
                $stmt->execute([
                    $form['supplier_code'],
                    $form['supplier_name'],
                    $form['contact_person'],
                    $form['email'],
                    $form['phone'],
                    $form['address'],
                    $form['city'],
                    $form['state'],
                    $form['category'],
                    $form['supplier_type'],
                    $form['contract_start_date'],
                    $form['payment_terms'],
                    $form['status']
                ]);

                $new_id = $db->lastInsertId();

                // Compute baseline score
                recalculate_supplier_period_score($new_id, date('Y') . '-Q' . ceil(date('n') / 3));

                log_activity($_SESSION['user_id'], 'Supplier Registered', 'Suppliers', $new_id, "Added supplier {$form['supplier_name']} ({$form['supplier_code']})");

                set_flash('success', "Cosmetics Supplier '{$form['supplier_name']}' registered successfully!");
                header("Location: " . BASE_URL . "suppliers/view.php?id=" . $new_id);
                exit;

            } catch (Exception $e) {
                $errors[] = "Failed to add supplier: " . $e->getMessage();
            }
        }
    }
}
?>

<!-- Header -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 fw-bold text-dark mb-1">Add New Cosmetics Supplier</h1>
        <p class="text-muted small mb-0">Onboard a new beauty formulation lab, raw material distributor, or packaging vendor.</p>
    </div>
    <a href="<?= BASE_URL ?>suppliers/index.php" class="btn btn-outline-secondary btn-sm rounded-pill">
        <i class="fa-solid fa-arrow-left me-1"></i> Back to Directory
    </a>
</div>

<?php if (!empty($errors)): ?>
    <div class="alert alert-danger alert-dismissible fade show shadow-sm" role="alert">
        <h6 class="fw-bold mb-1"><i class="fa-solid fa-triangle-exclamation me-2"></i> Errors:</h6>
        <ul class="mb-0 small ps-3">
            <?php foreach ($errors as $err): ?>
                <li><?= htmlspecialchars($err) ?></li>
            <?php endforeach; ?>
        </ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="card-saas" style="max-width: 900px;">
    <div class="card-saas-header">
        <h6 class="fw-bold mb-0 text-dark"><i class="fa-solid fa-building text-primary me-2"></i> Supplier Registration Details</h6>
    </div>
    <div class="card-saas-body">
        <form action="<?= htmlspecialchars($_SERVER['PHP_SELF']) ?>" method="POST">
            <?= csrf_input() ?>

            <!-- SECTION 1: SUPPLIER BASIC INFORMATION -->
            <div class="mb-4">
                <div class="d-flex align-items-center gap-2 mb-3 pb-1 border-bottom">
                    <span class="badge bg-primary rounded-circle extra-small" style="width: 20px; height: 20px; display: inline-flex; align-items: center; justify-content: center;">1</span>
                    <h6 class="fw-bold text-dark mb-0 small text-uppercase tracking-wider">Supplier Basic Information</h6>
                </div>
                <div class="row g-3">
                    <div class="col-12 col-md-4">
                        <label class="form-label small fw-semibold text-secondary">Supplier Code <span class="text-danger">*</span></label>
                        <input type="text" name="supplier_code" class="form-control form-control-sm font-monospace" value="<?= htmlspecialchars($form['supplier_code']) ?>" placeholder="e.g. SUP015" required>
                        <span class="extra-small text-muted">Unique corporate code / SKU identifier</span>
                    </div>
                    <div class="col-12 col-md-8">
                        <label class="form-label small fw-semibold text-secondary">Supplier Company / Brand Name <span class="text-danger">*</span></label>
                        <input type="text" name="supplier_name" class="form-control form-control-sm" placeholder="e.g. PureBotanics Lab & Supply Hub" value="<?= htmlspecialchars($form['supplier_name']) ?>" required>
                    </div>
                    <div class="col-12 col-md-6">
                        <label class="form-label small fw-semibold text-secondary">Primary Product Category <span class="text-danger">*</span></label>
                        <select name="category" class="form-select form-select-sm" required>
                            <option value="Face Products">💄 Face Products</option>
                            <option value="Eye Products">👁️ Eye Products</option>
                            <option value="Lip Products">💋 Lip Products</option>
                            <option value="Skincare Products">🧴 Skincare Products</option>
                            <option value="Nail Products">💅 Nail Products</option>
                            <option value="Hair Beauty Products">💇 Hair Beauty Products</option>
                            <option value="Body Care Products">🧼 Body Care Products</option>
                            <option value="Beauty Tools & Accessories">🧑‍🦱 Beauty Tools & Accessories</option>
                        </select>
                    </div>
                    <div class="col-12 col-md-6">
                        <label class="form-label small fw-semibold text-secondary">Supplier Classification</label>
                        <select name="supplier_type" class="form-select form-select-sm">
                            <option value="Manufacturer" selected>Manufacturer / Primary Lab</option>
                            <option value="Contract Manufacturer">Contract Manufacturer</option>
                            <option value="Organic Formulator">Organic Formulator / R&D</option>
                            <option value="Importer & Supplier">National Distribution Hub</option>
                            <option value="Packaging Specialist">Packaging Specialist</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- SECTION 2: CONTACT INFORMATION -->
            <div class="mb-4">
                <div class="d-flex align-items-center gap-2 mb-3 pb-1 border-bottom">
                    <span class="badge bg-primary rounded-circle extra-small" style="width: 20px; height: 20px; display: inline-flex; align-items: center; justify-content: center;">2</span>
                    <h6 class="fw-bold text-dark mb-0 small text-uppercase tracking-wider">Contact Information</h6>
                </div>
                <div class="row g-3">
                    <div class="col-12 col-md-4">
                        <label class="form-label small fw-semibold text-secondary">Contact Person <span class="text-danger">*</span></label>
                        <input type="text" name="contact_person" class="form-control form-control-sm" placeholder="e.g. Rahul Sharma" value="<?= htmlspecialchars($form['contact_person']) ?>" required>
                    </div>
                    <div class="col-12 col-md-4">
                        <label class="form-label small fw-semibold text-secondary">Official Email Address <span class="text-danger">*</span></label>
                        <input type="email" name="email" class="form-control form-control-sm" placeholder="sales@purebotanics.in" value="<?= htmlspecialchars($form['email']) ?>" required>
                    </div>
                    <div class="col-12 col-md-4">
                        <label class="form-label small fw-semibold text-secondary">Contact Phone / Mobile <span class="text-danger">*</span></label>
                        <input type="text" name="phone" class="form-control form-control-sm" placeholder="9876543210" value="<?= htmlspecialchars($form['phone']) ?>" required>
                    </div>
                </div>
            </div>

            <!-- SECTION 3: ADDRESS INFORMATION -->
            <div class="mb-4">
                <div class="d-flex align-items-center gap-2 mb-3 pb-1 border-bottom">
                    <span class="badge bg-primary rounded-circle extra-small" style="width: 20px; height: 20px; display: inline-flex; align-items: center; justify-content: center;">3</span>
                    <h6 class="fw-bold text-dark mb-0 small text-uppercase tracking-wider">Address & Facility Information</h6>
                </div>
                <div class="row g-3">
                    <div class="col-12 col-md-6">
                        <label class="form-label small fw-semibold text-secondary">Facility Address</label>
                        <input type="text" name="address" class="form-control form-control-sm" placeholder="Plot / Industrial Estate Area..." value="<?= htmlspecialchars($form['address']) ?>">
                    </div>
                    <div class="col-6 col-md-3">
                        <label class="form-label small fw-semibold text-secondary">City <span class="text-danger">*</span></label>
                        <input type="text" name="city" class="form-control form-control-sm" placeholder="Mumbai" value="<?= htmlspecialchars($form['city']) ?>" required>
                    </div>
                    <div class="col-6 col-md-3">
                        <label class="form-label small fw-semibold text-secondary">State <span class="text-danger">*</span></label>
                        <input type="text" name="state" class="form-control form-control-sm" placeholder="Maharashtra" value="<?= htmlspecialchars($form['state']) ?>" required>
                    </div>
                </div>
            </div>

            <!-- SECTION 4: CONTRACT & ACCOUNT INFORMATION -->
            <div class="mb-4">
                <div class="d-flex align-items-center gap-2 mb-3 pb-1 border-bottom">
                    <span class="badge bg-primary rounded-circle extra-small" style="width: 20px; height: 20px; display: inline-flex; align-items: center; justify-content: center;">4</span>
                    <h6 class="fw-bold text-dark mb-0 small text-uppercase tracking-wider">Contract & Commercial Terms</h6>
                </div>
                <div class="row g-3">
                    <div class="col-12 col-md-4">
                        <label class="form-label small fw-semibold text-secondary">Contract Start Date <span class="text-danger">*</span></label>
                        <input type="date" name="contract_start_date" class="form-control form-control-sm" value="<?= htmlspecialchars($form['contract_start_date']) ?>" required>
                    </div>
                    <div class="col-12 col-md-4">
                        <label class="form-label small fw-semibold text-secondary">Payment Terms</label>
                        <select name="payment_terms" class="form-select form-select-sm">
                            <option value="Net 30" selected>Net 30 Days</option>
                            <option value="Net 15">Net 15 Days</option>
                            <option value="Net 45">Net 45 Days</option>
                            <option value="Net 60">Net 60 Days</option>
                            <option value="Advance">100% Advance Payment</option>
                        </select>
                    </div>
                    <div class="col-12 col-md-4">
                        <label class="form-label small fw-semibold text-secondary">Operational Status</label>
                        <select name="status" class="form-select form-select-sm">
                            <option value="Active" selected>Active / Approved</option>
                            <option value="Inactive">Inactive / Suspended</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="mt-4 pt-3 border-top text-end d-flex justify-content-end gap-2">
                <a href="<?= BASE_URL ?>suppliers/index.php" class="btn btn-light border btn-sm px-3 rounded-3">Cancel</a>
                <button type="submit" class="btn btn-primary btn-sm px-4 rounded-3 shadow-sm fw-semibold">
                    <i class="fa-solid fa-floppy-disk me-1"></i> Register Supplier
                </button>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
