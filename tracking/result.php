<?php
/**
 * Goods Tracking and Transportation Management System
 * Consignment Tracking Results & Visual Progress Tracker
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

$tracking_number = trim($_GET['tracking'] ?? '');
$consignment = null;
$history = [];

if (!empty($tracking_number)) {
    $stmt = $pdo->prepare("
        SELECT c.*, w.waybill_number, w.issue_date,
               d.full_name AS driver_name,
               v.registration_number AS vehicle_reg,
               dc.received_by, dc.confirmed_at
        FROM consignments c
        LEFT JOIN waybills w ON c.consignment_id = w.consignment_id
        LEFT JOIN drivers d ON c.driver_id = d.driver_id
        LEFT JOIN vehicles v ON c.vehicle_id = v.vehicle_id
        LEFT JOIN delivery_confirmation dc ON c.consignment_id = dc.consignment_id
        WHERE c.tracking_number = ? OR w.waybill_number = ?
        LIMIT 1
    ");
    $stmt->execute([$tracking_number, $tracking_number]);
    $consignment = $stmt->fetch();

    if ($consignment) {
        // Fetch chronological tracking history
        $hist_stmt = $pdo->prepare("
            SELECT * FROM delivery_status_history
            WHERE consignment_id = ?
            ORDER BY status_id ASC
        ");
        $hist_stmt->execute([$consignment['consignment_id']]);
        $history = $hist_stmt->fetchAll();

        flash('success', "Consignment {$consignment['tracking_number']} found! Current status: {$consignment['current_status']}", 'Tracking Found');
    } else {
        flash('error', "No consignment found with tracking number '{$tracking_number}'. Please check the code.", 'Not Found');
    }
}

$page_title = $consignment ? 'Tracking ' . htmlspecialchars($consignment['tracking_number']) : 'Track Your Goods';
$isLoggedIn = isset($_SESSION['user_id']);

// Define exact milestone steps for the visual progress stepper
$milestones = [
    'Registered' => ['step' => 1, 'label' => 'Registered', 'desc' => 'Logged at Samaru depot'],
    'Dispatched' => ['step' => 2, 'label' => 'Dispatched', 'desc' => 'Loaded & departed'],
    'In Transit' => ['step' => 3, 'label' => 'In Transit', 'desc' => 'On transit route'],
    'Arrived'    => ['step' => 4, 'label' => 'Arrived', 'desc' => 'At arrival terminal'],
    'Delivered'  => ['step' => 5, 'label' => 'Delivered', 'desc' => 'Delivery complete']
];

$currentStepNum = 1;
if ($consignment) {
    $currentStepNum = $milestones[$consignment['current_status']]['step'] ?? 1;
}

// Map dates from history
$milestoneDates = [];
foreach ($history as $h) {
    $milestoneDates[$h['status']] = $h['updated_at'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo $page_title; ?> - <?php echo APP_NAME; ?></title>

  <!-- iziToast CSS -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/izitoast/1.4.0/css/iziToast.min.css" onerror="this.onerror=null;this.href='<?php echo BASE_URL; ?>assets/css/iziToast.min.css';">
  <!-- Main CSS -->
  <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/style.css">
</head>
<body style="background-color: var(--bg-page);">

<div style="min-height: 100vh; display: flex; flex-direction: column;">

  <!-- Header Bar -->
  <header style="background-color: var(--primary-navy); color: #fff; padding: 18px 28px; display: flex; justify-content: space-between; align-items: center;">
    <div style="display: flex; align-items: center; gap: 12px;">
      <a href="<?php echo BASE_URL; ?>tracking/index.php" style="width: 36px; height: 36px; background: var(--accent-blue); border-radius: 8px; display: flex; align-items: center; justify-content: center; color: #fff;">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
      </a>
      <div>
        <h2 style="font-size: 17px; font-weight: 700; line-height: 1.2;">FGSZ Goods Tracking System</h2>
        <span style="font-size: 11px; color: #94a3b8; text-transform: uppercase;">Funtua Garage &bull; Samaru, Zaria</span>
      </div>
    </div>
    <div>
      <?php if ($isLoggedIn): ?>
        <a href="<?php echo BASE_URL; ?>dashboard.php" class="btn btn-secondary btn-sm">&larr; Dashboard</a>
      <?php else: ?>
        <a href="<?php echo BASE_URL; ?>login.php" class="btn btn-primary btn-sm">Staff Login &rarr;</a>
      <?php endif; ?>
    </div>
  </header>

  <main style="flex: 1; padding: 36px 20px; max-width: 960px; margin: 0 auto; width: 100%;">

    <!-- Search Form -->
    <div style="background-color: #fff; border: 1px solid var(--border-color); border-radius: var(--radius-md); padding: 20px 24px; margin-bottom: 24px; box-shadow: var(--shadow-sm);">
      <form method="GET" action="<?php echo BASE_URL; ?>tracking/result.php" style="display: flex; gap: 12px; align-items: center; flex-wrap: wrap;">
        <div style="flex: 1; min-width: 260px;">
          <input type="text" name="tracking" class="form-control" placeholder="Enter Tracking Number (e.g. FGSZ-2026-000001)" value="<?php echo htmlspecialchars($tracking_number); ?>" required>
        </div>
        <button type="submit" class="btn btn-primary">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
          Track Consignment
        </button>
      </form>
    </div>

    <?php if ($consignment): ?>
      <!-- Consignment Banner -->
      <div class="card">
        <div class="card-header" style="background: linear-gradient(135deg, #0f172a, #1e3a8a); color: #fff;">
          <div>
            <span style="font-size: 11px; text-transform: uppercase; letter-spacing: 0.8px; color: #94a3b8; font-weight: 700;">CONSIGNMENT TRACKING NUMBER</span>
            <h1 style="font-size: 22px; font-weight: 800; font-family: monospace; letter-spacing: 0.5px; color: #fff; margin-top: 2px;">
              <?php echo htmlspecialchars($consignment['tracking_number']); ?>
            </h1>
            <div style="font-size: 13px; color: #cbd5e1; margin-top: 4px;">
              Waybill No: <strong><?php echo htmlspecialchars($consignment['waybill_number'] ?? 'N/A'); ?></strong> &bull; Destination: <strong><?php echo htmlspecialchars($consignment['destination']); ?></strong>
            </div>
          </div>
          <div style="text-align: right;">
            <div style="font-size: 12px; color: #94a3b8; margin-bottom: 4px;">CURRENT STATUS</div>
            <?php echo get_status_badge($consignment['current_status']); ?>
          </div>
        </div>

        <div class="card-body">

          <!-- Section: Clean Visual Tracking Progress Stepper -->
          <div class="stepper-container">
            <div class="stepper-progress">
              <?php 
              $percentFill = (($currentStepNum - 1) / 4) * 100;
              ?>
              <div class="stepper-bar-fill" style="width: calc(<?php echo $percentFill; ?>% - 30px);"></div>

              <?php foreach ($milestones as $key => $m): 
                $stepClass = '';
                if ($m['step'] < $currentStepNum) {
                    $stepClass = 'completed';
                } elseif ($m['step'] == $currentStepNum) {
                    $stepClass = 'active';
                }
              ?>
                <div class="stepper-step <?php echo $stepClass; ?>">
                  <div class="step-circle">
                    <?php if ($m['step'] < $currentStepNum): ?>
                      <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"></polyline></svg>
                    <?php else: ?>
                      <?php echo $m['step']; ?>
                    <?php endif; ?>
                  </div>
                  <div class="step-label"><?php echo $m['label']; ?></div>
                  <div class="step-timestamp">
                    <?php 
                    if (isset($milestoneDates[$key])) {
                        echo date('M d, h:i A', strtotime($milestoneDates[$key]));
                    } else {
                        echo $m['desc'];
                    }
                    ?>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
          </div>

          <!-- Consignment Specifications Table -->
          <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 20px; margin-top: 10px; margin-bottom: 24px;">
            <div style="background-color: #f8fafc; border: 1px solid var(--border-color); border-radius: 8px; padding: 16px;">
              <div style="font-size: 12px; color: var(--text-muted); font-weight: 700; text-transform: uppercase;">Sender &bull; Origin</div>
              <div style="font-size: 16px; font-weight: 700; color: var(--primary-navy); margin-top: 4px;">
                <?php echo htmlspecialchars($consignment['sender_name']); ?>
              </div>
              <div style="font-size: 13px; color: var(--text-muted); margin-top: 2px;">
                Origin Depot: Samaru Garage, Zaria
              </div>
            </div>

            <div style="background-color: #f8fafc; border: 1px solid var(--border-color); border-radius: 8px; padding: 16px;">
              <div style="font-size: 12px; color: var(--text-muted); font-weight: 700; text-transform: uppercase;">Recipient &bull; Destination</div>
              <div style="font-size: 16px; font-weight: 700; color: var(--primary-navy); margin-top: 4px;">
                <?php echo htmlspecialchars($consignment['recipient_name']); ?>
              </div>
              <div style="font-size: 13px; color: #1e3a8a; font-weight: 600; margin-top: 2px;">
                Destination: <?php echo htmlspecialchars($consignment['destination']); ?>
              </div>
            </div>
          </div>

          <!-- Goods Details -->
          <div style="background-color: #fff; border: 1px solid var(--border-color); border-radius: 8px; padding: 18px; margin-bottom: 24px;">
            <div style="display: flex; justify-content: space-between; align-items: baseline; border-bottom: 1px solid var(--border-color); padding-bottom: 10px; margin-bottom: 12px;">
              <h3 style="font-size: 14.5px; font-weight: 700; color: var(--primary-navy);">Goods Description</h3>
              <span style="font-size: 13px; font-weight: 700; color: var(--accent-blue);">Quantity: <?php echo (int)$consignment['quantity']; ?> Package(s)</span>
            </div>
            <p style="font-size: 14.5px; color: var(--text-main); margin-bottom: 8px;">
              <?php echo nl2br(htmlspecialchars($consignment['goods_description'])); ?>
            </p>
            <div style="font-size: 12.5px; color: var(--text-muted);">
              <strong>Category:</strong> <?php echo htmlspecialchars($consignment['goods_category'] ?: 'General Goods'); ?> &bull; 
              <strong>Registration Date:</strong> <?php echo format_datetime($consignment['created_at']); ?>
            </div>
          </div>

          <!-- Chronological Tracking Milestone History -->
          <h3 style="font-size: 16px; font-weight: 700; color: var(--primary-navy); margin-bottom: 16px;">
            Transportation Timeline History (Chronological Order)
          </h3>
          <div class="timeline-list">
            <?php foreach ($history as $item): ?>
              <div class="timeline-item <?php echo ($item['status'] === 'Delivered') ? 'delivered' : ''; ?>">
                <div class="timeline-marker"></div>
                <div class="timeline-content">
                  <div class="timeline-header">
                    <span class="timeline-title"><?php echo get_status_badge($item['status']); ?></span>
                    <span class="timeline-time"><?php echo format_datetime($item['updated_at']); ?></span>
                  </div>
                  <div class="timeline-remarks">
                    <?php echo htmlspecialchars($item['remarks'] ?: 'Status updated at transport corridor.'); ?>
                  </div>
                </div>
              </div>
            <?php endforeach; ?>
          </div>

        </div>

        <div class="card-footer">
          <span style="font-size: 13px; color: var(--text-muted);">
            Status-based tracking provided by Funtua Garage Management System, Samaru, Zaria.
          </span>
          <div>
            <?php if ($isLoggedIn): ?>
              <a href="<?php echo BASE_URL; ?>consignments/view.php?id=<?php echo $consignment['consignment_id']; ?>" class="btn btn-secondary btn-sm">Admin View</a>
              <a href="<?php echo BASE_URL; ?>waybills/view.php?id=<?php echo $consignment['consignment_id']; ?>" class="btn btn-navy btn-sm">Waybill</a>
            <?php endif; ?>
          </div>
        </div>

      </div>

    <?php elseif (!empty($tracking_number)): ?>
      <!-- Not Found State -->
      <div class="card" style="text-align: center; padding: 40px 20px;">
        <div style="width: 64px; height: 64px; background-color: #fee2e2; color: #dc2626; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 16px;">
          <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="15" y1="9" x2="9" y2="15"></line><line x1="9" y1="9" x2="15" y2="15"></line></svg>
        </div>
        <h2 style="font-size: 20px; font-weight: 700; color: var(--primary-navy); margin-bottom: 8px;">Consignment Not Found</h2>
        <p style="color: var(--text-muted); max-width: 500px; margin: 0 auto 20px;">
          We could not locate any consignment matching <code><?php echo htmlspecialchars($tracking_number); ?></code> in the system database. Please verify your tracking number and try again.
        </p>
        <a href="<?php echo BASE_URL; ?>tracking/index.php" class="btn btn-primary">Try Another Tracking Number</a>
      </div>
    <?php endif; ?>

  </main>

  <footer style="background-color: #fff; border-top: 1px solid var(--border-color); text-align: center; padding: 20px; font-size: 12.5px; color: var(--text-muted);">
    &copy; <?php echo date('Y'); ?> Funtua Garage Goods Tracking &amp; Transportation Management System.
  </footer>

</div>

<!-- iziToast JS -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/izitoast/1.4.0/js/iziToast.min.js"></script>
<script>
  if (typeof iziToast === 'undefined') {
    document.write('<script src="<?php echo BASE_URL; ?>assets/js/iziToast.min.js"><\/script>');
  }
</script>
<script>
  <?php
  $flashes = get_flashes();
  foreach ($flashes as $flash):
    $type = in_array($flash['type'], ['success', 'error', 'warning', 'info']) ? $flash['type'] : 'info';
    $title = json_encode($flash['title']);
    $message = json_encode($flash['message']);
  ?>
  iziToast.<?php echo $type; ?>({
    title: <?php echo $title; ?>,
    message: <?php echo $message; ?>
  });
  <?php endforeach; ?>
</script>
</body>
</html>
