<?php
/**
 * Goods Tracking and Transportation Management System
 * Waybill Search & Generation Selector
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

require_login();

$page_title = 'Generate Waybill';
$current_nav = 'waybill';

$search_query = trim($_GET['search'] ?? '');
$consignments = [];

if (!empty($search_query)) {
    $stmt = $pdo->prepare("
        SELECT c.*, w.waybill_number, w.issue_date
        FROM consignments c
        LEFT JOIN waybills w ON c.consignment_id = w.consignment_id
        WHERE c.tracking_number LIKE ? 
           OR w.waybill_number LIKE ? 
           OR c.sender_name LIKE ? 
           OR c.recipient_name LIKE ?
        ORDER BY c.consignment_id DESC
    ");
    $term = "%{$search_query}%";
    $stmt->execute([$term, $term, $term, $term]);
    $consignments = $stmt->fetchAll();

    if (empty($consignments)) {
        flash('info', 'No matching consignments found for the entered query.', 'Search Result');
    }
} else {
    // Show latest 12 consignments ready for waybill printing
    $stmt = $pdo->query("
        SELECT c.*, w.waybill_number, w.issue_date
        FROM consignments c
        LEFT JOIN waybills w ON c.consignment_id = w.consignment_id
        ORDER BY c.consignment_id DESC
        LIMIT 12
    ");
    $consignments = $stmt->fetchAll();
}

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<div class="page-header">
  <div class="page-header-title">
    <h1>Generate &amp; Print Waybills</h1>
    <p>Select any registered consignment to view and print its official transportation waybill voucher.</p>
  </div>
  <div class="page-header-actions">
    <a href="<?php echo BASE_URL; ?>consignments/register.php" class="btn btn-primary">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M5 12h14"></path></svg>
      Register New Goods
    </a>
  </div>
</div>

<!-- Search Box -->
<div class="filter-card">
  <form method="GET" action="<?php echo BASE_URL; ?>waybills/generate.php" class="filter-form">
    <div class="filter-group" style="flex: 2;">
      <label for="search">Search by Tracking Number, Waybill Number, Sender or Recipient:</label>
      <input type="text" id="search" name="search" class="form-control" placeholder="e.g. FGSZ-2026-000001 or WB-2026-000001" value="<?php echo htmlspecialchars($search_query); ?>">
    </div>
    <div>
      <button type="submit" class="btn btn-primary">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
        Search Waybill
      </button>
      <?php if (!empty($search_query)): ?>
        <a href="<?php echo BASE_URL; ?>waybills/generate.php" class="btn btn-secondary">Clear</a>
      <?php endif; ?>
    </div>
  </form>
</div>

<!-- Consignments Ready for Waybill Table -->
<div class="card">
  <div class="card-header">
    <div>
      <h2>Registered Consignments for Waybill Generation</h2>
      <span class="card-subtitle"><?php echo !empty($search_query) ? 'Filtered results' : 'Recent registered transportation items'; ?></span>
    </div>
  </div>

  <div class="table-responsive">
    <table class="data-table">
      <thead>
        <tr>
          <th>Waybill No.</th>
          <th>Tracking No.</th>
          <th>Sender</th>
          <th>Recipient</th>
          <th>Destination</th>
          <th>Goods Summary</th>
          <th>Status</th>
          <th>Action</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($consignments)): ?>
          <tr>
            <td colspan="8" style="text-align: center; color: var(--text-muted); padding: 30px;">
              No consignment records found.
            </td>
          </tr>
        <?php else: ?>
          <?php foreach ($consignments as $c): ?>
            <tr>
              <td>
                <strong style="color: var(--primary-navy);"><?php echo htmlspecialchars($c['waybill_number'] ?? 'WB-' . $c['consignment_id']); ?></strong>
              </td>
              <td>
                <span class="tracking-code"><?php echo htmlspecialchars($c['tracking_number']); ?></span>
              </td>
              <td><?php echo htmlspecialchars($c['sender_name']); ?></td>
              <td><?php echo htmlspecialchars($c['recipient_name']); ?></td>
              <td><strong><?php echo htmlspecialchars($c['destination']); ?></strong></td>
              <td><?php echo htmlspecialchars(mb_strimwidth($c['goods_description'], 0, 35, '...')); ?> (Qty: <?php echo (int)$c['quantity']; ?>)</td>
              <td><?php echo get_status_badge($c['current_status']); ?></td>
              <td>
                <a href="<?php echo BASE_URL; ?>waybills/view.php?id=<?php echo $c['consignment_id']; ?>" class="btn btn-navy btn-sm">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 6 2 18 2 18 9"></polyline><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path><rect x="6" y="14" width="12" height="8"></rect></svg>
                  Print / View Waybill
                </a>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
