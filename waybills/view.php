<?php
/**
 * Goods Tracking and Transportation Management System
 * Digital Waybill Viewer & Printable Voucher
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

require_login();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$tracking = isset($_GET['tracking']) ? trim($_GET['tracking']) : '';

// Retrieve consignment and waybill details
$query = "
    SELECT c.*, 
           w.waybill_number, w.issue_date,
           d.full_name AS driver_name, d.phone AS driver_phone,
           v.registration_number AS vehicle_reg, v.vehicle_type,
           u.full_name AS registrar_name, u.role AS registrar_role,
           dc.received_by, dc.confirmed_at
    FROM consignments c
    LEFT JOIN waybills w ON c.consignment_id = w.consignment_id
    LEFT JOIN drivers d ON c.driver_id = d.driver_id
    LEFT JOIN vehicles v ON c.vehicle_id = v.vehicle_id
    LEFT JOIN users u ON c.registered_by = u.user_id
    LEFT JOIN delivery_confirmation dc ON c.consignment_id = dc.consignment_id
";

if ($id > 0) {
    $stmt = $pdo->prepare($query . " WHERE c.consignment_id = ? LIMIT 1");
    $stmt->execute([$id]);
} elseif (!empty($tracking)) {
    $stmt = $pdo->prepare($query . " WHERE c.tracking_number = ? LIMIT 1");
    $stmt->execute([$tracking]);
} else {
    flash('error', 'No consignment identifier provided.', 'Error');
    header('Location: ' . BASE_URL . 'consignments/list.php');
    exit;
}

$consignment = $stmt->fetch();

if (!$consignment) {
    flash('error', 'Consignment or Waybill record not found in the database.', 'Record Not Found');
    header('Location: ' . BASE_URL . 'consignments/list.php');
    exit;
}

$page_title = 'Digital Waybill - ' . htmlspecialchars($consignment['waybill_number'] ?? $consignment['tracking_number']);
$current_nav = 'waybill';

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<div class="page-header no-print">
  <div class="page-header-title">
    <h1>Digital Transportation Waybill</h1>
    <p>Official consignment dispatch documentation &bull; Funtua Garage, Samaru, Zaria</p>
  </div>
  <div class="page-header-actions">
    <button type="button" class="btn btn-primary" onclick="window.print();">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <polyline points="6 9 6 2 18 2 18 9"></polyline>
        <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path>
        <rect x="6" y="14" width="12" height="8"></rect>
      </svg>
      Print Official Waybill
    </button>
    <a href="<?php echo BASE_URL; ?>consignments/view.php?id=<?php echo $consignment['consignment_id']; ?>" class="btn btn-secondary">
      Consignment Details
    </a>
    <a href="<?php echo BASE_URL; ?>consignments/list.php" class="btn btn-secondary">
      All Consignments
    </a>
  </div>
</div>

<!-- Official Printable Waybill Container -->
<div class="waybill-container">
  <div class="waybill-watermark">FUNTUA GARAGE</div>

  <!-- Waybill Header -->
  <div class="waybill-header">
    <div class="waybill-brand">
      <h1>FUNTUA GARAGE GOODS TRACKING SYSTEM</h1>
      <p><strong>Operational Depot:</strong> Samaru Motor Park, Katsina Road Corridor, Samaru, Zaria, Kaduna State</p>
      <p><strong>Service:</strong> Interstate Freight, Cargo Dispatch &amp; Goods Transportation Management</p>
    </div>
    <div class="waybill-meta">
      <div class="wb-title">DIGITAL WAYBILL</div>
      <div class="wb-number"><?php echo htmlspecialchars($consignment['waybill_number'] ?? 'N/A'); ?></div>
      <div style="font-size: 13px; color: var(--text-muted); margin-top: 4px;">
        Issued: <?php echo format_datetime($consignment['issue_date'] ?? $consignment['created_at']); ?>
      </div>
    </div>
  </div>

  <!-- Prominent Tracking Banner -->
  <div style="display: flex; justify-content: space-between; align-items: center; background-color: #f1f5f9; border: 1px dashed #94a3b8; border-radius: 6px; padding: 12px 18px; margin-bottom: 24px;">
    <div>
      <span style="font-size: 11px; text-transform: uppercase; letter-spacing: 0.8px; color: #475569; font-weight: 700; display: block;">Unique Tracking Number</span>
      <span style="font-family: monospace; font-size: 20px; font-weight: 800; color: #1e3a8a; letter-spacing: 1px;">
        <?php echo htmlspecialchars($consignment['tracking_number']); ?>
      </span>
    </div>
    <div style="text-align: right;">
      <span style="font-size: 11px; text-transform: uppercase; letter-spacing: 0.8px; color: #475569; font-weight: 700; display: block;">Consignment Status</span>
      <?php echo get_status_badge($consignment['current_status']); ?>
    </div>
  </div>

  <!-- Sender and Receiver Panels -->
  <div class="waybill-grid-2">
    <!-- Sender Panel -->
    <div class="waybill-panel">
      <h3>Sender (Consignor) Information</h3>
      <div class="waybill-field">
        <span class="label">Full Name:</span>
        <strong><?php echo htmlspecialchars($consignment['sender_name']); ?></strong>
      </div>
      <div class="waybill-field">
        <span class="label">Phone:</span>
        <span><?php echo htmlspecialchars($consignment['sender_contact']); ?></span>
      </div>
      <div class="waybill-field">
        <span class="label">Origin / Address:</span>
        <span><?php echo htmlspecialchars($consignment['sender_address'] ?: 'Samaru Garage Depot, Zaria'); ?></span>
      </div>
    </div>

    <!-- Recipient Panel -->
    <div class="waybill-panel">
      <h3>Recipient (Consignee) Information</h3>
      <div class="waybill-field">
        <span class="label">Full Name:</span>
        <strong><?php echo htmlspecialchars($consignment['recipient_name']); ?></strong>
      </div>
      <div class="waybill-field">
        <span class="label">Phone:</span>
        <span><?php echo htmlspecialchars($consignment['recipient_contact']); ?></span>
      </div>
      <div class="waybill-field">
        <span class="label">Delivery Address:</span>
        <span><?php echo htmlspecialchars($consignment['recipient_address'] ?: 'Customer Collection Depot'); ?></span>
      </div>
    </div>
  </div>

  <!-- Transportation Logistics Summary -->
  <div class="waybill-panel" style="margin-bottom: 24px;">
    <h3>Transportation &amp; Haulage Particulars</h3>
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 12px;">
      <div class="waybill-field">
        <span class="label">Destination:</span>
        <strong style="color: #1e3a8a;"><?php echo htmlspecialchars($consignment['destination']); ?></strong>
      </div>
      <div class="waybill-field">
        <span class="label">Dispatch Date:</span>
        <span><?php echo format_date($consignment['dispatch_date'] ?? $consignment['created_at']); ?></span>
      </div>
      <div class="waybill-field">
        <span class="label">Assigned Driver:</span>
        <span><?php echo htmlspecialchars($consignment['driver_name'] ? $consignment['driver_name'] . ' (' . $consignment['driver_phone'] . ')' : 'To be assigned'); ?></span>
      </div>
      <div class="waybill-field">
        <span class="label">Vehicle Reg:</span>
        <span><?php echo htmlspecialchars($consignment['vehicle_reg'] ? $consignment['vehicle_reg'] . ' [' . $consignment['vehicle_type'] . ']' : 'To be assigned'); ?></span>
      </div>
    </div>
  </div>

  <!-- Consignment Goods Specifications Table -->
  <table class="waybill-table">
    <thead>
      <tr>
        <th style="width: 50px;">Item</th>
        <th>Description of Consignment Goods</th>
        <th style="width: 140px;">Category</th>
        <th style="width: 90px; text-align: center;">Quantity</th>
        <th>Special Handling Instructions</th>
      </tr>
    </thead>
    <tbody>
      <tr>
        <td style="text-align: center;">01</td>
        <td>
          <strong><?php echo nl2br(htmlspecialchars($consignment['goods_description'])); ?></strong>
        </td>
        <td><?php echo htmlspecialchars($consignment['goods_category'] ?: 'General Merchandise'); ?></td>
        <td style="text-align: center; font-weight: 700; font-size: 15px;"><?php echo (int)$consignment['quantity']; ?></td>
        <td><?php echo htmlspecialchars($consignment['special_instructions'] ?: 'Standard commercial transit handling.'); ?></td>
      </tr>
    </tbody>
  </table>

  <!-- Official Authorization & Terms -->
  <div style="font-size: 11.5px; color: var(--text-muted); line-height: 1.4; border-top: 1px solid var(--border-color); padding-top: 12px; margin-bottom: 24px;">
    <strong>Transportation Terms:</strong> Goods registered under this computerized waybill are accepted subject to the standard conditions of carriage of Funtua Garage, Samaru, Zaria. Goods must be inspected upon delivery. Recipient must present matching identification or verification code at terminal of arrival.
  </div>

  <!-- Signatures and Authorization Seals -->
  <div class="waybill-footer">
    <div class="signature-box">
      <div class="signature-line"></div>
      <p><strong>Consignor (Sender)</strong><br>Signature / Thumbprint</p>
    </div>

    <div class="signature-box">
      <div class="signature-line"></div>
      <p><strong>Assigned Driver</strong><br>Receipt &amp; Vehicle Loading</p>
    </div>

    <div class="signature-box">
      <div style="font-size: 12px; color: #1e3a8a; font-weight: 700; height: 40px; display: flex; flex-direction: column; justify-content: flex-end;">
        <div>SYSTEM CERTIFIED</div>
        <div style="font-size: 11px; color: #64748b;"><?php echo htmlspecialchars($consignment['registrar_name'] ?? 'Authorized Officer'); ?></div>
      </div>
      <div style="border-top: 1px solid #475569; margin-top: 4px;"></div>
      <p><strong>Funtua Garage Samaru</strong><br>Authorized Registration Officer</p>
    </div>
  </div>

</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
