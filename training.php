<?php
// training.php - My Training: the member's weekly schedule + assigned instructor/plan
session_start();
if (!isset($_SESSION['member_logged_in']) || $_SESSION['member_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

$db = Database::getInstance()->getConnection();
$member_id = $_SESSION['member_id'];

// Membership + assigned instructor
$stmt = $db->prepare("SELECT m.*, mt.name AS membership_name, mt.benefits, mt.duration_months,
                             CONCAT(i.first_name,' ',i.last_name) AS instructor_name, i.specialization
                      FROM Members m
                      LEFT JOIN Membership_Type mt ON m.membership_type_id = mt.membership_type_id
                      LEFT JOIN Instructor i ON m.assigned_instructor_id = i.instructor_id
                      WHERE m.member_id = ?");
$stmt->execute([$member_id]);
$member = $stmt->fetch();
if (!$member) { session_destroy(); header('Location: login.php'); exit; }

// Enrolled active sessions, grouped by day
$stmt = $db->prepare("SELECT ts.day_of_week, ts.start_time, ts.end_time, ts.capacity,
                             tt.name AS training_name, tt.difficulty, tt.duration_minutes,
                             CONCAT(i.first_name,' ',i.last_name) AS instructor_name
                      FROM Member_Section ms
                      JOIN Training_Section ts ON ms.section_id = ts.section_id
                      JOIN Training_Type tt ON ts.training_type_id = tt.training_type_id
                      LEFT JOIN Instructor i ON ts.instructor_id = i.instructor_id
                      WHERE ms.member_id = ? AND ms.status = 'active' AND ts.status = 'active'
                      ORDER BY FIELD(ts.day_of_week,'Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'), ts.start_time");
$stmt->execute([$member_id]);
$rows = $stmt->fetchAll();

$days = ['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'];
$byDay = array_fill_keys($days, []);
foreach ($rows as $r) { $byDay[$r['day_of_week']][] = $r; }

$active = 'training';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Training - USTED-K Gym</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Montserrat:wght@700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css?v=2">
    <style>
        .info-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 16px; margin-bottom: 24px; }
        .info-card { background: var(--bg-card); border: 1px solid var(--border-color); border-radius: var(--radius-md); padding: 18px 20px; }
        .info-card .k { color: var(--text-muted); font-size: 0.72rem; text-transform: uppercase; letter-spacing: 0.5px; }
        .info-card .v { font-weight: 700; font-size: 1.05rem; margin-top: 4px; }
        .week-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 16px; }
        .day-col { background: var(--bg-card); border: 1px solid var(--border-color); border-radius: var(--radius-md); overflow: hidden; }
        .day-col .day-head { padding: 10px 14px; font-weight: 700; background: rgba(255,107,0,0.1); color: var(--primary-light); font-size: 0.85rem; }
        .day-col .day-body { padding: 12px 14px; min-height: 60px; }
        .cls { background: rgba(255,255,255,0.03); border-radius: 8px; padding: 10px; margin-bottom: 8px; }
        .cls:last-child { margin-bottom: 0; }
        .cls h6 { font-size: 0.85rem; font-weight: 600; }
        .cls p { font-size: 0.72rem; color: var(--text-muted); margin-top: 2px; }
        .cls .diff { display:inline-block; margin-top:6px; padding:2px 8px; border-radius:6px; font-size:0.65rem; background:rgba(255,107,0,0.15); color:var(--primary-light); text-transform:capitalize; }
        .day-empty { color: var(--text-muted); font-size: 0.75rem; font-style: italic; }
    </style>
</head>
<body style="background: var(--bg-dark);">
    <div class="main-wrapper">
        <?php include __DIR__ . '/includes/member_sidebar.php'; ?>
        <main class="main-content" id="mainContent">
            <nav class="top-nav">
                <div class="top-nav-left">
                    <button class="sidebar-toggle" id="sidebarToggle"><i class="fas fa-bars"></i></button>
                    <div><h2>My Training</h2><div class="breadcrumb">Home / <span>My Training</span></div></div>
                </div>
                <div class="top-nav-right">
                    <div class="profile-dropdown">
                        <div class="avatar-initial"><?= strtoupper(substr($_SESSION['member_username'] ?? 'U', 0, 1)) ?></div>
                        <div class="info"><div class="name"><?= htmlspecialchars($_SESSION['member_name']) ?></div><div class="role">Member</div></div>
                    </div>
                </div>
            </nav>

            <div class="info-grid animate-fade-in">
                <div class="info-card"><div class="k">Membership Plan</div><div class="v"><?= htmlspecialchars($member['membership_name'] ?? 'None') ?></div></div>
                <div class="info-card"><div class="k">Assigned Instructor</div><div class="v"><?= htmlspecialchars($member['instructor_name'] ?? 'Not Assigned') ?></div></div>
                <div class="info-card"><div class="k">Enrolled Classes</div><div class="v"><?= count($rows) ?></div></div>
                <div class="info-card"><div class="k">Membership Ends</div><div class="v"><?= !empty($member['membership_end_date']) ? formatDate($member['membership_end_date']) : 'N/A' ?></div></div>
            </div>

            <?php if (!empty($member['benefits'])): ?>
            <div class="card animate-fade-in" style="margin-bottom:24px;">
                <div class="card-header"><h5><i class="fas fa-gift" style="color:var(--primary-light);"></i> Plan Benefits</h5></div>
                <div class="card-body"><p style="color:var(--text-secondary);"><?= nl2br(htmlspecialchars($member['benefits'])) ?></p></div>
            </div>
            <?php endif; ?>

            <div class="page-header" style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px;">
                <h3 style="font-size:1.15rem;">Weekly Schedule</h3>
                <a href="book_session.php" class="btn btn-primary" style="padding:8px 16px;"><i class="fas fa-calendar-plus"></i> Book More</a>
            </div>

            <?php if (empty($rows)): ?>
                <div class="card animate-fade-in"><div class="card-body" style="text-align:center; padding:40px 20px; color:var(--text-muted);">
                    <i class="fas fa-calendar-plus" style="font-size:3rem; color:var(--primary-light); display:block; margin-bottom:16px;"></i>
                    <h4>No Training Sessions Yet</h4>
                    <p style="margin:8px 0 16px;">You haven't enrolled in any classes. Book your first session to build your schedule.</p>
                    <a href="book_session.php" class="btn btn-primary"><i class="fas fa-calendar-plus"></i> Book a Session</a>
                </div></div>
            <?php else: ?>
                <div class="week-grid animate-fade-in">
                    <?php foreach ($days as $day): if (empty($byDay[$day])) continue; ?>
                        <div class="day-col">
                            <div class="day-head"><?= $day ?></div>
                            <div class="day-body">
                                <?php foreach ($byDay[$day] as $c): ?>
                                    <div class="cls">
                                        <h6><?= htmlspecialchars($c['training_name']) ?></h6>
                                        <p><i class="fas fa-clock"></i> <?= date('g:i A', strtotime($c['start_time'])) ?> - <?= date('g:i A', strtotime($c['end_time'])) ?></p>
                                        <p><i class="fas fa-user"></i> <?= htmlspecialchars($c['instructor_name'] ?? 'TBA') ?></p>
                                        <span class="diff"><?= htmlspecialchars($c['difficulty']) ?></span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <div class="footer">&copy; <?= date('Y') ?> USTED-K Gym Center</div>
        </main>
    </div>
    <script src="assets/js/script.js"></script>
    <script>document.getElementById('sidebarToggle').addEventListener('click',function(){document.getElementById('sidebar').classList.toggle('open');});</script>
</body>
</html>
