<?php
/**
 * Goods Tracking and Transportation Management System
 * Consignment Registration Module
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

require_login();

$page_title = 'Register Goods Consignment';
$current_nav = 'register';
$currentUser = current_user();

// Fetch available drivers and vehicles
$drivers = $pdo->query("SELECT * FROM drivers ORDER BY full_name ASC")->fetchAll();
$vehicles = $pdo->query("SELECT * FROM vehicles ORDER BY registration_number ASC")->fetchAll();

$errors = [];
$form_data = [
    'sender_name'          => '',
    'sender_contact'       => '',
    'sender_address'       => '',
    'recipient_name'       => '',
    'recipient_contact'    => '',
    'recipient_address'    => '',
    'goods_description'    => '',
    'quantity'             => '1',
    'goods_category'       => '',
    'special_instructions' => '',
    'destination'          => '',
    'dispatch_date'        => date('Y-m-d'),
    'driver_id'            => '',
    'vehicle_id'           => ''
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        flash('error', 'Invalid security token. Please resubmit the form.', 'CSRF Error');
    } else {
        // Collect and trim form inputs
        foreach ($form_data as $key => $val) {
            if (isset($_POST[$key])) {
                $form_data[$key] = trim($_POST[$key]);
            }
        }

        // Validate required fields
        if (empty($form_data['sender_name'])) {
            $errors[] = 'Sender full name is required.';
        }
        if (empty($form_data['sender_contact'])) {
            $errors[] = 'Sender contact phone number is required.';
        }
        if (empty($form_data['recipient_name'])) {
            $errors[] = 'Recipient full name is required.';
        }
        if (empty($form_data['recipient_contact'])) {
            $errors[] = 'Recipient contact phone number is required.';
        }
        if (empty($form_data['goods_description'])) {
            $errors[] = 'Goods description is required.';
        }
        if (empty($form_data['quantity']) || !filter_var($form_data['quantity'], FILTER_VALIDATE_INT) || (int)$form_data['quantity'] <= 0) {
            $errors[] = 'Quantity must be a positive integer.';
        }
        if (empty($form_data['destination'])) {
            $errors[] = 'Transportation destination is required.';
        }

        if (empty($errors)) {
            try {
                $pdo->beginTransaction();

                // 1. Generate unique Tracking Number and Waybill Number
                $tracking_number = generate_tracking_number($pdo);
                $waybill_number  = generate_waybill_number($pdo);

                $driver_id  = !empty($form_data['driver_id']) ? (int)$form_data['driver_id'] : null;
                $vehicle_id = !empty($form_data['vehicle_id']) ? (int)$form_data['vehicle_id'] : null;
                $dispatch_date = !empty($form_data['dispatch_date']) ? $form_data['dispatch_date'] : null;

                // 2. Insert Consignment Record
                $stmt = $pdo->prepare("
                    INSERT INTO consignments (
                        tracking_number, sender_name, sender_contact, sender_address,
                        recipient_name, recipient_contact, recipient_address,
                        goods_description, quantity, goods_category, special_instructions,
                        destination, dispatch_date, driver_id, vehicle_id,
                        current_status, registered_by, created_at
                    ) VALUES (
                        ?, ?, ?, ?,
                        ?, ?, ?,
                        ?, ?, ?, ?,
                        ?, ?, ?, ?,
                        'Registered', ?, NOW()
                    )
                ");

                $stmt->execute([
                    $tracking_number,
                    $form_data['sender_name'],
                    $form_data['sender_contact'],
                    $form_data['sender_address'] ?: null,
                    $form_data['recipient_name'],
                    $form_data['recipient_contact'],
                    $form_data['recipient_address'] ?: null,
                    $form_data['goods_description'],
                    (int)$form_data['quantity'],
                    $form_data['goods_category'] ?: null,
                    $form_data['special_instructions'] ?: null,
                    $form_data['destination'],
                    $dispatch_date,
                    $driver_id,
                    $vehicle_id,
                    $currentUser['user_id']
                ]);

                $consignment_id = (int)$pdo->lastInsertId();

                // 3. Insert Waybill Record
                $wb_stmt = $pdo->prepare("
                    INSERT INTO waybills (consignment_id, waybill_number, issue_date, issued_by)
                    VALUES (?, ?, NOW(), ?)
                ");
                $wb_stmt->execute([$consignment_id, $waybill_number, $currentUser['user_id']]);

                // 4. Insert Initial Delivery Status History Record
                $hist_stmt = $pdo->prepare("
                    INSERT INTO delivery_status_history (consignment_id, status, remarks, updated_by, updated_at)
                    VALUES (?, 'Registered', 'Consignment officially registered at Funtua Garage, Samaru, Zaria depot.', ?, NOW())
                ");
                $hist_stmt->execute([$consignment_id, $currentUser['user_id']]);

                $pdo->commit();

                flash('success', "Consignment successfully registered! Tracking No: {$tracking_number} | Waybill No: {$waybill_number}", 'Registration Successful');
                header("Location: " . BASE_URL . "waybills/view.php?id=" . $consignment_id);
                exit;

            } catch (Exception $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                flash('error', 'Failed to register consignment: ' . $e->getMessage(), 'Database Error');
            }
        } else {
            flash('warning', implode('<br>', $errors), 'Validation Error');
        }
    }
}

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<div class="page-header">
  <div class="page-header-title">
    <h1>Digital Consignment Registration</h1>
    <p>Register new goods for transportation from Funtua Garage, Samaru, Zaria.</p>
  </div>
  <div class="page-header-actions">
    <a href="<?php echo BASE_URL; ?>consignments/list.php" class="btn btn-secondary">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="8" y1="6" x2="21" y2="6"></line><line x1="8" y1="12" x2="21" y2="12"></line><line x1="8" y1="18" x2="21" y2="18"></line></svg>
      All Consignments
    </a>
  </div>
</div>

<div class="card">
  <div class="card-header">
    <div>
      <h2>Consignment Registration Form</h2>
      <span class="card-subtitle">Fill in complete sender, recipient, and transportation specifications</span>
    </div>
  </div>

  <div class="card-body">
    <form method="POST" action="<?php echo BASE_URL; ?>consignments/register.php">
      <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">

      <!-- Section 1: Sender Information -->
      <div class="form-section-title">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
        1. Sender Information
      </div>
      <div class="form-grid-3">
        <div class="form-group">
          <label class="form-label" for="sender_name">Sender Full Name <span class="required">*</span></label>
          <input type="text" id="sender_name" name="sender_name" class="form-control" placeholder="e.g. Alhaji Bashir Umar" value="<?php echo htmlspecialchars($form_data['sender_name']); ?>" required>
        </div>
        <div class="form-group">
          <label class="form-label" for="sender_contact">Sender Phone Number <span class="required">*</span></label>
          <input type="tel" id="sender_contact" name="sender_contact" class="form-control" placeholder="e.g. 08031234567" value="<?php echo htmlspecialchars($form_data['sender_contact']); ?>" required>
        </div>
        <div class="form-group">
          <label class="form-label" for="sender_address">Sender Address (Optional)</label>
          <input type="text" id="sender_address" name="sender_address" class="form-control" placeholder="e.g. Samaru Market Road, Zaria" value="<?php echo htmlspecialchars($form_data['sender_address']); ?>">
        </div>
      </div>

      <!-- Section 2: Receiver Information -->
      <div class="form-section-title" style="margin-top: 20px;">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
        2. Recipient / Receiver Information
      </div>
      <div class="form-grid-3">
        <div class="form-group">
          <label class="form-label" for="recipient_name">Receiver Full Name <span class="required">*</span></label>
          <input type="text" id="recipient_name" name="recipient_name" class="form-control" placeholder="e.g. Hajiya Fatima Bello" value="<?php echo htmlspecialchars($form_data['recipient_name']); ?>" required>
        </div>
        <div class="form-group">
          <label class="form-label" for="recipient_contact">Receiver Phone Number <span class="required">*</span></label>
          <input type="tel" id="recipient_contact" name="recipient_contact" class="form-control" placeholder="e.g. 08064445566" value="<?php echo htmlspecialchars($form_data['recipient_contact']); ?>" required>
        </div>
        <div class="form-group">
          <label class="form-label" for="recipient_address">Receiver Delivery Address (Optional)</label>
          <input type="text" id="recipient_address" name="recipient_address" class="form-control" placeholder="e.g. No. 5 Central Market, Funtua" value="<?php echo htmlspecialchars($form_data['recipient_address']); ?>">
        </div>
      </div>

      <!-- Section 3: Goods Description -->
      <div class="form-section-title" style="margin-top: 20px;">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path></svg>
        3. Goods Information
      </div>
      <div class="form-grid">
        <div class="form-group full-width">
          <label class="form-label" for="goods_description">Description of Goods <span class="required">*</span></label>
          <textarea id="goods_description" name="goods_description" class="form-control" placeholder="Provide full details of the items (e.g. 10 Bags of High-Yield Grains, 4 Cartons of Electrical Accessories...)" required><?php echo htmlspecialchars($form_data['goods_description']); ?></textarea>
        </div>
        <div class="form-group">
          <label class="form-label" for="quantity">Quantity / Number of Packages <span class="required">*</span></label>
          <input type="number" id="quantity" name="quantity" class="form-control" min="1" value="<?php echo htmlspecialchars($form_data['quantity']); ?>" required>
        </div>
        <div class="form-group">
          <label class="form-label" for="goods_category">Goods Category (Optional)</label>
          <select id="goods_category" name="goods_category" class="form-control">
            <option value="">Select Category...</option>
            <option value="Agricultural Produce" <?php echo ($form_data['goods_category'] === 'Agricultural Produce') ? 'selected' : ''; ?>>Agricultural Produce (Grains, Seeds)</option>
            <option value="Electronics & Equipment" <?php echo ($form_data['goods_category'] === 'Electronics & Equipment') ? 'selected' : ''; ?>>Electronics & Equipment</option>
            <option value="Textiles & Apparel" <?php echo ($form_data['goods_category'] === 'Textiles & Apparel') ? 'selected' : ''; ?>>Textiles & Apparel</option>
            <option value="Automobile Spare Parts" <?php echo ($form_data['goods_category'] === 'Automobile Spare Parts') ? 'selected' : ''; ?>>Automobile Spare Parts</option>
            <option value="Building Materials" <?php echo ($form_data['goods_category'] === 'Building Materials') ? 'selected' : ''; ?>>Building Materials</option>
            <option value="Fragile Goods" <?php echo ($form_data['goods_category'] === 'Fragile Goods') ? 'selected' : ''; ?>>Fragile Goods</option>
            <option value="General Merchandise" <?php echo ($form_data['goods_category'] === 'General Merchandise') ? 'selected' : ''; ?>>General Merchandise</option>
          </select>
        </div>
        <div class="form-group full-width">
          <label class="form-label" for="special_instructions">Special Instructions / Handling Notes (Optional)</label>
          <input type="text" id="special_instructions" name="special_instructions" class="form-control" placeholder="e.g. Fragile. Keep upright. Do not stack heavily." value="<?php echo htmlspecialchars($form_data['special_instructions']); ?>">
        </div>
      </div>

      <!-- Section 4: Transportation & Dispatch Information -->
      <div class="form-section-title" style="margin-top: 20px;">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="1" y="3" width="15" height="13"></rect><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"></polygon><circle cx="5.5" cy="18.5" r="2.5"></circle><circle cx="18.5" cy="18.5" r="2.5"></circle></svg>
        4. Transportation &amp; Dispatch Details
      </div>
      <div class="form-grid">
        <div class="form-group">
          <label class="form-label" for="destination">Destination Terminal / City <span class="required">*</span></label>
          <input type="text" id="destination" name="destination" class="form-control" placeholder="e.g. Funtua, Katsina, Kano, Kaduna, Gusau" value="<?php echo htmlspecialchars($form_data['destination']); ?>" required>
        </div>
        <div class="form-group">
          <label class="form-label" for="dispatch_date">Dispatch Date (Optional)</label>
          <input type="date" id="dispatch_date" name="dispatch_date" class="form-control" value="<?php echo htmlspecialchars($form_data['dispatch_date']); ?>">
        </div>
        <div class="form-group">
          <label class="form-label" for="driver_id">Assigned Driver (Optional)</label>
          <select id="driver_id" name="driver_id" class="form-control">
            <option value="">-- Assign Driver Later --</option>
            <?php foreach ($drivers as $d): ?>
              <option value="<?php echo $d['driver_id']; ?>" <?php echo ((string)$d['driver_id'] === $form_data['driver_id']) ? 'selected' : ''; ?>>
                <?php echo htmlspecialchars($d['full_name']); ?> (<?php echo htmlspecialchars($d['phone']); ?>)
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label" for="vehicle_id">Assigned Vehicle (Optional)</label>
          <select id="vehicle_id" name="vehicle_id" class="form-control">
            <option value="">-- Assign Vehicle Later --</option>
            <?php foreach ($vehicles as $v): ?>
              <option value="<?php echo $v['vehicle_id']; ?>" <?php echo ((string)$v['vehicle_id'] === $form_data['vehicle_id']) ? 'selected' : ''; ?>>
                <?php echo htmlspecialchars($v['registration_number']); ?> - <?php echo htmlspecialchars($v['vehicle_type']); ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>

      <div class="form-actions">
        <a href="<?php echo BASE_URL; ?>dashboard.php" class="btn btn-secondary">Cancel</a>
        <button type="submit" class="btn btn-primary btn-lg">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path><polyline points="17 21 17 13 7 13 7 21"></polyline><polyline points="7 3 7 8 15 8"></polyline></svg>
          Register Goods &amp; Generate Waybill
        </button>
      </div>

    </form>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
