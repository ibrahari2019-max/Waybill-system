<?php
/**
 * Goods Tracking and Transportation Management System
 * Consignment Tracking Search Interface
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

$page_title = 'Track Your Goods';
$current_nav = 'track';

// Tracking search input
$tracking_input = trim($_GET['tracking'] ?? '');

if (!empty($tracking_input)) {
    header('Location: ' . BASE_URL . 'tracking/result.php?tracking=' . urlencode($tracking_input));
    exit;
}

// Can be viewed by public customers or logged-in administrators
$isLoggedIn = isset($_SESSION['user_id']);
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

  <!-- Public / Admin Top Header -->
  <header style="background-color: var(--primary-navy); color: #fff; padding: 18px 28px; display: flex; justify-content: space-between; align-items: center;">
    <div style="display: flex; align-items: center; gap: 12px;">
      <div style="width: 36px; height: 36px; background: var(--accent-blue); border-radius: 8px; display: flex; align-items: center; justify-content: center;">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
      </div>
      <div>
        <h2 style="font-size: 17px; font-weight: 700; line-height: 1.2;">FGSZ Goods Tracking System</h2>
        <span style="font-size: 11px; color: #94a3b8; text-transform: uppercase;">Funtua Garage &bull; Samaru, Zaria</span>
      </div>
    </div>
    <div>
      <?php if ($isLoggedIn): ?>
        <a href="<?php echo BASE_URL; ?>dashboard.php" class="btn btn-secondary btn-sm">&larr; Back to Dashboard</a>
      <?php else: ?>
        <a href="<?php echo BASE_URL; ?>login.php" class="btn btn-primary btn-sm">Staff Login &rarr;</a>
      <?php endif; ?>
    </div>
  </header>

  <main style="flex: 1; padding: 40px 20px; max-width: 900px; margin: 0 auto; width: 100%;">

    <!-- Search Hero -->
    <div class="tracking-hero">
      <h1>Track Your Goods in Transit</h1>
      <p>Enter your unique consignment tracking number below to check the real-time status and transportation milestone history from Funtua Garage, Samaru.</p>

      <form method="GET" action="<?php echo BASE_URL; ?>tracking/result.php" class="tracking-search-box">
        <input type="text" name="tracking" placeholder="Enter Tracking No. (e.g. FGSZ-2026-000001)" required autofocus>
        <button type="submit">
          Track Goods
        </button>
      </form>
    </div>

    <!-- Sample Tracking Numbers & Help -->
    <div class="card" style="margin-top: 30px;">
      <div class="card-header">
        <h3>How It Works: Status-Based Transportation Tracking</h3>
      </div>
      <div class="card-body">
        <p style="color: var(--text-muted); font-size: 14px; margin-bottom: 20px;">
          Every consignment registered at our Samaru garage is assigned a unique digital tracking number and waybill. As your goods move through each operational phase, authorized personnel record milestones directly into the system.
        </p>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)); gap: 14px; text-align: center;">
          <div style="background: #f8fafc; border: 1px solid var(--border-color); border-radius: 8px; padding: 14px 10px;">
            <div style="font-weight: 700; color: #475569; font-size: 13.5px;">1. Registered</div>
            <div style="font-size: 11.5px; color: var(--text-muted); margin-top: 4px;">Logged at garage depot</div>
          </div>
          <div style="background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 8px; padding: 14px 10px;">
            <div style="font-weight: 700; color: #1d4ed8; font-size: 13.5px;">2. Dispatched</div>
            <div style="font-size: 11.5px; color: var(--text-muted); margin-top: 4px;">Loaded onto transport</div>
          </div>
          <div style="background: #fffbeb; border: 1px solid #fde68a; border-radius: 8px; padding: 14px 10px;">
            <div style="font-weight: 700; color: #b45309; font-size: 13.5px;">3. In Transit</div>
            <div style="font-size: 11.5px; color: var(--text-muted); margin-top: 4px;">En route to destination</div>
          </div>
          <div style="background: #f0fdfa; border: 1px solid #99f6e4; border-radius: 8px; padding: 14px 10px;">
            <div style="font-weight: 700; color: #0f766e; font-size: 13.5px;">4. Arrived</div>
            <div style="font-size: 11.5px; color: var(--text-muted); margin-top: 4px;">At arrival terminal</div>
          </div>
          <div style="background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 8px; padding: 14px 10px;">
            <div style="font-weight: 700; color: #15803d; font-size: 13.5px;">5. Delivered</div>
            <div style="font-size: 11.5px; color: var(--text-muted); margin-top: 4px;">Received by recipient</div>
          </div>
        </div>

        <div style="margin-top: 24px; padding-top: 18px; border-top: 1px solid var(--border-color); font-size: 13px; color: var(--text-muted); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
          <div>
            <strong>Need help with your tracking number?</strong> Check your printed waybill slip or contact the Samaru Garage goods counter.
          </div>
          <div>
            <?php if (!$isLoggedIn): ?>
              <a href="<?php echo BASE_URL; ?>login.php" style="font-weight: 600;">System Administrator Portal &rarr;</a>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>

  </main>

  <footer style="background-color: #fff; border-top: 1px solid var(--border-color); text-align: center; padding: 20px; font-size: 12.5px; color: var(--text-muted);">
    &copy; <?php echo date('Y'); ?> Funtua Garage Goods Tracking &amp; Transportation Management System. Samaru, Zaria. All rights reserved.
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
