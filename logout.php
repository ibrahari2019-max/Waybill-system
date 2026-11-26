<?php
/**
 * Goods Tracking and Transportation Management System
 * Administrator Logout
 */

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';

logout_user();

// Start a fresh session to deliver the logout flash message
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

flash('success', 'You have been successfully logged out of the system.', 'Session Ended');
header('Location: ' . BASE_URL . 'login.php');
exit;
