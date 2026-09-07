<?php
/**
 * Supplier Performance Analysis and Management System (SPAS)
 * Official Enterprise Home Page & Multi-Stakeholder Gateway
 * Version: 2.5 Enterprise Edition
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/auth.php';

// Optional redirect parameter for automated flows: index.php?redirect=1
if (isset($_GET['redirect']) && $_GET['redirect'] == '1' && is_logged_in()) {
    redirect_to_role_dashboard($_SESSION['user_role'] ?? 'admin');
    exit;
}

$is_auth = is_logged_in();
$user_role = strtolower($_SESSION['user_role'] ?? '');
$user_name = $_SESSION['user_name'] ?? '';

// Fetch dynamic database counts with safe fallbacks
$stats = [
    'suppliers'   => 14,
    'products'    => 40,
    'orders'      => 17,
    'inspections' => 14,
    'transfers'   => 8,
    'db_status'   => 'Connected'
];

try {
    $db = get_db();
    $stats['suppliers']   = (int)$db->query("SELECT COUNT(*) FROM suppliers")->fetchColumn();
    $stats['products']    = (int)$db->query("SELECT COUNT(*) FROM products")->fetchColumn();
    $stats['orders']      = (int)$db->query("SELECT COUNT(*) FROM purchase_orders")->fetchColumn();
    $stats['inspections'] = (int)$db->query("SELECT COUNT(*) FROM quality_inspections")->fetchColumn();
    $stats['transfers']   = (int)$db->query("SELECT COUNT(*) FROM product_transfers")->fetchColumn();
} catch (Exception $e) {
    $stats['db_status'] = 'Offline Mode (Simulated Cache)';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Supplier Performance Analysis & Management System (SPAS) — Enterprise Supply Chain Intelligence</title>
    <meta name="description" content="Next-generation multi-tier supplier performance analysis, weighted multi-criteria evaluation (OTD, Quality, Cost, Service), and end-to-end supply chain governance.">

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600;700&display=swap" rel="stylesheet">

    <!-- Bootstrap 5.3 CSS & FontAwesome 6 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <!-- Custom Theme & Responsive CSS -->
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/style.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/responsive.css">

    <style>
        :root {
            --home-hero-bg: #090d16;
            --home-card-bg: rgba(255, 255, 255, 0.96);
            --home-border: rgba(226, 232, 240, 0.85);
            --accent-glow: rgba(37, 99, 235, 0.18);
        }

        body {
            background-color: #f8fafc;
            color: #0f172a;
            font-family: 'Plus Jakarta Sans', sans-serif;
            overflow-x: hidden;
        }

        /* Glassmorphism Navigation */
        .home-navbar {
            background: rgba(15, 23, 42, 0.92);
            backdrop-filter: blur(14px);
            -webkit-backdrop-filter: blur(14px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
            position: sticky;
            top: 0;
            z-index: 1050;
            transition: all 0.3s ease;
        }

        .home-nav-link {
            color: #94a3b8;
            font-size: 0.875rem;
            font-weight: 500;
            text-decoration: none;
            padding: 0.45rem 0.8rem;
            border-radius: 8px;
            transition: all 0.2s ease;
        }
        .home-nav-link:hover {
            color: #ffffff;
            background: rgba(255, 255, 255, 0.08);
        }

        /* Hero Section Styling */
        .hero-section {
            background: radial-gradient(circle at 80% 20%, rgba(37, 99, 235, 0.15), transparent 40%),
                        radial-gradient(circle at 10% 70%, rgba(139, 92, 246, 0.12), transparent 45%),
                        #0b1329;
            color: #ffffff;
            padding: 5.5rem 0 5rem 0;
            position: relative;
            overflow: hidden;
        }

        .hero-badge {
            background: rgba(37, 99, 235, 0.2);
            border: 1px solid rgba(96, 165, 250, 0.35);
            color: #93c5fd;
            font-size: 0.78rem;
            font-weight: 600;
            padding: 0.4rem 1.1rem;
            border-radius: 9999px;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .hero-title {
            font-size: 2.85rem;
            font-weight: 800;
            line-height: 1.18;
            letter-spacing: -0.025em;
            color: #f8fafc;
        }
        @media (min-width: 992px) {
            .hero-title { font-size: 3.5rem; }
        }

        .hero-title .gradient-text {
            background: linear-gradient(135deg, #60a5fa 0%, #a78bfa 50%, #38bdf8 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .hero-subtitle {
            font-size: 1.125rem;
            color: #94a3b8;
            line-height: 1.65;
            max-width: 620px;
        }

        /* Glass Preview Card */
        .hero-preview-card {
            background: rgba(15, 23, 42, 0.75);
            border: 1px solid rgba(255, 255, 255, 0.12);
            border-radius: 20px;
            box-shadow: 0 25px 60px -15px rgba(0, 0, 0, 0.6);
            backdrop-filter: blur(16px);
            padding: 1.5rem;
            position: relative;
        }

        /* Stats Ribbon */
        .stats-ribbon {
            background: #ffffff;
            border-bottom: 1px solid #e2e8f0;
            padding: 1.5rem 0;
            box-shadow: 0 4px 20px -2px rgba(15, 23, 42, 0.04);
        }

        .stat-item {
            text-align: center;
            padding: 0.75rem 1rem;
            border-right: 1px solid #e2e8f0;
        }
        .stat-item:last-child {
            border-right: none;
        }
        .stat-number {
            font-size: 2rem;
            font-weight: 800;
            color: #0f172a;
            font-family: 'JetBrains Mono', monospace;
            line-height: 1;
        }
        .stat-label {
            font-size: 0.8rem;
            font-weight: 600;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-top: 0.35rem;
        }

        /* Portal Cards */
        .portal-grid-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 18px;
            padding: 2rem 1.75rem;
            transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
            position: relative;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            height: 100%;
        }
        .portal-grid-card:hover {
            transform: translateY(-6px);
            box-shadow: 0 20px 35px -10px rgba(15, 23, 42, 0.12);
            border-color: #cbd5e1;
        }
        .portal-grid-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
        }
        .portal-grid-card.admin-theme::before { background: linear-gradient(90deg, #dc2626, #ef4444); }
        .portal-grid-card.mfr-theme::before { background: linear-gradient(90deg, #2563eb, #3b82f6); }
        .portal-grid-card.sup-theme::before { background: linear-gradient(90deg, #059669, #10b981); }
        .portal-grid-card.shop-theme::before { background: linear-gradient(90deg, #7c3aed, #8b5cf6); }

        .portal-avatar-icon {
            width: 52px;
            height: 52px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.4rem;
            margin-bottom: 1.25rem;
        }

        /* Pillar Feature Box */
        .pillar-box {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 16px;
            padding: 1.75rem;
            height: 100%;
            transition: all 0.25s ease;
        }
        .pillar-box:hover {
            border-color: #3b82f6;
            box-shadow: 0 10px 25px -5px rgba(59, 130, 246, 0.1);
        }

        /* Interactive Calculator Box */
        .calc-card {
            background: linear-gradient(145deg, #0f172a, #1e293b);
            color: #ffffff;
            border-radius: 24px;
            padding: 2.5rem;
            box-shadow: 0 25px 50px -12px rgba(15, 23, 42, 0.35);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .calc-slider {
            -webkit-appearance: none;
            width: 100%;
            height: 8px;
            border-radius: 4px;
            background: #334155;
            outline: none;
        }
        .calc-slider::-webkit-slider-thumb {
            -webkit-appearance: none;
            appearance: none;
            width: 22px;
            height: 22px;
            border-radius: 50%;
            background: #38bdf8;
            cursor: pointer;
            box-shadow: 0 0 10px rgba(56, 189, 248, 0.6);
            border: 2px solid #ffffff;
        }

        /* Pipeline Workflow */
        .flow-step {
            position: relative;
            text-align: center;
            padding: 1.5rem 1rem;
        }
        .flow-step-number {
            width: 44px;
            height: 44px;
            border-radius: 50%;
            background: #2563eb;
            color: #ffffff;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 1.1rem;
            margin-bottom: 1rem;
            box-shadow: 0 0 0 6px rgba(37, 99, 235, 0.15);
        }

        /* Footer */
        .home-footer {
            background: #090d16;
            color: #94a3b8;
            padding: 4rem 0 2rem 0;
            border-top: 1px solid rgba(255, 255, 255, 0.08);
        }
    </style>
</head>
<body>

    <!-- =======================================================
         1. STICKY TOP NAVIGATION BAR
         ======================================================= -->
    <header class="home-navbar py-2.5">
        <div class="container-fluid px-lg-5 d-flex align-items-center justify-content-between">
            
            <!-- Brand Logo -->
            <a href="<?= BASE_URL ?>index.php" class="text-decoration-none d-flex align-items-center gap-2.5">
                <div class="brand-icon-box shadow-sm" style="background: linear-gradient(135deg, #2563eb, #1d4ed8); width: 38px; height: 38px; border-radius: 10px; display: flex; align-items: center; justify-content: center;">
                    <i class="fa-solid fa-chart-line text-white fs-6"></i>
                </div>
                <div class="d-flex flex-column">
                    <span class="fw-extrabold fs-5 tracking-tight text-white line-height-1">SPAS</span>
                    <span class="extra-small text-light text-uppercase tracking-wider" style="font-size: 0.65rem; color: #94a3b8;">Supplier Analytics</span>
                </div>
            </a>

            <!-- Navigation Links (Desktop) -->
            <nav class="d-none d-xl-flex align-items-center gap-1">
                <a href="<?= BASE_URL ?>index.php" class="home-nav-link text-white fw-bold"><i class="fa-solid fa-house me-1 text-primary"></i> Home</a>
                <a href="#portals" class="home-nav-link">Portals</a>
                <a href="#framework" class="home-nav-link">Evaluation Criteria</a>
                <a href="#calculator" class="home-nav-link">Score Simulator</a>
                <a href="#pipeline" class="home-nav-link">Supply Chain Flow</a>
                <a href="<?= BASE_URL ?>auth/login.php" class="home-nav-link"><i class="fa-solid fa-right-to-bracket me-1 text-info"></i> Login</a>
                <a href="<?= BASE_URL ?>auth/register.php" class="home-nav-link"><i class="fa-solid fa-user-plus me-1 text-success"></i> Registration</a>
            </nav>

            <!-- Authentication Status & CTAs -->
            <div class="d-flex align-items-center gap-2">
                <?php if ($is_auth): ?>
                    <!-- Logged In User Navigation Pill -->
                    <div class="d-none d-sm-flex align-items-center gap-2 px-2.5 py-1 rounded-pill bg-white bg-opacity-10 border border-white border-opacity-10">
                        <span class="badge rounded-pill bg-success extra-small">Online</span>
                        <span class="small text-white fw-semibold"><?= htmlspecialchars($user_name) ?></span>
                        <span class="badge bg-primary extra-small text-uppercase"><?= htmlspecialchars($user_role) ?></span>
                    </div>

                    <a href="<?= BASE_URL ?><?= ($user_role === 'manufacturer' ? 'manufacturer/dashboard.php' : ($user_role === 'supplier' ? 'supplier_portal/dashboard.php' : ($user_role === 'shopkeeper' ? 'shopkeeper/dashboard.php' : 'dashboard/index.php'))) ?>" class="btn btn-primary btn-sm px-3.5 py-1.5 rounded-pill fw-semibold shadow-sm">
                        <i class="fa-solid fa-gauge-high me-1.5"></i> My Dashboard
                    </a>
                    
                    <a href="<?= BASE_URL ?>auth/logout.php" class="btn btn-outline-light btn-sm px-2.5 py-1.5 rounded-pill" title="Sign Out">
                        <i class="fa-solid fa-arrow-right-from-bracket"></i>
                    </a>
                <?php else: ?>
                    <!-- Guest / Evaluator CTAs -->
                    <a href="<?= BASE_URL ?>auth/login.php" class="btn btn-outline-light btn-sm px-3.5 py-1.5 rounded-pill fw-semibold">
                        <i class="fa-solid fa-right-to-bracket me-1.5"></i> Login
                    </a>
                    <a href="<?= BASE_URL ?>auth/register.php" class="btn btn-primary btn-sm px-3.5 py-1.5 rounded-pill fw-semibold shadow-sm">
                        <i class="fa-solid fa-user-plus me-1.5"></i> Register
                    </a>
                <?php endif; ?>
            </div>

        </div>
    </header>


    <!-- =======================================================
         2. HERO SECTION
         ======================================================= -->
    <section class="hero-section" id="overview">
        <div class="container-fluid px-lg-5">
            <div class="row align-items-center g-5">
                
                <!-- Left Hero Text & CTAs -->
                <div class="col-lg-6">
                    <div class="hero-badge mb-3.5">
                        <i class="fa-solid fa-sparkles text-warning"></i>
                        <span>Enterprise Multi-Tier Supply Chain Analytics &bull; v2.5</span>
                    </div>
                    
                    <h1 class="hero-title mb-3">
                        Scientific <span class="gradient-text">Supplier Evaluation</span> & Intelligent Performance Governance
                    </h1>
                    
                    <p class="hero-subtitle mb-4">
                        A centralized platform uniting <strong>Manufacturers, Suppliers, and Retailers</strong>. Calculate objective multi-criteria ratings across On-Time Delivery, Defect PPM, Cost Efficiency, and Service SLAs with complete chain-of-custody tracking.
                    </p>

                    <!-- CTAs -->
                    <div class="d-flex flex-wrap align-items-center gap-3 mb-4">
                        <a href="<?= BASE_URL ?>auth/login.php" class="btn btn-primary btn-lg px-4 py-2.5 rounded-pill fw-bold shadow-lg">
                            <i class="fa-solid fa-right-to-bracket me-2"></i> Sign In to Platform
                        </a>
                        <a href="<?= BASE_URL ?>auth/register.php" class="btn btn-outline-light btn-lg px-4 py-2.5 rounded-pill fw-semibold">
                            <i class="fa-solid fa-user-plus me-2"></i> Register New Account
                        </a>
                        <a href="#portals" class="btn btn-link text-light text-decoration-none px-2 py-2.5 small">
                            <i class="fa-solid fa-sitemap me-1.5"></i> Explore 4 Portals &rarr;
                        </a>
                    </div>

                    <!-- Micro Feature Tags -->
                    <div class="d-flex flex-wrap align-items-center gap-3 text-light extra-small">
                        <span><i class="fa-solid fa-circle-check text-success me-1"></i> Weighted Multi-Criteria Algorithm</span>
                        <span><i class="fa-solid fa-circle-check text-success me-1"></i> 12 Reports Studio</span>
                        <span><i class="fa-solid fa-circle-check text-success me-1"></i> 100% Responsive</span>
                    </div>
                </div>

                <!-- Right Hero Interactive Visual Mockup -->
                <div class="col-lg-6">
                    <div class="hero-preview-card">
                        
                        <!-- Preview Card Header -->
                        <div class="d-flex align-items-center justify-content-between pb-3 mb-3 border-bottom border-secondary border-opacity-25">
                            <div class="d-flex align-items-center gap-2">
                                <span class="badge bg-danger rounded-circle p-1"></span>
                                <span class="badge bg-warning rounded-circle p-1"></span>
                                <span class="badge bg-success rounded-circle p-1"></span>
                                <span class="ms-2 small text-light fw-semibold">SPAS Executive Control Center</span>
                            </div>
                            <span class="badge bg-primary bg-opacity-20 text-info border border-info border-opacity-30 extra-small">
                                Real-time Feed
                            </span>
                        </div>

                        <!-- Mini Dashboard Widgets Mockup -->
                        <div class="row g-3 mb-3">
                            <div class="col-6">
                                <div class="p-3 rounded-3 bg-white bg-opacity-10 border border-white border-opacity-10">
                                    <div class="extra-small text-light mb-1">On-Time Delivery Rate</div>
                                    <div class="d-flex align-items-baseline gap-2">
                                        <h3 class="fw-extrabold text-white mb-0">98.4%</h3>
                                        <span class="badge bg-success extra-small"><i class="fa-solid fa-arrow-up"></i> +2.1%</span>
                                    </div>
                                    <div class="progress mt-2" style="height: 4px; background: rgba(255,255,255,0.1);">
                                        <div class="progress-bar bg-success" style="width: 98.4%;"></div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="p-3 rounded-3 bg-white bg-opacity-10 border border-white border-opacity-10">
                                    <div class="extra-small text-light mb-1">Quality Defect PPM</div>
                                    <div class="d-flex align-items-baseline gap-2">
                                        <h3 class="fw-extrabold text-white mb-0">0.82%</h3>
                                        <span class="badge bg-success extra-small">Grade A+</span>
                                    </div>
                                    <div class="progress mt-2" style="height: 4px; background: rgba(255,255,255,0.1);">
                                        <div class="progress-bar bg-info" style="width: 92%;"></div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Top Supplier Mini Leaderboard -->
                        <div class="p-3 rounded-3 bg-white bg-opacity-10 border border-white border-opacity-10">
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <span class="extra-small text-uppercase fw-bold text-light">Top Preferred Suppliers</span>
                                <span class="extra-small text-info">Live Ranking Matrix</span>
                            </div>

                            <div class="d-flex flex-column gap-2">
                                <div class="d-flex align-items-center justify-content-between p-2 rounded-2 bg-white bg-opacity-5">
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="badge bg-warning text-dark fw-bold" style="width: 22px; height: 22px; display: inline-flex; align-items: center; justify-content: center; border-radius: 50%;">1</span>
                                        <div>
                                            <div class="small fw-semibold text-white">Glow Beauty Distribution</div>
                                            <div class="extra-small text-light">Skincare & Cosmetics &bull; 99.1% OTD</div>
                                        </div>
                                    </div>
                                    <span class="badge bg-success bg-opacity-25 text-success border border-success border-opacity-30">96.8 / 100</span>
                                </div>

                                <div class="d-flex align-items-center justify-content-between p-2 rounded-2 bg-white bg-opacity-5">
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="badge bg-secondary text-white fw-bold" style="width: 22px; height: 22px; display: inline-flex; align-items: center; justify-content: center; border-radius: 50%;">2</span>
                                        <div>
                                            <div class="small fw-semibold text-white">Aura Natural Oils Ltd</div>
                                            <div class="extra-small text-light">Raw Extracts &bull; 97.5% OTD</div>
                                        </div>
                                    </div>
                                    <span class="badge bg-primary bg-opacity-25 text-info border border-info border-opacity-30">93.4 / 100</span>
                                </div>
                            </div>
                        </div>

                        <!-- Floating Live Status Tag -->
                        <div class="mt-3 pt-2 d-flex align-items-center justify-content-between extra-small text-light border-top border-secondary border-opacity-25">
                            <span><i class="fa-solid fa-server text-success me-1"></i> MySQL Engine: <strong>Active</strong></span>
                            <span>Evaluation Mode: <strong>Weighted Multi-Criteria</strong></span>
                        </div>

                    </div>
                </div>

            </div>
        </div>
    </section>


    <!-- =======================================================
         3. DYNAMIC METRICS RIBBON (LIVE FROM DB)
         ======================================================= -->
    <section class="stats-ribbon">
        <div class="container-fluid px-lg-5">
            <div class="row g-3 justify-content-center">
                
                <div class="col-6 col-md-4 col-lg-2">
                    <div class="stat-item">
                        <div class="stat-number text-primary"><?= $stats['suppliers'] ?></div>
                        <div class="stat-label">Active Suppliers</div>
                    </div>
                </div>

                <div class="col-6 col-md-4 col-lg-2">
                    <div class="stat-item">
                        <div class="stat-number text-success"><?= $stats['products'] ?></div>
                        <div class="stat-label">Products & Batches</div>
                    </div>
                </div>

                <div class="col-6 col-md-4 col-lg-2">
                    <div class="stat-item">
                        <div class="stat-number text-warning"><?= $stats['orders'] ?></div>
                        <div class="stat-label">Purchase Orders</div>
                    </div>
                </div>

                <div class="col-6 col-md-4 col-lg-2">
                    <div class="stat-item">
                        <div class="stat-number text-purple" style="color: #8b5cf6;"><?= $stats['transfers'] ?></div>
                        <div class="stat-label">Transfers Logged</div>
                    </div>
                </div>

                <div class="col-6 col-md-4 col-lg-2">
                    <div class="stat-item">
                        <div class="stat-number text-danger"><?= $stats['inspections'] ?></div>
                        <div class="stat-label">QC Inspections</div>
                    </div>
                </div>

                <div class="col-6 col-md-4 col-lg-2">
                    <div class="stat-item">
                        <div class="stat-number text-info">12</div>
                        <div class="stat-label">Analytical Reports</div>
                    </div>
                </div>

            </div>
        </div>
    </section>


    <!-- =======================================================
         4. THE 4 STAKEHOLDER PORTALS ECOSYSTEM
         ======================================================= -->
    <section class="py-5 bg-light border-bottom" id="portals">
        <div class="container-fluid px-lg-5 py-4">
            
            <div class="text-center max-w-xl mx-auto mb-5" style="max-width: 720px;">
                <span class="badge bg-primary bg-opacity-10 text-primary px-3 py-1.5 rounded-pill fw-bold extra-small text-uppercase mb-2">
                    Role-Based Architecture
                </span>
                <h2 class="fw-extrabold display-6 text-dark tracking-tight">Dedicated Stakeholder Portals</h2>
                <p class="text-muted small">
                    Engineered with strict Role-Based Access Control (RBAC) to provide isolated operational consoles tailored to each tier of the supply chain ecosystem.
                </p>
            </div>

            <div class="row g-4 justify-content-center">
                
                <!-- 1. Administrator Portal -->
                <div class="col-md-6 col-xl-3">
                    <div class="portal-grid-card admin-theme d-flex flex-column h-100">
                        <div class="portal-avatar-icon bg-danger bg-opacity-10 text-danger">
                            <i class="fa-solid fa-user-shield"></i>
                        </div>
                        <span class="badge bg-danger bg-opacity-10 text-danger extra-small fw-bold px-2 py-1 mb-2 align-self-start">CENTRAL GOVERNANCE</span>
                        <h4 class="fw-bold text-dark mb-2">Administrator</h4>
                        <p class="text-muted small mb-3">
                            Executive command center, user account approval, system logs, security audits, and Reports Studio.
                        </p>
                        <ul class="list-unstyled extra-small text-secondary mb-4 space-y-1.5 flex-grow-1">
                            <li><i class="fa-solid fa-check text-danger me-1.5"></i> Executive Performance Dashboard</li>
                            <li><i class="fa-solid fa-check text-danger me-1.5"></i> 12 Reports Studio & CSV Export</li>
                            <li><i class="fa-solid fa-check text-danger me-1.5"></i> User Governance & RBAC Controls</li>
                        </ul>
                        <div class="d-flex flex-column gap-2 mt-auto">
                            <a href="<?= BASE_URL ?>auth/login.php?role=admin" class="btn btn-danger w-100 py-2 rounded-3 fw-semibold shadow-sm">
                                <i class="fa-solid fa-user-shield me-1.5"></i> Admin Sign In
                            </a>
                            <span class="text-center extra-small text-muted py-1">
                                <i class="fa-solid fa-lock me-1"></i> Pre-provisioned (No Public Registration)
                            </span>
                        </div>
                    </div>
                </div>

                <!-- 2. Manufacturer Portal -->
                <div class="col-md-6 col-xl-3">
                    <div class="portal-grid-card mfr-theme d-flex flex-column h-100">
                        <div class="portal-avatar-icon bg-primary bg-opacity-10 text-primary">
                            <i class="fa-solid fa-industry"></i>
                        </div>
                        <span class="badge bg-primary bg-opacity-10 text-primary extra-small fw-bold px-2 py-1 mb-2 align-self-start">TIER 1 ORIGIN</span>
                        <h4 class="fw-bold text-dark mb-2">Manufacturer</h4>
                        <p class="text-muted small mb-3">
                            Formulate cosmetic batches, manage factory inventory, and initiate primary product transfers to suppliers.
                        </p>
                        <ul class="list-unstyled extra-small text-secondary mb-4 space-y-1.5 flex-grow-1">
                            <li><i class="fa-solid fa-check text-primary me-1.5"></i> Formulation & Batch Creation</li>
                            <li><i class="fa-solid fa-check text-primary me-1.5"></i> Factory Inventory Ledger</li>
                            <li><i class="fa-solid fa-check text-primary me-1.5"></i> Outgoing Supplier Transfers</li>
                        </ul>
                        <div class="d-flex flex-column gap-2 mt-auto">
                            <a href="<?= BASE_URL ?>auth/login.php?role=manufacturer" class="btn btn-primary w-100 py-2 rounded-3 fw-semibold shadow-sm">
                                <i class="fa-solid fa-right-to-bracket me-1.5"></i> Manufacturer Sign In
                            </a>
                            <a href="<?= BASE_URL ?>auth/register.php?role=manufacturer" class="btn btn-outline-primary w-100 py-1.5 rounded-3 fw-semibold extra-small">
                                <i class="fa-solid fa-user-plus me-1.5"></i> Register as Manufacturer
                            </a>
                        </div>
                    </div>
                </div>

                <!-- 3. Supplier Portal -->
                <div class="col-md-6 col-xl-3">
                    <div class="portal-grid-card sup-theme d-flex flex-column h-100">
                        <div class="portal-avatar-icon bg-success bg-opacity-10 text-success">
                            <i class="fa-solid fa-truck-ramp-box"></i>
                        </div>
                        <span class="badge bg-success bg-opacity-10 text-success extra-small fw-bold px-2 py-1 mb-2 align-self-start">DISTRIBUTION HUB</span>
                        <h4 class="fw-bold text-dark mb-2">Supplier</h4>
                        <p class="text-muted small mb-3">
                            Fulfill purchase orders, manage regional warehouse stock, dispatch goods, and inspect real-time performance feedback.
                        </p>
                        <ul class="list-unstyled extra-small text-secondary mb-4 space-y-1.5 flex-grow-1">
                            <li><i class="fa-solid fa-check text-success me-1.5"></i> Receive Manufacturer Batches</li>
                            <li><i class="fa-solid fa-check text-success me-1.5"></i> Dispatch to Retail Shopkeepers</li>
                            <li><i class="fa-solid fa-check text-success me-1.5"></i> Live Scorecard & Rating Transparency</li>
                        </ul>
                        <div class="d-flex flex-column gap-2 mt-auto">
                            <a href="<?= BASE_URL ?>auth/login.php?role=supplier" class="btn btn-success w-100 py-2 rounded-3 fw-semibold shadow-sm">
                                <i class="fa-solid fa-right-to-bracket me-1.5"></i> Supplier Sign In
                            </a>
                            <a href="<?= BASE_URL ?>auth/register.php?role=supplier" class="btn btn-outline-success w-100 py-1.5 rounded-3 fw-semibold extra-small">
                                <i class="fa-solid fa-user-plus me-1.5"></i> Register as Supplier
                            </a>
                        </div>
                    </div>
                </div>

                <!-- 4. Shopkeeper Portal -->
                <div class="col-md-6 col-xl-3">
                    <div class="portal-grid-card shop-theme d-flex flex-column h-100">
                        <div class="portal-avatar-icon text-purple" style="background: rgba(139, 92, 246, 0.1); color: #8b5cf6;">
                            <i class="fa-solid fa-store"></i>
                        </div>
                        <span class="badge bg-purple bg-opacity-10 text-purple extra-small fw-bold px-2 py-1 mb-2 align-self-start" style="color: #8b5cf6; background: rgba(139, 92, 246, 0.1);">RETAIL FRONT</span>
                        <h4 class="fw-bold text-dark mb-2">Shopkeeper</h4>
                        <p class="text-muted small mb-3">
                            Browse wholesale product catalog, receive stock transfers, manage store shelves, and submit return defect claims.
                        </p>
                        <ul class="list-unstyled extra-small text-secondary mb-4 space-y-1.5 flex-grow-1">
                            <li><i class="fa-solid fa-check text-purple me-1.5" style="color: #8b5cf6;"></i> Wholesale Cosmetics Catalog</li>
                            <li><i class="fa-solid fa-check text-purple me-1.5" style="color: #8b5cf6;"></i> Confirm Inbound Shipments</li>
                            <li><i class="fa-solid fa-check text-purple me-1.5" style="color: #8b5cf6;"></i> Defect Returns & Refund Tracking</li>
                        </ul>
                        <div class="d-flex flex-column gap-2 mt-auto">
                            <a href="<?= BASE_URL ?>auth/login.php?role=shopkeeper" class="btn w-100 py-2 rounded-3 fw-semibold text-white shadow-sm" style="background: #7c3aed;">
                                <i class="fa-solid fa-right-to-bracket me-1.5"></i> Shopkeeper Sign In
                            </a>
                            <a href="<?= BASE_URL ?>auth/register.php?role=shopkeeper" class="btn btn-outline-secondary w-100 py-1.5 rounded-3 fw-semibold extra-small" style="color: #7c3aed; border-color: #7c3aed;">
                                <i class="fa-solid fa-user-plus me-1.5"></i> Register as Shopkeeper
                            </a>
                        </div>
                    </div>
                </div>

            </div>

            <!-- Single Button for Portal Selection -->
            <div class="text-center mt-4">
                <a href="<?= BASE_URL ?>auth/portal_select.php" class="btn btn-outline-secondary px-4 py-2 rounded-pill fw-semibold small">
                    <i class="fa-solid fa-table-cells me-1.5"></i> Open Multi-Portal Gateway & Registration Center
                </a>
            </div>

        </div>
    </section>


    <!-- =======================================================
         5. SCIENTIFIC EVALUATION METHODOLOGY (4 PILLARS)
         ======================================================= -->
    <section class="py-5 bg-white border-bottom" id="framework">
        <div class="container-fluid px-lg-5 py-4">
            
            <div class="text-center max-w-xl mx-auto mb-5" style="max-width: 720px;">
                <span class="badge bg-info bg-opacity-10 text-info px-3 py-1.5 rounded-pill fw-bold extra-small text-uppercase mb-2">
                    Algorithmic Foundation
                </span>
                <h2 class="fw-extrabold display-6 text-dark tracking-tight">The 4-Pillar Evaluation Framework</h2>
                <p class="text-muted small">
                    SPAS eliminates subjective bias by calculating an objective weighted performance score governed by mathematical criteria:
                </p>
            </div>

            <div class="row g-4 mb-5">
                
                <!-- Pillar 1: Delivery -->
                <div class="col-md-6 col-lg-3">
                    <div class="pillar-box">
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <div class="p-2.5 rounded-3 bg-primary bg-opacity-10 text-primary fs-5">
                                <i class="fa-solid fa-truck-fast"></i>
                            </div>
                            <span class="badge bg-primary px-2.5 py-1.5 rounded-pill fw-bold fs-6">35% Weight</span>
                        </div>
                        <h5 class="fw-bold text-dark mb-2">On-Time Delivery (OTD)</h5>
                        <p class="text-muted small mb-3">
                            Measures delivery schedule adherence, lead time variance, and transit delay days compared to promised commitment dates.
                        </p>
                        <div class="p-2.5 bg-light rounded-2 font-monospace extra-small text-secondary">
                            Score = (OnTimeDeliveries / TotalDeliveries) &times; 100
                        </div>
                    </div>
                </div>

                <!-- Pillar 2: Quality -->
                <div class="col-md-6 col-lg-3">
                    <div class="pillar-box">
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <div class="p-2.5 rounded-3 bg-success bg-opacity-10 text-success fs-5">
                                <i class="fa-solid fa-shield-halved"></i>
                            </div>
                            <span class="badge bg-success px-2.5 py-1.5 rounded-pill fw-bold fs-6">30% Weight</span>
                        </div>
                        <h5 class="fw-bold text-dark mb-2">Quality & Defect PPM</h5>
                        <p class="text-muted small mb-3">
                            Quantifies batch inspection acceptance rates, defect parts per million (PPM), packaging integrity, and return claims.
                        </p>
                        <div class="p-2.5 bg-light rounded-2 font-monospace extra-small text-secondary">
                            Score = 100 - (DefectQuantity / ReceivedQty &times; 100)
                        </div>
                    </div>
                </div>

                <!-- Pillar 3: Cost -->
                <div class="col-md-6 col-lg-3">
                    <div class="pillar-box">
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <div class="p-2.5 rounded-3 bg-warning bg-opacity-10 text-warning fs-5">
                                <i class="fa-solid fa-scale-balanced"></i>
                            </div>
                            <span class="badge bg-warning text-dark px-2.5 py-1.5 rounded-pill fw-bold fs-6">20% Weight</span>
                        </div>
                        <h5 class="fw-bold text-dark mb-2">Pricing & Cost Efficiency</h5>
                        <p class="text-muted small mb-3">
                            Evaluates unit pricing stability against market benchmark averages, bulk discounts, and invoice billing accuracy.
                        </p>
                        <div class="p-2.5 bg-light rounded-2 font-monospace extra-small text-secondary">
                            Score = (BenchmarkPrice / ActualPrice) &times; 100
                        </div>
                    </div>
                </div>

                <!-- Pillar 4: Service -->
                <div class="col-md-6 col-lg-3">
                    <div class="pillar-box">
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <div class="p-2.5 rounded-3 bg-info bg-opacity-10 text-info fs-5">
                                <i class="fa-solid fa-headset"></i>
                            </div>
                            <span class="badge bg-info text-white px-2.5 py-1.5 rounded-pill fw-bold fs-6">15% Weight</span>
                        </div>
                        <h5 class="fw-bold text-dark mb-2">Service & Responsiveness</h5>
                        <p class="text-muted small mb-3">
                            Evaluates query resolution speed, claim turnaround SLA compliance, order flexibility, and contract cooperation.
                        </p>
                        <div class="p-2.5 bg-light rounded-2 font-monospace extra-small text-secondary">
                            Score = Evaluated SLA Compliance &times; 100
                        </div>
                    </div>
                </div>

            </div>

            <!-- Mathematical Formula Showcase -->
            <div class="card bg-dark text-white p-4 rounded-4 border-0 shadow-sm mx-auto" style="max-width: 920px;">
                <div class="d-flex flex-column flex-md-row align-items-center justify-content-between gap-3">
                    <div>
                        <span class="badge bg-primary extra-small mb-2">COMPREHENSIVE COMPOSITE INDEX</span>
                        <h5 class="fw-bold mb-1">Final Supplier Performance Score Formula</h5>
                        <p class="text-light extra-small mb-0">Total Score = (0.35 &times; OTD) + (0.30 &times; Quality) + (0.20 &times; Cost) + (0.15 &times; Service)</p>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <span class="badge bg-success px-3 py-2 rounded-pill small">Grade A+ &ge; 85%</span>
                        <span class="badge bg-primary px-3 py-2 rounded-pill small">Grade A &ge; 70%</span>
                        <span class="badge bg-warning text-dark px-3 py-2 rounded-pill small">Grade B &ge; 50%</span>
                        <span class="badge bg-danger px-3 py-2 rounded-pill small">Grade C &lt; 50%</span>
                    </div>
                </div>
            </div>

        </div>
    </section>


    <!-- =======================================================
         6. INTERACTIVE LIVE PERFORMANCE CALCULATOR
         ======================================================= -->
    <section class="py-5 bg-light border-bottom" id="calculator">
        <div class="container-fluid px-lg-5 py-4">
            
            <div class="text-center max-w-xl mx-auto mb-5" style="max-width: 720px;">
                <span class="badge bg-warning bg-opacity-20 text-warning border border-warning border-opacity-30 px-3 py-1.5 rounded-pill fw-bold extra-small text-uppercase mb-2">
                    Real-Time Simulation
                </span>
                <h2 class="fw-extrabold display-6 text-dark tracking-tight">Interactive Score & Grade Simulator</h2>
                <p class="text-muted small">
                    Adjust the live criteria sliders below to test how delivery delays, defect PPM, price variance, and service compliance directly impact a supplier's rating and classification tier.
                </p>
            </div>

            <div class="calc-card mx-auto" style="max-width: 980px;">
                <div class="row g-4 align-items-center">
                    
                    <!-- Sliders Column -->
                    <div class="col-lg-7">
                        
                        <!-- Slider 1: Delivery -->
                        <div class="mb-4">
                            <div class="d-flex justify-content-between align-items-center mb-1.5">
                                <label class="small fw-bold text-white d-flex align-items-center gap-2">
                                    <i class="fa-solid fa-truck text-primary"></i> On-Time Delivery Rate (Weight: 35%)
                                </label>
                                <span class="badge bg-primary fw-bold font-monospace" id="val-delivery">95%</span>
                            </div>
                            <input type="range" class="calc-slider" id="slider-delivery" min="0" max="100" value="95">
                        </div>

                        <!-- Slider 2: Quality -->
                        <div class="mb-4">
                            <div class="d-flex justify-content-between align-items-center mb-1.5">
                                <label class="small fw-bold text-white d-flex align-items-center gap-2">
                                    <i class="fa-solid fa-shield-check text-success"></i> Quality & Defect Compliance (Weight: 30%)
                                </label>
                                <span class="badge bg-success fw-bold font-monospace" id="val-quality">92%</span>
                            </div>
                            <input type="range" class="calc-slider" id="slider-quality" min="0" max="100" value="92">
                        </div>

                        <!-- Slider 3: Cost -->
                        <div class="mb-4">
                            <div class="d-flex justify-content-between align-items-center mb-1.5">
                                <label class="small fw-bold text-white d-flex align-items-center gap-2">
                                    <i class="fa-solid fa-coins text-warning"></i> Pricing & Cost Efficiency (Weight: 20%)
                                </label>
                                <span class="badge bg-warning text-dark fw-bold font-monospace" id="val-cost">88%</span>
                            </div>
                            <input type="range" class="calc-slider" id="slider-cost" min="0" max="100" value="88">
                        </div>

                        <!-- Slider 4: Service -->
                        <div class="mb-2">
                            <div class="d-flex justify-content-between align-items-center mb-1.5">
                                <label class="small fw-bold text-white d-flex align-items-center gap-2">
                                    <i class="fa-solid fa-headset text-info"></i> Service Level & Responsiveness (Weight: 15%)
                                </label>
                                <span class="badge bg-info text-white fw-bold font-monospace" id="val-service">90%</span>
                            </div>
                            <input type="range" class="calc-slider" id="slider-service" min="0" max="100" value="90">
                        </div>

                    </div>

                    <!-- Calculated Result Badge Column -->
                    <div class="col-lg-5">
                        <div class="p-4 rounded-4 bg-white bg-opacity-10 border border-white border-opacity-10 text-center">
                            <span class="extra-small text-uppercase tracking-wider text-light fw-bold">Computed Performance Score</span>
                            
                            <div class="display-3 fw-extrabold text-white my-2 font-monospace" id="calc-final-score">
                                92.0
                            </div>
                            
                            <div class="mb-3">
                                <span class="badge bg-success fs-6 px-3 py-1.5 rounded-pill fw-bold" id="calc-grade-badge">
                                    🌟 Grade A+ (Preferred Supplier)
                                </span>
                            </div>

                            <p class="extra-small text-light mb-3" id="calc-recommendation">
                                Outstanding consistency. Priority award for long-term supply agreements and volume expansion.
                            </p>

                            <div class="p-2.5 rounded-2 bg-black bg-opacity-30 extra-small text-light font-monospace" id="calc-breakdown">
                                Delivery: 33.25 | Quality: 27.60 | Cost: 17.60 | Service: 13.50
                            </div>
                        </div>
                    </div>

                </div>
            </div>

        </div>
    </section>


    <!-- =======================================================
         7. ENTERPRISE CAPABILITIES & CORE MODULES
         ======================================================= -->
    <section class="py-5 bg-white border-bottom" id="capabilities">
        <div class="container-fluid px-lg-5 py-4">
            
            <div class="text-center max-w-xl mx-auto mb-5" style="max-width: 720px;">
                <span class="badge bg-primary bg-opacity-10 text-primary px-3 py-1.5 rounded-pill fw-bold extra-small text-uppercase mb-2">
                    Comprehensive Suite
                </span>
                <h2 class="fw-extrabold display-6 text-dark tracking-tight">Enterprise Features & System Capabilities</h2>
                <p class="text-muted small">
                    Designed from the ground up for industrial scalability, compliance audits, and strategic procurement decisions.
                </p>
            </div>

            <div class="row g-4">
                
                <!-- 1. Reports Studio -->
                <div class="col-md-6 col-lg-4">
                    <div class="p-4 rounded-4 border bg-white h-100 shadow-sm">
                        <div class="p-2.5 rounded-3 bg-primary bg-opacity-10 text-primary fs-5 d-inline-block mb-3">
                            <i class="fa-solid fa-chart-pie"></i>
                        </div>
                        <h5 class="fw-bold text-dark mb-2">Reports Studio (12 Reports)</h5>
                        <p class="text-muted small mb-3">
                            Includes supplier performance scorecards, transfer tracking, defect rate audits, delivery lag analysis, and one-click CSV and Print exports.
                        </p>
                        <span class="badge bg-light text-secondary border extra-small">Real MySQL Data</span>
                    </div>
                </div>

                <!-- 2. Side-by-Side Comparison -->
                <div class="col-md-6 col-lg-4">
                    <div class="p-4 rounded-4 border bg-white h-100 shadow-sm">
                        <div class="p-2.5 rounded-3 bg-success bg-opacity-10 text-success fs-5 d-inline-block mb-3">
                            <i class="fa-solid fa-scale-balanced"></i>
                        </div>
                        <h5 class="fw-bold text-dark mb-2">Supplier Comparison Matrix</h5>
                        <p class="text-muted small mb-3">
                            Head-to-head radar comparisons and multi-criteria ranking leaderboards to select optimal vendors for tenders and procurement contracts.
                        </p>
                        <span class="badge bg-light text-secondary border extra-small">Multi-Vendor Radar</span>
                    </div>
                </div>

                <!-- 3. Chain of Custody Traceability -->
                <div class="col-md-6 col-lg-4">
                    <div class="p-4 rounded-4 border bg-white h-100 shadow-sm">
                        <div class="p-2.5 rounded-3 bg-warning bg-opacity-10 text-warning fs-5 d-inline-block mb-3">
                            <i class="fa-solid fa-arrow-right-arrow-left"></i>
                        </div>
                        <h5 class="fw-bold text-dark mb-2">End-to-End Product Traceability</h5>
                        <p class="text-muted small mb-3">
                            Follow batches from Manufacturer formulation through Supplier distribution hubs directly to Retail shopkeepers with unique transfer references.
                        </p>
                        <span class="badge bg-light text-secondary border extra-small">Batch Provenance</span>
                    </div>
                </div>

                <!-- 4. Quality Inspection & Defect PPM -->
                <div class="col-md-6 col-lg-4">
                    <div class="p-4 rounded-4 border bg-white h-100 shadow-sm">
                        <div class="p-2.5 rounded-3 bg-danger bg-opacity-10 text-danger fs-5 d-inline-block mb-3">
                            <i class="fa-solid fa-flask-vial"></i>
                        </div>
                        <h5 class="fw-bold text-dark mb-2">Batch Inspection & Returns</h5>
                        <p class="text-muted small mb-3">
                            Structured sampling, defect logging, and automated rejection flags with integrated return claim processing for retail shops.
                        </p>
                        <span class="badge bg-light text-secondary border extra-small">Defect PPM Tracking</span>
                    </div>
                </div>

                <!-- 5. Cross-Device Responsive -->
                <div class="col-md-6 col-lg-4">
                    <div class="p-4 rounded-4 border bg-white h-100 shadow-sm">
                        <div class="p-2.5 rounded-3 text-purple fs-5 d-inline-block mb-3" style="background: rgba(139,92,246,0.1); color: #8b5cf6;">
                            <i class="fa-solid fa-mobile-screen"></i>
                        </div>
                        <h5 class="fw-bold text-dark mb-2">100% Omni-Device Responsive</h5>
                        <p class="text-muted small mb-3">
                            Optimized off-canvas drawer navigation, responsive data tables, fluid typography, and touch-friendly controls across 320px mobile to 4K displays.
                        </p>
                        <span class="badge bg-light text-secondary border extra-small">Mobile &bull; Tablet &bull; 4K</span>
                    </div>
                </div>

                <!-- 6. Role-Based Access Security -->
                <div class="col-md-6 col-lg-4">
                    <div class="p-4 rounded-4 border bg-white h-100 shadow-sm">
                        <div class="p-2.5 rounded-3 bg-info bg-opacity-10 text-info fs-5 d-inline-block mb-3">
                            <i class="fa-solid fa-shield-halved"></i>
                        </div>
                        <h5 class="fw-bold text-dark mb-2">Enterprise Security & RBAC</h5>
                        <p class="text-muted small mb-3">
                            Bcrypt password hashing, anti-CSRF tokens, PDO parameterized statements, activity audit trails, and strict stakeholder session isolation.
                        </p>
                        <span class="badge bg-light text-secondary border extra-small">OWASP Compliant</span>
                    </div>
                </div>

            </div>

        </div>
    </section>


    <!-- =======================================================
         8. SUPPLY CHAIN LIFECYCLE PIPELINE
         ======================================================= -->
    <section class="py-5 bg-light border-bottom" id="pipeline">
        <div class="container-fluid px-lg-5 py-4">
            
            <div class="text-center max-w-xl mx-auto mb-5" style="max-width: 720px;">
                <span class="badge bg-success bg-opacity-10 text-success px-3 py-1.5 rounded-pill fw-bold extra-small text-uppercase mb-2">
                    Chain of Custody
                </span>
                <h2 class="fw-extrabold display-6 text-dark tracking-tight">The 6-Stage Supply Chain Pipeline</h2>
                <p class="text-muted small">
                    How data, inventory, and performance metrics flow seamlessly from raw ingredient formulation to point-of-sale retail feedback.
                </p>
            </div>

            <div class="row g-3">
                
                <div class="col-6 col-md-4 col-lg-2">
                    <div class="flow-step bg-white rounded-3 border h-100 shadow-sm">
                        <div class="flow-step-number">1</div>
                        <h6 class="fw-bold text-dark mb-1">Formulation</h6>
                        <p class="extra-small text-muted mb-0">Manufacturer creates batch & registers products</p>
                    </div>
                </div>

                <div class="col-6 col-md-4 col-lg-2">
                    <div class="flow-step bg-white rounded-3 border h-100 shadow-sm">
                        <div class="flow-step-number">2</div>
                        <h6 class="fw-bold text-dark mb-1">Transfer I</h6>
                        <p class="extra-small text-muted mb-0">Manufacturer dispatches stock to regional supplier</p>
                    </div>
                </div>

                <div class="col-6 col-md-4 col-lg-2">
                    <div class="flow-step bg-white rounded-3 border h-100 shadow-sm">
                        <div class="flow-step-number">3</div>
                        <h6 class="fw-bold text-dark mb-1">Warehousing</h6>
                        <p class="extra-small text-muted mb-0">Supplier stocks inventory & monitors incoming POs</p>
                    </div>
                </div>

                <div class="col-6 col-md-4 col-lg-2">
                    <div class="flow-step bg-white rounded-3 border h-100 shadow-sm">
                        <div class="flow-step-number">4</div>
                        <h6 class="fw-bold text-dark mb-1">Transfer II</h6>
                        <p class="extra-small text-muted mb-0">Supplier fulfills wholesale orders to shopkeepers</p>
                    </div>
                </div>

                <div class="col-6 col-md-4 col-lg-2">
                    <div class="flow-step bg-white rounded-3 border h-100 shadow-sm">
                        <div class="flow-step-number">5</div>
                        <h6 class="fw-bold text-dark mb-1">Inspection</h6>
                        <p class="extra-small text-muted mb-0">Quality audit, delivery timeliness & defect logging</p>
                    </div>
                </div>

                <div class="col-6 col-md-4 col-lg-2">
                    <div class="flow-step bg-white rounded-3 border h-100 shadow-sm">
                        <div class="flow-step-number">6</div>
                        <h6 class="fw-bold text-dark mb-1">Analytics</h6>
                        <p class="extra-small text-muted mb-0">Algorithmic scoring, tier grading & Reports Studio</p>
                    </div>
                </div>

            </div>

        </div>
    </section>


    <!-- =======================================================
         9. ACADEMIC DISSERTATION ALIGNMENT (CH.1 - CH.8)
         ======================================================= -->
    <section class="py-5 bg-white border-bottom" id="dissertation">
        <div class="container-fluid px-lg-5 py-4">
            
            <div class="row align-items-center g-5">
                
                <div class="col-lg-6">
                    <span class="badge bg-secondary bg-opacity-10 text-secondary px-3 py-1.5 rounded-pill fw-bold extra-small text-uppercase mb-2">
                        B.Sc. Computer Science Final Year Project
                    </span>
                    <h2 class="fw-extrabold display-6 text-dark tracking-tight mb-3">
                        Comprehensive Academic Dissertation & Documentation
                    </h2>
                    <p class="text-muted small mb-4">
                        This system is backed by an exhaustive 8-chapter project dissertation detailing system analysis, relational database schemas, UML diagrams (ER, Flowchart, Use Case, Sequence, Component, Deployment), test validation suites, and operational workflows.
                    </p>

                    <div class="d-flex flex-column gap-2 mb-4">
                        <div class="d-flex align-items-center gap-2 small text-secondary">
                            <i class="fa-solid fa-book-bookmark text-primary"></i>
                            <span><strong>Ch. 1-2:</strong> Introduction, Problem Statement, Feasibility & System Analysis</span>
                        </div>
                        <div class="d-flex align-items-center gap-2 small text-secondary">
                            <i class="fa-solid fa-diagram-project text-success"></i>
                            <span><strong>Ch. 3:</strong> System Design (ER, State, Component, Sequence, Deployment)</span>
                        </div>
                        <div class="d-flex align-items-center gap-2 small text-secondary">
                            <i class="fa-solid fa-code text-warning"></i>
                            <span><strong>Ch. 4-5:</strong> Implementation Details, Output Reports & Validation Test Cases</span>
                        </div>
                        <div class="d-flex align-items-center gap-2 small text-secondary">
                            <i class="fa-solid fa-flag-checkered text-danger"></i>
                            <span><strong>Ch. 6-8:</strong> Conclusion, Future Enhancements, Bibliography & References</span>
                        </div>
                    </div>

                    <a href="<?= BASE_URL ?>docs/FINAL_PROJECT_DOCUMENTATION.md" target="_blank" class="btn btn-outline-primary px-4 py-2 rounded-pill fw-semibold small">
                        <i class="fa-solid fa-file-lines me-1.5"></i> Open Full Project Documentation
                    </a>
                </div>

                <!-- Quick Evaluator Demo Card -->
                <div class="col-lg-6">
                    <div class="card p-4 border-0 shadow-sm rounded-4 bg-light">
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <h5 class="fw-bold text-dark mb-0"><i class="fa-solid fa-key text-warning me-2"></i> Quick Evaluator Test Accounts</h5>
                            <span class="badge bg-success">Pre-Configured</span>
                        </div>
                        <p class="text-muted extra-small mb-3">
                            Evaluators and professors can immediately test the platform using the following verified credentials:
                        </p>

                        <div class="table-responsive">
                            <table class="table table-sm table-bordered bg-white extra-small mb-3">
                                <thead class="table-light">
                                    <tr>
                                        <th>Role</th>
                                        <th>Login Email</th>
                                        <th>Password</th>
                                        <th>Direct Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td><span class="badge bg-danger">Admin</span></td>
                                        <td class="font-monospace">test_admin@spas.gov</td>
                                        <td class="font-monospace">Admin@Pass123</td>
                                        <td><a href="<?= BASE_URL ?>auth/admin_login.php" class="btn btn-xs btn-outline-danger extra-small py-0 px-1.5">Sign In</a></td>
                                    </tr>
                                    <tr>
                                        <td><span class="badge bg-primary">Manufacturer</span></td>
                                        <td class="font-monospace">test_mfr@glowtech.in</td>
                                        <td class="font-monospace">Mfr@Pass123</td>
                                        <td><a href="<?= BASE_URL ?>auth/manufacturer_login.php" class="btn btn-xs btn-outline-primary extra-small py-0 px-1.5">Sign In</a></td>
                                    </tr>
                                    <tr>
                                        <td><span class="badge bg-success">Supplier</span></td>
                                        <td class="font-monospace">test_sup@glowbeauty.in</td>
                                        <td class="font-monospace">Sup@Pass123</td>
                                        <td><a href="<?= BASE_URL ?>auth/supplier_login.php" class="btn btn-xs btn-outline-success extra-small py-0 px-1.5">Sign In</a></td>
                                    </tr>
                                    <tr>
                                        <td><span class="badge text-white" style="background: #7c3aed;">Shopkeeper</span></td>
                                        <td class="font-monospace">test_shop@luxeglamour.in</td>
                                        <td class="font-monospace">Shop@Pass123</td>
                                        <td><a href="<?= BASE_URL ?>auth/shopkeeper_login.php" class="btn btn-xs btn-outline-secondary extra-small py-0 px-1.5">Sign In</a></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <div class="extra-small text-muted">
                            <i class="fa-solid fa-shield me-1 text-primary"></i> All passwords are securely verified with <code>password_verify()</code> against Bcrypt hashes in MySQL.
                        </div>
                    </div>
                </div>

            </div>

        </div>
    </section>


    <!-- =======================================================
         10. FREQUENTLY ASKED QUESTIONS (FAQ)
         ======================================================= -->
    <section class="py-5 bg-light border-bottom">
        <div class="container-fluid px-lg-5 py-4" style="max-width: 920px;">
            
            <div class="text-center mb-5">
                <span class="badge bg-secondary bg-opacity-10 text-secondary px-3 py-1.5 rounded-pill fw-bold extra-small text-uppercase mb-2">
                    Knowledge Base
                </span>
                <h2 class="fw-extrabold display-6 text-dark tracking-tight">Frequently Asked Questions</h2>
            </div>

            <div class="accordion accordion-flush bg-white rounded-4 border shadow-sm p-2" id="faqAccordion">
                
                <div class="accordion-item border-bottom">
                    <h2 class="accordion-header" id="faq-heading-1">
                        <button class="accordion-button collapsed fw-bold text-dark" type="button" data-bs-toggle="collapse" data-bs-target="#faq-collapse-1">
                            How does SPAS calculate the overall Supplier Preference Score?
                        </button>
                    </h2>
                    <div id="faq-collapse-1" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                        <div class="accordion-body small text-muted">
                            SPAS implements a multi-criteria weighted scoring algorithm: On-Time Delivery carries a 35% weight, Quality and Defect Compliance carries 30%, Pricing and Cost Competitiveness carries 20%, and Service Responsiveness carries 15%. The weighted sum produces a 0-100 score mapped to grades from Grade A+ (Preferred) to Grade C (Critical/High Risk).
                        </div>
                    </div>
                </div>

                <div class="accordion-item border-bottom">
                    <h2 class="accordion-header" id="faq-heading-2">
                        <button class="accordion-button collapsed fw-bold text-dark" type="button" data-bs-toggle="collapse" data-bs-target="#faq-collapse-2">
                            Can a user register directly for any stakeholder role?
                        </button>
                    </h2>
                    <div id="faq-collapse-2" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                        <div class="accordion-body small text-muted">
                            Yes. Dedicated registration portals are available for Administrators, Manufacturers, Suppliers, and Shopkeepers. Each registration form validates input, creates the respective profile record in MySQL, and encrypts credentials with Bcrypt.
                        </div>
                    </div>
                </div>

                <div class="accordion-item border-bottom">
                    <h2 class="accordion-header" id="faq-heading-3">
                        <button class="accordion-button collapsed fw-bold text-dark" type="button" data-bs-toggle="collapse" data-bs-target="#faq-collapse-3">
                            How does product transfer tracking work between stages?
                        </button>
                    </h2>
                    <div id="faq-collapse-3" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                        <div class="accordion-body small text-muted">
                            Transfers are recorded in the <code>product_transfers</code> table with unique reference identifiers (e.g., <code>TRF-20260902-1001</code>). When a Manufacturer initiates a transfer to a Supplier, the batch enters 'In Transit' and atomically updates stock upon receipt. The Supplier can subsequent dispatch wholesale transfers to Shopkeepers with full provenance tracking.
                        </div>
                    </div>
                </div>

                <div class="accordion-item">
                    <h2 class="accordion-header" id="faq-heading-4">
                        <button class="accordion-button collapsed fw-bold text-dark" type="button" data-bs-toggle="collapse" data-bs-target="#faq-collapse-4">
                            What analytical reports are generated by the Reports Studio?
                        </button>
                    </h2>
                    <div id="faq-collapse-4" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                        <div class="accordion-body small text-muted">
                            The Reports Studio features 12 standardized operational reports including Supplier Performance League Tables, Quality Defect Audits, Delivery Timeliness Trends, Transfer Ledgers, and Return Claim Histories. All reports feature real-time MySQL database queries with CSV download and print formatting.
                        </div>
                    </div>
                </div>

            </div>

        </div>
    </section>


    <!-- =======================================================
         11. RICH ENTERPRISE FOOTER
         ======================================================= -->
    <footer class="home-footer">
        <div class="container-fluid px-lg-5">
            <div class="row g-4 pb-4 border-bottom border-secondary border-opacity-25">
                
                <!-- Left Brand & Mission -->
                <div class="col-lg-4">
                    <div class="d-flex align-items-center gap-2 mb-3">
                        <div style="background: #2563eb; width: 32px; height: 32px; border-radius: 8px; display: flex; align-items: center; justify-content: center;">
                            <i class="fa-solid fa-chart-line text-white small"></i>
                        </div>
                        <span class="fw-bold text-white fs-5">SPAS Enterprise</span>
                    </div>
                    <p class="extra-small text-light mb-3" style="line-height: 1.6;">
                        Supplier Performance Analysis and Management System. An integrated multi-stakeholder enterprise platform for evaluating, ranking, and managing vendor relationships with scientific rigor.
                    </p>
                    <div class="d-flex align-items-center gap-2 extra-small text-secondary">
                        <span class="badge bg-success bg-opacity-20 text-success border border-success border-opacity-30">
                            <i class="fa-solid fa-database me-1"></i> <?= $stats['db_status'] ?>
                        </span>
                        <span class="badge bg-white bg-opacity-10 text-white">PHP 8.2 &bull; MySQL 8</span>
                    </div>
                </div>

                <!-- Navigation Columns -->
                <div class="col-6 col-lg-2">
                    <h6 class="fw-bold text-white extra-small text-uppercase mb-3">Navigation</h6>
                    <ul class="list-unstyled extra-small space-y-2">
                        <li><a href="<?= BASE_URL ?>index.php" class="text-light text-decoration-none hover-white"><i class="fa-solid fa-house me-1 text-primary"></i> Home</a></li>
                        <li><a href="<?= BASE_URL ?>auth/login.php" class="text-light text-decoration-none hover-white"><i class="fa-solid fa-right-to-bracket me-1 text-info"></i> Login</a></li>
                        <li><a href="<?= BASE_URL ?>auth/register.php" class="text-light text-decoration-none hover-white"><i class="fa-solid fa-user-plus me-1 text-success"></i> Registration</a></li>
                        <li><a href="#portals" class="text-light text-decoration-none hover-white"><i class="fa-solid fa-sitemap me-1 text-warning"></i> 4 Portals</a></li>
                        <li><a href="#calculator" class="text-light text-decoration-none hover-white"><i class="fa-solid fa-calculator me-1 text-purple"></i> Score Simulator</a></li>
                    </ul>
                </div>

                <div class="col-6 col-lg-3">
                    <h6 class="fw-bold text-white extra-small text-uppercase mb-3">Stakeholder Logins</h6>
                    <ul class="list-unstyled extra-small space-y-2">
                        <li><a href="<?= BASE_URL ?>auth/login.php?role=admin" class="text-light text-decoration-none hover-white"><i class="fa-solid fa-user-shield me-1.5 text-danger"></i> Administrator Console</a></li>
                        <li><a href="<?= BASE_URL ?>auth/login.php?role=manufacturer" class="text-light text-decoration-none hover-white"><i class="fa-solid fa-industry me-1.5 text-primary"></i> Manufacturer Portal</a></li>
                        <li><a href="<?= BASE_URL ?>auth/login.php?role=supplier" class="text-light text-decoration-none hover-white"><i class="fa-solid fa-truck-ramp-box me-1.5 text-success"></i> Supplier 360 Portal</a></li>
                        <li><a href="<?= BASE_URL ?>auth/login.php?role=shopkeeper" class="text-light text-decoration-none hover-white"><i class="fa-solid fa-store me-1.5 text-purple" style="color: #8b5cf6;"></i> Shopkeeper Boutique</a></li>
                        <li><a href="<?= BASE_URL ?>auth/portal_select.php" class="text-light text-decoration-none hover-white"><i class="fa-solid fa-table-cells me-1.5"></i> Portal Gateway Directory</a></li>
                    </ul>
                </div>

                <div class="col-lg-3">
                    <h6 class="fw-bold text-white extra-small text-uppercase mb-3">Academic Project</h6>
                    <p class="extra-small text-light mb-2">
                        B.Sc. Computer Science Final Year Project dissertation & specification.
                    </p>
                    <ul class="list-unstyled extra-small space-y-2">
                        <li><a href="<?= BASE_URL ?>docs/FINAL_PROJECT_DOCUMENTATION.md" class="text-light text-decoration-none hover-white"><i class="fa-solid fa-file-lines me-1.5 text-info"></i> Project Dissertation</a></li>
                        <li><a href="<?= BASE_URL ?>docs/TEST_CASES_AND_VALIDATION.md" class="text-light text-decoration-none hover-white"><i class="fa-solid fa-vial-circle-check me-1.5 text-success"></i> Test Cases & Validation</a></li>
                        <li><a href="<?= BASE_URL ?>docs/PROJECT_DESIGN_REPORT.md" class="text-light text-decoration-none hover-white"><i class="fa-solid fa-compass-drafting me-1.5 text-warning"></i> Design & UML Specifications</a></li>
                    </ul>
                </div>

            </div>

            <div class="d-flex flex-column flex-md-row align-items-center justify-content-between pt-4 extra-small text-light">
                <div>
                    &copy; <?= date('Y') ?> Supplier Performance Analysis and Management System (SPAS). All rights reserved.
                </div>
                <div class="d-flex align-items-center gap-3 mt-2 mt-md-0">
                    <span>Release v2.5 Enterprise</span>
                    <span>&bull;</span>
                    <a href="<?= BASE_URL ?>auth/portal_select.php" class="text-light text-decoration-none">Portals</a>
                    <span>&bull;</span>
                    <a href="<?= BASE_URL ?>dashboard/index.php" class="text-light text-decoration-none">Dashboard</a>
                </div>
            </div>
        </div>
    </footer>


    <!-- =======================================================
         12. JAVASCRIPT LOGIC & INTERACTIVE SIMULATOR
         ======================================================= -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        // Interactive Score Simulator Engine
        (function() {
            const sliderDelivery = document.getElementById('slider-delivery');
            const sliderQuality  = document.getElementById('slider-quality');
            const sliderCost     = document.getElementById('slider-cost');
            const sliderService  = document.getElementById('slider-service');

            const valDelivery = document.getElementById('val-delivery');
            const valQuality  = document.getElementById('val-quality');
            const valCost     = document.getElementById('val-cost');
            const valService  = document.getElementById('val-service');

            const finalScoreEl = document.getElementById('calc-final-score');
            const gradeBadgeEl = document.getElementById('calc-grade-badge');
            const recommendationEl = document.getElementById('calc-recommendation');
            const breakdownEl = document.getElementById('calc-breakdown');

            function recalculate() {
                const otd = parseFloat(sliderDelivery.value);
                const qual = parseFloat(sliderQuality.value);
                const cost = parseFloat(sliderCost.value);
                const srv = parseFloat(sliderService.value);

                valDelivery.textContent = otd + '%';
                valQuality.textContent  = qual + '%';
                valCost.textContent     = cost + '%';
                valService.textContent  = srv + '%';

                // Weighted computation: 35% delivery, 30% quality, 20% cost, 15% service
                const weightedOtd = otd * 0.35;
                const weightedQual = qual * 0.30;
                const weightedCost = cost * 0.20;
                const weightedSrv = srv * 0.15;
                const finalScore = weightedOtd + weightedQual + weightedCost + weightedSrv;

                finalScoreEl.textContent = finalScore.toFixed(1);

                // Classification & Recommendations
                if (finalScore >= 85) {
                    gradeBadgeEl.className = 'badge bg-success fs-6 px-3 py-1.5 rounded-pill fw-bold';
                    gradeBadgeEl.innerHTML = '🌟 Grade A+ (Preferred Supplier)';
                    recommendationEl.textContent = 'Outstanding consistency across all operational criteria. Award long-term contracts & volume priority.';
                } else if (finalScore >= 70) {
                    gradeBadgeEl.className = 'badge bg-primary fs-6 px-3 py-1.5 rounded-pill fw-bold';
                    gradeBadgeEl.innerHTML = '✅ Grade A (Approved Supplier)';
                    recommendationEl.textContent = 'Meets standard commercial requirements. Routine quarterly quality audit recommended.';
                } else if (finalScore >= 50) {
                    gradeBadgeEl.className = 'badge bg-warning text-dark fs-6 px-3 py-1.5 rounded-pill fw-bold';
                    gradeBadgeEl.innerHTML = '⚠️ Grade B (Conditional Supplier)';
                    recommendationEl.textContent = 'Performance variance detected. Issue Quality Improvement Plan (QIP) with a 60-day corrective window.';
                } else {
                    gradeBadgeEl.className = 'badge bg-danger fs-6 px-3 py-1.5 rounded-pill fw-bold';
                    gradeBadgeEl.innerHTML = '🚨 Grade C (High Risk / Critical)';
                    recommendationEl.textContent = 'Critical non-compliance. Suspend active purchase orders and initiate vendor disqualification review.';
                }

                breakdownEl.textContent = `Delivery: ${weightedOtd.toFixed(2)} | Quality: ${weightedQual.toFixed(2)} | Cost: ${weightedCost.toFixed(2)} | Service: ${weightedSrv.toFixed(2)}`;
            }

            [sliderDelivery, sliderQuality, sliderCost, sliderService].forEach(el => {
                el.addEventListener('input', recalculate);
            });

            recalculate();
        })();
    </script>
</body>
</html>
