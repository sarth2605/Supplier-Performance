<?php
/**
 * Administrator Registration Gateway - Restricted
 * Admin accounts are not available through public registration.
 */
require_once __DIR__ . '/../config/config.php';
set_flash('error', 'Administrator accounts are restricted and cannot be created through public registration.');
header('Location: ' . BASE_URL . 'auth/login.php?role=admin');
exit;
