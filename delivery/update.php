<?php
/**
 * Goods Tracking and Transportation Management System
 * Delivery Status Management Module
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

require_login();

$page_title = 'Update Delivery Status';
$current_nav = 'status_update';
$currentUser = current_user();

$tracking_query = trim($_GET['tracking'] ?? '');
$consignment = null;
$history = [];

if (!empty($tracking_query)) {
    $stmt = $pdo->prepare("
        SELECT c.*, w.waybill_number,
               d.full_name AS driver_name,
               v.registration_number AS vehicle_reg
        FROM consignments c
        LEFT JOIN waybills w ON c.consignment_id = w.consignment_id
        LEFT JOIN drivers d ON c.driver_id = d.driver_id
        LEFT JOIN vehicles v ON c.vehicle_id = v.vehicle_id
        WHERE c.tracking_number = ? OR w.waybill_number = ?
        LIMIT 1
    ");
    $stmt->execute([$tracking_query, $tracking_query]);
    $consignment = $stmt->fetch();

    if ($consignment) {
        $hist_stmt = $pdo->prepare("
            SELECT h.*, u.full_name AS updater_name
            FROM delivery_status_history h
            LEFT JOIN users u ON h.updated_by = u.user_id
            WHERE h.consignment_id = ?
            ORDER BY h.status_id DESC
        ");
        $hist_stmt->execute([$consignment['consignment_id']]);
        $history = $hist_stmt->fetchAll();
    } else {
        flash('error', "No consignment found matching '{$tracking_query}'.", 'Not Found');
    }
}

// Handle Status Update Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        flash('error', 'Security token expired. Please refresh the page.', 'CSRF Error');
    } else {
        $consignment_id = (int)($_POST['consignment_id'] ?? 0);
        $new_status     = trim($_POST['new_status'] ?? '');
        $remarks        = trim($_POST['remarks'] ?? '');

        // Fetch target consignment
        $fetch_stmt = $pdo->prepare("SELECT * FROM consignments WHERE consignment_id = ?");
        $fetch_stmt->execute([$consignment_id]);
        $target = $fetch_stmt->fetch();

        if (!$target) {
            flash('error', 'Consignment record was not found.', 'Error');
        } else {
            $current_status = $target['current_status'];

            // Validate status transitions (Section 10)
            if ($current_status === 'Delivered' && $new_status !== 'Delivered') {
                flash('warning', "Consignment {$target['tracking_number']} has already been Delivered. Reverting to an earlier status is not permitted.", 'Invalid Transition');
            } elseif (!in_array($new_status, ['Registered', 'Dispatched', 'In Transit', 'Arrived', 'Delivered'])) {
                flash('error', 'Invalid status selected.', 'Error');
            } else {
                try {
                    $pdo->beginTransaction();

                    // 1. Update Consignments table
                    $up_stmt = $pdo->prepare("UPDATE consignments SET current_status = ? WHERE consignment_id = ?");
                    $up_stmt->execute([$new_status, $consignment_id]);

                    // 2. Insert into delivery_status_history without overwriting previous logs
                    $log_stmt = $pdo->prepare("
                        INSERT INTO delivery_status_history (consignment_id, status, remarks, updated_by, updated_at)
                        VALUES (?, ?, ?, ?, NOW())
                    ");
                    $default_remarks = "Status updated to {$new_status} by " . $currentUser['full_name'];
                    $final_remarks = !empty($remarks) ? $remarks : $default_remarks;

                    $log_stmt->execute([
                        $consignment_id,
                        $new_status,
                        $final_remarks,
                        $currentUser['user_id']
                    ]);

                    $pdo->commit();

                    flash('success', "Status for consignment {$target['tracking_number']} updated to '{$new_status}' successfully!", 'Status Updated');

                    // If changed to Delivered, direct them to finalize Delivery Confirmation if needed
                    if ($new_status === 'Delivered') {
                        header('Location: ' . BASE_URL . 'delivery/confirm.php?tracking=' . urlencode($target['tracking_number']));
                    } else {
                        header('Location: ' . BASE_URL . 'delivery/update.php?tracking=' . urlencode($target['tracking_number']));
                    }
                    exit;

                } catch (Exception $e) {
                    if ($pdo->inTransaction()) {
                        $pdo->rollBack();
                    }
                    flash('error', 'Failed to update status: ' . $e->getMessage(), 'Database Error');
                }
            }
        }
    }
}

// Fetch active consignments requiring status updates for quick selection
$active_consignments = $pdo->query("
    SELECT tracking_number, destination, recipient_name, current_status 
    FROM consignments 
    WHERE current_status != 'Delivered' 
    ORDER BY consignment_id DESC 
    LIMIT 6
")->fetchAll();

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<div class="page-header">
  <div class="page-header-title">
    <h1>Update Delivery Status</h1>
    <p>Manage and log goods transportation milestones across the transit pipeline.</p>
  </div>
  <div class="page-header-actions">
    <a href="<?php echo BASE_URL; ?>consignments/list.php" class="btn btn-secondary">
      All Consignments
    </a>
  </div>
</div>

<!-- Search Consignment Box -->
<div class="filter-card">
  <form method="GET" action="<?php echo BASE_URL; ?>delivery/update.php" class="filter-form">
    <div class="filter-group" style="flex: 2;">
      <label for="tracking">Search Consignment Tracking Number or Waybill Number:</label>
      <input type="text" id="tracking" name="tracking" class="form-control" placeholder="e.g. FGSZ-2026-000001" value="<?php echo htmlspecialchars($tracking_query); ?>" required autofocus>
    </div>
    <div>
      <button type="submit" class="btn btn-primary">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
        Locate Consignment
      </button>
      <?php if (!empty($tracking_query)): ?>
        <a href="<?php echo BASE_URL; ?>delivery/update.php" class="btn btn-secondary">Clear</a>
      <?php endif; ?>
    </div>
  </form>
</div>

<?php if ($consignment): ?>
  <!-- Status Update Form and Record Details -->
  <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px;">

    <!-- Left: Status Update Form -->
    <div class="card">
      <div class="card-header">
        <div>
          <h2>Change Delivery Status</h2>
          <span class="card-subtitle">Select milestone and provide transit remarks / location</span>
        </div>
      </div>
      <div class="card-body">
        <form method="POST" action="<?php echo BASE_URL; ?>delivery/update.php?tracking=<?php echo urlencode($consignment['tracking_number']); ?>">
          <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
          <input type="hidden" name="consignment_id" value="<?php echo $consignment['consignment_id']; ?>">

          <div style="background-color: #f8fafc; border: 1px solid var(--border-color); border-radius: 8px; padding: 14px 18px; margin-bottom: 20px;">
            <div style="font-size: 12px; color: var(--text-muted); font-weight: 600;">CURRENT REGISTERED STATUS</div>
            <div style="margin-top: 4px; display: flex; align-items: center; gap: 10px;">
              <?php echo get_status_badge($consignment['current_status']); ?>
              <span style="font-size: 13px; color: var(--text-muted);">
                Destination: <strong><?php echo htmlspecialchars($consignment['destination']); ?></strong>
              </span>
            </div>
          </div>

          <div class="form-group">
            <label class="form-label" for="new_status">Select New Delivery Status <span class="required">*</span></label>
            <select id="new_status" name="new_status" class="form-control" required>
              <option value="">-- Choose Target Status --</option>
              <option value="Registered" <?php echo ($consignment['current_status'] === 'Registered') ? 'selected' : ''; ?>>1. Registered (Depot Logged)</option>
              <option value="Dispatched" <?php echo ($consignment['current_status'] === 'Dispatched') ? 'selected' : ''; ?>>2. Dispatched (Loaded on Vehicle)</option>
              <option value="In Transit" <?php echo ($consignment['current_status'] === 'In Transit') ? 'selected' : ''; ?>>3. In Transit (En Route)</option>
              <option value="Arrived" <?php echo ($consignment['current_status'] === 'Arrived') ? 'selected' : ''; ?>>4. Arrived (Arrival Terminal)</option>
              <option value="Delivered" <?php echo ($consignment['current_status'] === 'Delivered') ? 'selected' : ''; ?>>5. Delivered (Received by Consignee)</option>
            </select>
            <span class="form-hint">Select the actual operational milestone reached by this consignment.</span>
          </div>

          <div class="form-group">
            <label class="form-label" for="remarks">Location / Operational Remarks</label>
            <textarea id="remarks" name="remarks" class="form-control" placeholder="e.g. Departed Samaru terminal; Passing Kano checkpoint; Arrived Funtua goods park..."></textarea>
            <span class="form-hint">Provide transit location checkpoint or verification note.</span>
          </div>

          <div style="display: flex; gap: 12px; margin-top: 24px;">
            <button type="submit" class="btn btn-primary" style="flex: 1;">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="23 4 23 10 17 10"></polyline><polyline points="1 20 1 14 7 14"></polyline></svg>
              Save Status Update
            </button>
            <a href="<?php echo BASE_URL; ?>consignments/view.php?id=<?php echo $consignment['consignment_id']; ?>" class="btn btn-secondary">
              View Consignment
            </a>
          </div>

        </form>
      </div>
    </div>

    <!-- Right: Consignment Info & Previous History Log -->
    <div>
      <div class="card" style="margin-bottom: 24px;">
        <div class="card-header">
          <h3>Consignment Summary</h3>
        </div>
        <div class="card-body">
          <table class="data-table" style="font-size: 13.5px;">
            <tr>
              <td style="font-weight: 600; color: var(--text-muted); width: 130px;">Tracking No:</td>
              <td><span class="tracking-code"><?php echo htmlspecialchars($consignment['tracking_number']); ?></span></td>
            </tr>
            <tr>
              <td style="font-weight: 600; color: var(--text-muted);">Waybill No:</td>
              <td><strong><?php echo htmlspecialchars($consignment['waybill_number'] ?? 'N/A'); ?></strong></td>
            </tr>
            <tr>
              <td style="font-weight: 600; color: var(--text-muted);">Sender:</td>
              <td><?php echo htmlspecialchars($consignment['sender_name']); ?> (<?php echo htmlspecialchars($consignment['sender_contact']); ?>)</td>
            </tr>
            <tr>
              <td style="font-weight: 600; color: var(--text-muted);">Recipient:</td>
              <td><?php echo htmlspecialchars($consignment['recipient_name']); ?> (<?php echo htmlspecialchars($consignment['recipient_contact']); ?>)</td>
            </tr>
            <tr>
              <td style="font-weight: 600; color: var(--text-muted);">Goods:</td>
              <td><?php echo htmlspecialchars($consignment['goods_description']); ?> (Qty: <?php echo (int)$consignment['quantity']; ?>)</td>
            </tr>
            <tr>
              <td style="font-weight: 600; color: var(--text-muted);">Driver / Vehicle:</td>
              <td><?php echo htmlspecialchars($consignment['driver_name'] ?: 'None assigned'); ?> / <?php echo htmlspecialchars($consignment['vehicle_reg'] ?: 'None'); ?></td>
            </tr>
          </table>
        </div>
      </div>

      <!-- Historical Updates List -->
      <div class="card">
        <div class="card-header">
          <h3>Previous Status History</h3>
        </div>
        <div class="card-body">
          <?php if (empty($history)): ?>
            <p style="font-size: 13px; color: var(--text-muted);">No history recorded yet.</p>
          <?php else: ?>
            <div class="timeline-list" style="padding-left: 20px;">
              <?php foreach ($history as $h): ?>
                <div class="timeline-item <?php echo ($h['status'] === 'Delivered') ? 'delivered' : ''; ?>" style="margin-bottom: 16px;">
                  <div class="timeline-marker" style="left: -24px;"></div>
                  <div style="font-size: 13px;">
                    <div style="display: flex; justify-content: space-between;">
                      <strong><?php echo get_status_badge($h['status']); ?></strong>
                      <span style="font-size: 11px; color: var(--text-light);"><?php echo format_datetime($h['updated_at']); ?></span>
                    </div>
                    <div style="color: var(--text-muted); font-size: 12.5px; margin-top: 4px;">
                      <?php echo htmlspecialchars($h['remarks']); ?>
                    </div>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>

  </div>

<?php else: ?>
  <!-- Active Consignments Awaiting Updates Table -->
  <div class="card">
    <div class="card-header">
      <div>
        <h2>Active Transit Consignments</h2>
        <span class="card-subtitle">Quickly pick an active shipment to update its transit status</span>
      </div>
    </div>
    <div class="table-responsive">
      <table class="data-table">
        <thead>
          <tr>
            <th>Tracking No.</th>
            <th>Recipient</th>
            <th>Destination</th>
            <th>Current Status</th>
            <th>Action</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($active_consignments)): ?>
            <tr>
              <td colspan="5" style="text-align: center; color: var(--text-muted); padding: 30px;">
                No active pending consignments. All goods are delivered or no records exist.
              </td>
            </tr>
          <?php else: ?>
            <?php foreach ($active_consignments as $ac): ?>
              <tr>
                <td><span class="tracking-code"><?php echo htmlspecialchars($ac['tracking_number']); ?></span></td>
                <td><?php echo htmlspecialchars($ac['recipient_name']); ?></td>
                <td><strong><?php echo htmlspecialchars($ac['destination']); ?></strong></td>
                <td><?php echo get_status_badge($ac['current_status']); ?></td>
                <td>
                  <a href="<?php echo BASE_URL; ?>delivery/update.php?tracking=<?php echo urlencode($ac['tracking_number']); ?>" class="btn btn-primary btn-sm">
                    Select &amp; Update
                  </a>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
