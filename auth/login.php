<?php
/**
 * Supplier Performance Analysis and Management System (SPAS)
 * Modern Unified Authentication Gateway
 * Single login card with segmented role selector for: Administrator, Manufacturer, Supplier, Shopkeeper
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

// If already logged in, redirect directly to role dashboard
if (is_logged_in()) {
    redirect_to_role_dashboard($_SESSION['user_role'] ?? 'admin');
}

$error = '';
$identity = '';
$selected_role = strtolower(trim($_GET['role'] ?? $_POST['role'] ?? 'admin'));
$valid_roles = ['admin', 'manufacturer', 'supplier', 'shopkeeper'];
if (!in_array($selected_role, $valid_roles)) {
    $selected_role = 'admin';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $identity = trim($_POST['email'] ?? $_POST['identity'] ?? '');
    $password = $_POST['password'] ?? '';
    $selected_role = strtolower(trim($_POST['role'] ?? 'admin'));
    $csrf_token = $_POST['csrf_token'] ?? '';

    if (!in_array($selected_role, $valid_roles)) {
        $selected_role = 'admin';
    }

    if (!verify_csrf_token($csrf_token)) {
        $error = 'Security session expired. Please refresh the page and try again.';
    } elseif (empty($identity) || empty($password)) {
        $error = 'Please enter both your Email / Username and Password.';
    } else {
        try {
            $db = get_db();
            // Allow login by email OR username/full name
            $stmt = $db->prepare("SELECT * FROM users WHERE email = ? OR name = ? LIMIT 1");
            $stmt->execute([$identity, $identity]);
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
                        $error = "This account is registered as <strong>{$actual_label}</strong>. Please switch the role toggle to <strong>{$actual_label}</strong> to sign in.";
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

                        log_activity($user['id'], 'User Sign In', 'Auth', $user['id'], 'User logged in via unified portal as ' . $actual_role);
                        set_flash('success', 'Welcome back, ' . htmlspecialchars($user['name']) . '!');

                        redirect_to_role_dashboard($actual_role);
                    }
                }
            } else {
                $error = 'Invalid email/username or password. Please verify your credentials.';
            }
        } catch (Exception $e) {
            $error = 'Database connection error: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Unified Sign In — <?= APP_FULL_NAME ?></title>
    
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

        .auth-card {
            background: rgba(17, 24, 39, 0.85);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.12);
            border-radius: 20px;
            box-shadow: 0 25px 60px -15px rgba(0, 0, 0, 0.6);
            width: 100%;
            max-width: 480px;
        }

        /* Segmented Role Pill Selector */
        .role-segmented-bar {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 14px;
            padding: 4px;
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 4px;
            user-select: none;
        }

        .role-pill-btn {
            background: transparent;
            border: none;
            border-radius: 10px;
            color: #94a3b8;
            font-size: 0.72rem;
            font-weight: 600;
            padding: 8px 4px;
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
            font-size: 1.05rem;
            transition: transform 0.2s ease;
        }

        .role-pill-btn:hover {
            color: #ffffff;
            background: rgba(255, 255, 255, 0.06);
        }

        /* Active Roles */
        .role-pill-btn.active[data-role="admin"] {
            background: #dc2626;
            color: #ffffff;
            box-shadow: 0 4px 14px rgba(220, 38, 38, 0.4);
        }
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

        .form-control-dark {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.15);
            color: #ffffff;
            border-radius: 10px;
            padding: 0.7rem 1rem;
            font-size: 0.9rem;
        }
        .form-control-dark:focus {
            background: rgba(255, 255, 255, 0.08);
            border-color: #38bdf8;
            color: #ffffff;
            box-shadow: 0 0 0 3px rgba(56, 189, 248, 0.25);
        }

        .btn-submit-action {
            border-radius: 10px;
            font-weight: 700;
            padding: 0.75rem;
            font-size: 0.92rem;
            transition: all 0.2s ease;
        }

        .quick-fill-chip {
            background: rgba(255, 255, 255, 0.06);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 9999px;
            font-size: 0.7rem;
            padding: 3px 10px;
            color: #94a3b8;
            cursor: pointer;
            transition: all 0.15s ease;
            text-decoration: none;
        }
        .quick-fill-chip:hover {
            background: rgba(255, 255, 255, 0.15);
            color: #ffffff;
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
            <a href="<?= BASE_URL ?>auth/register.php" class="btn btn-outline-light btn-sm px-3 rounded-pill extra-small fw-semibold">
                <i class="fa-solid fa-user-plus me-1"></i> Register
            </a>
        </div>
    </header>

    <!-- Center Unified Login Card -->
    <main class="container py-4 d-flex align-items-center justify-content-center flex-grow-1">
        <div class="auth-card p-4 p-md-4 shadow">
            
            <!-- Header Section -->
            <div class="text-center mb-3">
                <h4 class="fw-bold text-white mb-1">Unified Sign In</h4>
                <p class="text-muted extra-small mb-0">Select your role and enter your credentials to access your portal</p>
            </div>

            <!-- Flash & Error Alerts -->
            <?php render_flash(); ?>
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger py-2 px-3 extra-small mb-3 rounded-3 d-flex align-items-start gap-2">
                    <i class="fa-solid fa-circle-exclamation mt-0.5"></i>
                    <div><?= $error ?></div>
                </div>
            <?php endif; ?>

            <form method="POST" action="<?= BASE_URL ?>auth/login.php" id="unifiedLoginForm">
                <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
                <input type="hidden" name="role" id="selectedRoleInput" value="<?= htmlspecialchars($selected_role) ?>">

                <!-- 1. Segmented Role Selector -->
                <div class="mb-3">
                    <label class="form-label extra-small fw-bold text-uppercase text-light mb-1.5 d-flex justify-content-between">
                        <span>Select Role</span>
                        <span id="roleBadgeLabel" class="text-secondary font-monospace" style="font-size: 0.68rem;">ADMINISTRATOR</span>
                    </label>
                    <div class="role-segmented-bar">
                        <button type="button" class="role-pill-btn <?= ($selected_role === 'admin') ? 'active' : '' ?>" data-role="admin" onclick="switchRole('admin')">
                            <i class="fa-solid fa-user-shield"></i>
                            <span>Admin</span>
                        </button>
                        <button type="button" class="role-pill-btn <?= ($selected_role === 'manufacturer') ? 'active' : '' ?>" data-role="manufacturer" onclick="switchRole('manufacturer')">
                            <i class="fa-solid fa-industry"></i>
                            <span>Manufacturer</span>
                        </button>
                        <button type="button" class="role-pill-btn <?= ($selected_role === 'supplier') ? 'active' : '' ?>" data-role="supplier" onclick="switchRole('supplier')">
                            <i class="fa-solid fa-truck-ramp-box"></i>
                            <span>Supplier</span>
                        </button>
                        <button type="button" class="role-pill-btn <?= ($selected_role === 'shopkeeper') ? 'active' : '' ?>" data-role="shopkeeper" onclick="switchRole('shopkeeper')">
                            <i class="fa-solid fa-store"></i>
                            <span>Shopkeeper</span>
                        </button>
                    </div>
                </div>

                <!-- 2. Email or Username -->
                <div class="mb-3">
                    <label for="identity" class="form-label extra-small fw-bold text-uppercase text-light mb-1">Email / Username</label>
                    <div class="input-group">
                        <span class="input-group-text bg-transparent border-secondary border-opacity-50 text-light border-end-0">
                            <i class="fa-solid fa-user"></i>
                        </span>
                        <input type="text" class="form-control form-control-dark border-start-0 ps-0" id="identity" name="email" value="<?= htmlspecialchars($identity) ?>" placeholder="Email address or username" required autofocus>
                    </div>
                </div>

                <!-- 3. Password -->
                <div class="mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <label for="password" class="form-label extra-small fw-bold text-uppercase text-light mb-0">Password</label>
                        <a href="<?= BASE_URL ?>auth/forgot_password.php" class="extra-small text-info text-decoration-none">Forgot Password?</a>
                    </div>
                    <div class="input-group">
                        <span class="input-group-text bg-transparent border-secondary border-opacity-50 text-light border-end-0">
                            <i class="fa-solid fa-lock"></i>
                        </span>
                        <input type="password" class="form-control form-control-dark border-start-0 border-end-0 ps-0" id="password" name="password" placeholder="••••••••••••" required>
                        <button class="btn btn-outline-secondary border-secondary border-opacity-50 text-light" type="button" onclick="togglePasswordVisibility()">
                            <i class="fa-solid fa-eye" id="toggleIcon"></i>
                        </button>
                    </div>
                </div>

                <!-- 4. Sign In Button -->
                <button type="submit" class="btn btn-primary w-100 btn-submit-action shadow-sm mb-3" id="submitBtn">
                    <span id="submitBtnLabel">Sign In as Administrator</span> <i class="fa-solid fa-arrow-right ms-1"></i>
                </button>

                <!-- 5. Register Link / Role Toggle Note -->
                <div class="text-center extra-small text-muted mb-3" id="registerToggleNotice">
                    <span id="regQuestionText">Don't have an account?</span> 
                    <a href="<?= BASE_URL ?>auth/register.php" class="text-info text-decoration-none fw-semibold" id="regTargetLink">Register here</a>
                </div>

                <!-- 6. Quick Evaluator Pre-fill Chips -->
                <div class="pt-3 border-top border-secondary border-opacity-25">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="extra-small text-muted">Quick Evaluator Test:</span>
                        <span class="extra-small text-info font-monospace">Click to pre-fill</span>
                    </div>
                    <div class="d-flex flex-wrap gap-1.5 justify-content-center">
                        <span class="quick-fill-chip" onclick="quickFill('admin', 'test_admin@spas.gov', 'Admin@Pass123')">Admin</span>
                        <span class="quick-fill-chip" onclick="quickFill('manufacturer', 'test_mfr@glowtech.in', 'Mfr@Pass123')">Manufacturer</span>
                        <span class="quick-fill-chip" onclick="quickFill('supplier', 'test_sup@glowbeauty.in', 'Sup@Pass123')">Supplier</span>
                        <span class="quick-fill-chip" onclick="quickFill('shopkeeper', 'test_shop@luxeglamour.in', 'Shop@Pass123')">Shopkeeper</span>
                    </div>
                </div>

            </form>
        </div>
    </main>

    <!-- Footer -->
    <footer class="py-3 px-4 text-center extra-small text-muted">
        <span>&copy; <?= date('Y') ?> Supplier Performance Analysis & Management System (SPAS). Enterprise v2.5</span>
    </footer>

    <!-- JavaScript Interactions -->
    <script>
        const roleMeta = {
            'admin': {
                name: 'Administrator',
                color: '#dc2626',
                btnClass: 'btn-danger',
                hasPublicReg: false
            },
            'manufacturer': {
                name: 'Manufacturer',
                color: '#2563eb',
                btnClass: 'btn-primary',
                hasPublicReg: true
            },
            'supplier': {
                name: 'Supplier',
                color: '#059669',
                btnClass: 'btn-success',
                hasPublicReg: true
            },
            'shopkeeper': {
                name: 'Shopkeeper',
                color: '#7c3aed',
                btnClass: 'btn-purple',
                hasPublicReg: true
            }
        };

        function switchRole(roleKey) {
            const role = roleMeta[roleKey] ? roleKey : 'admin';
            const meta = roleMeta[role];

            // 1. Update Hidden Input
            document.getElementById('selectedRoleInput').value = role;

            // 2. Update Segmented Buttons
            document.querySelectorAll('.role-pill-btn').forEach(btn => {
                btn.classList.toggle('active', btn.getAttribute('data-role') === role);
            });

            // 3. Update Badge Label
            const badge = document.getElementById('roleBadgeLabel');
            if (badge) {
                badge.innerText = meta.name.toUpperCase();
                badge.style.color = meta.color;
            }

            // 4. Update Button Text and Style
            const submitBtn = document.getElementById('submitBtn');
            const submitBtnLabel = document.getElementById('submitBtnLabel');
            if (submitBtn && submitBtnLabel) {
                submitBtnLabel.innerText = 'Sign In as ' + meta.name;
                submitBtn.className = 'btn w-100 btn-submit-action shadow-sm mb-3';
                if (role === 'admin') {
                    submitBtn.classList.add('btn-danger');
                } else if (role === 'manufacturer') {
                    submitBtn.classList.add('btn-primary');
                } else if (role === 'supplier') {
                    submitBtn.classList.add('btn-success');
                } else {
                    submitBtn.classList.add('btn-primary');
                    submitBtn.style.backgroundColor = '#7c3aed';
                    submitBtn.style.borderColor = '#7c3aed';
                }
            }

            // 5. Update Registration Toggle Link
            const regNotice = document.getElementById('registerToggleNotice');
            const regQuestion = document.getElementById('regQuestionText');
            const regLink = document.getElementById('regTargetLink');

            if (role === 'admin') {
                regQuestion.innerText = "Admin accounts are pre-provisioned.";
                regLink.innerText = "Need partner account? Register here";
                regLink.href = "<?= BASE_URL ?>auth/register.php?role=manufacturer";
            } else {
                regQuestion.innerText = "Don't have an account?";
                regLink.innerText = "Register here";
                regLink.href = "<?= BASE_URL ?>auth/register.php?role=" + encodeURIComponent(role);
            }
        }

        function togglePasswordVisibility() {
            const passInput = document.getElementById('password');
            const icon = document.getElementById('toggleIcon');
            if (passInput.type === 'password') {
                passInput.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                passInput.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }

        function quickFill(role, email, pass) {
            switchRole(role);
            document.getElementById('identity').value = email;
            document.getElementById('password').value = pass;
        }

        // Initialize with default/URL role
        document.addEventListener('DOMContentLoaded', () => {
            const initialRole = "<?= $selected_role ?>";
            switchRole(initialRole);
        });
    </script>
</body>
</html>
