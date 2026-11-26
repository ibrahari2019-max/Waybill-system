<?php
/**
 * Goods Tracking and Transportation Management System
 * All Consignments List & Search & Filter
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

require_login();

$page_title = 'All Registered Consignments';
$current_nav = 'consignments';

// Filters and Search parameters
$search = trim($_GET['search'] ?? '');
$status_filter = trim($_GET['status'] ?? '');
$page = max(1, (int)($_GET['page'] ?? 1));
$per_page = 10;
$offset = ($page - 1) * $per_page;

$where_clauses = [];
$params = [];

if (!empty($search)) {
    $where_clauses[] = "(c.tracking_number LIKE ? OR c.sender_name LIKE ? OR c.recipient_name LIKE ? OR c.destination LIKE ? OR w.waybill_number LIKE ?)";
    $term = "%{$search}%";
    $params[] = $term;
    $params[] = $term;
    $params[] = $term;
    $params[] = $term;
    $params[] = $term;
}

if (!empty($status_filter)) {
    $where_clauses[] = "c.current_status = ?";
    $params[] = $status_filter;
}

$where_sql = !empty($where_clauses) ? 'WHERE ' . implode(' AND ', $where_clauses) : '';

// Count total matching records for pagination
$count_query = "
    SELECT COUNT(*) 
    FROM consignments c
    LEFT JOIN waybills w ON c.consignment_id = w.consignment_id
    {$where_sql}
";
$count_stmt = $pdo->prepare($count_query);
$count_stmt->execute($params);
$total_rows = (int)$count_stmt->fetchColumn();
$total_pages = ceil($total_rows / $per_page);

// Fetch page records
$data_query = "
    SELECT c.*, w.waybill_number,
           d.full_name AS driver_name,
           v.registration_number AS vehicle_reg
    FROM consignments c
    LEFT JOIN waybills w ON c.consignment_id = w.consignment_id
    LEFT JOIN drivers d ON c.driver_id = d.driver_id
    LEFT JOIN vehicles v ON c.vehicle_id = v.vehicle_id
    {$where_sql}
    ORDER BY c.consignment_id DESC
    LIMIT {$per_page} OFFSET {$offset}
";
$stmt = $pdo->prepare($data_query);
$stmt->execute($params);
$consignments = $stmt->fetchAll();

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<div class="page-header">
  <div class="page-header-title">
    <h1>All Registered Consignments</h1>
    <p>Comprehensive register of freight items, statuses, and waybills in Funtua Garage, Samaru.</p>
  </div>
  <div class="page-header-actions">
    <a href="<?php echo BASE_URL; ?>consignments/register.php" class="btn btn-primary">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M5 12h14"></path></svg>
      Register Goods
    </a>
  </div>
</div>

<!-- Search and Filtering Form -->
<div class="filter-card">
  <form method="GET" action="<?php echo BASE_URL; ?>consignments/list.php" class="filter-form">
    <div class="filter-group" style="flex: 2;">
      <label for="search">Search Keywords:</label>
      <input type="text" id="search" name="search" class="form-control" placeholder="Search by Tracking No, Sender, Receiver, Destination..." value="<?php echo htmlspecialchars($search); ?>">
    </div>

    <div class="filter-group">
      <label for="status">Filter by Status:</label>
      <select id="status" name="status" class="form-control">
        <option value="">-- All Statuses --</option>
        <option value="Registered" <?php echo ($status_filter === 'Registered') ? 'selected' : ''; ?>>Registered</option>
        <option value="Dispatched" <?php echo ($status_filter === 'Dispatched') ? 'selected' : ''; ?>>Dispatched</option>
        <option value="In Transit" <?php echo ($status_filter === 'In Transit') ? 'selected' : ''; ?>>In Transit</option>
        <option value="Arrived" <?php echo ($status_filter === 'Arrived') ? 'selected' : ''; ?>>Arrived</option>
        <option value="Delivered" <?php echo ($status_filter === 'Delivered') ? 'selected' : ''; ?>>Delivered</option>
      </select>
    </div>

    <div>
      <button type="submit" class="btn btn-primary">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
        Filter Records
      </button>
      <?php if (!empty($search) || !empty($status_filter)): ?>
        <a href="<?php echo BASE_URL; ?>consignments/list.php" class="btn btn-secondary">Reset</a>
      <?php endif; ?>
    </div>
  </form>
</div>

<!-- Consignments Table -->
<div class="card">
  <div class="card-header">
    <div>
      <h2>Consignments Ledger</h2>
      <span class="card-subtitle">Showing <?php echo count($consignments); ?> of <?php echo $total_rows; ?> total recorded consignments</span>
    </div>
  </div>

  <div class="table-responsive">
    <table class="data-table">
      <thead>
        <tr>
          <th>S/N</th>
          <th>Tracking No.</th>
          <th>Sender</th>
          <th>Receiver</th>
          <th>Destination</th>
          <th>Goods Description</th>
          <th style="text-align: center;">Qty</th>
          <th>Reg. Date</th>
          <th>Current Status</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($consignments)): ?>
          <tr>
            <td colspan="10" style="text-align: center; color: var(--text-muted); padding: 35px;">
              No consignment records match your search or filter criteria.
            </td>
          </tr>
        <?php else: ?>
          <?php 
          $serial = $offset + 1;
          foreach ($consignments as $row): 
          ?>
          <tr>
            <td><?php echo $serial++; ?></td>
            <td>
              <a href="<?php echo BASE_URL; ?>consignments/view.php?id=<?php echo $row['consignment_id']; ?>" class="tracking-code">
                <?php echo htmlspecialchars($row['tracking_number']); ?>
              </a>
            </td>
            <td>
              <strong><?php echo htmlspecialchars($row['sender_name']); ?></strong><br>
              <small style="color: var(--text-muted);"><?php echo htmlspecialchars($row['sender_contact']); ?></small>
            </td>
            <td>
              <strong><?php echo htmlspecialchars($row['recipient_name']); ?></strong><br>
              <small style="color: var(--text-muted);"><?php echo htmlspecialchars($row['recipient_contact']); ?></small>
            </td>
            <td><strong><?php echo htmlspecialchars($row['destination']); ?></strong></td>
            <td>
              <?php echo htmlspecialchars(mb_strimwidth($row['goods_description'], 0, 30, '...')); ?>
            </td>
            <td style="text-align: center; font-weight: 600;"><?php echo (int)$row['quantity']; ?></td>
            <td><?php echo format_date($row['created_at']); ?></td>
            <td><?php echo get_status_badge($row['current_status']); ?></td>
            <td>
              <div class="table-actions">
                <a href="<?php echo BASE_URL; ?>consignments/view.php?id=<?php echo $row['consignment_id']; ?>" class="btn btn-secondary btn-sm" title="View Consignment Details">
                  View
                </a>
                <a href="<?php echo BASE_URL; ?>consignments/edit.php?id=<?php echo $row['consignment_id']; ?>" class="btn btn-secondary btn-sm" title="Edit Consignment">
                  Edit
                </a>
                <a href="<?php echo BASE_URL; ?>delivery/update.php?tracking=<?php echo urlencode($row['tracking_number']); ?>" class="btn btn-secondary btn-sm" title="Update Status">
                  Status
                </a>
                <a href="<?php echo BASE_URL; ?>waybills/view.php?id=<?php echo $row['consignment_id']; ?>" class="btn btn-navy btn-sm" title="Digital Waybill">
                  Waybill
                </a>
                <a href="<?php echo BASE_URL; ?>consignments/delete.php?id=<?php echo $row['consignment_id']; ?>" class="btn btn-danger btn-sm" title="Delete Consignment" data-confirm="Are you sure you want to delete consignment <?php echo htmlspecialchars($row['tracking_number']); ?>? This will permanently delete its waybill and history!">
                  Delete
                </a>
              </div>
            </td>
          </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

  <!-- Pagination Controls -->
  <?php if ($total_pages > 1): ?>
  <div class="pagination-wrapper">
    <div style="font-size: 13.5px; color: var(--text-muted);">
      Page <strong><?php echo $page; ?></strong> of <strong><?php echo $total_pages; ?></strong>
    </div>
    <div class="pagination-links">
      <?php if ($page > 1): ?>
        <a href="<?php echo BASE_URL; ?>consignments/list.php?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status_filter); ?>" class="page-link">&laquo; Prev</a>
      <?php endif; ?>

      <?php for ($p = 1; $p <= $total_pages; $p++): ?>
        <a href="<?php echo BASE_URL; ?>consignments/list.php?page=<?php echo $p; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status_filter); ?>" class="page-link <?php echo ($p === $page) ? 'active' : ''; ?>">
          <?php echo $p; ?>
        </a>
      <?php endfor; ?>

      <?php if ($page < $total_pages): ?>
        <a href="<?php echo BASE_URL; ?>consignments/list.php?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status_filter); ?>" class="page-link">Next &raquo;</a>
      <?php endif; ?>
    </div>
  </div>
  <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
