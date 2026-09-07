<?php
/**
 * Supplier Performance Analysis and Management System (SPAS)
 * Common Dynamic Registration Gateway
 * Public registration for: Manufacturer, Supplier, Shopkeeper
 * (Admin account is strictly excluded from public registration)
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
    'shop_type' => 'Cosmetics Boutique'
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf_token = $_POST['csrf_token'] ?? '';
    $selected_role = strtolower(trim($_POST['role'] ?? 'manufacturer'));
    
    // Strict RBAC enforcement: Block any attempt to register as Admin
    if (!in_array($selected_role, $public_roles)) {
        $error = 'Invalid registration role specified. Administrative accounts cannot be registered publicly.';
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
                        set_flash('success', 'Manufacturer account registered successfully! You can now sign in.');
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
                        set_flash('success', 'Supplier account registered successfully! You can now sign in.');
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
                        set_flash('success', 'Shopkeeper boutique registered successfully! You can now sign in.');
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
    <title>Stakeholder Registration — <?= APP_FULL_NAME ?></title>
    
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
            background-color: #090d16;
            background-image: 
                radial-gradient(circle at 10% 20%, rgba(37, 99, 235, 0.12), transparent 35%),
                radial-gradient(circle at 90% 80%, rgba(16, 185, 129, 0.12), transparent 35%);
            font-family: 'Plus Jakarta Sans', sans-serif;
            color: #f8fafc;
            min-height: 100vh;
        }

        .register-container {
            max-width: 680px;
            width: 100%;
        }

        .register-box {
            background: rgba(15, 23, 42, 0.88);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 24px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
        }

        /* 3-Role Selection Cards */
        .role-selector-3col {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 12px;
            margin-bottom: 1.75rem;
        }

        .role-card-select {
            background: rgba(255, 255, 255, 0.04);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 14px;
            padding: 1rem 0.75rem;
            text-align: center;
            cursor: pointer;
            transition: all 0.22s cubic-bezier(0.16, 1, 0.3, 1);
            color: #94a3b8;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 8px;
            user-select: none;
        }

        .role-card-select i {
            font-size: 1.5rem;
            transition: transform 0.2s ease;
        }

        .role-card-select .role-title {
            font-size: 0.85rem;
            font-weight: 700;
        }

        .role-card-select .role-sub {
            font-size: 0.68rem;
            color: #64748b;
        }

        .role-card-select:hover {
            background: rgba(255, 255, 255, 0.08);
            color: #ffffff;
            transform: translateY(-2px);
        }

        /* Active State */
        .role-card-select.active[data-role="manufacturer"] {
            background: rgba(37, 99, 235, 0.18);
            border-color: #3b82f6;
            color: #60a5fa;
            box-shadow: 0 0 16px rgba(59, 130, 246, 0.3);
        }
        .role-card-select.active[data-role="supplier"] {
            background: rgba(16, 185, 129, 0.18);
            border-color: #10b981;
            color: #34d399;
            box-shadow: 0 0 16px rgba(16, 185, 129, 0.3);
        }
        .role-card-select.active[data-role="shopkeeper"] {
            background: rgba(139, 92, 246, 0.18);
            border-color: #8b5cf6;
            color: #c084fc;
            box-shadow: 0 0 16px rgba(139, 92, 246, 0.3);
        }

        .form-control-dark, .form-select-dark {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.15);
            color: #ffffff;
            border-radius: 10px;
            padding: 0.65rem 1rem;
        }
        .form-control-dark:focus, .form-select-dark:focus {
            background: rgba(255, 255, 255, 0.08);
            border-color: #38bdf8;
            color: #ffffff;
            box-shadow: 0 0 0 3px rgba(56, 189, 248, 0.2);
        }
        .form-select-dark option {
            background: #0f172a;
            color: #ffffff;
        }

        .admin-note-box {
            background: rgba(220, 38, 38, 0.08);
            border: 1px solid rgba(220, 38, 38, 0.2);
            border-radius: 12px;
            padding: 0.75rem 1rem;
        }
    </style>
</head>
<body class="d-flex flex-column justify-content-between">

    <!-- Top Simple Bar -->
    <header class="py-3 px-4 d-flex align-items-center justify-content-between">
        <a href="<?= BASE_URL ?>index.php" class="text-decoration-none d-flex align-items-center gap-2 text-white">
            <div style="width: 32px; height: 32px; border-radius: 8px; background: #2563eb; display: flex; align-items: center; justify-content: center;">
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

    <!-- Center Dynamic Registration Form -->
    <main class="container py-4 d-flex align-items-center justify-content-center flex-grow-1">
        <div class="register-container">
            
            <div class="register-box p-4 p-md-5 mb-3">
                
                <div class="text-center mb-4">
                    <div class="badge bg-primary bg-opacity-20 text-info px-3 py-1 rounded-pill extra-small fw-bold mb-2">
                        MULTI-STAKEHOLDER REGISTRATION
                    </div>
                    <h3 class="fw-extrabold text-white mb-1">Create Your Account</h3>
                    <p class="text-muted extra-small mb-0">Select your position in the supply chain to open your personalized console</p>
                </div>

                <!-- Flash / Error Notification -->
                <?php render_flash(); ?>
                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger py-2 px-3 extra-small mb-3 rounded-3 d-flex align-items-start gap-2">
                        <i class="fa-solid fa-circle-exclamation mt-0.5"></i>
                        <div><?= $error ?></div>
                    </div>
                <?php endif; ?>

                <form method="POST" action="<?= BASE_URL ?>auth/register.php" id="registerForm">
                    <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
                    <input type="hidden" name="role" id="registerRoleInput" value="<?= htmlspecialchars($selected_role) ?>">

                    <!-- 1. Who is Registering? Role Selector (Strictly Excludes Admin) -->
                    <label class="form-label extra-small fw-bold text-uppercase text-light mb-2">
                        1. Who is registering?
                    </label>
                    <div class="role-selector-3col">
                        
                        <!-- Manufacturer -->
                        <div class="role-card-select <?= ($selected_role === 'manufacturer') ? 'active' : '' ?>" data-role="manufacturer" onclick="setRegisterRole('manufacturer')">
                            <i class="fa-solid fa-industry text-primary"></i>
                            <div class="role-title">Manufacturer</div>
                            <div class="role-sub">Tier 1 Production Lab</div>
                        </div>

                        <!-- Supplier -->
                        <div class="role-card-select <?= ($selected_role === 'supplier') ? 'active' : '' ?>" data-role="supplier" onclick="setRegisterRole('supplier')">
                            <i class="fa-solid fa-truck-ramp-box text-success"></i>
                            <div class="role-title">Supplier</div>
                            <div class="role-sub">Distribution & Hub</div>
                        </div>

                        <!-- Shopkeeper -->
                        <div class="role-card-select <?= ($selected_role === 'shopkeeper') ? 'active' : '' ?>" data-role="shopkeeper" onclick="setRegisterRole('shopkeeper')">
                            <i class="fa-solid fa-store" style="color: #a855f7;"></i>
                            <div class="role-title">Shopkeeper</div>
                            <div class="role-sub">Retail Boutique</div>
                        </div>

                    </div>

                    <!-- 2. Dynamic Organization Fields -->
                    <div class="mb-4 p-3 rounded-3 bg-white bg-opacity-5 border border-white border-opacity-10">
                        <span class="extra-small fw-bold text-uppercase text-info d-block mb-3" id="orgSectionTitle">
                            2. Organization Details (Manufacturer)
                        </span>

                        <!-- A. Manufacturer Specific Field -->
                        <div class="role-field-group" id="field-manufacturer">
                            <div class="mb-3">
                                <label for="company_name" class="form-label extra-small fw-bold text-light">Manufacturing Company / Formulation Lab Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control form-control-dark" id="company_name" name="company_name" value="<?= htmlspecialchars($formData['company_name']) ?>" placeholder="e.g., GlowTech Bio-Cosmetics Ltd.">
                            </div>
                        </div>

                        <!-- B. Supplier Specific Fields -->
                        <div class="role-field-group d-none" id="field-supplier">
                            <div class="mb-3">
                                <label for="supplier_name" class="form-label extra-small fw-bold text-light">Supplier / Distribution Company Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control form-control-dark" id="supplier_name" name="supplier_name" value="<?= htmlspecialchars($formData['supplier_name']) ?>" placeholder="e.g., Luxe Pure Distribution Hub">
                            </div>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="category" class="form-label extra-small fw-bold text-light">Primary Category Supplied</label>
                                    <select class="form-select form-select-dark" id="category" name="category">
                                        <option value="Skincare Products" <?= ($formData['category'] === 'Skincare Products') ? 'selected' : '' ?>>Skincare Products</option>
                                        <option value="Face Products" <?= ($formData['category'] === 'Face Products') ? 'selected' : '' ?>>Face Products</option>
                                        <option value="Haircare & Shampoos" <?= ($formData['category'] === 'Haircare & Shampoos') ? 'selected' : '' ?>>Haircare & Shampoos</option>
                                        <option value="Fragrance & Perfumes" <?= ($formData['category'] === 'Fragrance & Perfumes') ? 'selected' : '' ?>>Fragrance & Perfumes</option>
                                        <option value="Raw Materials & Oils" <?= ($formData['category'] === 'Raw Materials & Oils') ? 'selected' : '' ?>>Raw Materials & Oils</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label for="payment_terms" class="form-label extra-small fw-bold text-light">Default Payment Terms</label>
                                    <select class="form-select form-select-dark" id="payment_terms" name="payment_terms">
                                        <option value="Net 30">Net 30 Days</option>
                                        <option value="Net 15">Net 15 Days</option>
                                        <option value="Immediate">Immediate Transfer</option>
                                        <option value="COD">Cash On Delivery</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- C. Shopkeeper Specific Field -->
                        <div class="role-field-group d-none" id="field-shopkeeper">
                            <div class="mb-3">
                                <label for="shop_name" class="form-label extra-small fw-bold text-light">Retail Store / Cosmetics Boutique Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control form-control-dark" id="shop_name" name="shop_name" value="<?= htmlspecialchars($formData['shop_name']) ?>" placeholder="e.g., Glamour Glow Beauty Storefront">
                            </div>
                        </div>

                        <!-- Address Field for Facility / Hub / Store -->
                        <div class="mt-3">
                            <label for="address" class="form-label extra-small fw-bold text-light" id="addressLabel">Factory / Facility Street Address</label>
                            <input type="text" class="form-control form-control-dark" id="address" name="address" value="<?= htmlspecialchars($formData['address']) ?>" placeholder="Building, Street, Industrial Area">
                        </div>

                    </div>

                    <!-- 3. Account Representative & Contact Details -->
                    <div class="mb-4">
                        <span class="extra-small fw-bold text-uppercase text-light d-block mb-2">3. Primary Contact Person & Login Credentials</span>
                        
                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label for="name" class="form-label extra-small fw-bold text-light">Representative Full Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control form-control-dark" id="name" name="name" value="<?= htmlspecialchars($formData['name']) ?>" placeholder="e.g., Dr. Rajesh Mehta" required>
                            </div>
                            <div class="col-md-6">
                                <label for="email" class="form-label extra-small fw-bold text-light">Official Email Address <span class="text-danger">*</span></label>
                                <input type="email" class="form-control form-control-dark" id="email" name="email" value="<?= htmlspecialchars($formData['email']) ?>" placeholder="name@company.com" required>
                            </div>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-md-4">
                                <label for="phone" class="form-label extra-small fw-bold text-light">Phone Number</label>
                                <input type="text" class="form-control form-control-dark" id="phone" name="phone" value="<?= htmlspecialchars($formData['phone']) ?>" placeholder="e.g., 9822334455">
                            </div>
                            <div class="col-md-4">
                                <label for="city" class="form-label extra-small fw-bold text-light">City</label>
                                <input type="text" class="form-control form-control-dark" id="city" name="city" value="<?= htmlspecialchars($formData['city']) ?>" placeholder="City">
                            </div>
                            <div class="col-md-4">
                                <label for="state" class="form-label extra-small fw-bold text-light">State</label>
                                <input type="text" class="form-control form-control-dark" id="state" name="state" value="<?= htmlspecialchars($formData['state']) ?>" placeholder="State">
                            </div>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label for="password" class="form-label extra-small fw-bold text-light">Password (Min 6 chars) <span class="text-danger">*</span></label>
                                <input type="password" class="form-control form-control-dark" id="password" name="password" placeholder="••••••••••••" required>
                            </div>
                            <div class="col-md-6">
                                <label for="confirm_password" class="form-label extra-small fw-bold text-light">Confirm Password <span class="text-danger">*</span></label>
                                <input type="password" class="form-control form-control-dark" id="confirm_password" name="confirm_password" placeholder="••••••••••••" required>
                            </div>
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <button type="submit" class="btn btn-primary w-100 py-2.5 rounded-3 fw-bold shadow-sm mb-3" id="registerSubmitBtn">
                        Complete Manufacturer Registration <i class="fa-solid fa-arrow-right ms-1"></i>
                    </button>

                </form>

                <!-- Admin Notice Box -->
                <div class="admin-note-box extra-small text-light d-flex align-items-center gap-2">
                    <i class="fa-solid fa-shield-halved text-danger fs-6"></i>
                    <div>
                        <strong>Administrator accounts are not available through public registration.</strong> Central governance credentials are restricted to designated system administrators.
                    </div>
                </div>

            </div>

            <!-- Link to Unified Login -->
            <div class="text-center">
                <p class="text-muted small mb-0">
                    Already registered? 
                    <a href="<?= BASE_URL ?>auth/login.php" class="text-primary fw-bold text-decoration-none">
                        Sign In through the Common Portal
                    </a>
                </p>
            </div>

        </div>
    </main>

    <!-- Bottom Footer -->
    <footer class="py-3 text-center text-muted extra-small">
        &copy; <?= date('Y') ?> Supplier Performance Analysis and Management System (SPAS) &bull; Enterprise Edition
    </footer>

    <!-- Dynamic Switching JavaScript -->
    <script>
        const registerMeta = {
            'manufacturer': {
                name: 'Manufacturer',
                btnClass: 'btn-primary',
                btnText: 'Complete Manufacturer Registration',
                sectionTitle: '2. Organization Details (Manufacturer Formulation Lab)',
                addressLabel: 'Manufacturing Plant / Lab Address'
            },
            'supplier': {
                name: 'Supplier',
                btnClass: 'btn-success',
                btnText: 'Complete Supplier Registration',
                sectionTitle: '2. Organization Details (Supplier Distribution Hub)',
                addressLabel: 'Regional Distribution Warehouse Address'
            },
            'shopkeeper': {
                name: 'Shopkeeper',
                btnClass: 'btn-purple text-white',
                btnText: 'Complete Shopkeeper Store Registration',
                sectionTitle: '2. Organization Details (Retail Boutique Store)',
                addressLabel: 'Retail Storefront Address'
            }
        };

        function setRegisterRole(roleKey) {
            if (!registerMeta[roleKey]) roleKey = 'manufacturer';
            document.getElementById('registerRoleInput').value = roleKey;

            // Update card active states
            document.querySelectorAll('.role-card-select').forEach(card => {
                card.classList.remove('active');
                if (card.getAttribute('data-role') === roleKey) {
                    card.classList.add('active');
                }
            });

            // Toggle dynamic field groups
            document.getElementById('field-manufacturer').classList.add('d-none');
            document.getElementById('field-supplier').classList.add('d-none');
            document.getElementById('field-shopkeeper').classList.add('d-none');

            document.getElementById('field-' + roleKey).classList.remove('d-none');

            // Update labels and button styling
            const meta = registerMeta[roleKey];
            document.getElementById('orgSectionTitle').textContent = meta.sectionTitle;
            document.getElementById('addressLabel').textContent = meta.addressLabel;

            const btn = document.getElementById('registerSubmitBtn');
            btn.className = 'btn w-100 py-2.5 rounded-3 fw-bold shadow-sm mb-3 ' + meta.btnClass;
            if (roleKey === 'shopkeeper') {
                btn.style.backgroundColor = '#7c3aed';
            } else {
                btn.style.backgroundColor = '';
            }
            btn.innerHTML = meta.btnText + ' <i class="fa-solid fa-arrow-right ms-1"></i>';
        }

        document.addEventListener('DOMContentLoaded', function() {
            const initialRole = document.getElementById('registerRoleInput').value || 'manufacturer';
            setRegisterRole(initialRole);
        });
    </script>

</body>
</html>
