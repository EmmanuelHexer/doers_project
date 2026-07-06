<?php
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: admin_login.php');
    exit;
}

require_once 'config/database.php';
require_once 'includes/functions.php';

$db = Database::getInstance()->getConnection();

// Get report data
$totalMembers = $db->query("SELECT COUNT(*) as count FROM Members")->fetch()['count'];
$activeMembers = $db->query("SELECT COUNT(*) as count FROM Members WHERE status = 'approved'")->fetch()['count'];
$totalRevenue = $db->query("SELECT SUM(amount) as total FROM Payment WHERE status = 'completed'")->fetch()['total'] ?? 0;
$monthlyRevenue = $db->query("SELECT SUM(amount) as total FROM Payment WHERE status = 'completed' AND MONTH(payment_date) = MONTH(CURDATE()) AND YEAR(payment_date) = YEAR(CURDATE())")->fetch()['total'] ?? 0;

// Membership distribution
$membershipDist = $db->query("SELECT mt.name, COUNT(m.member_id) as count 
                              FROM Membership_Type mt 
                              LEFT JOIN Members m ON mt.membership_type_id = m.membership_type_id 
                              GROUP BY mt.membership_type_id")->fetchAll();

// Instructor load
$instructorLoad = $db->query("SELECT CONCAT(i.first_name, ' ', i.last_name) as name, 
                              COUNT(ts.section_id) as sections 
                              FROM Instructor i 
                              LEFT JOIN Training_Section ts ON i.instructor_id = ts.instructor_id 
                              GROUP BY i.instructor_id 
                              ORDER BY sections DESC LIMIT 5")->fetchAll();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Reports - Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Montserrat:wght@700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
            flex-wrap: wrap;
            gap: 16px;
        }
        .stats-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
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
            font-size: 1.75rem;
            font-weight: 800;
            font-family: var(--font-heading);
            background: var(--primary-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        .stats-row .stat-box .label { color: var(--text-muted); font-size: 0.75rem; }
        .chart-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 24px;
            margin-bottom: 24px;
        }
        .btn-export {
            background: var(--primary-gradient);
            color: white;
            padding: 8px 20px;
            border: none;
            border-radius: var(--radius-md);
            font-weight: 600;
            cursor: pointer;
            transition: all var(--transition-base);
        }
        .btn-export:hover {
            transform: scale(1.05);
            box-shadow: var(--shadow-lg);
        }
        .report-list li {
            padding: 8px 0;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .report-list li:last-child { border-bottom: none; }
        @media (max-width: 992px) {
            .chart-grid { grid-template-columns: 1fr; }
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
                <a href="admin_reports.php" class="sidebar-item active">
                    <i class="fas fa-chart-line"></i> Reports
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
                        <h2>Reports & Analytics</h2>
                        <div class="breadcrumb">Admin / <span>Reports</span></div>
                    </div>
                </div>
                <div class="top-nav-right">
                    <button class="btn-export" onclick="window.print()">
                        <i class="fas fa-print"></i> Print Report
                    </button>
                    <div class="profile-dropdown">
                        <img src="assets/images/default-avatar.png" alt="Admin">
                        <div class="info">
                            <div class="name"><?= htmlspecialchars($_SESSION['admin_fullname'] ?? 'Admin') ?></div>
                            <div class="role"><?= ucfirst(str_replace('_', ' ', $_SESSION['admin_role'] ?? 'admin')) ?></div>
                        </div>
                    </div>
                </div>
            </nav>

            <div class="stats-row animate-fade-in">
                <div class="stat-box">
                    <div class="number"><?= number_format($totalMembers) ?></div>
                    <div class="label">Total Members</div>
                </div>
                <div class="stat-box">
                    <div class="number"><?= number_format($activeMembers) ?></div>
                    <div class="label">Active Members</div>
                </div>
                <div class="stat-box">
                    <div class="number"><?= formatCurrency($totalRevenue) ?></div>
                    <div class="label">Total Revenue</div>
                </div>
                <div class="stat-box">
                    <div class="number"><?= formatCurrency($monthlyRevenue) ?></div>
                    <div class="label">This Month</div>
                </div>
            </div>

            <div class="chart-grid">
                <div class="card">
                    <div class="card-header">
                        <h5><i class="fas fa-chart-pie" style="color: var(--primary-light);"></i> Membership Distribution</h5>
                    </div>
                    <div class="card-body">
                        <div class="chart-container" style="height: 250px;">
                            <canvas id="membershipChart"></canvas>
                        </div>
                    </div>
                </div>
                <div class="card">
                    <div class="card-header">
                        <h5><i class="fas fa-chalkboard-teacher" style="color: var(--primary-light);"></i> Instructor Load</h5>
                    </div>
                    <div class="card-body">
                        <div class="chart-container" style="height: 250px;">
                            <canvas id="instructorChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px;">
                <div class="card animate-fade-in">
                    <div class="card-header">
                        <h5><i class="fas fa-file-invoice" style="color: var(--primary-light);"></i> Quick Reports</h5>
                    </div>
                    <div class="card-body">
                        <ul class="report-list">
                            <li>
                                <span><i class="fas fa-users"></i> Member List</span>
                                <a href="admin_members.php" style="color: var(--primary-light);">View →</a>
                            </li>
                            <li>
                                <span><i class="fas fa-credit-card"></i> Payment History</span>
                                <a href="admin_payments.php" style="color: var(--primary-light);">View →</a>
                            </li>
                            <li>
                                <span><i class="fas fa-calendar-alt"></i> Training Schedule</span>
                                <a href="admin_training_sections.php" style="color: var(--primary-light);">View →</a>
                            </li>
                            <li>
                                <span><i class="fas fa-user-clock"></i> Pending Members</span>
                                <a href="admin_members.php?status=pending" style="color: var(--primary-light);">View →</a>
                            </li>
                            <li>
                                <span><i class="fas fa-chart-bar"></i> Revenue Report</span>
                                <a href="admin_payments.php" style="color: var(--primary-light);">View →</a>
                            </li>
                        </ul>
                    </div>
                </div>

                <div class="card animate-fade-in">
                    <div class="card-header">
                        <h5><i class="fas fa-file-export" style="color: var(--primary-light);"></i> Export Data</h5>
                    </div>
                    <div class="card-body">
                        <div style="display: flex; flex-direction: column; gap: 12px;">
                            <button class="btn btn-primary" style="width: 100%;" onclick="alert('Exporting members data...')">
                                <i class="fas fa-file-csv"></i> Export Members (CSV)
                            </button>
                            <button class="btn btn-success" style="width: 100%;" onclick="alert('Exporting payments data...')">
                                <i class="fas fa-file-excel"></i> Export Payments (Excel)
                            </button>
                            <button class="btn btn-warning" style="width: 100%;" onclick="alert('Generating PDF report...')">
                                <i class="fas fa-file-pdf"></i> Generate PDF Report
                            </button>
                            <button class="btn btn-outline" style="width: 100%;" onclick="window.print()">
                                <i class="fas fa-print"></i> Print Dashboard
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="footer">
                &copy; <?= date('Y') ?> USTED-K Gym Center • Reports & Analytics
            </div>
        </main>
    </div>

    <script src="assets/js/script.js"></script>
    <script>
        // Membership Distribution Chart
        const membershipData = <?= json_encode($membershipDist) ?>;
        const ctx1 = document.getElementById('membershipChart').getContext('2d');
        new Chart(ctx1, {
            type: 'doughnut',
            data: {
                labels: membershipData.map(d => d.name),
                datasets: [{
                    data: membershipData.map(d => d.count),
                    backgroundColor: [
                        'rgba(255,107,0,0.8)',
                        'rgba(255,140,0,0.8)',
                        'rgba(255,165,0,0.8)',
                        'rgba(255,187,51,0.8)',
                        'rgba(255,200,100,0.8)'
                    ],
                    borderColor: 'rgba(13,13,13,0.3)',
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: { color: '#A68A7A', padding: 12 }
                    }
                }
            }
        });

        // Instructor Load Chart
        const instructorData = <?= json_encode($instructorLoad) ?>;
        const ctx2 = document.getElementById('instructorChart').getContext('2d');
        new Chart(ctx2, {
            type: 'bar',
            data: {
                labels: instructorData.map(d => d.name),
                datasets: [{
                    label: 'Sections Assigned',
                    data: instructorData.map(d => d.sections),
                    backgroundColor: 'rgba(255,107,0,0.2)',
                    borderColor: 'rgba(255,107,0,1)',
                    borderWidth: 2,
                    borderRadius: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: { color: '#A68A7A', stepSize: 1 }
                    },
                    x: {
                        ticks: { color: '#A68A7A' }
                    }
                }
            }
        });

        document.getElementById('sidebarToggle').addEventListener('click', function() {
            document.getElementById('sidebar').classList.toggle('open');
        });
    </script>
</body>
</html>