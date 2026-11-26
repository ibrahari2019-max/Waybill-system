<?php
/**
 * Goods Tracking and Transportation Management System
 * Administrator Login
 */

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';

// Redirect if already logged in
if (is_logged_in()) {
    header('Location: ' . BASE_URL . 'dashboard.php');
    exit;
}

$error = '';
$username_val = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!verify_csrf_token($token)) {
        flash('error', 'Security validation failed. Please refresh the page and try again.', 'CSRF Error');
    } else {
        $username_val = trim($_POST['username'] ?? '');
        $password_val = $_POST['password'] ?? '';

        if (empty($username_val) || empty($password_val)) {
            flash('warning', 'Please enter both username and password.', 'Required Fields');
        } else {
            if (attempt_login($pdo, $username_val, $password_val)) {
                flash('success', 'Authentication successful. Welcome to Funtua Garage Management System.', 'Login Successful');
                header('Location: ' . BASE_URL . 'dashboard.php');
                exit;
            } else {
                flash('error', 'Invalid username or password. Please verify credentials.', 'Authentication Failed');
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Administrator Login - <?php echo APP_NAME; ?></title>

  <!-- iziToast CSS -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/izitoast/1.4.0/css/iziToast.min.css" onerror="this.onerror=null;this.href='<?php echo BASE_URL; ?>assets/css/iziToast.min.css';">

  <!-- Main System CSS -->
  <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/style.css">
</head>
<body class="login-page">

<div class="login-card">
  <div class="login-brand">
    <div class="login-brand-icon">
      <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <rect x="1" y="3" width="15" height="13"></rect>
        <polygon points="16 8 20 8 23 11 23 16 16 16 16 8"></polygon>
        <circle cx="5.5" cy="18.5" r="2.5"></circle>
        <circle cx="18.5" cy="18.5" r="2.5"></circle>
      </svg>
    </div>
    <h1>Funtua Garage Logistics</h1>
    <p>Samaru, Zaria Goods Tracking &amp; Management System</p>
  </div>

  <form method="POST" action="<?php echo BASE_URL; ?>login.php" class="login-form">
    <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">

    <div class="form-group">
      <label class="form-label" for="username">Administrator Username</label>
      <input type="text" id="username" name="username" class="form-control" placeholder="Enter username" value="<?php echo htmlspecialchars($username_val); ?>" required autofocus>
    </div>

    <div class="form-group">
      <label class="form-label" for="password">Password</label>
      <input type="password" id="password" name="password" class="form-control" placeholder="Enter password" required>
    </div>

    <button type="submit" class="btn btn-primary login-btn">
      Sign In to Dashboard
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <line x1="5" y1="12" x2="19" y2="12"></line>
        <polyline points="12 5 19 12 12 19"></polyline>
      </svg>
    </button>
  </form>

  <div class="login-credentials-hint">
    <strong>Default Administrator Credentials:</strong><br>
    Username: <code>admin</code> &nbsp;|&nbsp; Password: <code>admin</code>
  </div>

  <div style="margin-top: 24px; text-align: center; font-size: 13.5px; border-top: 1px solid var(--border-color); padding-top: 18px;">
    <span>Customer or Client? </span>
    <a href="<?php echo BASE_URL; ?>tracking/index.php" style="font-weight: 600;">Track Your Consignment Here &rarr;</a>
  </div>
</div>

<!-- iziToast JS -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/izitoast/1.4.0/js/iziToast.min.js"></script>
<script>
  if (typeof iziToast === 'undefined') {
    document.write('<script src="<?php echo BASE_URL; ?>assets/js/iziToast.min.js"><\/script>');
  }
</script>

<script>
  if (typeof iziToast !== 'undefined') {
    iziToast.settings({
      timeout: 4500,
      resetOnHover: true,
      position: 'topRight',
      progressBar: true,
      close: true
    });

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
  }
</script>
</body>
</html>
