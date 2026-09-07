<?php
/**
 * Administrator Login Gateway
 * Redirects to Unified Common Login Portal
 */
require_once __DIR__ . '/../config/config.php';
header('Location: ' . BASE_URL . 'auth/login.php?role=admin');
exit;
