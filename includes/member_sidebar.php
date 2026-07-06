<?php
// includes/member_sidebar.php - shared member sidebar. Set $active before including.
$active = $active ?? '';
function _msActive($name, $active) { return $name === $active ? ' active' : ''; }
?>
<aside class="sidebar" id="sidebar">
    <div class="sidebar-brand">
        <img src="assets/images/logo.png" alt="Logo" onerror="this.style.display='none'">
        <span>USTED-K Gym</span>
    </div>
    <nav class="sidebar-menu">
        <div class="sidebar-menu-label">Main</div>
        <a href="dashboard.php" class="sidebar-item<?= _msActive('dashboard',$active) ?>"><i class="fas fa-th-large"></i> Dashboard</a>
        <a href="profile.php" class="sidebar-item<?= _msActive('profile',$active) ?>"><i class="fas fa-user"></i> My Profile</a>
        <a href="training.php" class="sidebar-item<?= _msActive('training',$active) ?>"><i class="fas fa-calendar-alt"></i> My Training</a>
        <div class="sidebar-menu-label" style="margin-top: 20px;">Activity</div>
        <a href="book_session.php" class="sidebar-item<?= _msActive('book',$active) ?>"><i class="fas fa-calendar-plus"></i> Book Session</a>
        <a href="my_sessions.php" class="sidebar-item<?= _msActive('sessions',$active) ?>"><i class="fas fa-list"></i> My Sessions</a>
        <div class="sidebar-menu-label" style="margin-top: 20px;">Billing</div>
        <a href="make_payment.php" class="sidebar-item<?= _msActive('make_payment',$active) ?>"><i class="fas fa-hand-holding-usd"></i> Make Payment</a>
        <a href="payments.php" class="sidebar-item<?= _msActive('payments',$active) ?>"><i class="fas fa-credit-card"></i> Payment History</a>
        <a href="logout.php" class="sidebar-item" style="color: #FF6B6B; margin-top: 20px;"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </nav>
</aside>
