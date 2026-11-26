<?php
/**
 * Goods Tracking and Transportation Management System
 * Consignment Record Deletion
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

require_login();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id > 0) {
    try {
        $stmt = $pdo->prepare("SELECT tracking_number FROM consignments WHERE consignment_id = ?");
        $stmt->execute([$id]);
        $tracking_number = $stmt->fetchColumn();

        if ($tracking_number) {
            $del = $pdo->prepare("DELETE FROM consignments WHERE consignment_id = ?");
            $del->execute([$id]);

            flash('success', "Consignment {$tracking_number} and all associated records were deleted successfully.", 'Deleted');
        } else {
            flash('warning', 'Consignment record was not found or already deleted.', 'Notice');
        }
    } catch (PDOException $e) {
        flash('error', 'Unable to delete consignment: ' . $e->getMessage(), 'Database Error');
    }
}

header('Location: ' . BASE_URL . 'consignments/list.php');
exit;
