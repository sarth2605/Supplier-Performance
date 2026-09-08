<?php
/**
 * Supplier Performance Analysis and Management System (SPAS)
 * Modern Unified Dynamic Registration Gateway
 * Public registration for: Manufacturer, Supplier, Shopkeeper
 * (Admin accounts are strictly pre-provisioned and restricted from public registration)
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

// If already logged in, redirect directly to their dashboard
if (is_logged_in()) {
    redirect_to_role_dashboard($_SESSION['user_role'] ?? 'admin');
}

$error = '';
$db = get_db();

$selected_role = strtolower(trim($_GET['role'] ?? $_POST['role'] ?? 'manufacturer'));
$public_roles = ['manufacturer', 'supplier', 'shopkeeper'];
if (!in_array($selected_role, $public_roles)) {
    $selected_role = 'manufacturer';
}

// Form state preservation
$formData = [
    'name' => '',
    'email' => '',
    'phone' => '',
    'city' => 'Mumbai',
    'state' => 'Maharashtra',
    'company_name' => '',
    'supplier_name' => '',
    'shop_name' => '',
    'address' => '',
    'category' => 'Skincare Products',
    'payment_terms' => 'Net 30',
    'shop_type' => 'Cosmetics Boutique',
    'factory_reg_no' => '',
    'production_capacity' => '50,000',
    'tax_id' => '',
    'trade_license' => ''
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf_token = $_POST['csrf_token'] ?? '';
    $selected_role = strtolower(trim($_POST['role'] ?? 'manufacturer'));
    
    // Strict RBAC enforcement: Block any attempt to register as Admin
    if (!in_array($selected_role, $public_roles)) {
        $error = 'Invalid registration role specified. Admin accounts are pre-provisioned and cannot be registered publicly.';
    } elseif (!verify_csrf_token($csrf_token)) {
        $error = 'Security session expired. Please refresh the page and try again.';
    } else {
        // Collect common fields
        $formData['name'] = trim($_POST['name'] ?? '');
        $formData['email'] = trim($_POST['email'] ?? '');
        $formData['phone'] = trim($_POST['phone'] ?? '');
        $formData['city'] = trim($_POST['city'] ?? '');
        $formData['state'] = trim($_POST['state'] ?? '');
        $formData['address'] = trim($_POST['address'] ?? '');
        
        $password = $_POST['password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';

        // Role-specific fields
        $formData['company_name'] = trim($_POST['company_name'] ?? '');
        $formData['supplier_name'] = trim($_POST['supplier_name'] ?? '');
        $formData['shop_name'] = trim($_POST['shop_name'] ?? '');
        $formData['category'] = trim($_POST['category'] ?? 'Skincare Products');
        $formData['payment_terms'] = trim($_POST['payment_terms'] ?? 'Net 30');
        $formData['shop_type'] = trim($_POST['shop_type'] ?? 'Cosmetics Boutique');
        $formData['factory_reg_no'] = trim($_POST['factory_reg_no'] ?? '');
        $formData['production_capacity'] = trim($_POST['production_capacity'] ?? '');
        $formData['tax_id'] = trim($_POST['tax_id'] ?? '');
        $formData['trade_license'] = trim($_POST['trade_license'] ?? '');

        // Validation
        if (empty($formData['name']) || empty($formData['email']) || empty($password)) {
            $error = 'Please fill in all mandatory fields: Full Name, Email, and Password.';
        } elseif (!filter_var($formData['email'], FILTER_VALIDATE_EMAIL)) {
            $error = 'Please enter a valid email address.';
        } elseif (strlen($password) < 6) {
            $error = 'Password must be at least 6 characters long.';
        } elseif ($password !== $confirm_password) {
            $error = 'Passwords do not match. Please verify your confirmation password.';
        } else {
            // Check if email already exists
            $check = $db->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
            $check->execute([$formData['email']]);
            if ($check->fetch()) {
                $error = 'An account with this email address already exists. Please sign in or use another email.';
            } else {
                $hash = password_hash($password, PASSWORD_BCRYPT);

                if ($selected_role === 'manufacturer') {
                    if (empty($formData['company_name'])) {
                        $error = 'Please enter your Manufacturing Company / Lab name.';
                    } else {
                        $ins = $db->prepare("
                            INSERT INTO users (name, email, password, role, company_name, phone, city, state, status)
                            VALUES (?, ?, ?, 'manufacturer', ?, ?, ?, ?, 'Active')
                        ");
                        $ins->execute([
                            $formData['name'],
                            $formData['email'],
                            $hash,
                            $formData['company_name'],
                            $formData['phone'],
                            $formData['city'],
                            $formData['state']
                        ]);
                        $new_id = $db->lastInsertId();
                        log_activity($new_id, 'Manufacturer Registered', 'Auth', $new_id, 'New manufacturer lab registered: ' . $formData['company_name']);
                        set_flash('success', 'Manufacturer account registered successfully! Please sign in with your credentials.');
                        header('Location: ' . BASE_URL . 'auth/login.php?role=manufacturer');
                        exit;
                    }
                } elseif ($selected_role === 'supplier') {
                    if (empty($formData['supplier_name'])) {
                        $error = 'Please enter your Supplier / Distribution Hub name.';
                    } else {
                        // Generate next supplier code e.g. SUP014
                        $last_sup = $db->query("SELECT id FROM suppliers ORDER BY id DESC LIMIT 1")->fetch();
                        $next_id = ($last_sup['id'] ?? 10) + 1;
                        $sup_code = 'SUP' . str_pad($next_id, 3, '0', STR_PAD_LEFT);

                        // Insert into suppliers directory table
                        $ins_sup = $db->prepare("
                            INSERT INTO suppliers (supplier_code, supplier_name, contact_person, phone, email, address, city, state, category, supplier_type, payment_terms, status)
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'Distributor', ?, 'Active')
                        ");
                        $ins_sup->execute([
                            $sup_code,
                            $formData['supplier_name'],
                            $formData['name'],
                            $formData['phone'],
                            $formData['email'],
                            $formData['address'],
                            $formData['city'],
                            $formData['state'],
                            $formData['category'],
                            $formData['payment_terms']
                        ]);
                        $supplier_profile_id = $db->lastInsertId();

                        // Insert into users authentication table
                        $ins_user = $db->prepare("
                            INSERT INTO users (name, email, password, role, company_name, phone, city, state, supplier_id, status)
                            VALUES (?, ?, ?, 'supplier', ?, ?, ?, ?, ?, 'Active')
                        ");
                        $ins_user->execute([
                            $formData['name'],
                            $formData['email'],
                            $hash,
                            $formData['supplier_name'],
                            $formData['phone'],
                            $formData['city'],
                            $formData['state'],
                            $supplier_profile_id
                        ]);
                        $new_id = $db->lastInsertId();

                        log_activity($new_id, 'Supplier Registered', 'Auth', $new_id, 'New supplier distribution hub registered: ' . $formData['supplier_name']);
                        set_flash('success', 'Supplier account registered successfully! Please sign in with your credentials.');
                        header('Location: ' . BASE_URL . 'auth/login.php?role=supplier');
                        exit;
                    }
                } elseif ($selected_role === 'shopkeeper') {
                    if (empty($formData['shop_name'])) {
                        $error = 'Please enter your Retail Boutique / Store name.';
                    } else {
                        $ins = $db->prepare("
                            INSERT INTO users (name, email, password, role, shop_name, phone, city, state, status)
                            VALUES (?, ?, ?, 'shopkeeper', ?, ?, ?, ?, 'Active')
                        ");
                        $ins->execute([
                            $formData['name'],
                            $formData['email'],
                            $hash,
                            $formData['shop_name'],
                            $formData['phone'],
                            $formData['city'],
                            $formData['state']
                        ]);
                        $new_id = $db->lastInsertId();

                        log_activity($new_id, 'Shopkeeper Registered', 'Auth', $new_id, 'New retail store registered: ' . $formData['shop_name']);
                        set_flash('success', 'Shopkeeper account registered successfully! Please sign in with your credentials.');
                        header('Location: ' . BASE_URL . 'auth/login.php?role=shopkeeper');
                        exit;
                    }
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Unified Registration — <?= APP_FULL_NAME ?></title>
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
    
    <!-- Bootstrap 5.3 & FontAwesome -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    
    <!-- Custom Style -->
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/style.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/responsive.css">

    <style>
        body {
            background-color: #0b0f19;
            background-image: 
                radial-gradient(circle at 10% 20%, rgba(37, 99, 235, 0.14), transparent 40%),
                radial-gradient(circle at 90% 80%, rgba(139, 92, 246, 0.14), transparent 40%),
                radial-gradient(circle at 50% 50%, rgba(16, 185, 129, 0.05), transparent 60%);
            font-family: 'Plus Jakarta Sans', sans-serif;
            color: #f8fafc;
            min-height: 100vh;
        }

        .register-card {
            background: rgba(17, 24, 39, 0.88);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.12);
            border-radius: 20px;
            box-shadow: 0 25px 60px -15px rgba(0, 0, 0, 0.6);
            width: 100%;
            max-width: 680px;
        }

        /* Segmented Role Pill Selector */
        .role-segmented-bar {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 14px;
            padding: 4px;
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 6px;
            user-select: none;
        }

        .role-pill-btn {
            background: transparent;
            border: none;
            border-radius: 10px;
            color: #94a3b8;
            font-size: 0.78rem;
            font-weight: 600;
            padding: 10px 6px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 4px;
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
            cursor: pointer;
            width: 100%;
        }

        .role-pill-btn i {
            font-size: 1.15rem;
            transition: transform 0.2s ease;
        }

        .role-pill-btn:hover {
            color: #ffffff;
            background: rgba(255, 255, 255, 0.06);
        }

        /* Active State */
        .role-pill-btn.active[data-role="manufacturer"] {
            background: #2563eb;
            color: #ffffff;
            box-shadow: 0 4px 14px rgba(37, 99, 235, 0.4);
        }
        .role-pill-btn.active[data-role="supplier"] {
            background: #059669;
            color: #ffffff;
            box-shadow: 0 4px 14px rgba(5, 150, 105, 0.4);
        }
        .role-pill-btn.active[data-role="shopkeeper"] {
            background: #7c3aed;
            color: #ffffff;
            box-shadow: 0 4px 14px rgba(124, 58, 237, 0.4);
        }

        .form-control-dark, .form-select-dark {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.15);
            color: #ffffff;
            border-radius: 10px;
            padding: 0.65rem 1rem;
            font-size: 0.9rem;
        }
        .form-control-dark:focus, .form-select-dark:focus {
            background: rgba(255, 255, 255, 0.08);
            border-color: #38bdf8;
            color: #ffffff;
            box-shadow: 0 0 0 3px rgba(56, 189, 248, 0.25);
        }
        .form-select-dark option {
            background: #0f172a;
            color: #ffffff;
        }

        .btn-submit-action {
            border-radius: 10px;
            font-weight: 700;
            padding: 0.75rem;
            font-size: 0.92rem;
            transition: all 0.2s ease;
        }
    </style>
</head>
<body class="d-flex flex-column justify-content-between">

    <!-- Top Navigation Header -->
    <header class="py-3 px-4 d-flex align-items-center justify-content-between">
        <a href="<?= BASE_URL ?>index.php" class="text-decoration-none d-flex align-items-center gap-2 text-white">
            <div style="width: 34px; height: 34px; border-radius: 8px; background: linear-gradient(135deg, #2563eb, #7c3aed); display: flex; align-items: center; justify-content: center;">
                <i class="fa-solid fa-chart-line text-white small"></i>
            </div>
            <span class="fw-bold tracking-tight">SPAS</span>
        </a>
        <div class="d-flex align-items-center gap-3">
            <a href="<?= BASE_URL ?>index.php" class="text-light text-decoration-none small hover-white">
                <i class="fa-solid fa-house me-1"></i> Home
            </a>
            <a href="<?= BASE_URL ?>auth/login.php" class="btn btn-outline-light btn-sm px-3 rounded-pill extra-small fw-semibold">
                <i class="fa-solid fa-right-to-bracket me-1"></i> Sign In
            </a>
        </div>
    </header>

    <!-- Center Unified Registration Card -->
    <main class="container py-4 d-flex align-items-center justify-content-center flex-grow-1">
        <div class="register-card p-4 p-md-4 shadow">
            
            <!-- Header Section -->
            <div class="text-center mb-3">
                <h4 class="fw-bold text-white mb-1">Create Account</h4>
                <p class="text-muted extra-small mb-0">Register your organization to join the supply chain network</p>
            </div>

            <!-- Administrator Restriction Note -->
            <div class="alert alert-dark border-secondary border-opacity-50 py-2 px-3 extra-small mb-3 rounded-3 d-flex align-items-center justify-content-between">
                <div class="d-flex align-items-center gap-2">
                    <i class="fa-solid fa-shield-halved text-danger"></i>
                    <span><strong>Admin accounts are pre-provisioned</strong> (Central governance only).</span>
                </div>
                <a href="<?= BASE_URL ?>auth/login.php?role=admin" class="text-danger fw-bold text-decoration-none ms-2">Admin Sign In &rarr;</a>
            </div>

            <!-- Flash & Error Alerts -->
            <?php render_flash(); ?>
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger py-2 px-3 extra-small mb-3 rounded-3 d-flex align-items-start gap-2">
                    <i class="fa-solid fa-circle-exclamation mt-0.5"></i>
                    <div><?= $error ?></div>
                </div>
            <?php endif; ?>

            <form method="POST" action="<?= BASE_URL ?>auth/register.php" id="unifiedRegisterForm">
                <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
                <input type="hidden" name="role" id="selectedRoleInput" value="<?= htmlspecialchars($selected_role) ?>">

                <!-- 1. Segmented Role Selector -->
                <div class="mb-3">
                    <label class="form-label extra-small fw-bold text-uppercase text-light mb-1.5 d-flex justify-content-between">
                        <span>Who is Registering?</span>
                        <span id="roleBadgeLabel" class="text-primary font-monospace" style="font-size: 0.68rem;">MANUFACTURER LAB</span>
                    </label>
                    <div class="role-segmented-bar">
                        <button type="button" class="role-pill-btn <?= ($selected_role === 'manufacturer') ? 'active' : '' ?>" data-role="manufacturer" onclick="switchRegRole('manufacturer')">
                            <i class="fa-solid fa-industry"></i>
                            <span>Manufacturer</span>
                        </button>
                        <button type="button" class="role-pill-btn <?= ($selected_role === 'supplier') ? 'active' : '' ?>" data-role="supplier" onclick="switchRegRole('supplier')">
                            <i class="fa-solid fa-truck-ramp-box"></i>
                            <span>Supplier</span>
                        </button>
                        <button type="button" class="role-pill-btn <?= ($selected_role === 'shopkeeper') ? 'active' : '' ?>" data-role="shopkeeper" onclick="switchRegRole('shopkeeper')">
                            <i class="fa-solid fa-store"></i>
                            <span>Shopkeeper</span>
                        </button>
                    </div>
                </div>

                <!-- 2. Dynamic Role-Specific Form Fields -->
                
                <!-- Manufacturer Dynamic Fields -->
                <div id="manufacturerFields" class="p-3 rounded-3 bg-primary bg-opacity-10 border border-primary border-opacity-25 mb-3" style="<?= ($selected_role === 'manufacturer') ? '' : 'display: none;' ?>">
                    <h6 class="fw-bold text-primary extra-small text-uppercase mb-2">
                        <i class="fa-solid fa-industry me-1"></i> Manufacturer Lab Details
                    </h6>
                    <div class="row g-2">
                        <div class="col-md-6">
                            <label class="form-label extra-small text-light mb-1">Company / Lab Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control form-control-dark" name="company_name" value="<?= htmlspecialchars($formData['company_name']) ?>" placeholder="e.g. GlowTech Formulation Labs">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label extra-small text-light mb-1">Factory License / Reg No.</label>
                            <input type="text" class="form-control form-control-dark" name="factory_reg_no" value="<?= htmlspecialchars($formData['factory_reg_no']) ?>" placeholder="e.g. FACT-MH-2026-891">
                        </div>
                        <div class="col-12">
                            <label class="form-label extra-small text-light mb-1">Monthly Production Capacity (Units)</label>
                            <input type="text" class="form-control form-control-dark" name="production_capacity" value="<?= htmlspecialchars($formData['production_capacity']) ?>" placeholder="e.g. 50,000 units/mo">
                        </div>
                    </div>
                </div>

                <!-- Supplier Dynamic Fields -->
                <div id="supplierFields" class="p-3 rounded-3 bg-success bg-opacity-10 border border-success border-opacity-25 mb-3" style="<?= ($selected_role === 'supplier') ? '' : 'display: none;' ?>">
                    <h6 class="fw-bold text-success extra-small text-uppercase mb-2">
                        <i class="fa-solid fa-truck-ramp-box me-1"></i> Supplier / Distribution Hub Details
                    </h6>
                    <div class="row g-2">
                        <div class="col-md-6">
                            <label class="form-label extra-small text-light mb-1">Supplier / Hub Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control form-control-dark" name="supplier_name" value="<?= htmlspecialchars($formData['supplier_name']) ?>" placeholder="e.g. Glow Beauty Distribution Hub">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label extra-small text-light mb-1">GSTIN / Tax ID</label>
                            <input type="text" class="form-control form-control-dark" name="tax_id" value="<?= htmlspecialchars($formData['tax_id']) ?>" placeholder="27AAACG0123M1Z5">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label extra-small text-light mb-1">Product Category</label>
                            <select class="form-select form-select-dark" name="category">
                                <option value="Skincare Products" <?= ($formData['category'] === 'Skincare Products') ? 'selected' : '' ?>>Skincare Products</option>
                                <option value="Haircare & Shampoos" <?= ($formData['category'] === 'Haircare & Shampoos') ? 'selected' : '' ?>>Haircare & Shampoos</option>
                                <option value="Organic Cosmetics" <?= ($formData['category'] === 'Organic Cosmetics') ? 'selected' : '' ?>>Organic Cosmetics</option>
                                <option value="Packaging & Dispensers" <?= ($formData['category'] === 'Packaging & Dispensers') ? 'selected' : '' ?>>Packaging & Dispensers</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label extra-small text-light mb-1">Payment Terms</label>
                            <select class="form-select form-select-dark" name="payment_terms">
                                <option value="Net 15" <?= ($formData['payment_terms'] === 'Net 15') ? 'selected' : '' ?>>Net 15 Days</option>
                                <option value="Net 30" <?= ($formData['payment_terms'] === 'Net 30') ? 'selected' : '' ?>>Net 30 Days (Standard)</option>
                                <option value="Net 60" <?= ($formData['payment_terms'] === 'Net 60') ? 'selected' : '' ?>>Net 60 Days</option>
                                <option value="Advance" <?= ($formData['payment_terms'] === 'Advance') ? 'selected' : '' ?>>100% Advance</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Shopkeeper Dynamic Fields -->
                <div id="shopkeeperFields" class="p-3 rounded-3 bg-purple bg-opacity-10 border border-purple border-opacity-25 mb-3" style="border-color: rgba(124, 58, 237, 0.3) !important; <?= ($selected_role === 'shopkeeper') ? '' : 'display: none;' ?>">
                    <h6 class="fw-bold extra-small text-uppercase mb-2" style="color: #c084fc;">
                        <i class="fa-solid fa-store me-1"></i> Retail Store / Boutique Details
                    </h6>
                    <div class="row g-2">
                        <div class="col-md-6">
                            <label class="form-label extra-small text-light mb-1">Store / Boutique Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control form-control-dark" name="shop_name" value="<?= htmlspecialchars($formData['shop_name']) ?>" placeholder="e.g. Luxe Glamour Boutique">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label extra-small text-light mb-1">Trade License / Shop Reg No.</label>
                            <input type="text" class="form-control form-control-dark" name="trade_license" value="<?= htmlspecialchars($formData['trade_license']) ?>" placeholder="TL-KA-2026-4412">
                        </div>
                        <div class="col-12">
                            <label class="form-label extra-small text-light mb-1">Retail Store Type</label>
                            <select class="form-select form-select-dark" name="shop_type">
                                <option value="Cosmetics Boutique" <?= ($formData['shop_type'] === 'Cosmetics Boutique') ? 'selected' : '' ?>>Cosmetics Boutique</option>
                                <option value="Beauty Salon & Spa" <?= ($formData['shop_type'] === 'Beauty Salon & Spa') ? 'selected' : '' ?>>Beauty Salon & Spa</option>
                                <option value="Department Store Counter" <?= ($formData['shop_type'] === 'Department Store Counter') ? 'selected' : '' ?>>Department Store Counter</option>
                                <option value="Online Retailer" <?= ($formData['shop_type'] === 'Online Retailer') ? 'selected' : '' ?>>Online E-Commerce Retailer</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- 3. Standard Account & Contact Credentials -->
                <div class="row g-2 mb-3">
                    <div class="col-md-6">
                        <label for="name" class="form-label extra-small fw-bold text-uppercase text-light mb-1">Full Name / Contact Person <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text bg-transparent border-secondary border-opacity-50 text-light border-end-0">
                                <i class="fa-solid fa-user"></i>
                            </span>
                            <input type="text" class="form-control form-control-dark border-start-0 ps-0" id="name" name="name" value="<?= htmlspecialchars($formData['name']) ?>" placeholder="e.g. Dr. Rajesh Mehta" required>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <label for="email" class="form-label extra-small fw-bold text-uppercase text-light mb-1">Work Email Address <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text bg-transparent border-secondary border-opacity-50 text-light border-end-0">
                                <i class="fa-solid fa-envelope"></i>
                            </span>
                            <input type="email" class="form-control form-control-dark border-start-0 ps-0" id="email" name="email" value="<?= htmlspecialchars($formData['email']) ?>" placeholder="contact@company.com" required>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <label for="phone" class="form-label extra-small fw-bold text-uppercase text-light mb-1">Phone Number</label>
                        <div class="input-group">
                            <span class="input-group-text bg-transparent border-secondary border-opacity-50 text-light border-end-0">
                                <i class="fa-solid fa-phone"></i>
                            </span>
                            <input type="tel" class="form-control form-control-dark border-start-0 ps-0" id="phone" name="phone" value="<?= htmlspecialchars($formData['phone']) ?>" placeholder="98XXXXXXXX">
                        </div>
                    </div>

                    <div class="col-md-3">
                        <label for="city" class="form-label extra-small fw-bold text-uppercase text-light mb-1">City</label>
                        <input type="text" class="form-control form-control-dark" id="city" name="city" value="<?= htmlspecialchars($formData['city']) ?>" placeholder="Mumbai">
                    </div>

                    <div class="col-md-3">
                        <label for="state" class="form-label extra-small fw-bold text-uppercase text-light mb-1">State</label>
                        <input type="text" class="form-control form-control-dark" id="state" name="state" value="<?= htmlspecialchars($formData['state']) ?>" placeholder="Maharashtra">
                    </div>

                    <div class="col-12">
                        <label for="address" class="form-label extra-small fw-bold text-uppercase text-light mb-1">Street Address</label>
                        <input type="text" class="form-control form-control-dark" id="address" name="address" value="<?= htmlspecialchars($formData['address']) ?>" placeholder="Industrial Area, Sector 4">
                    </div>

                    <div class="col-md-6">
                        <label for="password" class="form-label extra-small fw-bold text-uppercase text-light mb-1">Password <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text bg-transparent border-secondary border-opacity-50 text-light border-end-0">
                                <i class="fa-solid fa-lock"></i>
                            </span>
                            <input type="password" class="form-control form-control-dark border-start-0 border-end-0 ps-0" id="password" name="password" placeholder="Min 6 characters" required>
                            <button class="btn btn-outline-secondary border-secondary border-opacity-50 text-light" type="button" onclick="togglePassVisibility('password', 'passEye')">
                                <i class="fa-solid fa-eye" id="passEye"></i>
                            </button>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <label for="confirm_password" class="form-label extra-small fw-bold text-uppercase text-light mb-1">Confirm Password <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text bg-transparent border-secondary border-opacity-50 text-light border-end-0">
                                <i class="fa-solid fa-lock"></i>
                            </span>
                            <input type="password" class="form-control form-control-dark border-start-0 border-end-0 ps-0" id="confirm_password" name="confirm_password" placeholder="Repeat password" required>
                            <button class="btn btn-outline-secondary border-secondary border-opacity-50 text-light" type="button" onclick="togglePassVisibility('confirm_password', 'confPassEye')">
                                <i class="fa-solid fa-eye" id="confPassEye"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- 4. Submit Button -->
                <button type="submit" class="btn btn-primary w-100 btn-submit-action shadow-sm mb-3" id="submitRegBtn">
                    <span id="submitRegLabel">Register as Manufacturer</span> <i class="fa-solid fa-arrow-right ms-1"></i>
                </button>

                <!-- 5. Already Have Account Toggle -->
                <div class="text-center extra-small text-muted">
                    Already have an account? 
                    <a href="<?= BASE_URL ?>auth/login.php" class="text-info text-decoration-none fw-semibold">Sign In</a>
                </div>

            </form>
        </div>
    </main>

    <!-- Footer -->
    <footer class="py-3 px-4 text-center extra-small text-muted">
        <span>&copy; <?= date('Y') ?> Supplier Performance Analysis & Management System (SPAS). Enterprise v2.5</span>
    </footer>

    <!-- JavaScript Role Selector Logic -->
    <script>
        const roleConfig = {
            'manufacturer': {
                label: 'MANUFACTURER LAB',
                color: '#3b82f6',
                btnText: 'Register as Manufacturer',
                btnClass: 'btn-primary'
            },
            'supplier': {
                label: 'SUPPLIER HUB',
                color: '#10b981',
                btnText: 'Register as Supplier',
                btnClass: 'btn-success'
            },
            'shopkeeper': {
                label: 'RETAIL BOUTIQUE',
                color: '#c084fc',
                btnText: 'Register as Shopkeeper',
                btnClass: 'btn-primary'
            }
        };

        function switchRegRole(roleKey) {
            const role = roleConfig[roleKey] ? roleKey : 'manufacturer';
            const cfg = roleConfig[role];

            // 1. Update hidden input
            document.getElementById('selectedRoleInput').value = role;

            // 2. Update pill selector active class
            document.querySelectorAll('.role-pill-btn').forEach(btn => {
                btn.classList.toggle('active', btn.getAttribute('data-role') === role);
            });

            // 3. Update badge label
            const badge = document.getElementById('roleBadgeLabel');
            if (badge) {
                badge.innerText = cfg.label;
                badge.style.color = cfg.color;
            }

            // 4. Toggle dynamic fields
            document.getElementById('manufacturerFields').style.display = (role === 'manufacturer') ? 'block' : 'none';
            document.getElementById('supplierFields').style.display = (role === 'supplier') ? 'block' : 'none';
            document.getElementById('shopkeeperFields').style.display = (role === 'shopkeeper') ? 'block' : 'none';

            // 5. Update Submit button
            const submitBtn = document.getElementById('submitRegBtn');
            const submitLabel = document.getElementById('submitRegLabel');
            if (submitBtn && submitLabel) {
                submitLabel.innerText = cfg.btnText;
                submitBtn.className = 'btn w-100 btn-submit-action shadow-sm mb-3';
                if (role === 'manufacturer') {
                    submitBtn.classList.add('btn-primary');
                } else if (role === 'supplier') {
                    submitBtn.classList.add('btn-success');
                } else if (role === 'shopkeeper') {
                    submitBtn.classList.add('btn-primary');
                    submitBtn.style.backgroundColor = '#7c3aed';
                    submitBtn.style.borderColor = '#7c3aed';
                }
            }
        }

        function togglePassVisibility(inputId, iconId) {
            const input = document.getElementById(inputId);
            const icon = document.getElementById(iconId);
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }

        // Initialize on page load
        document.addEventListener('DOMContentLoaded', () => {
            const initialRole = "<?= $selected_role ?>";
            switchRegRole(initialRole);
        });
    </script>
</body>
</html>
