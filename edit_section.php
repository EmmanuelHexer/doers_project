<?php
// edit_section.php
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: admin_login.php');
    exit;
}
require_once __DIR__ . '/config/database.php';

$db = Database::getInstance()->getConnection();
$id = intval($_GET['id'] ?? $_POST['section_id'] ?? 0);
$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $typeId = intval($_POST['training_type_id'] ?? 0);
    $instrId = $_POST['instructor_id'] !== '' ? intval($_POST['instructor_id']) : null;
    $day = $_POST['day_of_week'] ?? '';
    $start = $_POST['start_time'] ?? '';
    $end = $_POST['end_time'] ?? '';
    $capacity = intval($_POST['capacity'] ?? 20);
    $status = $_POST['status'] ?? 'active';

    if ($typeId > 0 && $day !== '' && $start !== '' && $end !== '') {
        $db->prepare("UPDATE Training_Section SET training_type_id=?, instructor_id=?, day_of_week=?, start_time=?, end_time=?, capacity=?, status=? WHERE section_id=?")
           ->execute([$typeId, $instrId, $day, $start, $end, $capacity, $status, $id]);
        $success = 'Section updated successfully.';
    } else {
        $error = 'Please fill in all required fields.';
    }
}

$stmt = $db->prepare("SELECT * FROM Training_Section WHERE section_id = ?");
$stmt->execute([$id]);
$section = $stmt->fetch();
if (!$section) { header('Location: admin_training_sections.php'); exit; }

$trainingTypes = $db->query("SELECT training_type_id, name FROM Training_Type ORDER BY name")->fetchAll();
$instructors = $db->query("SELECT instructor_id, first_name, last_name FROM Instructor ORDER BY first_name")->fetchAll();
$days = ['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'];
$active = 'sections';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Section - Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css?v=2">
    <style>.form-container { max-width: 560px; margin: 0 auto; }</style>
</head>
<body style="background: var(--bg-dark);">
    <div class="main-wrapper">
        <?php include __DIR__ . '/includes/admin_sidebar.php'; ?>
        <main class="main-content" id="mainContent">
            <nav class="top-nav">
                <div class="top-nav-left">
                    <button class="sidebar-toggle" id="sidebarToggle"><i class="fas fa-bars"></i></button>
                    <div><h2>Edit Section</h2><div class="breadcrumb">Admin / Sections / <span>Edit</span></div></div>
                </div>
            </nav>
            <div class="form-container animate-fade-in">
                <div class="card">
                    <div class="card-header"><h5><i class="fas fa-calendar-alt" style="color:var(--primary-light);"></i> Edit Training Section</h5></div>
                    <div class="card-body">
                        <?php if ($success): ?><div class="alert alert-success"><i class="fas fa-check-circle"></i> <?= htmlspecialchars($success) ?></div><?php endif; ?>
                        <?php if ($error): ?><div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?></div><?php endif; ?>
                        <form method="POST" action="edit_section.php?id=<?= $section['section_id'] ?>">
                            <input type="hidden" name="section_id" value="<?= $section['section_id'] ?>">
                            <div class="form-group"><label class="form-label">Training Type *</label>
                                <select name="training_type_id" class="form-control" required>
                                    <?php foreach ($trainingTypes as $tt): ?>
                                        <option value="<?= $tt['training_type_id'] ?>" <?= $section['training_type_id']==$tt['training_type_id']?'selected':'' ?>><?= htmlspecialchars($tt['name']) ?></option>
                                    <?php endforeach; ?>
                                </select></div>
                            <div class="form-group"><label class="form-label">Instructor</label>
                                <select name="instructor_id" class="form-control">
                                    <option value="">— Unassigned —</option>
                                    <?php foreach ($instructors as $i): ?>
                                        <option value="<?= $i['instructor_id'] ?>" <?= $section['instructor_id']==$i['instructor_id']?'selected':'' ?>><?= htmlspecialchars($i['first_name'].' '.$i['last_name']) ?></option>
                                    <?php endforeach; ?>
                                </select></div>
                            <div class="form-group"><label class="form-label">Day of Week *</label>
                                <select name="day_of_week" class="form-control" required>
                                    <?php foreach ($days as $d): ?>
                                        <option value="<?= $d ?>" <?= $section['day_of_week']==$d?'selected':'' ?>><?= $d ?></option>
                                    <?php endforeach; ?>
                                </select></div>
                            <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px;">
                                <div class="form-group"><label class="form-label">Start Time *</label><input type="time" name="start_time" class="form-control" value="<?= htmlspecialchars(substr($section['start_time'],0,5)) ?>" required></div>
                                <div class="form-group"><label class="form-label">End Time *</label><input type="time" name="end_time" class="form-control" value="<?= htmlspecialchars(substr($section['end_time'],0,5)) ?>" required></div>
                            </div>
                            <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px;">
                                <div class="form-group"><label class="form-label">Capacity</label><input type="number" name="capacity" class="form-control" value="<?= intval($section['capacity']) ?>"></div>
                                <div class="form-group"><label class="form-label">Status</label>
                                    <select name="status" class="form-control">
                                        <?php foreach (['active'=>'Active','cancelled'=>'Cancelled','full'=>'Full'] as $k=>$v): ?>
                                            <option value="<?= $k ?>" <?= $section['status']==$k?'selected':'' ?>><?= $v ?></option>
                                        <?php endforeach; ?>
                                    </select></div>
                            </div>
                            <button type="submit" class="btn btn-primary" style="width:100%;"><i class="fas fa-save"></i> Save Changes</button>
                        </form>
                    </div>
                </div>
                <div style="text-align:center; margin-top:16px;"><a href="admin_training_sections.php" style="color:var(--text-muted);"><i class="fas fa-arrow-left"></i> Back to Sections</a></div>
            </div>
            <div class="footer">&copy; <?= date('Y') ?> USTED-K Gym Center</div>
        </main>
    </div>
    <script src="assets/js/script.js"></script>
    <script>document.getElementById('sidebarToggle').addEventListener('click',function(){document.getElementById('sidebar').classList.toggle('open');});</script>
</body>
</html>
