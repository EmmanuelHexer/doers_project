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

$sql = "SELECT m.*, mt.name as membership_name, mt.fee, mt.duration_months,
        CONCAT(i.first_name, ' ', i.last_name) as instructor_name
        FROM Members m
        LEFT JOIN Membership_Type mt ON m.membership_type_id = mt.membership_type_id
        LEFT JOIN Instructor i ON m.assigned_instructor_id = i.instructor_id
        WHERE m.member_id = ?";
$stmt = $db->prepare($sql);
$stmt->execute([$member_id]);
$member = $stmt->fetch();

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $firstName = trim($_POST['first_name'] ?? '');
    $lastName = trim($_POST['last_name'] ?? '');
    $telephone = trim($_POST['telephone'] ?? '');
    $healthStatus = trim($_POST['health_status'] ?? '');
    
    if (empty($firstName) || empty($lastName)) {
        $error = 'First name and last name are required.';
    } else {
        $sql = "UPDATE Members SET first_name = ?, last_name = ?, telephone = ?, health_status = ? WHERE member_id = ?";
        $stmt = $db->prepare($sql);
        if ($stmt->execute([$firstName, $lastName, $telephone, $healthStatus, $member_id])) {
            $success = 'Profile updated successfully!';
            // Refresh data
            $stmt = $db->prepare($sql);
            $stmt->execute([$member_id]);
            $member = $stmt->fetch();
            $_SESSION['member_name'] = $firstName . ' ' . $lastName;
        } else {
            $error = 'Update failed. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>My Profile - USTED-K Gym</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Montserrat:wght@700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .profile-header {
            display: flex;
            align-items: center;
            gap: 32px;
            padding: 32px;
            background: var(--bg-card);
            border-radius: var(--radius-lg);
            border: 1px solid var(--border-color);
            margin-bottom: 24px;
            flex-wrap: wrap;
        }
        .profile-header .avatar {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            border: 4px solid var(--primary);
            object-fit: cover;
        }
        .profile-header .info h2 { margin-bottom: 4px; }
        .profile-header .info p { color: var(--text-muted); }
        .profile-header .info .status {
            display: inline-block;
            padding: 2px 14px;
            border-radius: var(--radius-full);
            font-size: 0.7rem;
            font-weight: 600;
            background: rgba(0,184,148,0.15);
            color: #00B894;
        }
        .profile-form .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
        }
        @media (max-width: 600px) {
            .profile-header { flex-direction: column; text-align: center; }
            .profile-form .form-row { grid-template-columns: 1fr; }
        }
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
                <a href="dashboard.php" class="sidebar-item">
                    <i class="fas fa-th-large"></i> Dashboard
                </a>
                <a href="profile.php" class="sidebar-item active">
                    <i class="fas fa-user"></i> My Profile
                </a>
                <a href="training.php" class="sidebar-item">
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

        <!-- Main -->
        <main class="main-content" id="mainContent">
            <nav class="top-nav">
                <div class="top-nav-left">
                    <button class="sidebar-toggle" id="sidebarToggle">
                        <i class="fas fa-bars"></i>
                    </button>
                    <div>
                        <h2>My Profile</h2>
                        <div class="breadcrumb">Home / <span>Profile</span></div>
                    </div>
                </div>
                <div class="top-nav-right">
                    <div class="profile-dropdown">
                        <img src="assets/images/default-avatar.png" alt="Profile">
                        <div class="info">
                            <div class="name"><?= htmlspecialchars($_SESSION['member_name']) ?></div>
                            <div class="role">Member</div>
                        </div>
                    </div>
                </div>
            </nav>

            <!-- Profile Header -->
            <div class="profile-header animate-fade-in">
                <img src="assets/images/default-avatar.png" class="avatar" alt="Profile">
                <div class="info">
                    <h2><?= htmlspecialchars($member['first_name'] . ' ' . $member['last_name']) ?></h2>
                    <p><i class="fas fa-envelope"></i> <?= htmlspecialchars($member['email']) ?></p>
                    <p><i class="fas fa-user"></i> @<?= htmlspecialchars($member['username']) ?></p>
                    <span class="status"><?= ucfirst($member['status']) ?></span>
                </div>
                <div style="margin-left: auto; text-align: right;">
                    <div style="font-size: 0.875rem; color: var(--text-muted);">Membership</div>
                    <div style="font-size: 1.1rem; font-weight: 700;"><?= htmlspecialchars($member['membership_name'] ?? 'N/A') ?></div>
                    <div style="font-size: 0.875rem; color: var(--text-muted);"><?= formatCurrency($member['fee'] ?? 0) ?> / <?= $member['duration_months'] ?? 0 ?> months</div>
                </div>
            </div>

            <?php if ($success): ?>
                <div class="alert alert-success animate-fade-in">
                    <i class="fas fa-check-circle"></i> <?= htmlspecialchars($success) ?>
                </div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="alert alert-danger animate-fade-in">
                    <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <!-- Edit Profile -->
            <div class="card animate-fade-in">
                <div class="card-header">
                    <h5><i class="fas fa-user-edit" style="color: var(--primary-light);"></i> Edit Profile</h5>
                </div>
                <div class="card-body">
                    <form method="POST" class="profile-form">
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label">First Name *</label>
                                <input type="text" name="first_name" class="form-control" value="<?= htmlspecialchars($member['first_name']) ?>" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Last Name *</label>
                                <input type="text" name="last_name" class="form-control" value="<?= htmlspecialchars($member['last_name']) ?>" required>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Email (Read Only)</label>
                            <input type="email" class="form-control" value="<?= htmlspecialchars($member['email']) ?>" disabled style="opacity: 0.6;">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Telephone</label>
                            <input type="tel" name="telephone" class="form-control" value="<?= htmlspecialchars($member['telephone']) ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Health Status</label>
                            <textarea name="health_status" class="form-control" rows="3"><?= htmlspecialchars($member['health_status']) ?></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Update Profile
                        </button>
                    </form>
                </div>
            </div>

            <div class="footer">
                &copy; <?= date('Y') ?> USTED-K Gym Center • <?= htmlspecialchars($_SESSION['member_name']) ?>
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