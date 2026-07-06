<?php
// view_instructor.php
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: admin_login.php');
    exit;
}
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

$db = Database::getInstance()->getConnection();
$id = intval($_GET['id'] ?? 0);

$stmt = $db->prepare("SELECT * FROM Instructor WHERE instructor_id = ?");
$stmt->execute([$id]);
$inst = $stmt->fetch();
if (!$inst) { header('Location: admin_instructors.php'); exit; }

$tt = $db->prepare("SELECT tt.name FROM Instructor_Train_Type itt JOIN Training_Type tt ON itt.training_type_id = tt.training_type_id WHERE itt.instructor_id = ?");
$tt->execute([$id]);
$trainingTypes = $tt->fetchAll(PDO::FETCH_COLUMN);

$sec = $db->prepare("SELECT s.day_of_week, s.start_time, s.end_time, tt.name AS type_name
                     FROM Training_Section s LEFT JOIN Training_Type tt ON s.training_type_id = tt.training_type_id
                     WHERE s.instructor_id = ? ORDER BY FIELD(s.day_of_week,'Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday')");
$sec->execute([$id]);
$sections = $sec->fetchAll();
$active = 'instructors';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Instructor Details - Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css?v=2">
    <style>
        .detail-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 16px; }
        .detail-item { padding: 12px 16px; background: var(--bg-input); border-radius: var(--radius-md); }
        .detail-item .k { font-size: 0.72rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.5px; }
        .detail-item .v { font-weight: 600; margin-top: 2px; }
        .chip { display:inline-block; padding:4px 12px; border-radius:20px; background:rgba(255,107,0,0.15); color:var(--primary-light); font-size:0.8rem; margin:2px; }
        @media (max-width: 600px) { .detail-grid { grid-template-columns: 1fr; } }
    </style>
</head>
<body style="background: var(--bg-dark);">
    <div class="main-wrapper">
        <?php include __DIR__ . '/includes/admin_sidebar.php'; ?>
        <main class="main-content" id="mainContent">
            <nav class="top-nav">
                <div class="top-nav-left">
                    <button class="sidebar-toggle" id="sidebarToggle"><i class="fas fa-bars"></i></button>
                    <div><h2>Instructor Details</h2><div class="breadcrumb">Admin / Instructors / <span>View</span></div></div>
                </div>
            </nav>
            <div class="page-header" style="display:flex; justify-content:space-between; align-items:center; margin-bottom:24px;">
                <h2 style="font-size:1.5rem;"><?= htmlspecialchars($inst['first_name'].' '.$inst['last_name']) ?>
                    <span class="status-badge <?= $inst['status']==='active'?'approved':'suspended' ?>" style="margin-left:8px;"><?= ucfirst(str_replace('_',' ',$inst['status'])) ?></span>
                </h2>
                <div style="display:flex; gap:8px;">
                    <a href="edit_instructor.php?id=<?= $inst['instructor_id'] ?>" class="btn btn-primary" style="padding:8px 16px;"><i class="fas fa-edit"></i> Edit</a>
                    <a href="assign_training.php?id=<?= $inst['instructor_id'] ?>" class="btn" style="padding:8px 16px; background:rgba(253,203,110,0.15); color:#FDCB6E;"><i class="fas fa-tags"></i> Training Types</a>
                </div>
            </div>
            <div class="card animate-fade-in">
                <div class="card-header"><h5><i class="fas fa-id-badge" style="color:var(--primary-light);"></i> Profile</h5></div>
                <div class="card-body">
                    <div class="detail-grid">
                        <div class="detail-item"><div class="k">Email</div><div class="v"><?= htmlspecialchars($inst['email']) ?></div></div>
                        <div class="detail-item"><div class="k">Telephone</div><div class="v"><?= htmlspecialchars($inst['telephone'] ?: 'N/A') ?></div></div>
                        <div class="detail-item"><div class="k">Specialization</div><div class="v"><?= htmlspecialchars($inst['specialization'] ?: 'N/A') ?></div></div>
                        <div class="detail-item"><div class="k">Hire Date</div><div class="v"><?= $inst['hire_date'] ? formatDate($inst['hire_date']) : 'N/A' ?></div></div>
                    </div>
                    <div style="margin-top:16px;">
                        <div class="k" style="font-size:0.72rem; color:var(--text-muted); text-transform:uppercase; margin-bottom:6px;">Training Types</div>
                        <?php if (empty($trainingTypes)): ?><span style="color:var(--text-muted);">None assigned</span>
                        <?php else: foreach ($trainingTypes as $t): ?><span class="chip"><?= htmlspecialchars($t) ?></span><?php endforeach; endif; ?>
                    </div>
                </div>
            </div>
            <div class="card animate-fade-in" style="margin-top:20px;">
                <div class="card-header"><h5><i class="fas fa-calendar-alt" style="color:var(--primary-light);"></i> Assigned Sections</h5></div>
                <div class="card-body">
                    <?php if (empty($sections)): ?><p style="color:var(--text-muted);">No sections assigned.</p>
                    <?php else: ?>
                        <div class="table-wrapper"><table class="table">
                            <thead><tr><th>Day</th><th>Time</th><th>Training Type</th></tr></thead>
                            <tbody>
                            <?php foreach ($sections as $s): ?>
                                <tr><td><?= htmlspecialchars($s['day_of_week']) ?></td>
                                    <td><?= date('g:i A', strtotime($s['start_time'])) ?> - <?= date('g:i A', strtotime($s['end_time'])) ?></td>
                                    <td><?= htmlspecialchars($s['type_name'] ?: 'N/A') ?></td></tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table></div>
                    <?php endif; ?>
                </div>
            </div>
            <div style="text-align:center; margin-top:16px;"><a href="admin_instructors.php" style="color:var(--text-muted);"><i class="fas fa-arrow-left"></i> Back to Instructors</a></div>
            <div class="footer">&copy; <?= date('Y') ?> USTED-K Gym Center</div>
        </main>
    </div>
    <script src="assets/js/script.js"></script>
    <script>document.getElementById('sidebarToggle').addEventListener('click',function(){document.getElementById('sidebar').classList.toggle('open');});</script>
</body>
</html>
