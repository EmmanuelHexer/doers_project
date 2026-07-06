<?php
// add_instructor.php
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: admin_login.php');
    exit;
}
require_once __DIR__ . '/config/database.php';

$db = Database::getInstance()->getConnection();
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first = trim($_POST['first_name'] ?? '');
    $last  = trim($_POST['last_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $tel   = trim($_POST['telephone'] ?? '');
    $spec  = trim($_POST['specialization'] ?? '');
    $status = $_POST['status'] ?? 'active';
    $hire  = $_POST['hire_date'] !== '' ? $_POST['hire_date'] : date('Y-m-d');

    if ($first === '' || $last === '' || $email === '') {
        $error = 'First name, last name and email are required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } else {
        try {
            $db->prepare("INSERT INTO Instructor (first_name, last_name, email, telephone, specialization, status, hire_date) VALUES (?,?,?,?,?,?,?)")
               ->execute([$first, $last, $email, $tel, $spec, $status, $hire]);
            header('Location: admin_instructors.php');
            exit;
        } catch (PDOException $e) {
            $error = (strpos($e->getMessage(), 'Duplicate') !== false)
                ? 'That email is already registered to another instructor.'
                : 'Failed to add instructor.';
        }
    }
}
$active = 'instructors';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Instructor - Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css?v=2">
    <style>.form-container { max-width: 640px; margin: 0 auto; }</style>
</head>
<body style="background: var(--bg-dark);">
    <div class="main-wrapper">
        <?php include __DIR__ . '/includes/admin_sidebar.php'; ?>
        <main class="main-content" id="mainContent">
            <nav class="top-nav">
                <div class="top-nav-left">
                    <button class="sidebar-toggle" id="sidebarToggle"><i class="fas fa-bars"></i></button>
                    <div><h2>Add Instructor</h2><div class="breadcrumb">Admin / Instructors / <span>Add</span></div></div>
                </div>
            </nav>
            <div class="form-container animate-fade-in">
                <div class="card">
                    <div class="card-header"><h5><i class="fas fa-chalkboard-teacher" style="color:var(--primary-light);"></i> New Instructor</h5></div>
                    <div class="card-body">
                        <?php if ($error): ?><div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?></div><?php endif; ?>
                        <form method="POST">
                            <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px;">
                                <div class="form-group"><label class="form-label">First Name *</label><input type="text" name="first_name" class="form-control" required></div>
                                <div class="form-group"><label class="form-label">Last Name *</label><input type="text" name="last_name" class="form-control" required></div>
                            </div>
                            <div class="form-group"><label class="form-label">Email *</label><input type="email" name="email" class="form-control" required></div>
                            <div class="form-group"><label class="form-label">Telephone</label><input type="text" name="telephone" class="form-control"></div>
                            <div class="form-group"><label class="form-label">Specialization</label><input type="text" name="specialization" class="form-control" placeholder="e.g., Strength Training, Yoga"></div>
                            <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px;">
                                <div class="form-group"><label class="form-label">Status</label>
                                    <select name="status" class="form-control">
                                        <option value="active">Active</option><option value="inactive">Inactive</option><option value="on_leave">On Leave</option>
                                    </select></div>
                                <div class="form-group"><label class="form-label">Hire Date</label><input type="date" name="hire_date" class="form-control" value="<?= date('Y-m-d') ?>"></div>
                            </div>
                            <button type="submit" class="btn btn-primary" style="width:100%;"><i class="fas fa-save"></i> Add Instructor</button>
                        </form>
                    </div>
                </div>
                <div style="text-align:center; margin-top:16px;"><a href="admin_instructors.php" style="color:var(--text-muted);"><i class="fas fa-arrow-left"></i> Back to Instructors</a></div>
            </div>
            <div class="footer">&copy; <?= date('Y') ?> USTED-K Gym Center</div>
        </main>
    </div>
    <script src="assets/js/script.js"></script>
    <script>document.getElementById('sidebarToggle').addEventListener('click',function(){document.getElementById('sidebar').classList.toggle('open');});</script>
</body>
</html>
