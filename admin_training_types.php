<?php
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: admin_login.php');
    exit;
}

require_once 'config/database.php';
require_once 'includes/functions.php';

$db = Database::getInstance()->getConnection();

// Handle CRUD operations
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $id = intval($_POST['id'] ?? 0);
    
    if ($action === 'add') {
        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $duration = intval($_POST['duration_minutes'] ?? 0);
        $difficulty = $_POST['difficulty'] ?? 'beginner';
        $status = $_POST['status'] ?? 'active';
        
        if ($name && $duration > 0) {
            $sql = "INSERT INTO Training_Type (name, description, duration_minutes, difficulty, status) 
                    VALUES (?, ?, ?, ?, ?)";
            $stmt = $db->prepare($sql);
            $stmt->execute([$name, $description, $duration, $difficulty, $status]);
            $success = 'Training type added successfully!';
        }
    } elseif ($action === 'edit' && $id > 0) {
        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $duration = intval($_POST['duration_minutes'] ?? 0);
        $difficulty = $_POST['difficulty'] ?? 'beginner';
        $status = $_POST['status'] ?? 'active';
        
        if ($name && $duration > 0) {
            $sql = "UPDATE Training_Type SET name = ?, description = ?, duration_minutes = ?, difficulty = ?, status = ? 
                    WHERE training_type_id = ?";
            $stmt = $db->prepare($sql);
            $stmt->execute([$name, $description, $duration, $difficulty, $status, $id]);
            $success = 'Training type updated successfully!';
        }
    } elseif ($action === 'delete' && $id > 0) {
        // Check if sections use this training type
        $check = $db->prepare("SELECT COUNT(*) FROM Training_Section WHERE training_type_id = ?");
        $check->execute([$id]);
        if ($check->fetchColumn() > 0) {
            $error = 'Cannot delete - this training type has active sections.';
        } else {
            $sql = "DELETE FROM Training_Type WHERE training_type_id = ?";
            $stmt = $db->prepare($sql);
            $stmt->execute([$id]);
            $success = 'Training type deleted successfully!';
        }
    }
}

$trainingTypes = $db->query("SELECT * FROM Training_Type ORDER BY name ASC")->fetchAll();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Training Types - Admin</title>
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
        .training-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 20px;
        }
        .training-card {
            background: var(--bg-card);
            border-radius: var(--radius-md);
            padding: 20px;
            border: 1px solid var(--border-color);
            transition: all var(--transition-base);
        }
        .training-card:hover {
            transform: translateY(-4px);
            border-color: var(--primary);
            box-shadow: var(--shadow-md);
        }
        .training-card .icon {
            font-size: 2rem;
            color: var(--primary-light);
            margin-bottom: 8px;
        }
        .training-card h4 { margin-bottom: 4px; }
        .training-card .meta {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
            margin: 8px 0;
        }
        .training-card .meta span {
            font-size: 0.75rem;
            padding: 2px 12px;
            border-radius: var(--radius-full);
            background: rgba(255,255,255,0.05);
        }
        .training-card .difficulty {
            display: inline-block;
            padding: 2px 12px;
            border-radius: var(--radius-full);
            font-size: 0.7rem;
            font-weight: 600;
        }
        .difficulty.beginner { background: rgba(0,184,148,0.15); color: #00B894; }
        .difficulty.intermediate { background: rgba(253,203,110,0.15); color: #FDCB6E; }
        .difficulty.advanced { background: rgba(255,107,0,0.15); color: var(--primary-light); }
        .difficulty.expert { background: rgba(255,68,68,0.15); color: #FF6B6B; }
        .training-card .actions {
            display: flex;
            gap: 8px;
            margin-top: 12px;
        }
        .training-card .actions button {
            padding: 6px 14px;
            border: none;
            border-radius: var(--radius-sm);
            font-size: 0.75rem;
            font-weight: 600;
            cursor: pointer;
            transition: all var(--transition-fast);
        }
        .btn-edit-training {
            background: rgba(255,107,0,0.15);
            color: var(--primary-light);
        }
        .btn-edit-training:hover {
            background: var(--primary-gradient);
            color: white;
            transform: scale(1.05);
        }
        .btn-delete-training {
            background: rgba(255,68,68,0.15);
            color: #FF6B6B;
        }
        .btn-delete-training:hover {
            background: #FF6B6B;
            color: white;
            transform: scale(1.05);
        }
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
                <a href="admin_training_types.php" class="sidebar-item active">
                    <i class="fas fa-dumbbell"></i> Training Types
                </a>
                <a href="admin_training_sections.php" class="sidebar-item">
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

        <main class="main-content" id="mainContent">
            <nav class="top-nav">
                <div class="top-nav-left">
                    <button class="sidebar-toggle" id="sidebarToggle">
                        <i class="fas fa-bars"></i>
                    </button>
                    <div>
                        <h2>Training Types</h2>
                        <div class="breadcrumb">Admin / Training / <span>Types</span></div>
                    </div>
                </div>
                <div class="top-nav-right">
                    <div class="profile-dropdown">
                        <img src="assets/images/default-avatar.png" alt="Admin">
                        <div class="info">
                            <div class="name"><?= htmlspecialchars($_SESSION['admin_fullname'] ?? 'Admin') ?></div>
                            <div class="role"><?= ucfirst(str_replace('_', ' ', $_SESSION['admin_role'] ?? 'admin')) ?></div>
                        </div>
                    </div>
                </div>
            </nav>

            <div class="page-header animate-fade-in">
                <div>
                    <h2 style="font-size: 1.5rem; font-weight: 600;">Training Programs</h2>
                    <p style="color: var(--text-muted); font-size: 0.875rem;">Manage all training types and their difficulty levels</p>
                </div>
                <button onclick="openAddModal()" class="btn-add">
                    <i class="fas fa-plus"></i> Add Training
                </button>
            </div>

            <?php if (isset($success)): ?>
                <div class="alert alert-success animate-fade-in">
                    <i class="fas fa-check-circle"></i> <?= htmlspecialchars($success) ?>
                </div>
            <?php endif; ?>
            <?php if (isset($error)): ?>
                <div class="alert alert-danger animate-fade-in">
                    <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <div class="training-grid stagger-children">
                <?php foreach ($trainingTypes as $t): ?>
                <div class="training-card">
                    <div class="icon"><i class="fas fa-dumbbell"></i></div>
                    <h4><?= htmlspecialchars($t['name']) ?></h4>
                    <p style="color: var(--text-muted); font-size: 0.875rem;"><?= htmlspecialchars($t['description'] ?? '') ?></p>
                    <div class="meta">
                        <span><i class="fas fa-clock"></i> <?= $t['duration_minutes'] ?> min</span>
                        <span class="difficulty <?= $t['difficulty'] ?>"><?= ucfirst($t['difficulty']) ?></span>
                        <span class="status-badge <?= $t['status'] ?>"><?= ucfirst($t['status']) ?></span>
                    </div>
                    <div class="actions">
                        <button class="btn-edit-training" onclick="openEditModal(<?= htmlspecialchars(json_encode($t)) ?>)">
                            <i class="fas fa-edit"></i> Edit
                        </button>
                        <button class="btn-delete-training" onclick="if(confirm('Delete this training type?')){document.getElementById('deleteForm<?= $t['training_type_id'] ?>').submit();}">
                            <i class="fas fa-trash"></i> Delete
                        </button>
                        <form id="deleteForm<?= $t['training_type_id'] ?>" method="POST" style="display: none;">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?= $t['training_type_id'] ?>">
                        </form>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <div class="footer">
                &copy; <?= date('Y') ?> USTED-K Gym Center • Training Management
            </div>
        </main>
    </div>

    <!-- Add Modal -->
    <div class="modal" id="addModal">
        <div class="modal-content">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h3 style="margin: 0;">Add Training Type</h3>
                <button onclick="closeModal('addModal')" class="close-btn">×</button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="add">
                <div class="form-group">
                    <label class="form-label">Training Name *</label>
                    <input type="text" name="name" class="form-control" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Description</label>
                    <textarea name="description" class="form-control" rows="2"></textarea>
                </div>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                    <div class="form-group">
                        <label class="form-label">Duration (minutes) *</label>
                        <input type="number" name="duration_minutes" class="form-control" min="5" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Difficulty</label>
                        <select name="difficulty" class="form-control">
                            <option value="beginner">Beginner</option>
                            <option value="intermediate">Intermediate</option>
                            <option value="advanced">Advanced</option>
                            <option value="expert">Expert</option>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-control">
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary" style="width: 100%;">
                    <i class="fas fa-save"></i> Add Training
                </button>
            </form>
        </div>
    </div>

    <!-- Edit Modal -->
    <div class="modal" id="editModal">
        <div class="modal-content">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h3 style="margin: 0;">Edit Training Type</h3>
                <button onclick="closeModal('editModal')" class="close-btn">×</button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="id" id="edit_id">
                <div class="form-group">
                    <label class="form-label">Training Name *</label>
                    <input type="text" name="name" id="edit_name" class="form-control" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Description</label>
                    <textarea name="description" id="edit_description" class="form-control" rows="2"></textarea>
                </div>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                    <div class="form-group">
                        <label class="form-label">Duration (minutes) *</label>
                        <input type="number" name="duration_minutes" id="edit_duration" class="form-control" min="5" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Difficulty</label>
                        <select name="difficulty" id="edit_difficulty" class="form-control">
                            <option value="beginner">Beginner</option>
                            <option value="intermediate">Intermediate</option>
                            <option value="advanced">Advanced</option>
                            <option value="expert">Expert</option>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Status</label>
                    <select name="status" id="edit_status" class="form-control">
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary" style="width: 100%;">
                    <i class="fas fa-save"></i> Update Training
                </button>
            </form>
        </div>
    </div>

    <script src="assets/js/script.js"></script>
    <script>
        function openAddModal() {
            document.getElementById('addModal').classList.add('active');
        }
        
        function openEditModal(data) {
            document.getElementById('edit_id').value = data.training_type_id;
            document.getElementById('edit_name').value = data.name;
            document.getElementById('edit_description').value = data.description || '';
            document.getElementById('edit_duration').value = data.duration_minutes;
            document.getElementById('edit_difficulty').value = data.difficulty;
            document.getElementById('edit_status').value = data.status;
            document.getElementById('editModal').classList.add('active');
        }
        
        function closeModal(id) {
            document.getElementById(id).classList.remove('active');
        }
        
        window.onclick = function(e) {
            if (e.target.classList.contains('modal')) {
                e.target.classList.remove('active');
            }
        }
        
        document.getElementById('sidebarToggle').addEventListener('click', function() {
            document.getElementById('sidebar').classList.toggle('open');
        });
    </script>
</body>
</html>