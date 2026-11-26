<?php
/**
 * Goods Tracking and Transportation Management System
 * Administrative Reporting & Statistical Analysis Module
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

require_login();

$page_title = 'Operational Reports';
$current_nav = 'reports';

// Filter inputs
$report_type = trim($_GET['report_type'] ?? 'all');
$status_filter = trim($_GET['status'] ?? '');
$destination_filter = trim($_GET['destination'] ?? '');
$start_date = trim($_GET['start_date'] ?? '');
$end_date = trim($_GET['end_date'] ?? '');

// If report_type is specific status
if (in_array($report_type, ['Registered', 'Dispatched', 'In Transit', 'Arrived', 'Delivered'])) {
    $status_filter = $report_type;
}

// Build query
$where = [];
$params = [];

if (!empty($status_filter)) {
    $where[] = "c.current_status = ?";
    $params[] = $status_filter;
}

if (!empty($destination_filter)) {
    $where[] = "c.destination LIKE ?";
    $params[] = "%{$destination_filter}%";
}

if (!empty($start_date)) {
    $where[] = "DATE(c.created_at) >= ?";
    $params[] = $start_date;
}

if (!empty($end_date)) {
    $where[] = "DATE(c.created_at) <= ?";
    $params[] = $end_date;
}

$where_sql = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

// Retrieve matching consignments
$query = "
    SELECT c.*, w.waybill_number,
           d.full_name AS driver_name,
           v.registration_number AS vehicle_reg,
           u.full_name AS registrar_name
    FROM consignments c
    LEFT JOIN waybills w ON c.consignment_id = w.consignment_id
    LEFT JOIN drivers d ON c.driver_id = d.driver_id
    LEFT JOIN vehicles v ON c.vehicle_id = v.vehicle_id
    LEFT JOIN users u ON c.registered_by = u.user_id
    {$where_sql}
    ORDER BY c.consignment_id DESC
";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$report_records = $stmt->fetchAll();

// Calculate Summary Metrics for the Report
$total_matching = count($report_records);
$total_delivered = 0;
$total_in_transit = 0;
$total_pending = 0;
$total_quantity = 0;

foreach ($report_records as $r) {
    $total_quantity += (int)$r['quantity'];
    if ($r['current_status'] === 'Delivered') {
        $total_delivered++;
    } elseif ($r['current_status'] === 'In Transit') {
        $total_in_transit++;
    } else {
        $total_pending++;
    }
}

// Distinct destinations for filter dropdown
$destinations = $pdo->query("SELECT DISTINCT destination FROM consignments ORDER BY destination ASC")->fetchAll(PDO::FETCH_COLUMN);

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<div class="page-header no-print">
  <div class="page-header-title">
    <h1>Administrative Transportation Reports</h1>
    <p>Auditing, filtering, and performance ledger of freight operations at Funtua Garage, Samaru.</p>
  </div>
  <div class="page-header-actions">
    <button type="button" class="btn btn-primary" onclick="window.print();">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <polyline points="6 9 6 2 18 2 18 9"></polyline>
        <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path>
        <rect x="6" y="14" width="12" height="8"></rect>
      </svg>
      Print Official Report
    </button>
  </div>
</div>

<!-- Report Filtering Interface -->
<div class="filter-card no-print">
  <form method="GET" action="<?php echo BASE_URL; ?>reports/index.php" class="filter-form">

    <div class="filter-group">
      <label for="status">Report Category / Status:</label>
      <select id="status" name="status" class="form-control">
        <option value="">-- All Registered Consignments --</option>
        <option value="Registered" <?php echo ($status_filter === 'Registered') ? 'selected' : ''; ?>>Registered Goods</option>
        <option value="Dispatched" <?php echo ($status_filter === 'Dispatched') ? 'selected' : ''; ?>>Dispatched Goods</option>
        <option value="In Transit" <?php echo ($status_filter === 'In Transit') ? 'selected' : ''; ?>>Goods In Transit</option>
        <option value="Arrived" <?php echo ($status_filter === 'Arrived') ? 'selected' : ''; ?>>Arrived Goods</option>
        <option value="Delivered" <?php echo ($status_filter === 'Delivered') ? 'selected' : ''; ?>>Delivered Goods</option>
      </select>
    </div>

    <div class="filter-group">
      <label for="destination">Destination Terminal:</label>
      <select id="destination" name="destination" class="form-control">
        <option value="">-- All Destinations --</option>
        <?php foreach ($destinations as $dest): ?>
          <option value="<?php echo htmlspecialchars($dest); ?>" <?php echo ($destination_filter === $dest) ? 'selected' : ''; ?>>
            <?php echo htmlspecialchars($dest); ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>

    <div class="filter-group">
      <label for="start_date">Start Date:</label>
      <input type="date" id="start_date" name="start_date" class="form-control" value="<?php echo htmlspecialchars($start_date); ?>">
    </div>

    <div class="filter-group">
      <label for="end_date">End Date:</label>
      <input type="date" id="end_date" name="end_date" class="form-control" value="<?php echo htmlspecialchars($end_date); ?>">
    </div>

    <div>
      <button type="submit" class="btn btn-primary">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
        Generate Report
      </button>
      <?php if (!empty($status_filter) || !empty($destination_filter) || !empty($start_date) || !empty($end_date)): ?>
        <a href="<?php echo BASE_URL; ?>reports/index.php" class="btn btn-secondary">Reset</a>
      <?php endif; ?>
    </div>

  </form>
</div>

<!-- Official Report Container (for Screen & Print) -->
<div class="card" style="padding: 24px;">

  <!-- Printable Report Header -->
  <div style="border-bottom: 2px solid var(--primary-navy); padding-bottom: 16px; margin-bottom: 20px;">
    <div style="display: flex; justify-content: space-between; align-items: flex-start;">
      <div>
        <h1 style="font-size: 20px; font-weight: 800; color: var(--primary-navy);">
          FUNTUA GARAGE GOODS TRACKING AND TRANSPORTATION SYSTEM
        </h1>
        <p style="font-size: 13px; color: var(--text-muted); margin-top: 2px;">
          Samaru Motor Park, Katsina Road, Samaru, Zaria &bull; Operational Audit Report
        </p>
      </div>
      <div style="text-align: right; font-size: 13px;">
        <div><strong>Generated Date:</strong> <?php echo date('M d, Y h:i A'); ?></div>
        <div><strong>Generated By:</strong> <?php echo htmlspecialchars($_SESSION['full_name'] ?? 'Administrator'); ?></div>
      </div>
    </div>
    <div style="margin-top: 10px; font-size: 13px; color: #1e3a8a; font-weight: 600;">
      Filter Parameters: 
      Status: [<?php echo $status_filter ?: 'All'; ?>] &bull; 
      Destination: [<?php echo $destination_filter ?: 'All'; ?>] &bull; 
      Date Range: [<?php echo $start_date ? $start_date : 'Beginning'; ?> &rarr; <?php echo $end_date ? $end_date : 'Present'; ?>]
    </div>
  </div>

  <!-- Summary Metric Counters (Section 13) -->
  <div class="report-summary-bar">
    <div class="report-metric-card">
      <span class="label">Total Consignments</span>
      <div class="val"><?php echo number_format($total_matching); ?></div>
    </div>
    <div class="report-metric-card">
      <span class="label">Delivered Goods</span>
      <div class="val" style="color: var(--success-color);"><?php echo number_format($total_delivered); ?></div>
    </div>
    <div class="report-metric-card">
      <span class="label">Goods in Transit</span>
      <div class="val" style="color: #ea580c;"><?php echo number_format($total_in_transit); ?></div>
    </div>
    <div class="report-metric-card">
      <span class="label">Pending Consignments</span>
      <div class="val" style="color: #7c3aed;"><?php echo number_format($total_pending); ?></div>
    </div>
    <div class="report-metric-card">
      <span class="label">Total Packages / Units</span>
      <div class="val" style="color: #2563eb;"><?php echo number_format($total_quantity); ?></div>
    </div>
  </div>

  <!-- Report Data Table -->
  <div class="table-responsive">
    <table class="data-table">
      <thead>
        <tr>
          <th>S/N</th>
          <th>Tracking No.</th>
          <th>Waybill No.</th>
          <th>Sender</th>
          <th>Recipient</th>
          <th>Destination</th>
          <th>Description</th>
          <th style="text-align: center;">Qty</th>
          <th>Reg. Date</th>
          <th>Driver / Vehicle</th>
          <th>Status</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($report_records)): ?>
          <tr>
            <td colspan="11" style="text-align: center; color: var(--text-muted); padding: 30px;">
              No records found matching the specified report criteria.
            </td>
          </tr>
        <?php else: ?>
          <?php 
          $sn = 1;
          foreach ($report_records as $rec): 
          ?>
            <tr>
              <td><?php echo $sn++; ?></td>
              <td><span class="tracking-code"><?php echo htmlspecialchars($rec['tracking_number']); ?></span></td>
              <td><strong><?php echo htmlspecialchars($rec['waybill_number'] ?? 'N/A'); ?></strong></td>
              <td><?php echo htmlspecialchars($rec['sender_name']); ?></td>
              <td><?php echo htmlspecialchars($rec['recipient_name']); ?></td>
              <td><strong><?php echo htmlspecialchars($rec['destination']); ?></strong></td>
              <td><?php echo htmlspecialchars(mb_strimwidth($rec['goods_description'], 0, 25, '...')); ?></td>
              <td style="text-align: center; font-weight: 700;"><?php echo (int)$rec['quantity']; ?></td>
              <td><?php echo format_date($rec['created_at']); ?></td>
              <td>
                <small><?php echo htmlspecialchars($rec['driver_name'] ? $rec['driver_name'] : 'Unassigned'); ?><br>
                <?php echo htmlspecialchars($rec['vehicle_reg'] ? '[' . $rec['vehicle_reg'] . ']' : ''); ?></small>
              </td>
              <td><?php echo get_status_badge($rec['current_status']); ?></td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

  <!-- Printable Report Signature Stamp Area -->
  <div style="margin-top: 40px; display: flex; justify-content: space-between; border-top: 1px dashed #cbd5e1; padding-top: 20px;">
    <div style="font-size: 12px; color: var(--text-muted);">
      Official transportation ledger generated automatically from MySQL Database.<br>
      Funtua Garage &bull; Samaru, Zaria Goods Transportation Management System.
    </div>
    <div style="text-align: center; width: 220px;">
      <div style="border-bottom: 1px solid #000; height: 35px; margin-bottom: 4px;"></div>
      <div style="font-size: 12px; font-weight: 700;">Chief Transportation Officer</div>
      <div style="font-size: 11px; color: var(--text-muted);">Funtua Garage, Samaru</div>
    </div>
  </div>

</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
