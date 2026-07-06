<?php
// instructor_assign.php - assign an instructor to a training section
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: admin_login.php');
    exit;
}
require_once __DIR__ . '/config/database.php';

$db = Database::getInstance()->getConnection();
$sectionId = intval($_GET['section'] ?? $_POST['section_id'] ?? 0);
$success = '';

$stmt = $db->prepare("SELECT s.*, tt.name AS type_name FROM Training_Section s LEFT JOIN Training_Type tt ON s.training_type_id = tt.training_type_id WHERE s.section_id = ?");
$stmt->execute([$sectionId]);
$section = $stmt->fetch();
if (!$section) { header('Location: admin_training_sections.php'); exit; }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $instr = $_POST['instructor_id'] !== '' ? intval($_POST['instructor_id']) : null;
    $db->prepare("UPDATE Training_Section SET instructor_id = ? WHERE section_id = ?")->execute([$instr, $sectionId]);
    header('Location: admin_training_sections.php');
    exit;
}

$instructors = $db->query("SELECT instructor_id, first_name, last_name FROM Instructor WHERE status = 'active' ORDER BY first_name")->fetchAll();
$active = 'sections';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Assign Instructor - Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>.form-container { max-width: 500px; margin: 0 auto; }</style>
</head>
<body style="background: var(--bg-dark);">
    <div class="main-wrapper">
        <?php include __DIR__ . '/includes/admin_sidebar.php'; ?>
        <main class="main-content" id="mainContent">
            <nav class="top-nav">
                <div class="top-nav-left">
                    <button class="sidebar-toggle" id="sidebarToggle"><i class="fas fa-bars"></i></button>
                    <div><h2>Assign Instructor</h2><div class="breadcrumb">Admin / Sections / <span>Assign</span></div></div>
                </div>
            </nav>
            <div class="form-container animate-fade-in">
                <div class="card">
                    <div class="card-header"><h5><i class="fas fa-user-tag" style="color:var(--primary-light);"></i> <?= htmlspecialchars($section['type_name'] ?: 'Section') ?> &mdash; <?= htmlspecialchars($section['day_of_week']) ?></h5></div>
                    <div class="card-body">
                        <?php if (empty($instructors)): ?>
                            <p style="color:var(--text-muted);">No active instructors available. <a href="add_instructor.php" style="color:var(--primary-light);">Add one first.</a></p>
                        <?php else: ?>
                        <form method="POST" action="instructor_assign.php?section=<?= $sectionId ?>">
                            <input type="hidden" name="section_id" value="<?= $sectionId ?>">
                            <div class="form-group"><label class="form-label">Instructor</label>
                                <select name="instructor_id" class="form-control">
                                    <option value="">— Unassigned —</option>
                                    <?php foreach ($instructors as $i): ?>
                                        <option value="<?= $i['instructor_id'] ?>" <?= $section['instructor_id']==$i['instructor_id']?'selected':'' ?>><?= htmlspecialchars($i['first_name'].' '.$i['last_name']) ?></option>
                                    <?php endforeach; ?>
                                </select></div>
                            <button type="submit" class="btn btn-primary" style="width:100%;"><i class="fas fa-save"></i> Assign</button>
                        </form>
                        <?php endif; ?>
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
