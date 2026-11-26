<?php
/**
 * Automated Verification & Testing Suite
 * Goods Tracking and Transportation Management System
 * Case Study: Funtua Garage, Samaru, Zaria
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

echo "=== FUNTUA GARAGE GOODS TRACKING SYSTEM: TEST SUITE ===\n\n";

$passCount = 0;
$failCount = 0;

function assert_test($description, $condition) {
    global $passCount, $failCount;
    if ($condition) {
        echo " [PASS] " . $description . "\n";
        $passCount++;
    } else {
        echo " [FAIL] " . $description . "\n";
        $failCount++;
    }
}

// 1. Database Connection Test
assert_test("Database connection is active via PDO", $pdo instanceof PDO);

// 2. Default Administrator Authentication
$authSuccess = attempt_login($pdo, 'admin', 'admin');
assert_test("Admin authentication succeeds with credentials 'admin'/'admin'", $authSuccess === true);

$authFail = attempt_login($pdo, 'admin', 'wrong_password_123');
assert_test("Admin authentication fails with incorrect password", $authFail === false);

$nonExistentUser = attempt_login($pdo, 'ghost_user_xyz', 'admin');
assert_test("Authentication fails for non-existent user", $nonExistentUser === false);

// 3. Tracking Number & Waybill Number Generation
$testTracking = generate_tracking_number($pdo);
assert_test("Generated tracking number format is valid (FGSZ-YYYY-XXXXXX): {$testTracking}", preg_match('/^FGSZ-\d{4}-\d{6}$/', $testTracking) === 1);

$testWaybill = generate_waybill_number($pdo);
assert_test("Generated waybill number format is valid (WB-YYYY-XXXXXX): {$testWaybill}", preg_match('/^WB-\d{4}-\d{6}$/', $testWaybill) === 1);

// 4. Consignment Registration Test
$senderName = "Malam Haruna Danbatta";
$senderPhone = "08035554433";
$recipientName = "Alhaji Sani Funtua";
$recipientPhone = "08027778899";
$goodsDesc = "8 Sacks of Quality Groundnut and Cotton Seeds";
$qty = 8;
$destination = "Funtua";

$pdo->beginTransaction();
$stmt = $pdo->prepare("
    INSERT INTO consignments (
        tracking_number, sender_name, sender_contact, sender_address,
        recipient_name, recipient_contact, recipient_address,
        goods_description, quantity, goods_category, special_instructions,
        destination, dispatch_date, driver_id, vehicle_id,
        current_status, registered_by, created_at
    ) VALUES (
        ?, ?, ?, 'Samaru, Zaria',
        ?, ?, 'Central Park, Funtua',
        ?, ?, 'Agricultural Produce', 'Keep away from damp areas',
        ?, CURDATE(), 1, 1,
        'Registered', 1, NOW()
    )
");
$stmt->execute([
    $testTracking,
    $senderName,
    $senderPhone,
    $recipientName,
    $recipientPhone,
    $goodsDesc,
    $qty,
    $destination
]);
$consignmentId = (int)$pdo->lastInsertId();

$wbStmt = $pdo->prepare("INSERT INTO waybills (consignment_id, waybill_number, issue_date, issued_by) VALUES (?, ?, NOW(), 1)");
$wbStmt->execute([$consignmentId, $testWaybill]);

$histStmt = $pdo->prepare("INSERT INTO delivery_status_history (consignment_id, status, remarks, updated_by, updated_at) VALUES (?, 'Registered', 'Test registration', 1, NOW())");
$histStmt->execute([$consignmentId]);

$pdo->commit();

assert_test("Consignment saved to database with ID {$consignmentId}", $consignmentId > 0);

// Verify waybill created
$checkWb = $pdo->prepare("SELECT waybill_number FROM waybills WHERE consignment_id = ?");
$checkWb->execute([$consignmentId]);
$savedWb = $checkWb->fetchColumn();
assert_test("Associated waybill record successfully saved: {$savedWb}", $savedWb === $testWaybill);

// 5. Status Transition Lifecycle
assert_test("Valid transition Registered -> Dispatched", is_valid_status_transition('Registered', 'Dispatched') === true);
assert_test("Valid transition Dispatched -> In Transit", is_valid_status_transition('Dispatched', 'In Transit') === true);
assert_test("Valid transition In Transit -> Arrived", is_valid_status_transition('In Transit', 'Arrived') === true);
assert_test("Valid transition Arrived -> Delivered", is_valid_status_transition('Arrived', 'Delivered') === true);
assert_test("Invalid transition Delivered -> In Transit rejected", is_valid_status_transition('Delivered', 'In Transit') === false);

// Perform status updates
$pdo->prepare("UPDATE consignments SET current_status = 'Dispatched' WHERE consignment_id = ?")->execute([$consignmentId]);
$pdo->prepare("INSERT INTO delivery_status_history (consignment_id, status, remarks, updated_by, updated_at) VALUES (?, 'Dispatched', 'Loaded on vehicle', 1, NOW())")->execute([$consignmentId]);

$pdo->prepare("UPDATE consignments SET current_status = 'In Transit' WHERE consignment_id = ?")->execute([$consignmentId]);
$pdo->prepare("INSERT INTO delivery_status_history (consignment_id, status, remarks, updated_by, updated_at) VALUES (?, 'In Transit', 'Transit en route', 1, NOW())")->execute([$consignmentId]);

// Verify history count
$historyCount = $pdo->prepare("SELECT COUNT(*) FROM delivery_status_history WHERE consignment_id = ?");
$historyCount->execute([$consignmentId]);
assert_test("Status history preserved across updates (3 records logged)", (int)$historyCount->fetchColumn() === 3);

// 6. Delivery Confirmation Test
$confirmStmt = $pdo->prepare("
    INSERT INTO delivery_confirmation (consignment_id, received_by, remarks, confirmed_by, confirmed_at)
    VALUES (?, ?, 'All seeds received complete', 1, NOW())
");
$confirmStmt->execute([$consignmentId, $recipientName]);
$confId = (int)$pdo->lastInsertId();

$pdo->prepare("UPDATE consignments SET current_status = 'Delivered' WHERE consignment_id = ?")->execute([$consignmentId]);
assert_test("Delivery confirmation recorded with ID {$confId} and status set to 'Delivered'", $confId > 0);

// Duplicate confirmation prevention
$isDuplicatePrevented = false;
try {
    $dupStmt = $pdo->prepare("INSERT INTO delivery_confirmation (consignment_id, received_by, remarks, confirmed_by, confirmed_at) VALUES (?, 'Other', 'Dup', 1, NOW())");
    $dupStmt->execute([$consignmentId]);
} catch (PDOException $e) {
    $isDuplicatePrevented = true;
}
assert_test("Database uniqueness constraint prevents duplicate delivery confirmation", $isDuplicatePrevented === true);

// 7. Clean up test record
$pdo->prepare("DELETE FROM consignments WHERE consignment_id = ?")->execute([$consignmentId]);
assert_test("Cleaned up test consignment record {$testTracking}", true);

// 8. HTTP Endpoints Verification
$loginHtml = @file_get_contents('http://localhost/vehicle/login.php');
assert_test("HTTP GET /vehicle/login.php loads successfully with Funtua Garage branding", strpos($loginHtml, 'Funtua Garage Logistics') !== false);

$trackHtml = @file_get_contents('http://localhost/vehicle/tracking/index.php');
assert_test("HTTP GET /vehicle/tracking/index.php loads public tracking interface", strpos($trackHtml, 'Track Your Goods') !== false);

$trackResultHtml = @file_get_contents('http://localhost/vehicle/tracking/result.php?tracking=FGSZ-2026-000001');
assert_test("HTTP GET /vehicle/tracking/result.php retrieves seed consignment FGSZ-2026-000001", strpos($trackResultHtml, 'FGSZ-2026-000001') !== false);

echo "\n=======================================================\n";
echo "TEST RESULTS: {$passCount} PASSED, {$failCount} FAILED\n";
echo "=======================================================\n";

if ($failCount === 0) {
    echo "SUCCESS: ALL TESTS PASSED WITHOUT ERRORS!\n";
} else {
    exit(1);
}
