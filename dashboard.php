<?php
/**
 * Goods Tracking and Transportation Management System
 * Administrator Dashboard Module
 */

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';

require_login();

$page_title = 'Administrative Dashboard';
$current_nav = 'dashboard';

// Fetch Live Statistics from MySQL Database
try {
    // Total Registered Consignments
    $total_stmt = $pdo->query("SELECT COUNT(*) FROM consignments");
    $total_consignments = (int)$total_stmt->fetchColumn();

    // Registered Goods
    $reg_stmt = $pdo->prepare("SELECT COUNT(*) FROM consignments WHERE current_status = 'Registered'");
    $reg_stmt->execute();
    $status_registered = (int)$reg_stmt->fetchColumn();

    // Dispatched Goods
    $disp_stmt = $pdo->prepare("SELECT COUNT(*) FROM consignments WHERE current_status = 'Dispatched'");
    $disp_stmt->execute();
    $status_dispatched = (int)$disp_stmt->fetchColumn();

    // Goods in Transit
    $transit_stmt = $pdo->prepare("SELECT COUNT(*) FROM consignments WHERE current_status = 'In Transit'");
    $transit_stmt->execute();
    $status_in_transit = (int)$transit_stmt->fetchColumn();

    // Arrived Goods
    $arrived_stmt = $pdo->prepare("SELECT COUNT(*) FROM consignments WHERE current_status = 'Arrived'");
    $arrived_stmt->execute();
    $status_arrived = (int)$arrived_stmt->fetchColumn();

    // Delivered Goods
    $deliv_stmt = $pdo->prepare("SELECT COUNT(*) FROM consignments WHERE current_status = 'Delivered'");
    $deliv_stmt->execute();
    $status_delivered = (int)$deliv_stmt->fetchColumn();

    // Total Drivers and Vehicles count
    $drivers_count = (int)$pdo->query("SELECT COUNT(*) FROM drivers")->fetchColumn();
    $vehicles_count = (int)$pdo->query("SELECT COUNT(*) FROM vehicles")->fetchColumn();

    // Recent Consignments (Latest 8)
    $recent_stmt = $pdo->query("
        SELECT c.*, w.waybill_number 
        FROM consignments c
        LEFT JOIN waybills w ON c.consignment_id = w.consignment_id
        ORDER BY c.consignment_id DESC
        LIMIT 8
    ");
    $recent_consignments = $recent_stmt->fetchAll();

    // Recently Updated Statuses (Latest 5 status transitions)
    $updates_stmt = $pdo->query("
        SELECT h.*, c.tracking_number, c.destination, u.full_name AS updater_name
        FROM delivery_status_history h
        JOIN consignments c ON h.consignment_id = c.consignment_id
        LEFT JOIN users u ON h.updated_by = u.user_id
        ORDER BY h.status_id DESC
        LIMIT 5
    ");
    $recent_updates = $updates_stmt->fetchAll();

} catch (PDOException $e) {
    flash('error', 'Database error loading dashboard data: ' . $e->getMessage(), 'Data Error');
    $total_consignments = $status_registered = $status_dispatched = $status_in_transit = $status_arrived = $status_delivered = 0;
    $recent_consignments = [];
    $recent_updates = [];
}

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';
?>

<div class="page-header">
  <div class="page-header-title">
    <h1>Transportation Management Dashboard</h1>
    <p>Operational overview of goods registration, transit, and delivery status at Funtua Garage, Samaru.</p>
  </div>
  <div class="page-header-actions">
    <a href="<?php echo BASE_URL; ?>consignments/register.php" class="btn btn-primary">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <path d="M12 5v14M5 12h14"></path>
      </svg>
      Register New Goods
    </a>
    <a href="<?php echo BASE_URL; ?>reports/index.php" class="btn btn-secondary">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <polyline points="6 9 6 2 18 2 18 9"></polyline>
        <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path>
        <rect x="6" y="14" width="12" height="8"></rect>
      </svg>
      Generate Report
    </a>
  </div>
</div>

<!-- Quick Action Shortcuts -->
<div class="quick-actions-bar">
  <a href="<?php echo BASE_URL; ?>consignments/register.php" class="btn btn-navy btn-sm">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M5 12h14"></path></svg>
    Register Goods
  </a>
  <a href="<?php echo BASE_URL; ?>tracking/index.php" class="btn btn-secondary btn-sm">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
    Track Goods
  </a>
  <a href="<?php echo BASE_URL; ?>delivery/update.php" class="btn btn-secondary btn-sm">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="23 4 23 10 17 10"></polyline><polyline points="1 20 1 14 7 14"></polyline></svg>
    Update Status
  </a>
  <a href="<?php echo BASE_URL; ?>delivery/confirm.php" class="btn btn-secondary btn-sm">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
    Confirm Delivery
  </a>
  <a href="<?php echo BASE_URL; ?>reports/index.php" class="btn btn-secondary btn-sm">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="20" x2="18" y2="10"></line><line x1="12" y1="20" x2="12" y2="4"></line><line x1="6" y1="20" x2="6" y2="14"></line></svg>
    System Reports
  </a>
</div>

<!-- Statistical Summary Cards (6 Exact Categories Required) -->
<div class="stats-grid">
  <!-- 1. Total Registered Consignments -->
  <div class="stat-card">
    <div class="stat-icon blue">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path>
        <polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline>
        <line x1="12" y1="22.08" x2="12" y2="12"></line>
      </svg>
    </div>
    <div class="stat-details">
      <span class="stat-label">Total Consignments</span>
      <div class="stat-value"><?php echo number_format($total_consignments); ?></div>
    </div>
  </div>

  <!-- 2. Registered Goods -->
  <div class="stat-card">
    <div class="stat-icon purple">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
        <polyline points="14 2 14 8 20 8"></polyline>
      </svg>
    </div>
    <div class="stat-details">
      <span class="stat-label">Registered Goods</span>
      <div class="stat-value"><?php echo number_format($status_registered); ?></div>
    </div>
  </div>

  <!-- 3. Dispatched Goods -->
  <div class="stat-card">
    <div class="stat-icon yellow">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <rect x="1" y="3" width="15" height="13"></rect>
        <polygon points="16 8 20 8 23 11 23 16 16 16 16 8"></polygon>
        <circle cx="5.5" cy="18.5" r="2.5"></circle>
        <circle cx="18.5" cy="18.5" r="2.5"></circle>
      </svg>
    </div>
    <div class="stat-details">
      <span class="stat-label">Dispatched Goods</span>
      <div class="stat-value"><?php echo number_format($status_dispatched); ?></div>
    </div>
  </div>

  <!-- 4. Goods in Transit -->
  <div class="stat-card">
    <div class="stat-icon orange">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <circle cx="12" cy="12" r="10"></circle>
        <polyline points="12 6 12 12 16 14"></polyline>
      </svg>
    </div>
    <div class="stat-details">
      <span class="stat-label">Goods in Transit</span>
      <div class="stat-value"><?php echo number_format($status_in_transit); ?></div>
    </div>
  </div>

  <!-- 5. Arrived Goods -->
  <div class="stat-card">
    <div class="stat-icon teal">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
        <polyline points="9 22 9 12 15 12 15 22"></polyline>
      </svg>
    </div>
    <div class="stat-details">
      <span class="stat-label">Arrived Goods</span>
      <div class="stat-value"><?php echo number_format($status_arrived); ?></div>
    </div>
  </div>

  <!-- 6. Delivered Goods -->
  <div class="stat-card">
    <div class="stat-icon green">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
        <polyline points="22 4 12 14.01 9 11.01"></polyline>
      </svg>
    </div>
    <div class="stat-details">
      <span class="stat-label">Delivered Goods</span>
      <div class="stat-value"><?php echo number_format($status_delivered); ?></div>
    </div>
  </div>
</div>

<!-- Main Section: Recent Consignments Table & Status Updates Feed -->
<div style="display: grid; grid-template-columns: 2fr 1fr; gap: 24px;">

  <!-- Recent Consignments Table -->
  <div class="card">
    <div class="card-header">
      <div>
        <h2>Recent Consignments</h2>
        <span class="card-subtitle">Latest goods registered at Samaru garage</span>
      </div>
      <a href="<?php echo BASE_URL; ?>consignments/list.php" class="btn btn-secondary btn-sm">View All Consignments &rarr;</a>
    </div>

    <div class="table-responsive">
      <table class="data-table">
        <thead>
          <tr>
            <th>Tracking No.</th>
            <th>Sender</th>
            <th>Receiver</th>
            <th>Destination</th>
            <th>Registered Date</th>
            <th>Status</th>
            <th>Action</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($recent_consignments)): ?>
          <tr>
            <td colspan="7" style="text-align: center; color: var(--text-muted); padding: 30px;">
              No registered consignments found in the database.
            </td>
          </tr>
          <?php else: ?>
            <?php foreach ($recent_consignments as $row): ?>
            <tr>
              <td>
                <a href="<?php echo BASE_URL; ?>consignments/view.php?id=<?php echo $row['consignment_id']; ?>" class="tracking-code">
                  <?php echo htmlspecialchars($row['tracking_number']); ?>
                </a>
              </td>
              <td><strong><?php echo htmlspecialchars($row['sender_name']); ?></strong></td>
              <td><?php echo htmlspecialchars($row['recipient_name']); ?></td>
              <td><?php echo htmlspecialchars($row['destination']); ?></td>
              <td><?php echo format_date($row['created_at']); ?></td>
              <td><?php echo get_status_badge($row['current_status']); ?></td>
              <td>
                <div class="table-actions">
                  <a href="<?php echo BASE_URL; ?>consignments/view.php?id=<?php echo $row['consignment_id']; ?>" class="btn btn-secondary btn-sm" title="View Consignment Details">
                    View
                  </a>
                  <a href="<?php echo BASE_URL; ?>waybills/view.php?id=<?php echo $row['consignment_id']; ?>" class="btn btn-navy btn-sm" title="View Waybill">
                    Waybill
                  </a>
                </div>
              </td>
            </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Recently Updated Consignments Feed -->
  <div class="card">
    <div class="card-header">
      <div>
        <h3>Recently Updated</h3>
        <span class="card-subtitle">Live transit activity trail</span>
      </div>
      <a href="<?php echo BASE_URL; ?>delivery/update.php" class="btn btn-secondary btn-sm">Update</a>
    </div>

    <div class="card-body">
      <?php if (empty($recent_updates)): ?>
        <p style="color: var(--text-muted); font-size: 13.5px;">No status activity recorded yet.</p>
      <?php else: ?>
        <div class="timeline-list" style="padding-left: 20px;">
          <?php foreach ($recent_updates as $act): ?>
          <div class="timeline-item <?php echo ($act['status'] === 'Delivered') ? 'delivered' : ''; ?>" style="margin-bottom: 18px;">
            <div class="timeline-marker" style="left: -24px;"></div>
            <div style="font-size: 13px;">
              <div style="display: flex; justify-content: space-between; align-items: baseline;">
                <span class="tracking-code" style="font-size: 11.5px;"><?php echo htmlspecialchars($act['tracking_number']); ?></span>
                <span style="font-size: 11px; color: var(--text-light);"><?php echo format_datetime($act['updated_at']); ?></span>
              </div>
              <div style="margin-top: 4px;">
                <strong>Status changed to: </strong> <?php echo get_status_badge($act['status']); ?>
              </div>
              <?php if (!empty($act['remarks'])): ?>
              <div style="color: var(--text-muted); font-size: 12px; margin-top: 4px;">
                <?php echo htmlspecialchars($act['remarks']); ?>
              </div>
              <?php endif; ?>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
  </div>

</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
