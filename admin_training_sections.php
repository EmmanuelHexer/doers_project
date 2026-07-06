<?php
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: admin_login.php');
    exit;
}

require_once 'config/database.php';
require_once 'includes/functions.php';

$db = Database::getInstance()->getConnection();

$search = trim($_GET['search'] ?? '');
$sql = "SELECT ts.*, tt.name as training_name,
        CONCAT(i.first_name, ' ', i.last_name) as instructor_name,
        (SELECT COUNT(*) FROM Member_Section WHERE section_id = ts.section_id AND status = 'active') as enrolled_count
        FROM Training_Section ts
        LEFT JOIN Training_Type tt ON ts.training_type_id = tt.training_type_id
        LEFT JOIN Instructor i ON ts.instructor_id = i.instructor_id";
$params = [];
if ($search !== '') {
    $sql .= " WHERE tt.name LIKE ? OR ts.day_of_week LIKE ? OR CONCAT(i.first_name,' ',i.last_name) LIKE ?";
    $params = ["%$search%", "%$search%", "%$search%"];
}
$sql .= " ORDER BY FIELD(ts.day_of_week, 'Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'), ts.start_time";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$sections = $stmt->fetchAll();

// Get dropdown data
$instructors = $db->query("SELECT instructor_id, first_name, last_name FROM Instructor WHERE status = 'active'")->fetchAll();
$trainingTypes = $db->query("SELECT training_type_id, name FROM Training_Type WHERE status = 'active'")->fetchAll();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Training Sections - Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Montserrat:wght@700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
            flex-wrap: wrap;
            gap: 16px;
        }
        .btn-add {
            background: var(--primary-gradient);
            color: white;
            padding: 10px 24px;
            border-radius: var(--radius-md);
            text-decoration: none;
            font-weight: 600;
            transition: all var(--transition-base);
            display: inline-flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            border: none;
        }
        .btn-add:hover {
            transform: translateY(-3px) scale(1.02);
            box-shadow: var(--shadow-lg);
            color: white;
        }
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
        .section-card .section-info h4 { margin: 0 0 4px; font-size: 1rem; }
        .section-card .section-info p { margin: 0; font-size: 0.8rem; color: var(--text-muted); }
        .section-card .section-meta {
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
        .capacity-badge {
            padding: 4px 14px;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        .capacity-badge.active { background: rgba(0,184,148,0.15); color: #00B894; }
        .capacity-badge.full { background: rgba(255,68,68,0.15); color: #FF6B6B; }
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.7);
            backdrop-filter: blur(8px);
            align-items: center;
            justify-content: center;
            z-index: 2000;
            animation: fadeIn 0.3s ease;
        }
        .modal.active { display: flex; }
        .modal-content {
            background: var(--bg-card);
            border-radius: var(--radius-xl);
            padding: 32px;
            max-width: 500px;
            width: 100%;
            max-height: 90vh;
            overflow-y: auto;
            animation: scaleIn 0.4s ease;
            border: 1px solid var(--border-color);
            box-shadow: var(--shadow-xl);
        }
        .modal-content .close-btn {
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            color: var(--text-muted);
            transition: all var(--transition-fast);
        }
        .modal-content .close-btn:hover {
            transform: rotate(90deg) scale(1.2);
            color: #FF6B6B;
        }
        .btn-sm-actions a {
            padding: 4px 10px;
            border-radius: 6px;
            font-size: 0.75rem;
            text-decoration: none;
            display: inline-block;
            transition: all var(--transition-fast);
        }
        .btn-sm-actions a:hover { transform: scale(1.05); }
        .btn-edit { background: rgba(255,107,0,0.15); color: var(--primary-light); }
        .btn-delete { background: rgba(255,68,68,0.15); color: #FF6B6B; }
    </style>
</head>
<body>
    <div class="main-wrapper">
        <!-- Sidebar -->
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-brand">
                <img src="assets/images/logo.png" alt="Logo" onerror="this.style.display='none'">
                <span>USTED-K Gym</span>
            </div>
            <nav class="sidebar-menu">
                <div class="sidebar-menu-label">Main</div>
                <a href="admin_dashboard.php" class="sidebar-item">
                    <i class="fas fa-th-large"></i> Dashboard
                </a>
                <a href="admin_members.php" class="sidebar-item">
                    <i class="fas fa-users"></i> Members
                </a>
                <a href="admin_instructors.php" class="sidebar-item">
                    <i class="fas fa-chalkboard-teacher"></i> Instructors
                </a>
                <a href="admin_memberships.php" class="sidebar-item">
                    <i class="fas fa-id-card"></i> Memberships
                </a>
                <div class="sidebar-menu-label" style="margin-top: 20px;">Training</div>
                <a href="admin_training_types.php" class="sidebar-item">
                    <i class="fas fa-dumbbell"></i> Training Types
                </a>
                <a href="admin_training_sections.php" class="sidebar-item active">
                    <i class="fas fa-calendar-alt"></i> Sections
                </a>
                <div class="sidebar-menu-label" style="margin-top: 20px;">Financial</div>
                <a href="admin_payments.php" class="sidebar-item">
                    <i class="fas fa-credit-card"></i> Payments
                </a>
                <a href="logout.php" class="sidebar-item" style="margin-top: 20px; color: #FF6B6B;">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </nav>
        </aside>

        <!-- Main -->
        <main class="main-content" id="mainContent">
            <nav class="top-nav">
                <div class="top-nav-left">
                    <button class="sidebar-toggle" id="sidebarToggle">
                        <i class="fas fa-bars"></i>
                    </button>
                    <div>
                        <h2>Training Sections</h2>
                        <div class="breadcrumb">Admin / Training / <span>Sections</span></div>
                    </div>
                </div>
                <div class="top-nav-right">
                    <div class="profile-dropdown">
                        <div class="avatar-initial"><?= strtoupper(substr($_SESSION['admin_username'] ?? 'A', 0, 1)) ?></div>
                        <div class="info">
                            <div class="name"><?= htmlspecialchars($_SESSION['admin_fullname'] ?? 'Admin') ?></div>
                            <div class="role"><?= ucfirst(str_replace('_', ' ', $_SESSION['admin_role'] ?? 'admin')) ?></div>
                        </div>
                    </div>
                </div>
            </nav>

            <div class="page-header animate-fade-in">
                <div>
                    <h2 style="font-size: 1.5rem; font-weight: 600;">All Sections</h2>
                    <p style="color: var(--text-muted); font-size: 0.875rem;">Manage training schedules and assignments</p>
                </div>
                <button onclick="openModal()" class="btn-add">
                    <i class="fas fa-plus"></i> New Section
                </button>
            </div>

            <form method="GET" class="filters animate-fade-in" style="display:flex; gap:12px; flex-wrap:wrap; margin-bottom:16px;">
                <input type="text" name="search" placeholder="Search by training type, instructor or day..." value="<?= htmlspecialchars($search) ?>"
                       style="flex:1; min-width:220px; padding:8px 16px; border:1px solid var(--border-color); border-radius:var(--radius-md); background:var(--bg-input); color:var(--text-primary); font-size:0.875rem;">
                <button type="submit" class="btn btn-primary" style="padding:8px 20px;"><i class="fas fa-search"></i> Search</button>
                <?php if ($search !== ''): ?><a href="admin_training_sections.php" class="btn btn-outline" style="padding:8px 20px;">Clear</a><?php endif; ?>
            </form>

            <?php foreach ($sections as $section): ?>
            <div class="section-card animate-fade-in">
                <div class="section-info">
                    <h4><?= htmlspecialchars($section['training_name']) ?></h4>
                    <p>
                        <i class="fas fa-user"></i> <?= htmlspecialchars($section['instructor_name'] ?? 'Not Assigned') ?>
                        <span style="margin: 0 8px;">•</span>
                        <i class="fas fa-users"></i> <?= $section['enrolled_count'] ?? 0 ?> / <?= $section['capacity'] ?> enrolled
                    </p>
                </div>
                <div class="section-meta">
                    <span class="day-badge"><i class="fas fa-calendar-day"></i> <?= $section['day_of_week'] ?></span>
                    <span class="time-badge"><i class="fas fa-clock"></i> <?= date('h:i A', strtotime($section['start_time'])) ?> - <?= date('h:i A', strtotime($section['end_time'])) ?></span>
                    <span class="capacity-badge <?= ($section['enrolled_count'] ?? 0) >= $section['capacity'] ? 'full' : 'active' ?>">
                        <i class="fas fa-chair"></i> <?= $section['capacity'] ?>
                    </span>
                    <span class="status-badge <?= $section['status'] ?>"><?= ucfirst($section['status']) ?></span>
                    <div class="btn-sm-actions" style="display: flex; gap: 4px;">
                        <a href="edit_section.php?id=<?= $section['section_id'] ?>" class="btn-edit"><i class="fas fa-edit"></i></a>
                        <a href="delete_section.php?id=<?= $section['section_id'] ?>" class="btn-delete" onclick="return confirm('Delete this section?')"><i class="fas fa-trash"></i></a>
                        <a href="instructor_assign.php?section=<?= $section['section_id'] ?>" class="btn-edit" style="background: rgba(253,203,110,0.15); color: #FDCB6E;"><i class="fas fa-user-tag"></i></a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>

            <div class="footer">
                &copy; <?= date('Y') ?> USTED-K Gym Center • Training Sections
            </div>
        </main>
    </div>

    <!-- Modal -->
    <div class="modal" id="addModal">
        <div class="modal-content">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h3 style="margin: 0;">Create Training Section</h3>
                <button onclick="closeModal()" class="close-btn">×</button>
            </div>
            <form method="POST" action="add_section.php">
                <div class="form-group">
                    <label class="form-label">Training Type</label>
                    <select name="training_type_id" class="form-control" required>
                        <option value="">Select...</option>
                        <?php foreach ($trainingTypes as $tt): ?>
                            <option value="<?= $tt['training_type_id'] ?>"><?= htmlspecialchars($tt['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Instructor</label>
                    <select name="instructor_id" class="form-control" required>
                        <option value="">Select...</option>
                        <?php foreach ($instructors as $inst): ?>
                            <option value="<?= $inst['instructor_id'] ?>"><?= htmlspecialchars($inst['first_name'] . ' ' . $inst['last_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Day of Week</label>
                    <select name="day_of_week" class="form-control" required>
                        <option value="">Select...</option>
                        <?php foreach (['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'] as $day): ?>
                            <option value="<?= $day ?>"><?= $day ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                    <div class="form-group">
                        <label class="form-label">Start Time</label>
                        <input type="time" name="start_time" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">End Time</label>
                        <input type="time" name="end_time" class="form-control" required>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Capacity</label>
                    <input type="number" name="capacity" class="form-control" value="20">
                </div>
                <button type="submit" class="btn btn-primary" style="width: 100%;">
                    <i class="fas fa-save"></i> Create Section
                </button>
            </form>
        </div>
    </div>

    <script src="assets/js/script.js"></script>
    <script>
        function openModal() { document.getElementById('addModal').classList.add('active'); }
        function closeModal() { document.getElementById('addModal').classList.remove('active'); }
        window.onclick = function(e) { if (e.target == document.getElementById('addModal')) closeModal(); }
        document.getElementById('sidebarToggle').addEventListener('click', function() {
            document.getElementById('sidebar').classList.toggle('open');
        });
    </script>
</body>
</html>