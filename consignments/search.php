<?php
/**
 * Goods Tracking and Transportation Management System
 * Consignment Advanced Search & Lookup
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

require_login();

// Redirect to list.php if search query provided
if (!empty($_GET['search']) || !empty($_GET['status'])) {
    header('Location: ' . BASE_URL . 'consignments/list.php?' . http_build_query($_GET));
    exit;
}

$page_title = 'Search Consignments';
$current_nav = 'consignments';

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<div class="page-header">
  <div class="page-header-title">
    <h1>Search Consignment Records</h1>
    <p>Search consignments by tracking number, waybill number, sender or receiver name.</p>
  </div>
</div>

<div class="card" style="max-width: 600px; margin: 0 auto;">
  <div class="card-header">
    <h2>Search Consignments Database</h2>
  </div>
  <div class="card-body">
    <form method="GET" action="<?php echo BASE_URL; ?>consignments/list.php">
      <div class="form-group">
        <label class="form-label" for="search">Keywords / Identifiers</label>
        <input type="text" id="search" name="search" class="form-control" placeholder="Enter Tracking No, Sender Name, Recipient..." required autofocus>
      </div>

      <div class="form-group">
        <label class="form-label" for="status">Filter by Status (Optional)</label>
        <select id="status" name="status" class="form-control">
          <option value="">All Statuses</option>
          <option value="Registered">Registered</option>
          <option value="Dispatched">Dispatched</option>
          <option value="In Transit">In Transit</option>
          <option value="Arrived">Arrived</option>
          <option value="Delivered">Delivered</option>
        </select>
      </div>

      <div style="margin-top: 20px;">
        <button type="submit" class="btn btn-primary" style="width: 100%;">
          Search Database
        </button>
      </div>
    </form>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
