<?php
/**
 * Goods Tracking and Transportation Management System
 * Sidebar Navigation
 */

$current_nav = $current_nav ?? '';
?>
<aside class="app-sidebar" id="appSidebar">
  <div class="sidebar-brand">
    <div class="brand-icon">
      <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <rect x="1" y="3" width="15" height="13"></rect>
        <polygon points="16 8 20 8 23 11 23 16 16 16 16 8"></polygon>
        <circle cx="5.5" cy="18.5" r="2.5"></circle>
        <circle cx="18.5" cy="18.5" r="2.5"></circle>
      </svg>
    </div>
    <div class="brand-info">
      <h1>FGSZ Goods Track</h1>
      <p>Funtua Garage &bull; Samaru</p>
    </div>
  </div>

  <nav class="sidebar-nav">
    <div class="nav-section-title">Core Management</div>
    <ul>
      <li class="<?php echo ($current_nav === 'dashboard') ? 'active' : ''; ?>">
        <a href="<?php echo BASE_URL; ?>dashboard.php">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <rect x="3" y="3" width="7" height="7"></rect>
            <rect x="14" y="3" width="7" height="7"></rect>
            <rect x="14" y="14" width="7" height="7"></rect>
            <rect x="3" y="14" width="7" height="7"></rect>
          </svg>
          Dashboard
        </a>
      </li>
      <li class="<?php echo ($current_nav === 'register') ? 'active' : ''; ?>">
        <a href="<?php echo BASE_URL; ?>consignments/register.php">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M12 5v14M5 12h14"></path>
          </svg>
          Register Goods
        </a>
      </li>
      <li class="<?php echo ($current_nav === 'consignments') ? 'active' : ''; ?>">
        <a href="<?php echo BASE_URL; ?>consignments/list.php">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <line x1="8" y1="6" x2="21" y2="6"></line>
            <line x1="8" y1="12" x2="21" y2="12"></line>
            <line x1="8" y1="18" x2="21" y2="18"></line>
            <line x1="3" y1="6" x2="3.01" y2="6"></line>
            <line x1="3" y1="12" x2="3.01" y2="12"></line>
            <line x1="3" y1="18" x2="3.01" y2="18"></line>
          </svg>
          All Consignments
        </a>
      </li>
      <li class="<?php echo ($current_nav === 'waybill') ? 'active' : ''; ?>">
        <a href="<?php echo BASE_URL; ?>waybills/generate.php">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
            <polyline points="14 2 14 8 20 8"></polyline>
            <line x1="16" y1="13" x2="8" y2="13"></line>
            <line x1="16" y1="17" x2="8" y2="17"></line>
            <polyline points="10 9 9 9 8 9"></polyline>
          </svg>
          Generate Waybill
        </a>
      </li>
    </ul>

    <div class="nav-section-title">Operations & Tracking</div>
    <ul>
      <li class="<?php echo ($current_nav === 'track') ? 'active' : ''; ?>">
        <a href="<?php echo BASE_URL; ?>tracking/index.php">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <circle cx="12" cy="12" r="10"></circle>
            <polyline points="12 6 12 12 16 14"></polyline>
          </svg>
          Track Consignment
        </a>
      </li>
      <li class="<?php echo ($current_nav === 'status_update') ? 'active' : ''; ?>">
        <a href="<?php echo BASE_URL; ?>delivery/update.php">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <polyline points="23 4 23 10 17 10"></polyline>
            <polyline points="1 20 1 14 7 14"></polyline>
            <path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"></path>
          </svg>
          Update Delivery Status
        </a>
      </li>
      <li class="<?php echo ($current_nav === 'confirm') ? 'active' : ''; ?>">
        <a href="<?php echo BASE_URL; ?>delivery/confirm.php">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
            <polyline points="22 4 12 14.01 9 11.01"></polyline>
          </svg>
          Delivery Confirmation
        </a>
      </li>
    </ul>

    <div class="nav-section-title">Fleet & Administration</div>
    <ul>
      <li class="<?php echo ($current_nav === 'drivers' || $current_nav === 'vehicles') ? 'active' : ''; ?>">
        <a href="<?php echo BASE_URL; ?>drivers/index.php">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
            <circle cx="9" cy="7" r="4"></circle>
            <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
            <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
          </svg>
          Drivers and Vehicles
        </a>
      </li>
      <li class="<?php echo ($current_nav === 'reports') ? 'active' : ''; ?>">
        <a href="<?php echo BASE_URL; ?>reports/index.php">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <line x1="18" y1="20" x2="18" y2="10"></line>
            <line x1="12" y1="20" x2="12" y2="4"></line>
            <line x1="6" y1="20" x2="6" y2="14"></line>
          </svg>
          Reports
        </a>
      </li>
      <li>
        <a href="<?php echo BASE_URL; ?>logout.php" data-confirm="Are you sure you want to log out of the Goods Tracking System?">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
            <polyline points="16 17 21 12 16 7"></polyline>
            <line x1="21" y1="12" x2="9" y2="12"></line>
          </svg>
          Logout
        </a>
      </li>
    </ul>
  </nav>

  <div class="sidebar-footer">
    <div><strong>Funtua Garage Logistics</strong></div>
    <div>Samaru, Zaria &bull; System v1.0</div>
  </div>
</aside>

<div class="app-main">
  <header class="app-header">
    <div class="header-left">
      <button type="button" class="mobile-toggle" onclick="document.getElementById('appSidebar').classList.toggle('show');" aria-label="Toggle Sidebar">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <line x1="3" y1="12" x2="21" y2="12"></line>
          <line x1="3" y1="6" x2="21" y2="6"></line>
          <line x1="3" y1="18" x2="21" y2="18"></line>
        </svg>
      </button>
      <div class="header-title">
        <h2>Funtua Garage Goods Tracking & Transportation System</h2>
        <span>Case Study of Funtua Garage, Samaru, Zaria</span>
      </div>
    </div>

    <div class="header-right">
      <div class="header-date">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
          <line x1="16" y1="2" x2="16" y2="6"></line>
          <line x1="8" y1="2" x2="8" y2="6"></line>
          <line x1="3" y1="10" x2="21" y2="10"></line>
        </svg>
        <span><?php echo date('D, M d, Y'); ?></span>
      </div>

      <?php if ($currentUser): ?>
      <div class="user-profile-badge">
        <div class="avatar-circle">
          <?php echo strtoupper(substr($currentUser['username'], 0, 1)); ?>
        </div>
        <div class="user-info-text">
          <span class="name"><?php echo htmlspecialchars($currentUser['full_name']); ?></span>
          <span class="role"><?php echo htmlspecialchars($currentUser['role']); ?></span>
        </div>
      </div>
      <?php endif; ?>
    </div>
  </header>

  <main class="page-body">
