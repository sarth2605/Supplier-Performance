<?php
/**
 * Supplier Registration Gateway
 * Redirects to Unified Common Registration Portal
 */
require_once __DIR__ . '/../config/config.php';
header('Location: ' . BASE_URL . 'auth/register.php?role=supplier');
exit;
