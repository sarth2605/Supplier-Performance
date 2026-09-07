<?php
/**
 * Supplier Performance Analysis and Management System (SPAS)
 * Common Unified Authentication Gateway
 * Single login portal for: Manufacturer, Supplier, Shopkeeper, Admin
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

// If already logged in, redirect directly to their role dashboard
if (is_logged_in()) {
    redirect_to_role_dashboard($_SESSION['user_role'] ?? 'admin');
}

$error = '';
$email = '';
$selected_role = strtolower(trim($_GET['role'] ?? $_POST['role'] ?? 'admin'));
$valid_roles = ['admin', 'manufacturer', 'supplier', 'shopkeeper'];
if (!in_array($selected_role, $valid_roles)) {
    $selected_role = 'admin';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $selected_role = strtolower(trim($_POST['role'] ?? 'admin'));
    $csrf_token = $_POST['csrf_token'] ?? '';

    if (!in_array($selected_role, $valid_roles)) {
        $selected_role = 'admin';
    }

    if (!verify_csrf_token($csrf_token)) {
        $error = 'Security session expired. Please refresh the page and try again.';
    } elseif (empty($email) || empty($password)) {
        $error = 'Please enter both your email address and password.';
    } else {
        try {
            $db = get_db();
            $stmt = $db->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                if ($user['status'] !== 'Active') {
                    $error = 'Your account has been deactivated. Please contact an administrator.';
                } else {
                    $actual_role = strtolower($user['role']);
                    
                    // Verify if selected role matches actual account role
                    if ($actual_role !== $selected_role) {
                        $role_display_names = [
                            'admin' => 'Administrator',
                            'manufacturer' => 'Manufacturer',
                            'supplier' => 'Supplier',
                            'shopkeeper' => 'Shopkeeper'
                        ];
                        $actual_label = $role_display_names[$actual_role] ?? ucfirst($actual_role);
                        $error = "This account is registered as <strong>{$actual_label}</strong>. Please select the <strong>{$actual_label}</strong> option above to sign in.";
                    } else {
                        // Successful Authentication - Populate Session
                        $_SESSION['user_id'] = (int)$user['id'];
                        $_SESSION['user_name'] = $user['name'];
                        $_SESSION['user_email'] = $user['email'];
                        $_SESSION['user_role'] = $actual_role;
                        $_SESSION['user_company'] = $user['company_name'] ?? '';
                        $_SESSION['user_shop'] = $user['shop_name'] ?? '';
                        $_SESSION['user_supplier_id'] = $user['supplier_id'] ?? null;
                        $_SESSION['user_dept'] = ($actual_role === 'manufacturer' ? 'R&D & Production' : ($actual_role === 'supplier' ? 'Logistics & Supply' : ($actual_role === 'shopkeeper' ? 'Retail Storefront' : 'System Governance')));

                        log_activity($user['id'], 'User Sign In', 'Auth', $user['id'], 'User logged in via common portal as ' . $actual_role);
                        set_flash('success', 'Welcome back, ' . htmlspecialchars($user['name']) . '!');

                        redirect_to_role_dashboard($actual_role);
                    }
                }
            } else {
                $error = 'Invalid email address or password. Please verify your credentials.';
            }
        } catch (Exception $e) {
            $error = 'Database error occurred: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Unified Portal Sign In — <?= APP_FULL_NAME ?></title>
    
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
                radial-gradient(circle at 15% 20%, rgba(37, 99, 235, 0.12), transparent 35%),
                radial-gradient(circle at 85% 80%, rgba(139, 92, 246, 0.12), transparent 35%);
            font-family: 'Plus Jakarta Sans', sans-serif;
            color: #f8fafc;
            min-height: 100vh;
        }

        .auth-container {
            max-width: 520px;
            width: 100%;
        }

        .auth-box {
            background: rgba(15, 23, 42, 0.85);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 22px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
        }

        /* Role Selector Cards */
        .role-selector-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 8px;
            margin-bottom: 1.5rem;
        }

        .role-option-btn {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 12px;
            padding: 0.75rem 0.5rem;
            text-align: center;
            cursor: pointer;
            transition: all 0.22s cubic-bezier(0.16, 1, 0.3, 1);
            color: #94a3b8;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 6px;
            text-decoration: none;
            user-select: none;
        }

        .role-option-btn i {
            font-size: 1.25rem;
            transition: transform 0.2s ease;
        }

        .role-option-btn span {
            font-size: 0.72rem;
            font-weight: 600;
            white-space: nowrap;
        }

        .role-option-btn:hover {
            background: rgba(255, 255, 255, 0.1);
            color: #ffffff;
            transform: translateY(-2px);
        }

        /* Active Role Styles */
        .role-option-btn.active[data-role="admin"] {
            background: rgba(220, 38, 38, 0.15);
            border-color: #ef4444;
            color: #f87171;
            box-shadow: 0 0 15px rgba(239, 68, 68, 0.3);
        }
        .role-option-btn.active[data-role="manufacturer"] {
            background: rgba(37, 99, 235, 0.15);
            border-color: #3b82f6;
            color: #60a5fa;
            box-shadow: 0 0 15px rgba(59, 130, 246, 0.3);
        }
        .role-option-btn.active[data-role="supplier"] {
            background: rgba(16, 185, 129, 0.15);
            border-color: #10b981;
            color: #34d399;
            box-shadow: 0 0 15px rgba(16, 185, 129, 0.3);
        }
        .role-option-btn.active[data-role="shopkeeper"] {
            background: rgba(139, 92, 246, 0.15);
            border-color: #8b5cf6;
            color: #c084fc;
            box-shadow: 0 0 15px rgba(139, 92, 246, 0.3);
        }

        .form-control-dark {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.15);
            color: #ffffff;
            border-radius: 10px;
            padding: 0.65rem 1rem;
        }
        .form-control-dark:focus {
            background: rgba(255, 255, 255, 0.08);
            border-color: #38bdf8;
            color: #ffffff;
            box-shadow: 0 0 0 3px rgba(56, 189, 248, 0.2);
        }

        .submit-btn {
            border-radius: 10px;
            font-weight: 700;
            padding: 0.75rem;
            transition: all 0.2s ease;
        }

        .quick-badge-pill {
            background: rgba(255, 255, 255, 0.06);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 9999px;
            font-size: 0.72rem;
            padding: 0.25rem 0.65rem;
            color: #94a3b8;
            cursor: pointer;
            transition: all 0.15s ease;
        }
        .quick-badge-pill:hover {
            background: rgba(255, 255, 255, 0.12);
            color: #ffffff;
        }
    </style>
</head>
<body class="d-flex flex-column justify-content-between">

    <!-- Top Simple Bar with Home link -->
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
            <a href="<?= BASE_URL ?>auth/register.php" class="btn btn-outline-light btn-sm px-3 rounded-pill extra-small fw-semibold">
                <i class="fa-solid fa-user-plus me-1"></i> Register
            </a>
        </div>
    </header>

    <!-- Center Login Form -->
    <main class="container py-4 d-flex align-items-center justify-content-center flex-grow-1">
        <div class="auth-container">
            
            <div class="auth-box p-4 p-md-4 mb-3">
                
                <!-- Title & Role Indicator -->
                <div class="text-center mb-3">
                    <h4 class="fw-extrabold text-white mb-1">Unified Sign In</h4>
                    <p class="text-muted extra-small mb-0">Select your designated supply chain role to access your portal</p>
                </div>

                <!-- Flash / Error Alerts -->
                <?php render_flash(); ?>
                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger py-2 px-3 extra-small mb-3 rounded-3 d-flex align-items-start gap-2">
                        <i class="fa-solid fa-circle-exclamation mt-0.5"></i>
                        <div><?= $error ?></div>
                    </div>
                <?php endif; ?>

                <form method="POST" action="<?= BASE_URL ?>auth/login.php" id="loginForm">
                    <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
                    <input type="hidden" name="role" id="roleInput" value="<?= htmlspecialchars($selected_role) ?>">

                    <!-- 1. Role Selection Grid -->
                    <label class="form-label extra-small fw-bold text-uppercase text-light mb-2">Select Your Role</label>
                    <div class="role-selector-grid">
                        
                        <!-- Admin -->
                        <div class="role-option-btn <?= ($selected_role === 'admin') ? 'active' : '' ?>" data-role="admin" onclick="selectRole('admin')">
                            <i class="fa-solid fa-user-shield text-danger"></i>
                            <span>Admin</span>
                        </div>

                        <!-- Manufacturer -->
                        <div class="role-option-btn <?= ($selected_role === 'manufacturer') ? 'active' : '' ?>" data-role="manufacturer" onclick="selectRole('manufacturer')">
                            <i class="fa-solid fa-industry text-primary"></i>
                            <span>Manufacturer</span>
                        </div>

                        <!-- Supplier -->
                        <div class="role-option-btn <?= ($selected_role === 'supplier') ? 'active' : '' ?>" data-role="supplier" onclick="selectRole('supplier')">
                            <i class="fa-solid fa-truck-ramp-box text-success"></i>
                            <span>Supplier</span>
                        </div>

                        <!-- Shopkeeper -->
                        <div class="role-option-btn <?= ($selected_role === 'shopkeeper') ? 'active' : '' ?>" data-role="shopkeeper" onclick="selectRole('shopkeeper')">
                            <i class="fa-solid fa-store" style="color: #a855f7;"></i>
                            <span>Shopkeeper</span>
                        </div>

                    </div>

                    <!-- Role Description Banner -->
                    <div class="p-2 rounded-3 bg-white bg-opacity-5 border border-white border-opacity-10 mb-3 extra-small text-light d-flex align-items-center gap-2" id="roleBanner">
                        <i class="fa-solid fa-circle-info text-info"></i>
                        <span id="roleBannerText">Logging in as Administrator</span>
                    </div>

                    <!-- 2. Email Address -->
                    <div class="mb-3">
                        <label for="email" class="form-label extra-small fw-bold text-uppercase text-light">Email Address</label>
                        <div class="input-group">
                            <span class="input-group-text bg-transparent border-secondary border-opacity-50 text-light border-end-0">
                                <i class="fa-solid fa-envelope"></i>
                            </span>
                            <input type="email" class="form-control form-control-dark border-start-0 ps-0" id="email" name="email" value="<?= htmlspecialchars($email) ?>" placeholder="name@company.com" required autofocus>
                        </div>
                    </div>

                    <!-- 3. Password -->
                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <label for="password" class="form-label extra-small fw-bold text-uppercase text-light mb-0">Password</label>
                        </div>
                        <div class="input-group">
                            <span class="input-group-text bg-transparent border-secondary border-opacity-50 text-light border-end-0">
                                <i class="fa-solid fa-lock"></i>
                            </span>
                            <input type="password" class="form-control form-control-dark border-start-0 border-end-0 ps-0" id="password" name="password" placeholder="••••••••••••" required>
                            <button class="btn btn-outline-secondary border-secondary border-opacity-50 text-light" type="button" id="togglePasswordBtn" onclick="togglePasswordVisibility()">
                                <i class="fa-solid fa-eye" id="toggleIcon"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <button type="submit" class="btn btn-primary w-100 submit-btn shadow-sm mb-3" id="submitBtn">
                        Sign In as Administrator <i class="fa-solid fa-arrow-right ms-1"></i>
                    </button>

                    <!-- Quick Demo Fill Pills for Testing / Evaluation -->
                    <div class="pt-2 border-top border-secondary border-opacity-25">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <span class="extra-small text-muted">Quick Test Fill:</span>
                            <span class="extra-small text-info">Click to pre-fill</span>
                        </div>
                        <div class="d-flex flex-wrap gap-1.5">
                            <span class="quick-badge-pill" onclick="quickFill('admin', 'test_admin@spas.gov', 'Admin@Pass123')">Admin</span>
                            <span class="quick-badge-pill" onclick="quickFill('manufacturer', 'test_mfr@glowtech.in', 'Mfr@Pass123')">Manufacturer</span>
                            <span class="quick-badge-pill" onclick="quickFill('supplier', 'test_sup@glowbeauty.in', 'Sup@Pass123')">Supplier</span>
                            <span class="quick-badge-pill" onclick="quickFill('shopkeeper', 'test_shop@luxeglamour.in', 'Shop@Pass123')">Shopkeeper</span>
                        </div>
                    </div>

                </form>

            </div>

            <!-- Footer Link to Unified Register -->
            <div class="text-center">
                <p class="text-muted small mb-0">
                    Don't have an account? 
                    <a href="<?= BASE_URL ?>auth/register.php" class="text-primary fw-bold text-decoration-none">
                        Register as Manufacturer, Supplier, or Shopkeeper
                    </a>
                </p>
            </div>

        </div>
    </main>

    <!-- Simple Bottom Footer -->
    <footer class="py-3 text-center text-muted extra-small">
        &copy; <?= date('Y') ?> Supplier Performance Analysis and Management System (SPAS) &bull; Enterprise Edition
    </footer>

    <!-- Role Selection JavaScript Logic -->
    <script>
        const roleData = {
            'admin': {
                name: 'Administrator',
                color: 'btn-danger',
                text: 'Central Governance, User Approvals & System Analytics'
            },
            'manufacturer': {
                name: 'Manufacturer',
                color: 'btn-primary',
                text: 'Batch Formulations, Factory Inventory & Outbound Transfers'
            },
            'supplier': {
                name: 'Supplier',
                color: 'btn-success',
                text: 'Wholesale Warehousing, Purchase Orders & Scorecard Feedback'
            },
            'shopkeeper': {
                name: 'Shopkeeper',
                color: 'btn-purple',
                text: 'Retail Boutique, Stock Receipt & Defect Claims'
            }
        };

        function selectRole(roleKey) {
            document.getElementById('roleInput').value = roleKey;

            // Highlight buttons
            document.querySelectorAll('.role-option-btn').forEach(btn => {
                btn.classList.remove('active');
                if (btn.getAttribute('data-role') === roleKey) {
                    btn.classList.add('active');
                }
            });

            // Update description banner and button text
            const meta = roleData[roleKey] || roleData['admin'];
            document.getElementById('roleBannerText').textContent = 'Logging in as ' + meta.name + ' — ' + meta.text;
            
            const submitBtn = document.getElementById('submitBtn');
            submitBtn.className = 'btn w-100 submit-btn shadow-sm mb-3 ' + (meta.color === 'btn-purple' ? 'text-white' : meta.color);
            if (meta.color === 'btn-purple') {
                submitBtn.style.backgroundColor = '#7c3aed';
            } else {
                submitBtn.style.backgroundColor = '';
            }
            submitBtn.innerHTML = 'Sign In as ' + meta.name + ' <i class="fa-solid fa-arrow-right ms-1"></i>';
        }

        function quickFill(role, email, pass) {
            selectRole(role);
            document.getElementById('email').value = email;
            document.getElementById('password').value = pass;
        }

        function togglePasswordVisibility() {
            const passInput = document.getElementById('password');
            const icon = document.getElementById('toggleIcon');
            if (passInput.type === 'password') {
                passInput.type = 'text';
                icon.className = 'fa-solid fa-eye-slash';
            } else {
                passInput.type = 'password';
                icon.className = 'fa-solid fa-eye';
            }
        }

        // Initialize state on page load
        document.addEventListener('DOMContentLoaded', function() {
            const initialRole = document.getElementById('roleInput').value || 'admin';
            selectRole(initialRole);
        });
    </script>

</body>
</html>
