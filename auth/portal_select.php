<?php
/**
 * Supplier Performance Analysis and Management System (SPAS)
 * Dedicated Multi-Stakeholder Portal Selector
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';

// If already logged in, redirect directly to their dashboard
if (is_logged_in()) {
    redirect_to_role_dashboard($_SESSION['user_role']);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Select Portal — Supplier Performance Analysis System</title>
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
    
    <!-- Bootstrap 5.3 & FontAwesome -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    
    <!-- Custom Style -->
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/style.css">
    <style>
        .portal-card {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            border: 1px solid rgba(0, 0, 0, 0.08);
            border-radius: 18px;
        }
        .portal-card:hover {
            transform: translateY(-6px);
            box-shadow: 0 20px 35px -10px rgba(0, 0, 0, 0.12);
        }
        .portal-icon-wrapper {
            width: 58px;
            height: 58px;
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.45rem;
            margin-bottom: 1.25rem;
        }
    </style>
</head>
<body class="bg-light">

<div class="container py-5 min-vh-100 d-flex flex-column justify-content-center">
    
    <!-- Header Section -->
    <div class="text-center mb-5 max-w-xl mx-auto">
        <div class="d-flex align-items-center justify-content-center gap-2 mb-3">
            <a href="<?= BASE_URL ?>index.php" class="btn btn-sm btn-outline-secondary rounded-pill px-3 py-1 extra-small fw-semibold">
                <i class="fa-solid fa-arrow-left me-1"></i> Back to Home
            </a>
            <div class="d-inline-flex align-items-center gap-2 px-3 py-1 rounded-pill bg-white shadow-sm border">
                <span class="badge rounded-pill bg-primary px-2 py-0.5 extra-small">SPAS 2.5</span>
                <span class="extra-small fw-semibold text-secondary">Cosmetics & Beauty Supply Chain</span>
            </div>
        </div>
        <h1 class="fw-extrabold text-dark display-6 mb-2 tracking-tight">Supplier Performance Analysis & Management</h1>
        <p class="text-muted small">Select your designated stakeholder portal below to sign in or complete registration for your role in the supply chain.</p>
        <?php render_flash(); ?>
    </div>

    <!-- 4 Dedicated Stakeholder Portals Grid -->
    <div class="row g-4 justify-content-center mb-4">
        
        <!-- 1. ADMINISTRATOR PORTAL -->
        <div class="col-md-6 col-lg-3">
            <div class="card portal-card h-100 bg-white p-4 shadow-sm position-relative overflow-hidden" style="border-top: 4px solid #dc2626 !important;">
                <div class="d-flex flex-column h-100">
                    <div class="portal-icon-wrapper bg-danger bg-opacity-10 text-danger">
                        <i class="fa-solid fa-user-shield"></i>
                    </div>
                    <div class="flex-grow-1">
                        <span class="badge bg-danger-subtle text-danger extra-small fw-bold px-2 py-1 mb-2">RESTRICTED GOVERNANCE</span>
                        <h4 class="fw-bold text-dark mb-2">Administrator</h4>
                        <p class="text-muted small mb-3">Central governance console, user approvals, audit trails, and multi-tier supply chain analytics.</p>
                        <ul class="list-unstyled extra-small text-secondary mb-4 space-y-1">
                            <li><i class="fa-solid fa-check text-danger me-1.5"></i> Executive Command Center</li>
                            <li><i class="fa-solid fa-check text-danger me-1.5"></i> End-to-End Transfer Audit</li>
                            <li><i class="fa-solid fa-check text-danger me-1.5"></i> System Logs & User Control</li>
                        </ul>
                    </div>
                    <div class="d-flex flex-column gap-2">
                        <a href="<?= BASE_URL ?>auth/login.php?role=admin" class="btn btn-danger w-100 py-2 rounded-3 fw-semibold shadow-sm">
                            Admin Sign In
                        </a>
                        <div class="text-center extra-small text-muted py-1">
                            <i class="fa-solid fa-shield-halved text-danger me-1"></i> Restricted Governance
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 2. MANUFACTURER PORTAL -->
        <div class="col-md-6 col-lg-3">
            <div class="card portal-card h-100 bg-white p-4 shadow-sm position-relative overflow-hidden" style="border-top: 4px solid #2563eb !important;">
                <div class="d-flex flex-column h-100">
                    <div class="portal-icon-wrapper bg-primary bg-opacity-10 text-primary">
                        <i class="fa-solid fa-industry"></i>
                    </div>
                    <div class="flex-grow-1">
                        <span class="badge bg-primary-subtle text-primary extra-small fw-bold px-2 py-1 mb-2">TIER 1 ORIGIN</span>
                        <h4 class="fw-bold text-dark mb-2">Manufacturer</h4>
                        <p class="text-muted small mb-3">Formulate cosmetics, register new products, manage factory stock, and dispatch transfers to suppliers.</p>
                        <ul class="list-unstyled extra-small text-secondary mb-4 space-y-1">
                            <li><i class="fa-solid fa-check text-primary me-1.5"></i> Create & Manage Formulations</li>
                            <li><i class="fa-solid fa-check text-primary me-1.5"></i> Transfer Stock to Suppliers</li>
                            <li><i class="fa-solid fa-check text-primary me-1.5"></i> Factory Batch Tracking</li>
                        </ul>
                    </div>
                    <div class="d-flex flex-column gap-2">
                        <a href="<?= BASE_URL ?>auth/login.php?role=manufacturer" class="btn btn-primary w-100 py-2 rounded-3 fw-semibold shadow-sm">
                            Manufacturer Sign In
                        </a>
                        <a href="<?= BASE_URL ?>auth/register.php?role=manufacturer" class="btn btn-outline-secondary btn-sm w-100 py-1.5 rounded-3 extra-small fw-semibold">
                            Register Lab / Factory
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- 3. SUPPLIER PORTAL -->
        <div class="col-md-6 col-lg-3">
            <div class="card portal-card h-100 bg-white p-4 shadow-sm position-relative overflow-hidden" style="border-top: 4px solid #10b981 !important;">
                <div class="d-flex flex-column h-100">
                    <div class="portal-icon-wrapper bg-success bg-opacity-10 text-success">
                        <i class="fa-solid fa-truck-ramp-box"></i>
                    </div>
                    <div class="flex-grow-1">
                        <span class="badge bg-success-subtle text-success extra-small fw-bold px-2 py-1 mb-2">TIER 2 DISTRIBUTION</span>
                        <h4 class="fw-bold text-dark mb-2">Supplier</h4>
                        <p class="text-muted small mb-3">Receive product batches from manufacturers, manage distribution warehouse, and transfer to shopkeepers.</p>
                        <ul class="list-unstyled extra-small text-secondary mb-4 space-y-1">
                            <li><i class="fa-solid fa-check text-success me-1.5"></i> Receive Manufacturer Batches</li>
                            <li><i class="fa-solid fa-check text-success me-1.5"></i> Transfer Stock to Shopkeepers</li>
                            <li><i class="fa-solid fa-check text-success me-1.5"></i> 360° Performance Ratings</li>
                        </ul>
                    </div>
                    <div class="d-flex flex-column gap-2">
                        <a href="<?= BASE_URL ?>auth/login.php?role=supplier" class="btn btn-success w-100 py-2 rounded-3 fw-semibold shadow-sm">
                            Supplier Sign In
                        </a>
                        <a href="<?= BASE_URL ?>auth/register.php?role=supplier" class="btn btn-outline-secondary btn-sm w-100 py-1.5 rounded-3 extra-small fw-semibold">
                            Register Supplier Account
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- 4. SHOPKEEPER PORTAL -->
        <div class="col-md-6 col-lg-3">
            <div class="card portal-card h-100 bg-white p-4 shadow-sm position-relative overflow-hidden" style="border-top: 4px solid #8b5cf6 !important;">
                <div class="d-flex flex-column h-100">
                    <div class="portal-icon-wrapper text-purple" style="background: rgba(139, 92, 246, 0.1); color: #8b5cf6;">
                        <i class="fa-solid fa-store"></i>
                    </div>
                    <div class="flex-grow-1">
                        <span class="badge extra-small fw-bold px-2 py-1 mb-2" style="background: rgba(139, 92, 246, 0.15); color: #7c3aed;">TIER 3 RETAIL</span>
                        <h4 class="fw-bold text-dark mb-2">Shopkeeper</h4>
                        <p class="text-muted small mb-3">Beauty boutique, salon & retail store owners. Receive products from suppliers and manage retail inventory.</p>
                        <ul class="list-unstyled extra-small text-secondary mb-4 space-y-1">
                            <li><i class="fa-solid fa-check me-1.5" style="color: #8b5cf6;"></i> Receive Supplier Deliveries</li>
                            <li><i class="fa-solid fa-check me-1.5" style="color: #8b5cf6;"></i> In-Store Stock Management</li>
                            <li><i class="fa-solid fa-check me-1.5" style="color: #8b5cf6;"></i> Full Provenance History</li>
                        </ul>
                    </div>
                    <div class="d-flex flex-column gap-2">
                        <a href="<?= BASE_URL ?>auth/login.php?role=shopkeeper" class="btn text-white w-100 py-2 rounded-3 fw-semibold shadow-sm" style="background-color: #8b5cf6;">
                            Shopkeeper Sign In
                        </a>
                        <a href="<?= BASE_URL ?>auth/register.php?role=shopkeeper" class="btn btn-outline-secondary btn-sm w-100 py-1.5 rounded-3 extra-small fw-semibold">
                            Register Beauty Store
                        </a>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <!-- Supply Chain Flow Banner -->
    <div class="card bg-white border shadow-sm p-3 max-w-lg mx-auto text-center" style="max-width: 720px; border-radius: 14px;">
        <span class="extra-small text-uppercase fw-bold text-muted d-block mb-1">Integrated Supply Chain Workflow</span>
        <div class="d-flex flex-wrap align-items-center justify-content-center gap-2 small fw-semibold text-secondary">
            <span class="badge bg-primary bg-opacity-10 text-primary px-2.5 py-1.5 rounded-pill"><i class="fa-solid fa-industry me-1"></i> Manufacturer</span>
            <i class="fa-solid fa-arrow-right text-muted extra-small"></i>
            <span class="badge bg-success bg-opacity-10 text-success px-2.5 py-1.5 rounded-pill"><i class="fa-solid fa-truck-ramp-box me-1"></i> Supplier</span>
            <i class="fa-solid fa-arrow-right text-muted extra-small"></i>
            <span class="badge bg-purple bg-opacity-10 px-2.5 py-1.5 rounded-pill" style="color: #8b5cf6; background: rgba(139, 92, 246, 0.1);"><i class="fa-solid fa-store me-1"></i> Shopkeeper</span>
        </div>
    </div>

    <!-- Footer -->
    <div class="text-center text-muted extra-small mt-4">
        &copy; <?= date('Y') ?> Supplier Performance Analysis & Management System (SPAS) &bull; Multi-Stakeholder Platform
    </div>

</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
