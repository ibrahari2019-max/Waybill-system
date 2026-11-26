<?php
/**
 * Goods Tracking and Transportation Management System
 * Helper Functions
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Sanitize string input
 */
function sanitize($data) {
    if (is_array($data)) {
        return array_map('sanitize', $data);
    }
    return htmlspecialchars(trim((string)$data), ENT_QUOTES, 'UTF-8');
}

/**
 * Set flash notification message for iziToast
 * Types: 'success', 'error', 'warning', 'info'
 */
function flash($type, $message, $title = '') {
    if (!isset($_SESSION['flash_messages'])) {
        $_SESSION['flash_messages'] = [];
    }
    if (empty($title)) {
        switch ($type) {
            case 'success': $title = 'Success'; break;
            case 'error':   $title = 'Error'; break;
            case 'warning': $title = 'Notice'; break;
            case 'info':    $title = 'Information'; break;
            default:        $title = 'Notification'; break;
        }
    }
    $_SESSION['flash_messages'][] = [
        'type'    => $type,
        'title'   => $title,
        'message' => $message
    ];
}

/**
 * Retrieve and clear flash notification messages
 */
function get_flashes() {
    $flashes = $_SESSION['flash_messages'] ?? [];
    unset($_SESSION['flash_messages']);
    return $flashes;
}

/**
 * Generate CSRF Token
 */
function csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Validate CSRF Token
 */
function verify_csrf_token($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], (string)$token);
}

/**
 * Generate unique tracking number: FGSZ-YYYY-XXXXXX
 */
function generate_tracking_number(PDO $pdo) {
    $year = date('Y');
    do {
        $stmt = $pdo->query("SELECT MAX(consignment_id) AS max_id FROM consignments");
        $row = $stmt->fetch();
        $nextId = ($row && $row['max_id']) ? ((int)$row['max_id'] + 1) : 1;
        $tracking_number = sprintf('FGSZ-%s-%06d', $year, $nextId);

        // Check if exists
        $check = $pdo->prepare("SELECT COUNT(*) FROM consignments WHERE tracking_number = ?");
        $check->execute([$tracking_number]);
        $exists = $check->fetchColumn() > 0;

        if ($exists) {
            // Append random digits if collision
            $tracking_number = sprintf('FGSZ-%s-%06d', $year, $nextId + mt_rand(1, 99));
            $check = $pdo->prepare("SELECT COUNT(*) FROM consignments WHERE tracking_number = ?");
            $check->execute([$tracking_number]);
            $exists = $check->fetchColumn() > 0;
        }
    } while ($exists);

    return $tracking_number;
}

/**
 * Generate unique waybill number: WB-YYYY-XXXXXX
 */
function generate_waybill_number(PDO $pdo) {
    $year = date('Y');
    do {
        $stmt = $pdo->query("SELECT MAX(waybill_id) AS max_id FROM waybills");
        $row = $stmt->fetch();
        $nextId = ($row && $row['max_id']) ? ((int)$row['max_id'] + 1) : 1;
        $waybill_number = sprintf('WB-%s-%06d', $year, $nextId);

        // Check if exists
        $check = $pdo->prepare("SELECT COUNT(*) FROM waybills WHERE waybill_number = ?");
        $check->execute([$waybill_number]);
        $exists = $check->fetchColumn() > 0;

        if ($exists) {
            $waybill_number = sprintf('WB-%s-%06d', $year, $nextId + mt_rand(1, 99));
            $check = $pdo->prepare("SELECT COUNT(*) FROM waybills WHERE waybill_number = ?");
            $check->execute([$waybill_number]);
            $exists = $check->fetchColumn() > 0;
        }
    } while ($exists);

    return $waybill_number;
}

/**
 * Valid Status Transitions
 */
function get_status_order($status) {
    $statuses = [
        'Registered' => 1,
        'Dispatched' => 2,
        'In Transit' => 3,
        'Arrived'    => 4,
        'Delivered'  => 5
    ];
    return $statuses[$status] ?? 0;
}

function is_valid_status_transition($currentStatus, $newStatus) {
    $currentOrder = get_status_order($currentStatus);
    $newOrder = get_status_order($newStatus);

    // If consignment is already delivered, prevent accidental regression
    if ($currentStatus === 'Delivered' && $newStatus !== 'Delivered') {
        return false;
    }

    // Must be a recognized status
    if ($newOrder === 0) {
        return false;
    }

    // Moving forward or re-submitting with new remarks is permitted
    if ($newOrder >= $currentOrder) {
        return true;
    }

    return false;
}

/**
 * HTML Status Badge
 */
function get_status_badge($status) {
    $badgeClass = 'badge-secondary';
    switch ($status) {
        case 'Registered':
            $badgeClass = 'badge-registered';
            break;
        case 'Dispatched':
            $badgeClass = 'badge-dispatched';
            break;
        case 'In Transit':
            $badgeClass = 'badge-transit';
            break;
        case 'Arrived':
            $badgeClass = 'badge-arrived';
            break;
        case 'Delivered':
            $badgeClass = 'badge-delivered';
            break;
    }
    return '<span class="status-badge ' . $badgeClass . '">' . htmlspecialchars($status) . '</span>';
}

/**
 * Format Datetime
 */
function format_datetime($datetime) {
    if (!$datetime || $datetime === '0000-00-00 00:00:00') return 'N/A';
    return date('M d, Y h:i A', strtotime($datetime));
}

/**
 * Format Date
 */
function format_date($date) {
    if (!$date || $date === '0000-00-00') return 'N/A';
    return date('M d, Y', strtotime($date));
}
