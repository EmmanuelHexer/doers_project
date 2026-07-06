<?php
// assign_training.php - assign which training types an instructor can teach
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: admin_login.php');
    exit;
}
require_once __DIR__ . '/config/database.php';

$db = Database::getInstance()->getConnection();
$id = intval($_GET['id'] ?? $_POST['instructor_id'] ?? 0);
$success = '';

$stmt = $db->prepare("SELECT * FROM Instructor WHERE instructor_id = ?");
$stmt->execute([$id]);
$inst = $stmt->fetch();
if (!$inst) { header('Location: admin_instructors.php'); exit; }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $selected = $_POST['types'] ?? [];
    try {
        $db->beginTransaction();
        $db->prepare("DELETE FROM Instructor_Train_Type WHERE instructor_id = ?")->execute([$id]);
        $ins = $db->prepare("INSERT INTO Instructor_Train_Type (instructor_id, training_type_id) VALUES (?, ?)");
        foreach ($selected as $typeId) {
            $ins->execute([$id, intval($typeId)]);
        }
        $db->commit();
        $success = 'Training types updated.';
    } catch (Exception $e) {
        $db->rollBack();
    }
}

$types = $db->query("SELECT training_type_id, name FROM Training_Type ORDER BY name")->fetchAll();
$assigned = $db->prepare("SELECT training_type_id FROM Instructor_Train_Type WHERE instructor_id = ?");
$assigned->execute([$id]);
$assignedIds = $assigned->fetchAll(PDO::FETCH_COLUMN);
$active = 'instructors';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Assign Training Types - Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css?v=2">
    <style>
        .form-container { max-width: 560px; margin: 0 auto; }
        .check-row { display:flex; align-items:center; gap:10px; padding:10px 12px; background:var(--bg-input); border-radius:var(--radius-md); margin-bottom:8px; }
        .check-row input { width:18px; height:18px; accent-color: var(--primary); }
    </style>
</head>
<body style="background: var(--bg-dark);">
    <div class="main-wrapper">
        <?php include __DIR__ . '/includes/admin_sidebar.php'; ?>
        <main class="main-content" id="mainContent">
            <nav class="top-nav">
                <div class="top-nav-left">
                    <button class="sidebar-toggle" id="sidebarToggle"><i class="fas fa-bars"></i></button>
                    <div><h2>Assign Training Types</h2><div class="breadcrumb">Admin / Instructors / <span>Training Types</span></div></div>
                </div>
            </nav>
            <div class="form-container animate-fade-in">
                <div class="card">
                    <div class="card-header"><h5><i class="fas fa-tags" style="color:var(--primary-light);"></i> <?= htmlspecialchars($inst['first_name'].' '.$inst['last_name']) ?></h5></div>
                    <div class="card-body">
                        <?php if ($success): ?><div class="alert alert-success"><i class="fas fa-check-circle"></i> <?= htmlspecialchars($success) ?></div><?php endif; ?>
                        <?php if (empty($types)): ?>
                            <p style="color:var(--text-muted);">No training types exist yet. <a href="admin_training_types.php" style="color:var(--primary-light);">Create some first.</a></p>
                        <?php else: ?>
                        <form method="POST" action="assign_training.php?id=<?= $id ?>">
                            <input type="hidden" name="instructor_id" value="<?= $id ?>">
                            <p style="color:var(--text-muted); margin-bottom:12px;">Select the training types this instructor can teach:</p>
                            <?php foreach ($types as $t): ?>
                                <label class="check-row">
                                    <input type="checkbox" name="types[]" value="<?= $t['training_type_id'] ?>" <?= in_array($t['training_type_id'], $assignedIds) ? 'checked' : '' ?>>
                                    <span><?= htmlspecialchars($t['name']) ?></span>
                                </label>
                            <?php endforeach; ?>
                            <button type="submit" class="btn btn-primary" style="width:100%; margin-top:12px;"><i class="fas fa-save"></i> Save Assignments</button>
                        </form>
                        <?php endif; ?>
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
