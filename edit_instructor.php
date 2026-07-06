<?php
// edit_instructor.php
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: admin_login.php');
    exit;
}
require_once __DIR__ . '/config/database.php';

$db = Database::getInstance()->getConnection();
$id = intval($_GET['id'] ?? $_POST['instructor_id'] ?? 0);
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first = trim($_POST['first_name'] ?? '');
    $last  = trim($_POST['last_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $tel   = trim($_POST['telephone'] ?? '');
    $spec  = trim($_POST['specialization'] ?? '');
    $status = $_POST['status'] ?? 'active';

    if ($first === '' || $last === '' || $email === '') {
        $error = 'First name, last name and email are required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } else {
        try {
            $db->prepare("UPDATE Instructor SET first_name=?, last_name=?, email=?, telephone=?, specialization=?, status=? WHERE instructor_id=?")
               ->execute([$first, $last, $email, $tel, $spec, $status, $id]);
            $success = 'Instructor updated successfully.';
        } catch (PDOException $e) {
            $error = (strpos($e->getMessage(), 'Duplicate') !== false)
                ? 'That email is already used by another instructor.'
                : 'Update failed.';
        }
    }
}

$stmt = $db->prepare("SELECT * FROM Instructor WHERE instructor_id = ?");
$stmt->execute([$id]);
$inst = $stmt->fetch();
if (!$inst) { header('Location: admin_instructors.php'); exit; }
$active = 'instructors';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Instructor - Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>.form-container { max-width: 640px; margin: 0 auto; }</style>
</head>
<body style="background: var(--bg-dark);">
    <div class="main-wrapper">
        <?php include __DIR__ . '/includes/admin_sidebar.php'; ?>
        <main class="main-content" id="mainContent">
            <nav class="top-nav">
                <div class="top-nav-left">
                    <button class="sidebar-toggle" id="sidebarToggle"><i class="fas fa-bars"></i></button>
                    <div><h2>Edit Instructor</h2><div class="breadcrumb">Admin / Instructors / <span>Edit</span></div></div>
                </div>
            </nav>
            <div class="form-container animate-fade-in">
                <div class="card">
                    <div class="card-header"><h5><i class="fas fa-user-edit" style="color:var(--primary-light);"></i> <?= htmlspecialchars($inst['first_name'].' '.$inst['last_name']) ?></h5></div>
                    <div class="card-body">
                        <?php if ($success): ?><div class="alert alert-success"><i class="fas fa-check-circle"></i> <?= htmlspecialchars($success) ?></div><?php endif; ?>
                        <?php if ($error): ?><div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?></div><?php endif; ?>
                        <form method="POST" action="edit_instructor.php?id=<?= $inst['instructor_id'] ?>">
                            <input type="hidden" name="instructor_id" value="<?= $inst['instructor_id'] ?>">
                            <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px;">
                                <div class="form-group"><label class="form-label">First Name *</label><input type="text" name="first_name" class="form-control" value="<?= htmlspecialchars($inst['first_name']) ?>" required></div>
                                <div class="form-group"><label class="form-label">Last Name *</label><input type="text" name="last_name" class="form-control" value="<?= htmlspecialchars($inst['last_name']) ?>" required></div>
                            </div>
                            <div class="form-group"><label class="form-label">Email *</label><input type="email" name="email" class="form-control" value="<?= htmlspecialchars($inst['email']) ?>" required></div>
                            <div class="form-group"><label class="form-label">Telephone</label><input type="text" name="telephone" class="form-control" value="<?= htmlspecialchars($inst['telephone']) ?>"></div>
                            <div class="form-group"><label class="form-label">Specialization</label><input type="text" name="specialization" class="form-control" value="<?= htmlspecialchars($inst['specialization']) ?>"></div>
                            <div class="form-group"><label class="form-label">Status</label>
                                <select name="status" class="form-control">
                                    <?php foreach (['active'=>'Active','inactive'=>'Inactive','on_leave'=>'On Leave'] as $k=>$v): ?>
                                        <option value="<?= $k ?>" <?= $inst['status']==$k?'selected':'' ?>><?= $v ?></option>
                                    <?php endforeach; ?>
                                </select></div>
                            <button type="submit" class="btn btn-primary" style="width:100%;"><i class="fas fa-save"></i> Save Changes</button>
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
