<?php
/**
 * Goods Tracking and Transportation Management System
 * Delivery Confirmation Module
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

require_login();

$page_title = 'Delivery Confirmation';
$current_nav = 'confirm';
$currentUser = current_user();

$tracking_query = trim($_GET['tracking'] ?? '');
$consignment = null;
$existing_confirmation = null;

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
        // Check for existing delivery confirmation
        $conf_stmt = $pdo->prepare("
            SELECT dc.*, u.full_name AS confirmer_name
            FROM delivery_confirmation dc
            LEFT JOIN users u ON dc.confirmed_by = u.user_id
            WHERE dc.consignment_id = ?
            LIMIT 1
        ");
        $conf_stmt->execute([$consignment['consignment_id']]);
        $existing_confirmation = $conf_stmt->fetch();
    } else {
        flash('error', "Consignment '{$tracking_query}' not found.", 'Not Found');
    }
}

// Handle Delivery Confirmation Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        flash('error', 'Security token expired. Please refresh the page.', 'CSRF Error');
    } else {
        $consignment_id = (int)($_POST['consignment_id'] ?? 0);
        $received_by    = trim($_POST['received_by'] ?? '');
        $remarks        = trim($_POST['remarks'] ?? '');
        $confirmed_at   = !empty($_POST['confirmed_at']) ? $_POST['confirmed_at'] : date('Y-m-d H:i:s');

        // Check if consignment exists
        $c_stmt = $pdo->prepare("SELECT * FROM consignments WHERE consignment_id = ?");
        $c_stmt->execute([$consignment_id]);
        $target = $c_stmt->fetch();

        if (!$target) {
            flash('error', 'Consignment record was not found.', 'Error');
        } elseif (empty($received_by)) {
            flash('warning', "Please specify the recipient's name who collected the goods.", 'Missing Information');
        } else {
            // Check for duplicate delivery confirmation
            $dup_stmt = $pdo->prepare("SELECT * FROM delivery_confirmation WHERE consignment_id = ?");
            $dup_stmt->execute([$consignment_id]);
            $is_already_confirmed = $dup_stmt->fetch();

            if ($is_already_confirmed) {
                flash('warning', "Consignment {$target['tracking_number']} already has an official delivery confirmation on record. Duplicate submission prevented.", 'Already Confirmed');
            } else {
                try {
                    $pdo->beginTransaction();

                    // 1. Save Confirmation record
                    $ins_conf = $pdo->prepare("
                        INSERT INTO delivery_confirmation (consignment_id, received_by, remarks, confirmed_by, confirmed_at)
                        VALUES (?, ?, ?, ?, ?)
                    ");
                    $ins_conf->execute([
                        $consignment_id,
                        $received_by,
                        $remarks ?: 'Consignment received in good condition.',
                        $currentUser['user_id'],
                        $confirmed_at
                    ]);

                    // 2. Update consignment status to Delivered
                    $up_c = $pdo->prepare("UPDATE consignments SET current_status = 'Delivered' WHERE consignment_id = ?");
                    $up_c->execute([$consignment_id]);

                    // 3. Log to delivery_status_history
                    $ins_hist = $pdo->prepare("
                        INSERT INTO delivery_status_history (consignment_id, status, remarks, updated_by, updated_at)
                        VALUES (?, 'Delivered', ?, ?, ?)
                    ");
                    $hist_remarks = "Official delivery confirmation: Received by {$received_by}. " . ($remarks ?: 'All items inspected and accepted intact.');
                    $ins_hist->execute([
                        $consignment_id,
                        $hist_remarks,
                        $currentUser['user_id'],
                        $confirmed_at
                    ]);

                    $pdo->commit();

                    flash('success', "Delivery of consignment {$target['tracking_number']} to '{$received_by}' confirmed successfully!", 'Delivery Confirmed');
                    header('Location: ' . BASE_URL . 'delivery/confirm.php?tracking=' . urlencode($target['tracking_number']));
                    exit;

                } catch (Exception $e) {
                    if ($pdo->inTransaction()) {
                        $pdo->rollBack();
                    }
                    flash('error', 'Failed to confirm delivery: ' . $e->getMessage(), 'Database Error');
                }
            }
        }
    }
}

// Fetch consignments that have arrived or are awaiting confirmation
$arrived_consignments = $pdo->query("
    SELECT tracking_number, destination, recipient_name, current_status 
    FROM consignments 
    WHERE current_status IN ('Arrived', 'In Transit', 'Dispatched')
    ORDER BY consignment_id DESC 
    LIMIT 6
")->fetchAll();

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<div class="page-header">
  <div class="page-header-title">
    <h1>Consignment Delivery Confirmation</h1>
    <p>Officially authenticate and log the collection and receipt of transported goods.</p>
  </div>
  <div class="page-header-actions">
    <a href="<?php echo BASE_URL; ?>consignments/list.php" class="btn btn-secondary">
      All Consignments
    </a>
  </div>
</div>

<!-- Search Consignment Box -->
<div class="filter-card">
  <form method="GET" action="<?php echo BASE_URL; ?>delivery/confirm.php" class="filter-form">
    <div class="filter-group" style="flex: 2;">
      <label for="tracking">Search Consignment Tracking Number or Waybill Number:</label>
      <input type="text" id="tracking" name="tracking" class="form-control" placeholder="e.g. FGSZ-2026-000001" value="<?php echo htmlspecialchars($tracking_query); ?>" required autofocus>
    </div>
    <div>
      <button type="submit" class="btn btn-primary">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
        Lookup Shipment
      </button>
      <?php if (!empty($tracking_query)): ?>
        <a href="<?php echo BASE_URL; ?>delivery/confirm.php" class="btn btn-secondary">Clear</a>
      <?php endif; ?>
    </div>
  </form>
</div>

<?php if ($consignment): ?>
  <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px;">

    <!-- Left: Confirmation Form / Confirmed Card -->
    <div>
      <?php if ($existing_confirmation): ?>
        <!-- Already Confirmed Notification Card -->
        <div class="card" style="border: 2px solid var(--success-color);">
          <div class="card-header" style="background-color: #f0fdf4;">
            <div>
              <h2 style="color: #166534;">Delivery Already Confirmed</h2>
              <span class="card-subtitle">Official delivery receipt has already been established</span>
            </div>
            <span class="status-badge badge-delivered">Delivered</span>
          </div>
          <div class="card-body">
            <p style="font-size: 14px; margin-bottom: 16px;">
              This consignment has been officially recorded as delivered to the recipient. To maintain ledger integrity, duplicate confirmations are locked.
            </p>

            <table class="data-table" style="font-size: 13.5px;">
              <tr>
                <td style="font-weight: 600; width: 140px; color: var(--text-muted);">Received By:</td>
                <td><strong><?php echo htmlspecialchars($existing_confirmation['received_by']); ?></strong></td>
              </tr>
              <tr>
                <td style="font-weight: 600; color: var(--text-muted);">Confirmed At:</td>
                <td><?php echo format_datetime($existing_confirmation['confirmed_at']); ?></td>
              </tr>
              <tr>
                <td style="font-weight: 600; color: var(--text-muted);">Confirmed By Officer:</td>
                <td><?php echo htmlspecialchars($existing_confirmation['confirmer_name'] ?? 'Authorized Officer'); ?></td>
              </tr>
              <tr>
                <td style="font-weight: 600; color: var(--text-muted);">Remarks:</td>
                <td><?php echo htmlspecialchars($existing_confirmation['remarks'] ?: 'All goods verified intact.'); ?></td>
              </tr>
            </table>

            <div style="margin-top: 20px;">
              <a href="<?php echo BASE_URL; ?>waybills/view.php?id=<?php echo $consignment['consignment_id']; ?>" class="btn btn-navy btn-sm">
                View Waybill Voucher
              </a>
              <a href="<?php echo BASE_URL; ?>consignments/view.php?id=<?php echo $consignment['consignment_id']; ?>" class="btn btn-secondary btn-sm">
                Consignment Details
              </a>
            </div>
          </div>
        </div>

      <?php else: ?>
        <!-- Confirmation Entry Form -->
        <div class="card">
          <div class="card-header">
            <div>
              <h2>Record Delivery Confirmation</h2>
              <span class="card-subtitle">Verify recipient identity and conclude transit</span>
            </div>
          </div>
          <div class="card-body">
            <form method="POST" action="<?php echo BASE_URL; ?>delivery/confirm.php?tracking=<?php echo urlencode($consignment['tracking_number']); ?>">
              <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
              <input type="hidden" name="consignment_id" value="<?php echo $consignment['consignment_id']; ?>">

              <div class="form-group">
                <label class="form-label" for="received_by">Recipient Full Name <span class="required">*</span></label>
                <input type="text" id="received_by" name="received_by" class="form-control" placeholder="Name of person receiving the goods" value="<?php echo htmlspecialchars($consignment['recipient_name']); ?>" required>
                <span class="form-hint">Must match recipient identity or an authorized representative.</span>
              </div>

              <div class="form-group">
                <label class="form-label" for="confirmed_at">Confirmation Date &amp; Time <span class="required">*</span></label>
                <input type="datetime-local" id="confirmed_at" name="confirmed_at" class="form-control" value="<?php echo date('Y-m-d\TH:i'); ?>" required>
              </div>

              <div class="form-group">
                <label class="form-label" for="remarks">Confirmation Remarks / Condition of Goods</label>
                <textarea id="remarks" name="remarks" class="form-control" placeholder="e.g. All 10 bags verified intact; Goods inspected and accepted without damages..."></textarea>
                <span class="form-hint">Note package condition, quantity verification, or remarks.</span>
              </div>

              <div style="margin-top: 24px;">
                <button type="submit" class="btn btn-success btn-lg" style="width: 100%;" data-confirm="Are you sure you want to officially confirm delivery for consignment <?php echo htmlspecialchars($consignment['tracking_number']); ?> to <?php echo htmlspecialchars($consignment['recipient_name']); ?>? This will finalize this shipment.">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
                  Confirm Delivery &amp; Close Consignment
                </button>
              </div>

            </form>
          </div>
        </div>
      <?php endif; ?>
    </div>

    <!-- Right: Consignment Info Summary -->
    <div>
      <div class="card">
        <div class="card-header">
          <h3>Consignment Particulars</h3>
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
              <td style="font-weight: 600; color: var(--text-muted);">Current Status:</td>
              <td><?php echo get_status_badge($consignment['current_status']); ?></td>
            </tr>
            <tr>
              <td style="font-weight: 600; color: var(--text-muted);">Expected Recipient:</td>
              <td><strong><?php echo htmlspecialchars($consignment['recipient_name']); ?></strong> (<?php echo htmlspecialchars($consignment['recipient_contact']); ?>)</td>
            </tr>
            <tr>
              <td style="font-weight: 600; color: var(--text-muted);">Sender:</td>
              <td><?php echo htmlspecialchars($consignment['sender_name']); ?> (<?php echo htmlspecialchars($consignment['sender_contact']); ?>)</td>
            </tr>
            <tr>
              <td style="font-weight: 600; color: var(--text-muted);">Destination:</td>
              <td><strong><?php echo htmlspecialchars($consignment['destination']); ?></strong></td>
            </tr>
            <tr>
              <td style="font-weight: 600; color: var(--text-muted);">Goods Description:</td>
              <td><?php echo htmlspecialchars($consignment['goods_description']); ?></td>
            </tr>
            <tr>
              <td style="font-weight: 600; color: var(--text-muted);">Item Quantity:</td>
              <td><strong><?php echo (int)$consignment['quantity']; ?></strong> Package(s)</td>
            </tr>
            <tr>
              <td style="font-weight: 600; color: var(--text-muted);">Driver / Vehicle:</td>
              <td><?php echo htmlspecialchars($consignment['driver_name'] ?: 'None'); ?> / <?php echo htmlspecialchars($consignment['vehicle_reg'] ?: 'None'); ?></td>
            </tr>
          </table>
        </div>
      </div>
    </div>

  </div>

<?php else: ?>
  <!-- Shipments Waiting For Delivery Confirmation -->
  <div class="card">
    <div class="card-header">
      <div>
        <h2>Shipments Awaiting Delivery Confirmation</h2>
        <span class="card-subtitle">Consignments currently in transit or arrived at destination terminals</span>
      </div>
    </div>
    <div class="table-responsive">
      <table class="data-table">
        <thead>
          <tr>
            <th>Tracking No.</th>
            <th>Recipient Name</th>
            <th>Contact</th>
            <th>Destination</th>
            <th>Status</th>
            <th>Action</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($arrived_consignments)): ?>
            <tr>
              <td colspan="6" style="text-align: center; color: var(--text-muted); padding: 30px;">
                No pending shipments currently requiring delivery confirmation.
              </td>
            </tr>
          <?php else: ?>
            <?php foreach ($arrived_consignments as $ac): ?>
              <tr>
                <td><span class="tracking-code"><?php echo htmlspecialchars($ac['tracking_number']); ?></span></td>
                <td><strong><?php echo htmlspecialchars($ac['recipient_name']); ?></strong></td>
                <td><?php echo htmlspecialchars($ac['destination']); ?></td>
                <td><strong><?php echo htmlspecialchars($ac['destination']); ?></strong></td>
                <td><?php echo get_status_badge($ac['current_status']); ?></td>
                <td>
                  <a href="<?php echo BASE_URL; ?>delivery/confirm.php?tracking=<?php echo urlencode($ac['tracking_number']); ?>" class="btn btn-success btn-sm">
                    Confirm Receipt
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
