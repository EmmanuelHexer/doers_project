<?php
// edit_member.php - edit member details
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: admin_login.php');
    exit;
}
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

$db = Database::getInstance()->getConnection();
$id = intval($_GET['id'] ?? $_POST['member_id'] ?? 0);
$error = '';
$success = '';

$membershipTypes = $db->query("SELECT membership_type_id, name FROM Membership_Type ORDER BY name")->fetchAll();
$instructors = $db->query("SELECT instructor_id, first_name, last_name FROM Instructor ORDER BY first_name")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first = trim($_POST['first_name'] ?? '');
    $last  = trim($_POST['last_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $tel   = trim($_POST['telephone'] ?? '');
    $health = trim($_POST['health_status'] ?? '');
    $mtype = $_POST['membership_type_id'] !== '' ? intval($_POST['membership_type_id']) : null;
    $instr = $_POST['assigned_instructor_id'] !== '' ? intval($_POST['assigned_instructor_id']) : null;
    $status = $_POST['status'] ?? 'pending';

    if ($first === '' || $last === '' || $email === '') {
        $error = 'First name, last name and email are required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } else {
        $sql = "UPDATE Members SET first_name=?, last_name=?, email=?, telephone=?, health_status=?,
                       membership_type_id=?, assigned_instructor_id=?, status=? WHERE member_id=?";
        try {
            $db->prepare($sql)->execute([$first, $last, $email, $tel, $health, $mtype, $instr, $status, $id]);
            $success = 'Member updated successfully.';
        } catch (PDOException $e) {
            $error = (strpos($e->getMessage(), 'Duplicate') !== false)
                ? 'That email is already used by another member.'
                : 'Update failed. Please try again.';
        }
    }
}

$stmt = $db->prepare("SELECT * FROM Members WHERE member_id = ?");
$stmt->execute([$id]);
$member = $stmt->fetch();
if (!$member) { header('Location: admin_members.php'); exit; }

$active = 'members';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Member - Admin</title>
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
                    <div><h2>Edit Member</h2><div class="breadcrumb">Admin / Members / <span>Edit</span></div></div>
                </div>
            </nav>

            <div class="form-container animate-fade-in">
                <div class="card">
                    <div class="card-header"><h5><i class="fas fa-user-edit" style="color:var(--primary-light);"></i> <?= htmlspecialchars($member['first_name'].' '.$member['last_name']) ?></h5></div>
                    <div class="card-body">
                        <?php if ($success): ?><div class="alert alert-success"><i class="fas fa-check-circle"></i> <?= htmlspecialchars($success) ?></div><?php endif; ?>
                        <?php if ($error): ?><div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?></div><?php endif; ?>

                        <form method="POST" action="edit_member.php?id=<?= $member['member_id'] ?>">
                            <input type="hidden" name="member_id" value="<?= $member['member_id'] ?>">
                            <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px;">
                                <div class="form-group"><label class="form-label">First Name *</label>
                                    <input type="text" name="first_name" class="form-control" value="<?= htmlspecialchars($member['first_name']) ?>" required></div>
                                <div class="form-group"><label class="form-label">Last Name *</label>
                                    <input type="text" name="last_name" class="form-control" value="<?= htmlspecialchars($member['last_name']) ?>" required></div>
                            </div>
                            <div class="form-group"><label class="form-label">Email *</label>
                                <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($member['email']) ?>" required></div>
                            <div class="form-group"><label class="form-label">Telephone</label>
                                <input type="text" name="telephone" class="form-control" value="<?= htmlspecialchars($member['telephone']) ?>"></div>
                            <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px;">
                                <div class="form-group"><label class="form-label">Membership Type</label>
                                    <select name="membership_type_id" class="form-control">
                                        <option value="">None</option>
                                        <?php foreach ($membershipTypes as $mt): ?>
                                            <option value="<?= $mt['membership_type_id'] ?>" <?= $member['membership_type_id']==$mt['membership_type_id']?'selected':'' ?>><?= htmlspecialchars($mt['name']) ?></option>
                                        <?php endforeach; ?>
                                    </select></div>
                                <div class="form-group"><label class="form-label">Assigned Instructor</label>
                                    <select name="assigned_instructor_id" class="form-control">
                                        <option value="">None</option>
                                        <?php foreach ($instructors as $inst): ?>
                                            <option value="<?= $inst['instructor_id'] ?>" <?= $member['assigned_instructor_id']==$inst['instructor_id']?'selected':'' ?>><?= htmlspecialchars($inst['first_name'].' '.$inst['last_name']) ?></option>
                                        <?php endforeach; ?>
                                    </select></div>
                            </div>
                            <div class="form-group"><label class="form-label">Status</label>
                                <select name="status" class="form-control">
                                    <?php foreach (['pending','approved','suspended','expired'] as $s): ?>
                                        <option value="<?= $s ?>" <?= $member['status']==$s?'selected':'' ?>><?= ucfirst($s) ?></option>
                                    <?php endforeach; ?>
                                </select></div>
                            <div class="form-group"><label class="form-label">Health Status</label>
                                <textarea name="health_status" class="form-control" rows="2"><?= htmlspecialchars($member['health_status']) ?></textarea></div>
                            <button type="submit" class="btn btn-primary" style="width:100%;"><i class="fas fa-save"></i> Save Changes</button>
                        </form>
                    </div>
                </div>
                <div style="text-align:center; margin-top:16px;">
                    <a href="admin_members.php" style="color:var(--text-muted);"><i class="fas fa-arrow-left"></i> Back to Members</a>
                </div>
            </div>
            <div class="footer">&copy; <?= date('Y') ?> USTED-K Gym Center</div>
        </main>
    </div>
    <script src="assets/js/script.js"></script>
    <script>document.getElementById('sidebarToggle').addEventListener('click',function(){document.getElementById('sidebar').classList.toggle('open');});</script>
</body>
</html>
