<?php
session_start();

if (!isset($_SESSION['member_logged_in']) || $_SESSION['member_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

require_once 'config/database.php';
require_once 'includes/functions.php';

$db = Database::getInstance()->getConnection();
$member_id = $_SESSION['member_id'];

$sql = "SELECT ts.*, ms.status as ms_status, ms.enrollment_date, tt.name as training_name,
        CONCAT(i.first_name, ' ', i.last_name) as instructor_name
        FROM Member_Section ms
        JOIN Training_Section ts ON ms.section_id = ts.section_id
        JOIN Training_Type tt ON ts.training_type_id = tt.training_type_id
        LEFT JOIN Instructor i ON ts.instructor_id = i.instructor_id
        WHERE ms.member_id = ?
        ORDER BY FIELD(ts.day_of_week, 'Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'), ts.start_time";
$stmt = $db->prepare($sql);
$stmt->execute([$member_id]);
$sessions = $stmt->fetchAll();

$activeCount = 0;
$completedCount = 0;
foreach ($sessions as $s) {
    if ($s['ms_status'] == 'active') $activeCount++;
    if ($s['ms_status'] == 'completed') $completedCount++;
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>My Sessions - USTED-K Gym</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Montserrat:wght@700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .stats-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 16px;
            margin-bottom: 24px;
        }
        .stats-row .stat-box {
            background: var(--bg-card);
            padding: 16px 20px;
            border-radius: var(--radius-md);
            border: 1px solid var(--border-color);
            text-align: center;
            transition: all var(--transition-base);
        }
        .stats-row .stat-box:hover {
            transform: translateY(-4px);
            border-color: var(--primary);
            box-shadow: var(--shadow-md);
        }
        .stats-row .stat-box .number {
            font-size: 1.5rem;
            font-weight: 800;
            font-family: var(--font-heading);
            background: var(--primary-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        .stats-row .stat-box .label { color: var(--text-muted); font-size: 0.75rem; }
        .session-card {
            background: var(--bg-card);
            border-radius: var(--radius-md);
            padding: 16px 20px;
            margin-bottom: 12px;
            border: 1px solid var(--border-color);
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: all var(--transition-base);
            flex-wrap: wrap;
            gap: 12px;
        }
        .session-card:hover {
            transform: translateY(-4px) scale(1.01);
            border-color: var(--primary);
            box-shadow: var(--shadow-md);
        }
        .session-card .info h4 { margin: 0 0 4px; font-size: 1rem; }
        .session-card .info p { margin: 0; font-size: 0.8rem; color: var(--text-muted); }
        .session-card .meta {
            display: flex;
            gap: 12px;
            align-items: center;
            flex-wrap: wrap;
        }
        .day-badge {
            background: rgba(255,107,0,0.1);
            padding: 4px 14px;
            border-radius: 9999px;
            font-weight: 600;
            font-size: 0.75rem;
            color: var(--primary-light);
        }
        .time-badge {
            background: rgba(255,255,255,0.05);
            padding: 4px 14px;
            border-radius: 9999px;
            font-size: 0.75rem;
            color: var(--text-secondary);
        }
        .btn-cancel {
            background: rgba(255,68,68,0.15);
            color: #FF6B6B;
            padding: 6px 16px;
            border: none;
            border-radius: var(--radius-md);
            font-weight: 600;
            cursor: pointer;
            transition: all var(--transition-base);
        }
        .btn-cancel:hover {
            background: #FF6B6B;
            color: white;
            transform: scale(1.05);
        }
        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: var(--text-muted);
        }
        .empty-state i { font-size: 3rem; display: block; margin-bottom: 16px; color: var(--primary-light); }
    </style>
</head>
<body>
    <div class="main-wrapper">
        <?php $active = 'sessions'; include __DIR__ . '/includes/member_sidebar.php'; ?>

        <main class="main-content" id="mainContent">
            <nav class="top-nav">
                <div class="top-nav-left">
                    <button class="sidebar-toggle" id="sidebarToggle">
                        <i class="fas fa-bars"></i>
                    </button>
                    <div>
                        <h2>My Sessions</h2>
                        <div class="breadcrumb">Home / <span>My Sessions</span></div>
                    </div>
                </div>
                <div class="top-nav-right">
                    <div class="profile-dropdown">
                        <div class="avatar-initial"><?= strtoupper(substr($_SESSION['member_username'] ?? 'U', 0, 1)) ?></div>
                        <div class="info">
                            <div class="name"><?= htmlspecialchars($_SESSION['member_name']) ?></div>
                            <div class="role">Member</div>
                        </div>
                    </div>
                </div>
            </nav>

            <div class="stats-row animate-fade-in">
                <div class="stat-box">
                    <div class="number"><?= $activeCount ?></div>
                    <div class="label">Active Sessions</div>
                </div>
                <div class="stat-box">
                    <div class="number"><?= $completedCount ?></div>
                    <div class="label">Completed</div>
                </div>
                <div class="stat-box">
                    <div class="number"><?= count($sessions) ?></div>
                    <div class="label">Total Enrolled</div>
                </div>
            </div>

            <div class="card animate-fade-in">
                <div class="card-header">
                    <h5><i class="fas fa-calendar-alt" style="color: var(--primary-light);"></i> All My Sessions</h5>
                    <a href="book_session.php" style="color: var(--primary-light); font-size: 0.75rem;">
                        <i class="fas fa-plus"></i> Book More
                    </a>
                </div>
                <div class="card-body" style="padding: 16px 20px;">
                    <?php if (empty($sessions)): ?>
                        <div class="empty-state">
                            <i class="fas fa-calendar-plus"></i>
                            <h4>No Sessions Yet</h4>
                            <p>You haven't enrolled in any training sessions yet.</p>
                            <a href="book_session.php" class="btn btn-primary" style="margin-top: 12px;">
                                <i class="fas fa-calendar-plus"></i> Book a Session
                            </a>
                        </div>
                    <?php else: ?>
                        <?php foreach ($sessions as $session): ?>
                            <div class="session-card animate-fade-in">
                                <div class="info">
                                    <h4><?= htmlspecialchars($session['training_name']) ?></h4>
                                    <p>
                                        <i class="fas fa-user"></i> <?= htmlspecialchars($session['instructor_name']) ?>
                                        <span style="margin: 0 8px;">•</span>
                                        <span class="status-badge <?= $session['ms_status'] ?>"><?= ucfirst($session['ms_status']) ?></span>
                                    </p>
                                </div>
                                <div class="meta">
                                    <span class="day-badge"><i class="fas fa-calendar-day"></i> <?= $session['day_of_week'] ?></span>
                                    <span class="time-badge"><i class="fas fa-clock"></i> <?= date('h:i A', strtotime($session['start_time'])) ?></span>
                                    <?php if ($session['ms_status'] == 'active'): ?>
                                        <form method="POST" action="cancel_session.php" style="display: inline;" 
                                              onsubmit="return confirm('Cancel this session?')">
                                            <input type="hidden" name="section_id" value="<?= $session['section_id'] ?>">
                                            <button type="submit" class="btn-cancel">
                                                <i class="fas fa-times"></i> Cancel
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <div class="footer">
                &copy; <?= date('Y') ?> USTED-K Gym Center
            </div>
        </main>
    </div>

    <script src="assets/js/script.js"></script>
    <script>
        document.getElementById('sidebarToggle').addEventListener('click', function() {
            document.getElementById('sidebar').classList.toggle('open');
        });
    </script>
</body>
</html>