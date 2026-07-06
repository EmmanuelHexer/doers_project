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

// Get all training types
$trainingTypes = $db->query("SELECT * FROM Training_Type WHERE status = 'active'")->fetchAll();

// Get member's enrolled sessions
$sql = "SELECT ts.*, tt.name as training_name, 
        CONCAT(i.first_name, ' ', i.last_name) as instructor_name
        FROM Member_Section ms
        JOIN Training_Section ts ON ms.section_id = ts.section_id
        JOIN Training_Type tt ON ts.training_type_id = tt.training_type_id
        JOIN Instructor i ON ts.instructor_id = i.instructor_id
        WHERE ms.member_id = ? AND ms.status = 'active'
        ORDER BY FIELD(ts.day_of_week, 'Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'), ts.start_time";
$stmt = $db->prepare($sql);
$stmt->execute([$member_id]);
$mySessions = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html>
<head>
    <title>My Training - USTED-K Gym</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Montserrat:wght@700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css?v=2">
    <style>
        .training-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 16px;
            margin-bottom: 24px;
        }
        .training-card {
            background: var(--bg-card);
            padding: 20px;
            border-radius: var(--radius-md);
            border: 1px solid var(--border-color);
            text-align: center;
            transition: all var(--transition-base);
        }
        .training-card:hover {
            transform: translateY(-6px) scale(1.02);
            border-color: var(--primary);
            box-shadow: var(--shadow-md);
        }
        .training-card .icon {
            font-size: 2rem;
            color: var(--primary-light);
            margin-bottom: 8px;
        }
        .training-card h4 { font-size: 0.95rem; margin-bottom: 4px; }
        .training-card p { color: var(--text-muted); font-size: 0.75rem; }
        .training-card .difficulty {
            display: inline-block;
            padding: 2px 12px;
            border-radius: var(--radius-full);
            font-size: 0.65rem;
            font-weight: 600;
        }
        .difficulty.beginner { background: rgba(0,184,148,0.15); color: #00B894; }
        .difficulty.intermediate { background: rgba(253,203,110,0.15); color: #FDCB6E; }
        .difficulty.advanced { background: rgba(255,107,0,0.15); color: var(--primary-light); }
        .difficulty.expert { background: rgba(255,68,68,0.15); color: #FF6B6B; }
        .session-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 0;
            border-bottom: 1px solid var(--border-color);
        }
        .session-item:last-child { border-bottom: none; }
        .session-item .info h6 { font-size: 0.875rem; font-weight: 600; }
        .session-item .info p { font-size: 0.75rem; color: var(--text-muted); margin: 0; }
        .session-item .time { font-size: 0.75rem; color: var(--text-muted); text-align: right; }
        .session-item .time strong { color: var(--text-primary); }
    </style>
</head>
<body>
    <div class="main-wrapper">
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-brand">
                <img src="assets/images/logo.png" alt="Logo" onerror="this.style.display='none'">
                <span>USTED-K Gym</span>
            </div>
            <nav class="sidebar-menu">
                <div class="sidebar-menu-label">Main</div>
                <a href="dashboard.php" class="sidebar-item">
                    <i class="fas fa-th-large"></i> Dashboard
                </a>
                <a href="profile.php" class="sidebar-item">
                    <i class="fas fa-user"></i> My Profile
                </a>
                <a href="training.php" class="sidebar-item active">
                    <i class="fas fa-calendar-alt"></i> My Training
                </a>
                <a href="payments.php" class="sidebar-item">
                    <i class="fas fa-credit-card"></i> Payments
                </a>
                <a href="book_session.php" class="sidebar-item">
                    <i class="fas fa-calendar-plus"></i> Book Session
                </a>
                <a href="my_sessions.php" class="sidebar-item">
                    <i class="fas fa-list"></i> My Sessions
                </a>
                <a href="logout.php" class="sidebar-item" style="color: #FF6B6B; margin-top: 20px;">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </nav>
        </aside>

        <main class="main-content" id="mainContent">
            <nav class="top-nav">
                <div class="top-nav-left">
                    <button class="sidebar-toggle" id="sidebarToggle">
                        <i class="fas fa-bars"></i>
                    </button>
                    <div>
                        <h2>My Training</h2>
                        <div class="breadcrumb">Home / <span>Training</span></div>
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

            <div class="card animate-fade-in" style="margin-bottom: 24px;">
                <div class="card-header">
                    <h5><i class="fas fa-dumbbell" style="color: var(--primary-light);"></i> Available Training Programs</h5>
                </div>
                <div class="card-body">
                    <div class="training-grid stagger-children">
                        <?php foreach ($trainingTypes as $type): ?>
                            <div class="training-card">
                                <div class="icon"><i class="fas fa-dumbbell"></i></div>
                                <h4><?= htmlspecialchars($type['name']) ?></h4>
                                <p><?= htmlspecialchars($type['description'] ?? '') ?></p>
                                <span class="difficulty <?= $type['difficulty'] ?>"><?= ucfirst($type['difficulty']) ?></span>
                                <p style="margin-top: 4px; font-size: 0.7rem;"><?= $type['duration_minutes'] ?> min</p>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <div class="card animate-fade-in">
                <div class="card-header">
                    <h5><i class="fas fa-calendar-check" style="color: var(--primary-light);"></i> My Enrolled Sessions</h5>
                    <a href="book_session.php" style="color: var(--primary-light); font-size: 0.75rem;">
                        <i class="fas fa-plus"></i> Book More
                    </a>
                </div>
                <div class="card-body" style="padding: 16px 20px;">
                    <?php if (empty($mySessions)): ?>
                        <p style="color: var(--text-muted); text-align: center; padding: 20px 0;">
                            <i class="fas fa-calendar-plus" style="font-size: 2rem; display: block; margin-bottom: 8px;"></i>
                            You are not enrolled in any sessions.
                            <br><a href="book_session.php" style="color: var(--primary-light);">Book a session now</a>
                        </p>
                    <?php else: ?>
                        <?php foreach ($mySessions as $session): ?>
                            <div class="session-item">
                                <div class="info">
                                    <h6><?= htmlspecialchars($session['training_name']) ?></h6>
                                    <p>
                                        <i class="fas fa-user"></i> <?= htmlspecialchars($session['instructor_name']) ?>
                                        <span style="margin: 0 8px;">•</span>
                                        <i class="fas fa-users"></i> <?= $session['capacity'] ?> capacity
                                    </p>
                                </div>
                                <div class="time">
                                    <div><strong><?= $session['day_of_week'] ?></strong></div>
                                    <div><?= date('h:i A', strtotime($session['start_time'])) ?></div>
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