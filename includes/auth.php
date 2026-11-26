<?php
/**
 * Goods Tracking and Transportation Management System
 * Authentication & Access Control
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/functions.php';

/**
 * Check if current user is logged in
 */
function is_logged_in() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Enforce authentication on protected pages
 */
function require_login() {
    if (!is_logged_in()) {
        flash('error', 'Authentication required. Please log in to access the system.', 'Access Denied');
        header('Location: ' . BASE_URL . 'login.php');
        exit;
    }
}

/**
 * Get logged-in user profile
 */
function current_user() {
    if (!is_logged_in()) {
        return null;
    }
    return [
        'user_id'   => $_SESSION['user_id'] ?? null,
        'username'  => $_SESSION['username'] ?? '',
        'full_name' => $_SESSION['full_name'] ?? 'Administrator',
        'role'      => $_SESSION['role'] ?? 'Administrator',
    ];
}

/**
 * Authenticate user credentials
 */
function attempt_login(PDO $pdo, $username, $password) {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? LIMIT 1");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password_hash'])) {
        // Regenerate session ID for security
        if (!headers_sent()) {
            session_regenerate_id(true);
        }
        $_SESSION['user_id']   = (int)$user['user_id'];
        $_SESSION['username']  = $user['username'];
        $_SESSION['full_name'] = $user['full_name'];
        $_SESSION['role']      = $user['role'];
        return true;
    }
    return false;
}

/**
 * Log out user
 */
function logout_user() {
    $_SESSION = [];
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    session_destroy();
}
