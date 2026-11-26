<?php
/**
 * Goods Tracking and Transportation Management System
 * Case Study of Funtua Garage, Samaru, Zaria
 * Entry Point / Router
 */

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';

if (is_logged_in()) {
    header('Location: ' . BASE_URL . 'dashboard.php');
} else {
    header('Location: ' . BASE_URL . 'login.php');
}
exit;
