<?php 
session_start();
require_once '../public/config/database.php';

// Check if admin is logged in
if (!isset($_SESSION['user_id']) || !$_SESSION['is_admin']) {
    header("Location: ../auth/login.php");
    exit();
}

// Get dashboard statistics
try {
    // Document counts
    $stmt = $pdo->prepare("
        SELECT 
            SUM(status = 'pending') as pending,
            SUM(status = 'approved') as approved,
            SUM(status = 'rejected') as rejected,
            COUNT(*) as total
        FROM documents
    ");
    $stmt->execute();
    $doc_stats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // User counts
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total,
            SUM(is_active = 1) as active,
            SUM(role = 'admin') as admins
        FROM users
    ");
    $stmt->execute();
    $user_stats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Recent activities
    $stmt = $pdo->prepare("
        SELECT * FROM admin_logs 
        ORDER BY action_date DESC 
        LIMIT 5
    ");
    $stmt->execute();
    $activities = $stmt->fetchAll();
    
    // Document type distribution
    $stmt = $pdo->prepare("
        SELECT 
            document_type,
            COUNT(*) as count
        FROM documents
        GROUP BY document_type
    ");
    $stmt->execute();
    $doc_types = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Error fetching dashboard data: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard | IdVault</title>
    <link rel="stylesheet" href="../public/assets/css/admin.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/js/all.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <div class="admin-container">
        <!-- Navigation -->
        <nav class="admin-nav">
            <div class="nav-header">
                <div class="logo">IdVault | Admin</div>
                <div class="user-menu">
                    <span><i class="fas fa-user"></i> <?= htmlspecialchars($_SESSION['user_name']) ?></span>
                    <a href="../auth/logout.php"><i class="fas fa-sign-out-alt"></i></a>
                </div>
            </div>
            <ul>
                <li><a href="dashboard.php" class="active"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                <li><a href="documents.php"><i class="fas fa-file-alt"></i> Documents</a></li>
                <li><a href="users.php"><i class="fas fa-users"></i> Users</a></li>
                <li><a href="reports.php"><i class="fas fa-chart-bar"></i> Reports</a></li>
            </ul>
        </nav>

        <!-- Main Content -->
        <main class="admin-main">
            <div class="dashboard-header">
                <h1><i class="fas fa-tachometer-alt"></i> Dashboard Overview</h1>
                <p>System insights and quick statistics</p>
            </div>

            <!-- Stats Cards -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon bg-blue">
                        <i class="fas fa-file-alt"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Pending Documents</h3>
                        <p><?= $doc_stats['pending'] ?? 0 ?></p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon bg-green">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Approved Documents</h3>
                        <p><?= $doc_stats['approved'] ?? 0 ?></p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon bg-red">
                        <i class="fas fa-times-circle"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Rejected Documents</h3>
                        <p><?= $doc_stats['rejected'] ?? 0 ?></p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon bg-purple">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Active Users</h3>
                        <p><?= $user_stats['active'] ?? 0 ?></p>
                    </div>
                </div>
            </div>

            <!-- Compact Charts Row -->
            <div class="compact-charts">
                <div class="chart-container">
                    <canvas id="docTypeChart"></canvas>
                </div>
                <div class="chart-container">
                    <canvas id="statusChart"></canvas>
                </div>
            </div>

            <!-- Recent Activity -->
            <section class="recent-activity">
                <h2><i class="fas fa-history"></i> Recent Activities</h2>
                <div class="activity-list">
                    <?php if (empty($activities)): ?>
                        <div class="empty-state">
                            <p>No recent activities found</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($activities as $activity): ?>
                        <div class="activity-item">
                            <div class="activity-icon">
                                <i class="fas fa-<?= strpos($activity['action_type'], 'approve') !== false ? 'check-circle' : 'times-circle' ?>"></i>
                            </div>
                            <div class="activity-details">
                                <p><?= htmlspecialchars($activity['description']) ?></p>
                                <small><?= date('M j, Y g:i a', strtotime($activity['action_date'])) ?></small>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </section>
        </main>
    </div>

    <script>
        // Document Type Chart
        const docTypeCtx = document.getElementById('docTypeChart').getContext('2d');
        const docTypeChart = new Chart(docTypeCtx, {
            type: 'doughnut',
            data: {
                labels: ['Police Forum', 'Local Certifications', 'Home Affairs'],
                datasets: [{
                    data: [
                        <?= count(array_filter($doc_types, fn($doc) => $doc['document_type'] === 'affidavit')) ?>,
                        <?= count(array_filter($doc_types, fn($doc) => $doc['document_type'] === 'certified')) ?>,
                        <?= count(array_filter($doc_types, fn($doc) => $doc['document_type'] === 'home_affairs')) ?>
                    ],
                    backgroundColor: [
                        '#3498db',
                        '#2ecc71',
                        '#9b59b6'
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    title: {
                        display: true,
                        text: 'Document Types',
                        font: { size: 14 }
                    },
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });

        // Status Chart
        const statusCtx = document.getElementById('statusChart').getContext('2d');
        const statusChart = new Chart(statusCtx, {
            type: 'bar',
            data: {
                labels: ['Pending', 'Approved', 'Rejected'],
                datasets: [{
                    label: 'Documents',
                    data: [
                        <?= $doc_stats['pending'] ?? 0 ?>,
                        <?= $doc_stats['approved'] ?? 0 ?>,
                        <?= $doc_stats['rejected'] ?? 0 ?>
                    ],
                    backgroundColor: [
                        '#FFC107',
                        '#28A745',
                        '#DC3545'
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    title: {
                        display: true,
                        text: 'Document Status',
                        font: { size: 14 }
                    },
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    </script>
</body>
</html>