<?php
/**
 * Goods Tracking and Transportation Management System
 * Driver Management Module
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

require_login();

$page_title = 'Drivers Management';
$current_nav = 'drivers';

$edit_id = isset($_GET['edit']) ? (int)$_GET['edit'] : 0;
$driver_to_edit = null;

if ($edit_id > 0) {
    $st = $pdo->prepare("SELECT * FROM drivers WHERE driver_id = ?");
    $st->execute([$edit_id]);
    $driver_to_edit = $st->fetch();
}

// Handle Add / Edit Form Submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        flash('error', 'Security token error. Please refresh.', 'CSRF Error');
    } else {
        $action = $_POST['action'] ?? '';
        $full_name = trim($_POST['full_name'] ?? '');
        $phone     = trim($_POST['phone'] ?? '');
        $address   = trim($_POST['address'] ?? '');

        if (empty($full_name) || empty($phone)) {
            flash('warning', 'Driver full name and phone number are required.', 'Missing Fields');
        } else {
            try {
                if ($action === 'add') {
                    $ins = $pdo->prepare("INSERT INTO drivers (full_name, phone, address, created_at) VALUES (?, ?, ?, NOW())");
                    $ins->execute([$full_name, $phone, $address ?: null]);
                    flash('success', "Driver '{$full_name}' added successfully to Funtua Garage roster.", 'Driver Added');
                    header('Location: ' . BASE_URL . 'drivers/index.php');
                    exit;
                } elseif ($action === 'edit' && $edit_id > 0) {
                    $up = $pdo->prepare("UPDATE drivers SET full_name = ?, phone = ?, address = ? WHERE driver_id = ?");
                    $up->execute([$full_name, $phone, $address ?: null, $edit_id]);
                    flash('success', "Driver '{$full_name}' updated successfully.", 'Driver Updated');
                    header('Location: ' . BASE_URL . 'drivers/index.php');
                    exit;
                }
            } catch (PDOException $e) {
                flash('error', 'Failed to save driver record: ' . $e->getMessage(), 'Database Error');
            }
        }
    }
}

// Handle Delete Request
if (isset($_GET['delete'])) {
    $del_id = (int)$_GET['delete'];
    try {
        $del_stmt = $pdo->prepare("DELETE FROM drivers WHERE driver_id = ?");
        $del_stmt->execute([$del_id]);
        flash('success', 'Driver record was deleted successfully.', 'Deleted');
        header('Location: ' . BASE_URL . 'drivers/index.php');
        exit;
    } catch (PDOException $e) {
        flash('error', 'Cannot delete driver because they are linked to consignments.', 'Constraint Error');
    }
}

// Fetch all drivers with count of assigned consignments
$drivers = $pdo->query("
    SELECT d.*, COUNT(c.consignment_id) AS total_trips
    FROM drivers d
    LEFT JOIN consignments c ON d.driver_id = c.driver_id
    GROUP BY d.driver_id
    ORDER BY d.driver_id DESC
")->fetchAll();

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<div class="page-header">
  <div class="page-header-title">
    <h1>Drivers Management</h1>
    <p>Manage transport drivers and commercial freight operators at Funtua Garage, Samaru.</p>
  </div>
  <div class="page-header-actions">
    <a href="<?php echo BASE_URL; ?>vehicles/index.php" class="btn btn-secondary">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="1" y="3" width="15" height="13"></rect><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"></polygon><circle cx="5.5" cy="18.5" r="2.5"></circle><circle cx="18.5" cy="18.5" r="2.5"></circle></svg>
      Manage Vehicles &rarr;
    </a>
  </div>
</div>

<div style="display: grid; grid-template-columns: 1fr 2fr; gap: 24px;">

  <!-- Add / Edit Driver Form -->
  <div class="card">
    <div class="card-header">
      <h2><?php echo $driver_to_edit ? 'Edit Driver Record' : 'Register New Driver'; ?></h2>
    </div>
    <div class="card-body">
      <form method="POST" action="<?php echo BASE_URL; ?>drivers/index.php<?php echo $driver_to_edit ? '?edit=' . $driver_to_edit['driver_id'] : ''; ?>">
        <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
        <input type="hidden" name="action" value="<?php echo $driver_to_edit ? 'edit' : 'add'; ?>">

        <div class="form-group">
          <label class="form-label" for="full_name">Driver Full Name <span class="required">*</span></label>
          <input type="text" id="full_name" name="full_name" class="form-control" placeholder="e.g. Malam Ibrahim Danladi" value="<?php echo htmlspecialchars($driver_to_edit['full_name'] ?? ''); ?>" required autofocus>
        </div>

        <div class="form-group">
          <label class="form-label" for="phone">Phone Number <span class="required">*</span></label>
          <input type="tel" id="phone" name="phone" class="form-control" placeholder="e.g. 08031234567" value="<?php echo htmlspecialchars($driver_to_edit['phone'] ?? ''); ?>" required>
        </div>

        <div class="form-group">
          <label class="form-label" for="address">Station / Residential Address</label>
          <textarea id="address" name="address" class="form-control" placeholder="e.g. Funtua Motor Park, Katsina Road, Funtua"><?php echo htmlspecialchars($driver_to_edit['address'] ?? ''); ?></textarea>
        </div>

        <div style="display: flex; gap: 10px; margin-top: 20px;">
          <button type="submit" class="btn btn-primary" style="flex: 1;">
            <?php echo $driver_to_edit ? 'Update Driver' : 'Save Driver Record'; ?>
          </button>
          <?php if ($driver_to_edit): ?>
            <a href="<?php echo BASE_URL; ?>drivers/index.php" class="btn btn-secondary">Cancel</a>
          <?php endif; ?>
        </div>

      </form>
    </div>
  </div>

  <!-- Drivers Table -->
  <div class="card">
    <div class="card-header">
      <div>
        <h2>Active Garage Drivers Roster</h2>
        <span class="card-subtitle"><?php echo count($drivers); ?> registered driver(s)</span>
      </div>
    </div>
    <div class="table-responsive">
      <table class="data-table">
        <thead>
          <tr>
            <th>ID</th>
            <th>Driver Name</th>
            <th>Phone</th>
            <th>Address</th>
            <th>Trips</th>
            <th>Created</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($drivers)): ?>
            <tr>
              <td colspan="7" style="text-align: center; color: var(--text-muted); padding: 30px;">
                No drivers registered yet.
              </td>
            </tr>
          <?php else: ?>
            <?php foreach ($drivers as $d): ?>
              <tr>
                <td>#<?php echo $d['driver_id']; ?></td>
                <td><strong><?php echo htmlspecialchars($d['full_name']); ?></strong></td>
                <td><?php echo htmlspecialchars($d['phone']); ?></td>
                <td><?php echo htmlspecialchars($d['address'] ?: 'Samaru Terminal'); ?></td>
                <td><span class="status-badge badge-registered"><?php echo (int)$d['total_trips']; ?> trips</span></td>
                <td><?php echo format_date($d['created_at']); ?></td>
                <td>
                  <div class="table-actions">
                    <a href="<?php echo BASE_URL; ?>drivers/index.php?edit=<?php echo $d['driver_id']; ?>" class="btn btn-secondary btn-sm">Edit</a>
                    <a href="<?php echo BASE_URL; ?>drivers/index.php?delete=<?php echo $d['driver_id']; ?>" class="btn btn-danger btn-sm" data-confirm="Are you sure you want to delete driver '<?php echo htmlspecialchars($d['full_name']); ?>'?">Delete</a>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
