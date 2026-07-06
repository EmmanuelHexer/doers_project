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

// Get available sections
$sql = "SELECT ts.*, tt.name as training_name, 
        CONCAT(i.first_name, ' ', i.last_name) as instructor_name,
        (SELECT COUNT(*) FROM Member_Section WHERE section_id = ts.section_id AND status = 'active') as enrolled_count
        FROM Training_Section ts
        JOIN Training_Type tt ON ts.training_type_id = tt.training_type_id
        JOIN Instructor i ON ts.instructor_id = i.instructor_id
        WHERE ts.status = 'active' 
        AND (SELECT COUNT(*) FROM Member_Section WHERE section_id = ts.section_id AND status = 'active') < ts.capacity
        AND ts.section_id NOT IN (SELECT section_id FROM Member_Section WHERE member_id = ? AND status = 'active')
        ORDER BY FIELD(ts.day_of_week, 'Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'), ts.start_time";
$stmt = $db->prepare($sql);
$stmt->execute([$member_id]);
$sections = $stmt->fetchAll();

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['section_id'])) {
    $sectionId = intval($_POST['section_id']);
    
    // Check if already enrolled
    $check = $db->prepare("SELECT COUNT(*) FROM Member_Section WHERE member_id = ? AND section_id = ? AND status = 'active'");
    $check->execute([$member_id, $sectionId]);
    if ($check->fetchColumn() > 0) {
        $error = 'You are already enrolled in this session.';
    } else {
        // Check capacity
        $capCheck = $db->prepare("SELECT capacity, (SELECT COUNT(*) FROM Member_Section WHERE section_id = ? AND status = 'active') as enrolled 
                                  FROM Training_Section WHERE section_id = ?");
        $capCheck->execute([$sectionId, $sectionId]);
        $capData = $capCheck->fetch();
        if ($capData && $capData['enrolled'] >= $capData['capacity']) {
            $error = 'This session is full.';
        } else {
            $insert = $db->prepare("INSERT INTO Member_Section (member_id, section_id, enrollment_date, status) VALUES (?, ?, CURDATE(), 'active')");
            if ($insert->execute([$member_id, $sectionId])) {
                $success = 'Session booked successfully!';
                // Refresh available sections
                $stmt->execute([$member_id]);
                $sections = $stmt->fetchAll();
            } else {
                $error = 'Booking failed. Please try again.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Book Session - USTED-K Gym</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Montserrat:wght@700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css?v=2">
    <style>
        .section-card {
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
        .section-card:hover {
            transform: translateY(-4px) scale(1.01);
            border-color: var(--primary);
            box-shadow: var(--shadow-md);
        }
        .section-card .info h4 { margin: 0 0 4px; font-size: 1rem; }
        .section-card .info p { margin: 0; font-size: 0.8rem; color: var(--text-muted); }
        .section-card .meta {
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
        .btn-book {
            background: var(--primary-gradient);
            color: white;
            padding: 8px 20px;
            border: none;
            border-radius: var(--radius-md);
            font-weight: 600;
            cursor: pointer;
            transition: all var(--transition-base);
        }
        .btn-book:hover {
            transform: scale(1.05);
            box-shadow: var(--shadow-lg);
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
        <?php $active = 'book'; include __DIR__ . '/includes/member_sidebar.php'; ?>

        <main class="main-content" id="mainContent">
            <nav class="top-nav">
                <div class="top-nav-left">
                    <button class="sidebar-toggle" id="sidebarToggle">
                        <i class="fas fa-bars"></i>
                    </button>
                    <div>
                        <h2>Book a Session</h2>
                        <div class="breadcrumb">Home / <span>Book Session</span></div>
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

            <?php if ($success): ?>
                <div class="alert alert-success animate-fade-in">
                    <i class="fas fa-check-circle"></i> <?= htmlspecialchars($success) ?>
                    <a href="my_sessions.php" style="color: var(--primary-light); font-weight: 600; margin-left: 12px;">View My Sessions →</a>
                </div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="alert alert-danger animate-fade-in">
                    <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <div class="card animate-fade-in">
                <div class="card-header">
                    <h5><i class="fas fa-calendar-check" style="color: var(--primary-light);"></i> Available Sessions</h5>
                    <span style="font-size: 0.75rem; color: var(--text-muted);">Book a session with our expert trainers</span>
                </div>
                <div class="card-body" style="padding: 16px 20px;">
                    <?php if (empty($sections)): ?>
                        <div class="empty-state">
                            <i class="fas fa-calendar-times"></i>
                            <h4>No Available Sessions</h4>
                            <p>All sessions are fully booked or you're already enrolled in all available sessions.</p>
                            <a href="dashboard.php" class="btn btn-primary" style="margin-top: 12px;">Go to Dashboard</a>
                        </div>
                    <?php else: ?>
                        <?php foreach ($sections as $section): ?>
                            <div class="section-card animate-fade-in">
                                <div class="info">
                                    <h4><?= htmlspecialchars($section['training_name']) ?></h4>
                                    <p>
                                        <i class="fas fa-user"></i> <?= htmlspecialchars($section['instructor_name']) ?>
                                        <span style="margin: 0 8px;">•</span>
                                        <i class="fas fa-users"></i> <?= $section['enrolled_count'] ?? 0 ?> / <?= $section['capacity'] ?> enrolled
                                    </p>
                                </div>
                                <div class="meta">
                                    <span class="day-badge"><i class="fas fa-calendar-day"></i> <?= $section['day_of_week'] ?></span>
                                    <span class="time-badge"><i class="fas fa-clock"></i> <?= date('h:i A', strtotime($section['start_time'])) ?></span>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="section_id" value="<?= $section['section_id'] ?>">
                                        <button type="submit" class="btn-book">
                                            <i class="fas fa-check"></i> Book Now
                                        </button>
                                    </form>
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