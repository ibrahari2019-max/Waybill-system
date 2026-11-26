<?php
/**
 * Goods Tracking and Transportation Management System
 * Consignment Record Detail Viewer
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

require_login();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$tracking = isset($_GET['tracking']) ? trim($_GET['tracking']) : '';

$query = "
    SELECT c.*, 
           w.waybill_number, w.issue_date,
           d.full_name AS driver_name, d.phone AS driver_phone,
           v.registration_number AS vehicle_reg, v.vehicle_type,
           u.full_name AS registrar_name,
           dc.received_by, dc.remarks AS confirmation_remarks, dc.confirmed_at,
           cu.full_name AS confirmer_name
    FROM consignments c
    LEFT JOIN waybills w ON c.consignment_id = w.consignment_id
    LEFT JOIN drivers d ON c.driver_id = d.driver_id
    LEFT JOIN vehicles v ON c.vehicle_id = v.vehicle_id
    LEFT JOIN users u ON c.registered_by = u.user_id
    LEFT JOIN delivery_confirmation dc ON c.consignment_id = dc.consignment_id
    LEFT JOIN users cu ON dc.confirmed_by = cu.user_id
";

if ($id > 0) {
    $stmt = $pdo->prepare($query . " WHERE c.consignment_id = ? LIMIT 1");
    $stmt->execute([$id]);
} elseif (!empty($tracking)) {
    $stmt = $pdo->prepare($query . " WHERE c.tracking_number = ? LIMIT 1");
    $stmt->execute([$tracking]);
} else {
    flash('error', 'No consignment identifier specified.', 'Error');
    header('Location: ' . BASE_URL . 'consignments/list.php');
    exit;
}

$consignment = $stmt->fetch();

if (!$consignment) {
    flash('error', 'Consignment record not found in the database.', 'Not Found');
    header('Location: ' . BASE_URL . 'consignments/list.php');
    exit;
}

// Fetch Full Tracking History
$hist_stmt = $pdo->prepare("
    SELECT h.*, u.full_name AS updater_name
    FROM delivery_status_history h
    LEFT JOIN users u ON h.updated_by = u.user_id
    WHERE h.consignment_id = ?
    ORDER BY h.status_id ASC
");
$hist_stmt->execute([$consignment['consignment_id']]);
$history = $hist_stmt->fetchAll();

$page_title = 'Consignment Details - ' . htmlspecialchars($consignment['tracking_number']);
$current_nav = 'consignments';

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<div class="page-header">
  <div class="page-header-title">
    <h1>Consignment: <?php echo htmlspecialchars($consignment['tracking_number']); ?></h1>
    <p>Registered at Funtua Garage, Samaru, Zaria &bull; <?php echo format_datetime($consignment['created_at']); ?></p>
  </div>
  <div class="page-header-actions">
    <a href="<?php echo BASE_URL; ?>waybills/view.php?id=<?php echo $consignment['consignment_id']; ?>" class="btn btn-navy">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 6 2 18 2 18 9"></polyline><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path><rect x="6" y="14" width="12" height="8"></rect></svg>
      Digital Waybill
    </a>
    <a href="<?php echo BASE_URL; ?>delivery/update.php?tracking=<?php echo urlencode($consignment['tracking_number']); ?>" class="btn btn-primary">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="23 4 23 10 17 10"></polyline><polyline points="1 20 1 14 7 14"></polyline></svg>
      Update Status
    </a>
    <?php if ($consignment['current_status'] !== 'Delivered'): ?>
      <a href="<?php echo BASE_URL; ?>delivery/confirm.php?tracking=<?php echo urlencode($consignment['tracking_number']); ?>" class="btn btn-success">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
        Confirm Delivery
      </a>
    <?php endif; ?>
    <a href="<?php echo BASE_URL; ?>consignments/edit.php?id=<?php echo $consignment['consignment_id']; ?>" class="btn btn-secondary">
      Edit
    </a>
  </div>
</div>

<!-- Status Banner -->
<div style="background-color: #fff; border: 1px solid var(--border-color); border-radius: var(--radius-md); padding: 18px 24px; margin-bottom: 24px; display: flex; justify-content: space-between; align-items: center; flex-wrap: gap; box-shadow: var(--shadow-sm);">
  <div>
    <span style="font-size: 12px; color: var(--text-muted); font-weight: 600; text-transform: uppercase;">Current Transportation Status</span>
    <div style="margin-top: 4px; display: flex; align-items: center; gap: 10px;">
      <?php echo get_status_badge($consignment['current_status']); ?>
      <span style="font-size: 13.5px; color: var(--text-muted);">Waybill No: <strong><?php echo htmlspecialchars($consignment['waybill_number'] ?? 'N/A'); ?></strong></span>
    </div>
  </div>
  <div style="text-align: right;">
    <span style="font-size: 12px; color: var(--text-muted); font-weight: 600; text-transform: uppercase;">Destination Terminal</span>
    <div style="font-size: 18px; font-weight: 700; color: var(--primary-navy);"><?php echo htmlspecialchars($consignment['destination']); ?></div>
  </div>
</div>

<!-- Details Grid -->
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 24px; margin-bottom: 24px;">

  <!-- Sender Information -->
  <div class="card" style="margin-bottom: 0;">
    <div class="card-header">
      <h3>Sender Information</h3>
    </div>
    <div class="card-body">
      <table class="data-table" style="font-size: 13.5px;">
        <tr>
          <td style="width: 130px; font-weight: 600; color: var(--text-muted);">Full Name:</td>
          <td><strong><?php echo htmlspecialchars($consignment['sender_name']); ?></strong></td>
        </tr>
        <tr>
          <td style="font-weight: 600; color: var(--text-muted);">Phone Number:</td>
          <td><?php echo htmlspecialchars($consignment['sender_contact']); ?></td>
        </tr>
        <tr>
          <td style="font-weight: 600; color: var(--text-muted);">Address:</td>
          <td><?php echo htmlspecialchars($consignment['sender_address'] ?: 'Samaru Garage Depot, Zaria'); ?></td>
        </tr>
        <tr>
          <td style="font-weight: 600; color: var(--text-muted);">Registered By:</td>
          <td><?php echo htmlspecialchars($consignment['registrar_name'] ?? 'Administrator'); ?></td>
        </tr>
      </table>
    </div>
  </div>

  <!-- Recipient Information -->
  <div class="card" style="margin-bottom: 0;">
    <div class="card-header">
      <h3>Recipient Information</h3>
    </div>
    <div class="card-body">
      <table class="data-table" style="font-size: 13.5px;">
        <tr>
          <td style="width: 130px; font-weight: 600; color: var(--text-muted);">Full Name:</td>
          <td><strong><?php echo htmlspecialchars($consignment['recipient_name']); ?></strong></td>
        </tr>
        <tr>
          <td style="font-weight: 600; color: var(--text-muted);">Phone Number:</td>
          <td><?php echo htmlspecialchars($consignment['recipient_contact']); ?></td>
        </tr>
        <tr>
          <td style="font-weight: 600; color: var(--text-muted);">Address:</td>
          <td><?php echo htmlspecialchars($consignment['recipient_address'] ?: 'Customer Pickup'); ?></td>
        </tr>
        <tr>
          <td style="font-weight: 600; color: var(--text-muted);">Destination:</td>
          <td><strong><?php echo htmlspecialchars($consignment['destination']); ?></strong></td>
        </tr>
      </table>
    </div>
  </div>

</div>

<!-- Goods and Transportation Details -->
<div style="display: grid; grid-template-columns: 2fr 1fr; gap: 24px; margin-bottom: 24px;">

  <!-- Goods Info -->
  <div class="card" style="margin-bottom: 0;">
    <div class="card-header">
      <h3>Goods Specifications</h3>
    </div>
    <div class="card-body">
      <table class="data-table" style="font-size: 13.5px;">
        <tr>
          <td style="width: 140px; font-weight: 600; color: var(--text-muted);">Goods Description:</td>
          <td><strong><?php echo nl2br(htmlspecialchars($consignment['goods_description'])); ?></strong></td>
        </tr>
        <tr>
          <td style="font-weight: 600; color: var(--text-muted);">Quantity:</td>
          <td><span style="font-weight: 700; font-size: 16px; color: var(--primary-navy);"><?php echo (int)$consignment['quantity']; ?></span> Units / Packages</td>
        </tr>
        <tr>
          <td style="font-weight: 600; color: var(--text-muted);">Category:</td>
          <td><?php echo htmlspecialchars($consignment['goods_category'] ?: 'General Goods'); ?></td>
        </tr>
        <tr>
          <td style="font-weight: 600; color: var(--text-muted);">Special Instructions:</td>
          <td><?php echo htmlspecialchars($consignment['special_instructions'] ?: 'None specified.'); ?></td>
        </tr>
      </table>
    </div>
  </div>

  <!-- Dispatch & Assigned Vehicle/Driver -->
  <div class="card" style="margin-bottom: 0;">
    <div class="card-header">
      <h3>Haulage Particulars</h3>
    </div>
    <div class="card-body">
      <table class="data-table" style="font-size: 13.5px;">
        <tr>
          <td style="width: 110px; font-weight: 600; color: var(--text-muted);">Driver:</td>
          <td><?php echo htmlspecialchars($consignment['driver_name'] ? $consignment['driver_name'] . ' (' . $consignment['driver_phone'] . ')' : 'Unassigned'); ?></td>
        </tr>
        <tr>
          <td style="font-weight: 600; color: var(--text-muted);">Vehicle:</td>
          <td><?php echo htmlspecialchars($consignment['vehicle_reg'] ? $consignment['vehicle_reg'] . ' - ' . $consignment['vehicle_type'] : 'Unassigned'); ?></td>
        </tr>
        <tr>
          <td style="font-weight: 600; color: var(--text-muted);">Dispatch Date:</td>
          <td><?php echo format_date($consignment['dispatch_date']); ?></td>
        </tr>
      </table>
    </div>
  </div>

</div>

<!-- Delivery Confirmation Information if Available -->
<?php if (!empty($consignment['received_by'])): ?>
<div class="card" style="border-left: 4px solid var(--success-color);">
  <div class="card-header" style="background-color: #f0fdf4;">
    <h3 style="color: #166534;">Official Delivery Confirmation</h3>
    <span class="status-badge badge-delivered">Confirmed Delivered</span>
  </div>
  <div class="card-body">
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 16px;">
      <div>
        <span style="font-size: 12px; color: var(--text-muted);">Received By:</span>
        <div style="font-weight: 700; font-size: 15px;"><?php echo htmlspecialchars($consignment['received_by']); ?></div>
      </div>
      <div>
        <span style="font-size: 12px; color: var(--text-muted);">Confirmation Date &amp; Time:</span>
        <div style="font-weight: 600;"><?php echo format_datetime($consignment['confirmed_at']); ?></div>
      </div>
      <div>
        <span style="font-size: 12px; color: var(--text-muted);">Confirmed By Officer:</span>
        <div><?php echo htmlspecialchars($consignment['confirmer_name'] ?? 'Administrator'); ?></div>
      </div>
    </div>
    <?php if (!empty($consignment['confirmation_remarks'])): ?>
      <div style="margin-top: 12px; padding-top: 12px; border-top: 1px solid var(--border-color); font-size: 13.5px;">
        <span style="color: var(--text-muted); font-weight: 600;">Remarks: </span>
        <?php echo htmlspecialchars($consignment['confirmation_remarks']); ?>
      </div>
    <?php endif; ?>
  </div>
</div>
<?php endif; ?>

<!-- Chronological Tracking History Table -->
<div class="card">
  <div class="card-header">
    <div>
      <h2>Chronological Delivery Status History</h2>
      <span class="card-subtitle">Permanent audit log of status transitions</span>
    </div>
    <a href="<?php echo BASE_URL; ?>delivery/update.php?tracking=<?php echo urlencode($consignment['tracking_number']); ?>" class="btn btn-secondary btn-sm">
      Update Status
    </a>
  </div>

  <div class="table-responsive">
    <table class="data-table">
      <thead>
        <tr>
          <th>Date &amp; Time</th>
          <th>Recorded Status</th>
          <th>Location / Remarks</th>
          <th>Logged By</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($history)): ?>
          <tr>
            <td colspan="4" style="text-align: center; color: var(--text-muted); padding: 24px;">
              No status history records found.
            </td>
          </tr>
        <?php else: ?>
          <?php foreach ($history as $h): ?>
          <tr>
            <td style="white-space: nowrap; font-weight: 600;"><?php echo format_datetime($h['updated_at']); ?></td>
            <td><?php echo get_status_badge($h['status']); ?></td>
            <td><?php echo htmlspecialchars($h['remarks'] ?: 'Status recorded at depot'); ?></td>
            <td><?php echo htmlspecialchars($h['updater_name'] ?? 'System Admin'); ?></td>
          </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
