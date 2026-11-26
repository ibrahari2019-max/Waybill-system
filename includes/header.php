<?php
/**
 * Goods Tracking and Transportation Management System
 * Header Template
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/auth.php';

$page_title = $page_title ?? 'Dashboard';
$currentUser = current_user();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo htmlspecialchars($page_title); ?> - <?php echo APP_NAME; ?></title>

  <!-- iziToast CSS (CDN with Local Fallback) -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/izitoast/1.4.0/css/iziToast.min.css" onerror="this.onerror=null;this.href='<?php echo BASE_URL; ?>assets/css/iziToast.min.css';">

  <!-- Main System CSS -->
  <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/style.css">
</head>
<body>
<div class="app-wrapper">
