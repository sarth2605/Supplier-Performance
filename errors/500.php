<?php
/**
 * Supplier Performance Analysis System
 * 500 Internal Server Error Page
 */

http_response_code(500);
require_once __DIR__ . '/../config/config.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>500 Internal Server Error — <?= APP_FULL_NAME ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/style.css">
</head>
<body class="bg-app d-flex align-items-center justify-content-center min-vh-100 p-3">

<div class="card-saas text-center p-5 shadow-lg border-0" style="max-width: 520px; border-radius: var(--radius-xl);">
    <div class="avatar-circle mx-auto mb-3 bg-danger-subtle text-danger" style="width: 72px; height: 72px; font-size: 2rem;">
        <i class="fa-solid fa-triangle-exclamation"></i>
    </div>
    <h1 class="h3 fw-bold text-dark mb-1">Internal System Error (500)</h1>
    <p class="text-muted small mb-4">An unexpected technical condition was encountered while processing your request. Please check your MySQL database connection or contact support.</p>
    <div>
        <a href="<?= BASE_URL ?>dashboard/index.php" class="btn btn-primary btn-sm rounded-pill px-4 shadow-sm">
            <i class="fa-solid fa-rotate-right me-1"></i> Try Again
        </a>
    </div>
</div>

</body>
</html>
