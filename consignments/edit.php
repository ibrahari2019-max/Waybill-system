<?php
/**
 * Goods Tracking and Transportation Management System
 * Consignment Record Editor
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

require_login();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$stmt = $pdo->prepare("SELECT * FROM consignments WHERE consignment_id = ?");
$stmt->execute([$id]);
$consignment = $stmt->fetch();

if (!$consignment) {
    flash('error', 'Consignment record not found.', 'Error');
    header('Location: ' . BASE_URL . 'consignments/list.php');
    exit;
}

$drivers = $pdo->query("SELECT * FROM drivers ORDER BY full_name ASC")->fetchAll();
$vehicles = $pdo->query("SELECT * FROM vehicles ORDER BY registration_number ASC")->fetchAll();

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        flash('error', 'Invalid security token.', 'CSRF Error');
    } else {
        $sender_name = trim($_POST['sender_name'] ?? '');
        $sender_contact = trim($_POST['sender_contact'] ?? '');
        $sender_address = trim($_POST['sender_address'] ?? '');
        $recipient_name = trim($_POST['recipient_name'] ?? '');
        $recipient_contact = trim($_POST['recipient_contact'] ?? '');
        $recipient_address = trim($_POST['recipient_address'] ?? '');
        $goods_description = trim($_POST['goods_description'] ?? '');
        $quantity = (int)($_POST['quantity'] ?? 0);
        $goods_category = trim($_POST['goods_category'] ?? '');
        $special_instructions = trim($_POST['special_instructions'] ?? '');
        $destination = trim($_POST['destination'] ?? '');
        $dispatch_date = !empty($_POST['dispatch_date']) ? $_POST['dispatch_date'] : null;
        $driver_id = !empty($_POST['driver_id']) ? (int)$_POST['driver_id'] : null;
        $vehicle_id = !empty($_POST['vehicle_id']) ? (int)$_POST['vehicle_id'] : null;

        if (empty($sender_name)) $errors[] = 'Sender full name is required.';
        if (empty($sender_contact)) $errors[] = 'Sender contact phone number is required.';
        if (empty($recipient_name)) $errors[] = 'Recipient full name is required.';
        if (empty($recipient_contact)) $errors[] = 'Recipient contact phone number is required.';
        if (empty($goods_description)) $errors[] = 'Goods description is required.';
        if ($quantity <= 0) $errors[] = 'Quantity must be a positive integer.';
        if (empty($destination)) $errors[] = 'Destination is required.';

        if (empty($errors)) {
            try {
                $update_stmt = $pdo->prepare("
                    UPDATE consignments SET
                        sender_name = ?,
                        sender_contact = ?,
                        sender_address = ?,
                        recipient_name = ?,
                        recipient_contact = ?,
                        recipient_address = ?,
                        goods_description = ?,
                        quantity = ?,
                        goods_category = ?,
                        special_instructions = ?,
                        destination = ?,
                        dispatch_date = ?,
                        driver_id = ?,
                        vehicle_id = ?
                    WHERE consignment_id = ?
                ");

                $update_stmt->execute([
                    $sender_name,
                    $sender_contact,
                    $sender_address ?: null,
                    $recipient_name,
                    $recipient_contact,
                    $recipient_address ?: null,
                    $goods_description,
                    $quantity,
                    $goods_category ?: null,
                    $special_instructions ?: null,
                    $destination,
                    $dispatch_date,
                    $driver_id,
                    $vehicle_id,
                    $id
                ]);

                flash('success', "Consignment record {$consignment['tracking_number']} successfully updated.", 'Record Updated');
                header('Location: ' . BASE_URL . 'consignments/view.php?id=' . $id);
                exit;

            } catch (PDOException $e) {
                flash('error', 'Update failed: ' . $e->getMessage(), 'Database Error');
            }
        } else {
            flash('warning', implode('<br>', $errors), 'Validation Warning');
        }
    }
}

$page_title = 'Edit Consignment - ' . htmlspecialchars($consignment['tracking_number']);
$current_nav = 'consignments';

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<div class="page-header">
  <div class="page-header-title">
    <h1>Edit Consignment: <?php echo htmlspecialchars($consignment['tracking_number']); ?></h1>
    <p>Modify consignment particulars or assign transit drivers &amp; vehicles.</p>
  </div>
  <div class="page-header-actions">
    <a href="<?php echo BASE_URL; ?>consignments/view.php?id=<?php echo $id; ?>" class="btn btn-secondary">
      View Details
    </a>
  </div>
</div>

<div class="card">
  <div class="card-header">
    <h2>Edit Consignment Details</h2>
  </div>
  <div class="card-body">
    <form method="POST" action="<?php echo BASE_URL; ?>consignments/edit.php?id=<?php echo $id; ?>">
      <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">

      <!-- Sender Information -->
      <div class="form-section-title">1. Sender Information</div>
      <div class="form-grid-3">
        <div class="form-group">
          <label class="form-label" for="sender_name">Sender Full Name <span class="required">*</span></label>
          <input type="text" id="sender_name" name="sender_name" class="form-control" value="<?php echo htmlspecialchars($consignment['sender_name']); ?>" required>
        </div>
        <div class="form-group">
          <label class="form-label" for="sender_contact">Sender Phone <span class="required">*</span></label>
          <input type="tel" id="sender_contact" name="sender_contact" class="form-control" value="<?php echo htmlspecialchars($consignment['sender_contact']); ?>" required>
        </div>
        <div class="form-group">
          <label class="form-label" for="sender_address">Sender Address</label>
          <input type="text" id="sender_address" name="sender_address" class="form-control" value="<?php echo htmlspecialchars($consignment['sender_address'] ?? ''); ?>">
        </div>
      </div>

      <!-- Recipient Information -->
      <div class="form-section-title" style="margin-top: 20px;">2. Recipient Information</div>
      <div class="form-grid-3">
        <div class="form-group">
          <label class="form-label" for="recipient_name">Recipient Full Name <span class="required">*</span></label>
          <input type="text" id="recipient_name" name="recipient_name" class="form-control" value="<?php echo htmlspecialchars($consignment['recipient_name']); ?>" required>
        </div>
        <div class="form-group">
          <label class="form-label" for="recipient_contact">Recipient Phone <span class="required">*</span></label>
          <input type="tel" id="recipient_contact" name="recipient_contact" class="form-control" value="<?php echo htmlspecialchars($consignment['recipient_contact']); ?>" required>
        </div>
        <div class="form-group">
          <label class="form-label" for="recipient_address">Delivery Address</label>
          <input type="text" id="recipient_address" name="recipient_address" class="form-control" value="<?php echo htmlspecialchars($consignment['recipient_address'] ?? ''); ?>">
        </div>
      </div>

      <!-- Goods Specifications -->
      <div class="form-section-title" style="margin-top: 20px;">3. Goods Information</div>
      <div class="form-grid">
        <div class="form-group full-width">
          <label class="form-label" for="goods_description">Goods Description <span class="required">*</span></label>
          <textarea id="goods_description" name="goods_description" class="form-control" required><?php echo htmlspecialchars($consignment['goods_description']); ?></textarea>
        </div>
        <div class="form-group">
          <label class="form-label" for="quantity">Quantity <span class="required">*</span></label>
          <input type="number" id="quantity" name="quantity" class="form-control" min="1" value="<?php echo (int)$consignment['quantity']; ?>" required>
        </div>
        <div class="form-group">
          <label class="form-label" for="goods_category">Category</label>
          <input type="text" id="goods_category" name="goods_category" class="form-control" value="<?php echo htmlspecialchars($consignment['goods_category'] ?? ''); ?>">
        </div>
        <div class="form-group full-width">
          <label class="form-label" for="special_instructions">Special Instructions</label>
          <input type="text" id="special_instructions" name="special_instructions" class="form-control" value="<?php echo htmlspecialchars($consignment['special_instructions'] ?? ''); ?>">
        </div>
      </div>

      <!-- Transportation Information -->
      <div class="form-section-title" style="margin-top: 20px;">4. Transportation &amp; Fleet</div>
      <div class="form-grid">
        <div class="form-group">
          <label class="form-label" for="destination">Destination <span class="required">*</span></label>
          <input type="text" id="destination" name="destination" class="form-control" value="<?php echo htmlspecialchars($consignment['destination']); ?>" required>
        </div>
        <div class="form-group">
          <label class="form-label" for="dispatch_date">Dispatch Date</label>
          <input type="date" id="dispatch_date" name="dispatch_date" class="form-control" value="<?php echo htmlspecialchars($consignment['dispatch_date'] ?? ''); ?>">
        </div>
        <div class="form-group">
          <label class="form-label" for="driver_id">Assigned Driver</label>
          <select id="driver_id" name="driver_id" class="form-control">
            <option value="">-- No Driver Assigned --</option>
            <?php foreach ($drivers as $d): ?>
              <option value="<?php echo $d['driver_id']; ?>" <?php echo ($d['driver_id'] == $consignment['driver_id']) ? 'selected' : ''; ?>>
                <?php echo htmlspecialchars($d['full_name']); ?> (<?php echo htmlspecialchars($d['phone']); ?>)
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label" for="vehicle_id">Assigned Vehicle</label>
          <select id="vehicle_id" name="vehicle_id" class="form-control">
            <option value="">-- No Vehicle Assigned --</option>
            <?php foreach ($vehicles as $v): ?>
              <option value="<?php echo $v['vehicle_id']; ?>" <?php echo ($v['vehicle_id'] == $consignment['vehicle_id']) ? 'selected' : ''; ?>>
                <?php echo htmlspecialchars($v['registration_number']); ?> - <?php echo htmlspecialchars($v['vehicle_type']); ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>

      <div class="form-actions">
        <a href="<?php echo BASE_URL; ?>consignments/view.php?id=<?php echo $id; ?>" class="btn btn-secondary">Cancel</a>
        <button type="submit" class="btn btn-primary">
          Save Changes
        </button>
      </div>

    </form>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
