<?php
// includes/admin_sidebar.php - shared admin sidebar. Set $active before including.
$active = $active ?? '';
function _sbActive($name, $active) { return $name === $active ? ' active' : ''; }
?>
<aside class="sidebar" id="sidebar">
    <div class="sidebar-brand">
        <img src="assets/images/logo.png" alt="Logo" onerror="this.style.display='none'">
        <span>USTED-K Gym</span>
    </div>
    <nav class="sidebar-menu">
        <div class="sidebar-menu-label">Main</div>
        <a href="admin_dashboard.php" class="sidebar-item<?= _sbActive('dashboard',$active) ?>"><i class="fas fa-th-large"></i> Dashboard</a>
        <a href="admin_members.php" class="sidebar-item<?= _sbActive('members',$active) ?>"><i class="fas fa-users"></i> Members</a>
        <a href="admin_instructors.php" class="sidebar-item<?= _sbActive('instructors',$active) ?>"><i class="fas fa-chalkboard-teacher"></i> Instructors</a>
        <a href="admin_memberships.php" class="sidebar-item<?= _sbActive('memberships',$active) ?>"><i class="fas fa-id-card"></i> Memberships</a>
        <div class="sidebar-menu-label" style="margin-top: 20px;">Training</div>
        <a href="admin_training_types.php" class="sidebar-item<?= _sbActive('training_types',$active) ?>"><i class="fas fa-dumbbell"></i> Training Types</a>
        <a href="admin_training_sections.php" class="sidebar-item<?= _sbActive('sections',$active) ?>"><i class="fas fa-calendar-alt"></i> Sections</a>
        <div class="sidebar-menu-label" style="margin-top: 20px;">Financial</div>
        <a href="admin_payments.php" class="sidebar-item<?= _sbActive('payments',$active) ?>"><i class="fas fa-credit-card"></i> Payments</a>
        <a href="admin_reports.php" class="sidebar-item<?= _sbActive('reports',$active) ?>"><i class="fas fa-chart-line"></i> Reports</a>
        <div class="sidebar-menu-label" style="margin-top: 20px;">System</div>
        <a href="admin_logs.php" class="sidebar-item<?= _sbActive('logs',$active) ?>"><i class="fas fa-list"></i> Logs</a>
        <a href="admin_backup.php" class="sidebar-item<?= _sbActive('backup',$active) ?>"><i class="fas fa-database"></i> Backup</a>
        <a href="logout.php" class="sidebar-item" style="margin-top: 20px; color: #FF6B6B;"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </nav>
</aside>
<?php
// Shared auth guard helper is expected to already have run in the including page.
?>
