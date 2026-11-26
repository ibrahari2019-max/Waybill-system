<?php
/**
 * Goods Tracking and Transportation Management System
 * Vehicle Fleet Management Module
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

require_login();

$page_title = 'Vehicles Management';
$current_nav = 'vehicles';

$edit_id = isset($_GET['edit']) ? (int)$_GET['edit'] : 0;
$vehicle_to_edit = null;

if ($edit_id > 0) {
    $st = $pdo->prepare("SELECT * FROM vehicles WHERE vehicle_id = ?");
    $st->execute([$edit_id]);
    $vehicle_to_edit = $st->fetch();
}

// Handle Add / Edit Submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        flash('error', 'Security token error. Please refresh.', 'CSRF Error');
    } else {
        $action = $_POST['action'] ?? '';
        $reg_num      = strtoupper(trim($_POST['registration_number'] ?? ''));
        $vehicle_type = trim($_POST['vehicle_type'] ?? '');
        $description  = trim($_POST['description'] ?? '');

        if (empty($reg_num) || empty($vehicle_type)) {
            flash('warning', 'Vehicle registration number and type are required.', 'Missing Fields');
        } else {
            try {
                if ($action === 'add') {
                    // Check duplicate reg number
                    $chk = $pdo->prepare("SELECT COUNT(*) FROM vehicles WHERE registration_number = ?");
                    $chk->execute([$reg_num]);
                    if ($chk->fetchColumn() > 0) {
                        flash('warning', "A vehicle with registration number '{$reg_num}' is already registered.", 'Duplicate');
                    } else {
                        $ins = $pdo->prepare("INSERT INTO vehicles (registration_number, vehicle_type, description, created_at) VALUES (?, ?, ?, NOW())");
                        $ins->execute([$reg_num, $vehicle_type, $description ?: null]);
                        flash('success', "Vehicle '{$reg_num}' registered successfully.", 'Vehicle Added');
                        header('Location: ' . BASE_URL . 'vehicles/index.php');
                        exit;
                    }
                } elseif ($action === 'edit' && $edit_id > 0) {
                    $up = $pdo->prepare("UPDATE vehicles SET registration_number = ?, vehicle_type = ?, description = ? WHERE vehicle_id = ?");
                    $up->execute([$reg_num, $vehicle_type, $description ?: null, $edit_id]);
                    flash('success', "Vehicle '{$reg_num}' updated successfully.", 'Vehicle Updated');
                    header('Location: ' . BASE_URL . 'vehicles/index.php');
                    exit;
                }
            } catch (PDOException $e) {
                flash('error', 'Failed to save vehicle: ' . $e->getMessage(), 'Database Error');
            }
        }
    }
}

// Handle Delete Request
if (isset($_GET['delete'])) {
    $del_id = (int)$_GET['delete'];
    try {
        $del_stmt = $pdo->prepare("DELETE FROM vehicles WHERE vehicle_id = ?");
        $del_stmt->execute([$del_id]);
        flash('success', 'Vehicle record was deleted successfully.', 'Deleted');
        header('Location: ' . BASE_URL . 'vehicles/index.php');
        exit;
    } catch (PDOException $e) {
        flash('error', 'Cannot delete vehicle because it is linked to consignments.', 'Constraint Error');
    }
}

// Fetch all vehicles with consignment usage count
$vehicles = $pdo->query("
    SELECT v.*, COUNT(c.consignment_id) AS total_hauls
    FROM vehicles v
    LEFT JOIN consignments c ON v.vehicle_id = c.vehicle_id
    GROUP BY v.vehicle_id
    ORDER BY v.vehicle_id DESC
")->fetchAll();

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<div class="page-header">
  <div class="page-header-title">
    <h1>Vehicles &amp; Haulage Fleet</h1>
    <p>Manage transport vehicles, registration numbers, and cargo capacities at Funtua Garage.</p>
  </div>
  <div class="page-header-actions">
    <a href="<?php echo BASE_URL; ?>drivers/index.php" class="btn btn-secondary">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle></svg>
      Manage Drivers &rarr;
    </a>
  </div>
</div>

<div style="display: grid; grid-template-columns: 1fr 2fr; gap: 24px;">

  <!-- Add / Edit Vehicle Form -->
  <div class="card">
    <div class="card-header">
      <h2><?php echo $vehicle_to_edit ? 'Edit Vehicle Record' : 'Register Vehicle'; ?></h2>
    </div>
    <div class="card-body">
      <form method="POST" action="<?php echo BASE_URL; ?>vehicles/index.php<?php echo $vehicle_to_edit ? '?edit=' . $vehicle_to_edit['vehicle_id'] : ''; ?>">
        <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
        <input type="hidden" name="action" value="<?php echo $vehicle_to_edit ? 'edit' : 'add'; ?>">

        <div class="form-group">
          <label class="form-label" for="registration_number">Registration Number <span class="required">*</span></label>
          <input type="text" id="registration_number" name="registration_number" class="form-control" placeholder="e.g. KTR-428-XA or ZAR-719-AA" value="<?php echo htmlspecialchars($vehicle_to_edit['registration_number'] ?? ''); ?>" required autofocus style="text-transform: uppercase;">
        </div>

        <div class="form-group">
          <label class="form-label" for="vehicle_type">Vehicle Type / Model <span class="required">*</span></label>
          <input type="text" id="vehicle_type" name="vehicle_type" class="form-control" placeholder="e.g. Canter Truck (3-Tonne), Toyota Dyna, Ford Transit" value="<?php echo htmlspecialchars($vehicle_to_edit['vehicle_type'] ?? ''); ?>" required>
        </div>

        <div class="form-group">
          <label class="form-label" for="description">Vehicle Description / Cargo Capacity</label>
          <textarea id="description" name="description" class="form-control" placeholder="e.g. White Mitsubishi Canter with secure waterproof canvas cover..."><?php echo htmlspecialchars($vehicle_to_edit['description'] ?? ''); ?></textarea>
        </div>

        <div style="display: flex; gap: 10px; margin-top: 20px;">
          <button type="submit" class="btn btn-primary" style="flex: 1;">
            <?php echo $vehicle_to_edit ? 'Update Vehicle' : 'Save Vehicle Record'; ?>
          </button>
          <?php if ($vehicle_to_edit): ?>
            <a href="<?php echo BASE_URL; ?>vehicles/index.php" class="btn btn-secondary">Cancel</a>
          <?php endif; ?>
        </div>

      </form>
    </div>
  </div>

  <!-- Vehicles Table -->
  <div class="card">
    <div class="card-header">
      <div>
        <h2>Registered Fleet Vehicles</h2>
        <span class="card-subtitle"><?php echo count($vehicles); ?> active vehicle(s)</span>
      </div>
    </div>
    <div class="table-responsive">
      <table class="data-table">
        <thead>
          <tr>
            <th>ID</th>
            <th>Plate Reg. No.</th>
            <th>Vehicle Type</th>
            <th>Description</th>
            <th>Hauls</th>
            <th>Created</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($vehicles)): ?>
            <tr>
              <td colspan="7" style="text-align: center; color: var(--text-muted); padding: 30px;">
                No fleet vehicles registered yet.
              </td>
            </tr>
          <?php else: ?>
            <?php foreach ($vehicles as $v): ?>
              <tr>
                <td>#<?php echo $v['vehicle_id']; ?></td>
                <td><strong style="color: var(--primary-navy);"><?php echo htmlspecialchars($v['registration_number']); ?></strong></td>
                <td><?php echo htmlspecialchars($v['vehicle_type']); ?></td>
                <td><?php echo htmlspecialchars($v['description'] ?: 'Commercial carrier'); ?></td>
                <td><span class="status-badge badge-dispatched"><?php echo (int)$v['total_hauls']; ?> hauls</span></td>
                <td><?php echo format_date($v['created_at']); ?></td>
                <td>
                  <div class="table-actions">
                    <a href="<?php echo BASE_URL; ?>vehicles/index.php?edit=<?php echo $v['vehicle_id']; ?>" class="btn btn-secondary btn-sm">Edit</a>
                    <a href="<?php echo BASE_URL; ?>vehicles/index.php?delete=<?php echo $v['vehicle_id']; ?>" class="btn btn-danger btn-sm" data-confirm="Are you sure you want to delete vehicle '<?php echo htmlspecialchars($v['registration_number']); ?>'?">Delete</a>
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
