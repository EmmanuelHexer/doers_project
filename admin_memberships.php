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
        $fee = floatval($_POST['fee'] ?? 0);
        $duration = intval($_POST['duration_months'] ?? 0);
        $benefits = trim($_POST['benefits'] ?? '');
        $status = $_POST['status'] ?? 'active';
        
        if ($name && $fee > 0 && $duration > 0) {
            $sql = "INSERT INTO Membership_Type (name, description, fee, duration_months, benefits, status) 
                    VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = $db->prepare($sql);
            $stmt->execute([$name, $description, $fee, $duration, $benefits, $status]);
            $success = 'Membership type added successfully!';
        }
    } elseif ($action === 'edit' && $id > 0) {
        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $fee = floatval($_POST['fee'] ?? 0);
        $duration = intval($_POST['duration_months'] ?? 0);
        $benefits = trim($_POST['benefits'] ?? '');
        $status = $_POST['status'] ?? 'active';
        
        if ($name && $fee > 0 && $duration > 0) {
            $sql = "UPDATE Membership_Type SET name = ?, description = ?, fee = ?, duration_months = ?, benefits = ?, status = ? 
                    WHERE membership_type_id = ?";
            $stmt = $db->prepare($sql);
            $stmt->execute([$name, $description, $fee, $duration, $benefits, $status, $id]);
            $success = 'Membership type updated successfully!';
        }
    } elseif ($action === 'delete' && $id > 0) {
        // Check if members are using this membership
        $check = $db->prepare("SELECT COUNT(*) FROM Members WHERE membership_type_id = ?");
        $check->execute([$id]);
        if ($check->fetchColumn() > 0) {
            $error = 'Cannot delete - this membership is assigned to members.';
        } else {
            $sql = "DELETE FROM Membership_Type WHERE membership_type_id = ?";
            $stmt = $db->prepare($sql);
            $stmt->execute([$id]);
            $success = 'Membership type deleted successfully!';
        }
    }
}

// Get all memberships
$search = trim($_GET['search'] ?? '');
if ($search !== '') {
    $stmt = $db->prepare("SELECT * FROM Membership_Type WHERE name LIKE ? OR description LIKE ? OR benefits LIKE ? ORDER BY fee ASC");
    $stmt->execute(["%$search%", "%$search%", "%$search%"]);
    $memberships = $stmt->fetchAll();
} else {
    $memberships = $db->query("SELECT * FROM Membership_Type ORDER BY fee ASC")->fetchAll();
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Membership Types - Admin</title>
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
        .membership-card {
            background: var(--bg-card);
            border-radius: var(--radius-md);
            padding: 20px;
            border: 1px solid var(--border-color);
            transition: all var(--transition-base);
            position: relative;
        }
        .membership-card:hover {
            transform: translateY(-4px);
            border-color: var(--primary);
            box-shadow: var(--shadow-md);
        }
        .membership-card .price {
            font-size: 2rem;
            font-weight: 800;
            font-family: var(--font-heading);
            background: var(--primary-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        .membership-card .duration {
            color: var(--text-muted);
            font-size: 0.875rem;
        }
        .membership-card .benefits {
            margin: 12px 0;
            font-size: 0.875rem;
            color: var(--text-secondary);
        }
        .membership-card .benefits li {
            list-style: none;
            padding: 4px 0;
        }
        .membership-card .benefits li i {
            color: var(--primary-light);
            margin-right: 8px;
        }
        .membership-card .actions {
            display: flex;
            gap: 8px;
            margin-top: 12px;
        }
        .membership-card .actions button {
            padding: 6px 14px;
            border: none;
            border-radius: var(--radius-sm);
            font-size: 0.75rem;
            font-weight: 600;
            cursor: pointer;
            transition: all var(--transition-fast);
        }
        .btn-edit-membership {
            background: rgba(255,107,0,0.15);
            color: var(--primary-light);
        }
        .btn-edit-membership:hover {
            background: var(--primary-gradient);
            color: white;
            transform: scale(1.05);
        }
        .btn-delete-membership {
            background: rgba(255,68,68,0.15);
            color: #FF6B6B;
        }
        .btn-delete-membership:hover {
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
        .membership-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 20px;
        }
        @media (max-width: 600px) {
            .membership-grid { grid-template-columns: 1fr; }
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
                <a href="admin_memberships.php" class="sidebar-item active">
                    <i class="fas fa-id-card"></i> Memberships
                </a>
                <div class="sidebar-menu-label" style="margin-top: 20px;">Training</div>
                <a href="admin_training_types.php" class="sidebar-item">
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
                        <h2>Membership Types</h2>
                        <div class="breadcrumb">Admin / <span>Memberships</span></div>
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
                    <h2 style="font-size: 1.5rem; font-weight: 600;">Membership Plans</h2>
                    <p style="color: var(--text-muted); font-size: 0.875rem;">Manage membership types and pricing</p>
                </div>
                <button onclick="openAddModal()" class="btn-add">
                    <i class="fas fa-plus"></i> Add Plan
                </button>
            </div>

            <form method="GET" class="filters animate-fade-in" style="display:flex; gap:12px; flex-wrap:wrap; margin-bottom:16px;">
                <input type="text" name="search" placeholder="Search plans by name, description or benefits..." value="<?= htmlspecialchars($search) ?>"
                       style="flex:1; min-width:220px; padding:8px 16px; border:1px solid var(--border-color); border-radius:var(--radius-md); background:var(--bg-input); color:var(--text-primary); font-size:0.875rem;">
                <button type="submit" class="btn btn-primary" style="padding:8px 20px;"><i class="fas fa-search"></i> Search</button>
                <?php if ($search !== ''): ?><a href="admin_memberships.php" class="btn btn-outline" style="padding:8px 20px;">Clear</a><?php endif; ?>
            </form>

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

            <div class="membership-grid stagger-children">
                <?php foreach ($memberships as $m): ?>
                <div class="membership-card">
                    <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                        <div>
                            <h3 style="margin-bottom: 4px;"><?= htmlspecialchars($m['name']) ?></h3>
                            <span class="status-badge <?= $m['status'] ?>"><?= ucfirst($m['status']) ?></span>
                        </div>
                        <div style="text-align: right;">
                            <div class="price"><?= formatCurrency($m['fee']) ?></div>
                            <div class="duration">/ <?= $m['duration_months'] ?> months</div>
                        </div>
                    </div>
                    <p style="color: var(--text-muted); font-size: 0.875rem; margin: 8px 0;"><?= htmlspecialchars($m['description'] ?? '') ?></p>
                    <?php if ($m['benefits']): ?>
                        <ul class="benefits">
                            <?php foreach (explode("\n", $m['benefits']) as $benefit): ?>
                                <?php if (trim($benefit)): ?>
                                    <li><i class="fas fa-check-circle"></i> <?= htmlspecialchars(trim($benefit)) ?></li>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                    <div class="actions">
                        <button class="btn-edit-membership" onclick="openEditModal(<?= htmlspecialchars(json_encode($m)) ?>)">
                            <i class="fas fa-edit"></i> Edit
                        </button>
                        <button class="btn-delete-membership" onclick="if(confirm('Delete this membership plan?')){document.getElementById('deleteForm<?= $m['membership_type_id'] ?>').submit();}">
                            <i class="fas fa-trash"></i> Delete
                        </button>
                        <form id="deleteForm<?= $m['membership_type_id'] ?>" method="POST" style="display: none;">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?= $m['membership_type_id'] ?>">
                        </form>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <div class="footer">
                &copy; <?= date('Y') ?> USTED-K Gym Center • Membership Management
            </div>
        </main>
    </div>

    <!-- Add Modal -->
    <div class="modal" id="addModal">
        <div class="modal-content">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h3 style="margin: 0;">Add Membership Plan</h3>
                <button onclick="closeModal('addModal')" class="close-btn">×</button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="add">
                <div class="form-group">
                    <label class="form-label">Plan Name *</label>
                    <input type="text" name="name" class="form-control" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Description</label>
                    <textarea name="description" class="form-control" rows="2"></textarea>
                </div>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                    <div class="form-group">
                        <label class="form-label">Fee (₵) *</label>
                        <input type="number" name="fee" class="form-control" step="0.01" min="0" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Duration (months) *</label>
                        <input type="number" name="duration_months" class="form-control" min="1" required>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Benefits (one per line)</label>
                    <textarea name="benefits" class="form-control" rows="3" placeholder="Full gym access&#10;Group classes&#10;Personal trainer"></textarea>
                </div>
                <div class="form-group">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-control">
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary" style="width: 100%;">
                    <i class="fas fa-save"></i> Add Plan
                </button>
            </form>
        </div>
    </div>

    <!-- Edit Modal -->
    <div class="modal" id="editModal">
        <div class="modal-content">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h3 style="margin: 0;">Edit Membership Plan</h3>
                <button onclick="closeModal('editModal')" class="close-btn">×</button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="id" id="edit_id">
                <div class="form-group">
                    <label class="form-label">Plan Name *</label>
                    <input type="text" name="name" id="edit_name" class="form-control" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Description</label>
                    <textarea name="description" id="edit_description" class="form-control" rows="2"></textarea>
                </div>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                    <div class="form-group">
                        <label class="form-label">Fee (₵) *</label>
                        <input type="number" name="fee" id="edit_fee" class="form-control" step="0.01" min="0" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Duration (months) *</label>
                        <input type="number" name="duration_months" id="edit_duration" class="form-control" min="1" required>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Benefits (one per line)</label>
                    <textarea name="benefits" id="edit_benefits" class="form-control" rows="3"></textarea>
                </div>
                <div class="form-group">
                    <label class="form-label">Status</label>
                    <select name="status" id="edit_status" class="form-control">
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary" style="width: 100%;">
                    <i class="fas fa-save"></i> Update Plan
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
            document.getElementById('edit_id').value = data.membership_type_id;
            document.getElementById('edit_name').value = data.name;
            document.getElementById('edit_description').value = data.description || '';
            document.getElementById('edit_fee').value = data.fee;
            document.getElementById('edit_duration').value = data.duration_months;
            document.getElementById('edit_benefits').value = data.benefits || '';
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