<?php 
session_start();
require_once '../public/config/database.php';

// Check if admin is logged in
if (!isset($_SESSION['user_id']) || !$_SESSION['is_admin']) {
    header("Location: ../auth/login.php");
    exit();
}

// Get statistics for dashboard
try {
    // Total pending documents
    $stmt = $pdo->prepare("SELECT COUNT(*) as total_pending FROM documents WHERE status = 'pending'");
    $stmt->execute();
    $total_pending = $stmt->fetch()['total_pending'];
    
    // Total approved documents
    $stmt = $pdo->prepare("SELECT COUNT(*) as total_approved FROM documents WHERE status = 'approved'");
    $stmt->execute();
    $total_approved = $stmt->fetch()['total_approved'];
    
    // Total users
    $stmt = $pdo->prepare("SELECT COUNT(*) as total_users FROM users");
    $stmt->execute();
    $total_users = $stmt->fetch()['total_users'];
    
    // Recent activities
    $stmt = $pdo->prepare("
        SELECT * FROM admin_logs 
        ORDER BY action_date DESC 
        LIMIT 5
    ");
    $stmt->execute();
    $recent_activities = $stmt->fetchAll();

    // Get pending documents from database
    $stmt = $pdo->prepare("
        SELECT d.*, u.name, u.email,
               CASE 
                   WHEN d.document_type = 'affidavit' THEN ad.file_path
                   WHEN d.document_type = 'certified' THEN cd.file_path
                   ELSE d.document_path
               END AS document_path,
               CASE 
                   WHEN d.document_type = 'affidavit' THEN 'Police Forum'
                   WHEN d.document_type = 'certified' THEN 'Local Certifications'
                   ELSE 'Home Affairs'
               END AS document_category
        FROM documents d
        JOIN users u ON d.user_id = u.id
        LEFT JOIN certified_documents cd ON d.id = cd.document_id AND d.document_type = 'certified'
        LEFT JOIN affidavit_data ad ON d.id = ad.document_id AND d.document_type = 'affidavit'
        WHERE d.status = 'pending'
        ORDER BY d.created_at DESC
    ");
    $stmt->execute();
    $documents = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error fetching data: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Idvault Online - Admin Dashboard</title>
    <link rel="stylesheet" href="../public/assets/css/home.css" />
    <link rel="stylesheet" href="../public/assets/css/admin.css" />
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/js/all.min.js" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <div class="container">
        <nav class="navbar">
            <div class="logo">IdVault | Admin</div>
            <ul>
                <li><a href="police-requests.php">Police Forum Requests</a></li>
                <li><a href="local-certification-requests.php">Local Certifications</a></li>
                <li><a href="home-affairs-requests.php">Home Affairs</a></li>
                <li><a href="approval-history.php">Approval History</a></li>
            </ul>
            <div class="user-actions">
                <i class="fa-solid fa-user"></i><span><?= htmlspecialchars($_SESSION['user_name']) ?></span>
                <i class="fa-solid fa-arrow-right-from-bracket"></i>
                <a href="../auth/logout.php">Log out</a>
            </div>
        </nav>

        <div class="submenu">
            <a href="dashboard.php" class="active">Dashboard Overview</a>
            <a href="user-management.php">User Management</a>
        </div>

        <main class="banner">
            <div class="banner-text">
                <h1>Admin Dashboard</h1>
                <p>Review and approve pending document requests from users.</p>
            </div>
        </main>

        
        <section class="dashboard-stats">
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-file-alt"></i></div>
                    <div class="stat-info">
                        <h3>Pending Documents</h3>
                        <p><?= $total_pending ?></p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-check-circle"></i></div>
                    <div class="stat-info">
                        <h3>Approved Documents</h3>
                        <p><?= $total_approved ?></p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-users"></i></div>
                    <div class="stat-info">
                        <h3>Total Users</h3>
                        <p><?= $total_users ?></p>
                    </div>
                </div>
            </div>

            <div class="charts-row">
                <div class="chart-container">
                    <canvas id="documentsChart"></canvas>
                </div>
                <div class="chart-container">
                    <canvas id="activityChart"></canvas>
                </div>
            </div>
        </section>

        <!-- Recent Activity Section -->
        <section class="recent-activity">
            <h2>Recent Activities</h2>
            <div class="activity-list">
                <?php foreach ($recent_activities as $activity): ?>
                <div class="activity-item">
                    <div class="activity-icon">
                        <?php 
                        $icon = 'fa-info-circle';
                        if (strpos($activity['action_type'], 'approve') !== false) $icon = 'fa-check-circle';
                        elseif (strpos($activity['action_type'], 'reject') !== false) $icon = 'fa-times-circle';
                        ?>
                        <i class="fas <?= $icon ?>"></i>
                    </div>
                    <div class="activity-details">
                        <p><?= htmlspecialchars($activity['description']) ?></p>
                        <small><?= date('M d, Y H:i', strtotime($activity['action_date'])) ?></small>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </section>

        
        <section class="admin-requests">
            <h2>Pending Document Requests</h2>
            <div class="requests-filter">
                <button class="filter-btn active" data-filter="all">All Requests</button>
                <button class="filter-btn" data-filter="affidavit">Police Forum</button>
                <button class="filter-btn" data-filter="certified">Local Certifications</button>
                <button class="filter-btn" data-filter="home_affairs">Home Affairs</button>
            </div>

            <div class="requests-list">
                <?php if (empty($documents)): ?>
                    <div class="no-requests">
                        <p>No pending document requests found.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($documents as $document): ?>
                    <div class="request-card" data-doc-type="<?= $document['document_type'] ?>">
                        <div class="request-header">
                            <span class="request-type <?= strtolower(str_replace(' ', '-', $document['document_category'])) ?>">
                                <?= htmlspecialchars($document['document_category']) ?>
                            </span>
                            <span class="request-date">
                                Submitted: <?= date('d M Y', strtotime($document['created_at'])) ?>
                            </span>
                        </div>
                        <div class="request-details">
                            <h3><?= htmlspecialchars($document['document_category']) ?> Request</h3>
                            <p><strong>User:</strong> <?= htmlspecialchars($document['name']) ?> (<?= htmlspecialchars($document['email']) ?>)</p>
                            <p><strong>Status:</strong> <span class="status-badge <?= $document['status'] ?>"><?= ucfirst($document['status']) ?></span></p>
                            <?php if (!empty($document['document_path'])): ?>
                            <p><strong>File:</strong> 
                                <a href="../<?= htmlspecialchars($document['document_path']) ?>" target="_blank" class="file-link">
                                    <?= htmlspecialchars(basename($document['document_path'])) ?>
                                </a>
                            </p>
                            <?php endif; ?>
                        </div>
                        <div class="request-actions">
                            <button class="action-btn approve" data-doc-id="<?= $document['id'] ?>">
                                <i class="fas fa-check"></i> Approve
                            </button>
                            <button class="action-btn reject" data-doc-id="<?= $document['id'] ?>">
                                <i class="fas fa-times"></i> Reject
                            </button>
                            <a href="view-document.php?id=<?= $document['id'] ?>" class="action-btn view">
                                <i class="fas fa-eye"></i> View Details
                            </a>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </section>
    </div>

    <script>
        // Document type filter
        document.querySelectorAll('.requests-filter .filter-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                document.querySelector('.requests-filter .filter-btn.active').classList.remove('active');
                this.classList.add('active');
                
                const filter = this.dataset.filter;
                const cards = document.querySelectorAll('.request-card');
                
                cards.forEach(card => {
                    if (filter === 'all' || card.dataset.docType === filter) {
                        card.style.display = 'block';
                    } else {
                        card.style.display = 'none';
                    }
                });
            });
        });

        // Handle approve/reject actions
        document.querySelectorAll('.action-btn.approve, .action-btn.reject').forEach(btn => {
            btn.addEventListener('click', async function() {
                const docId = this.dataset.docId;
                const action = this.classList.contains('approve') ? 'approve' : 'reject';
                const card = this.closest('.request-card');
                
                try {
                    const response = await fetch('handle_document.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ doc_id: docId, action: action })
                    });
                    
                    const result = await response.json();
                    
                    if (result.success) {
                        
                        const statusBadge = card.querySelector('.status-badge');
                        statusBadge.textContent = action === 'approve' ? 'approved' : 'rejected';
                        statusBadge.className = `status-badge ${action === 'approve' ? 'approved' : 'rejected'}`;
                        
                        // Disable action buttons
                        card.querySelectorAll('.request-actions button').forEach(btn => {
                            btn.disabled = true;
                        });
                        
                        // Remove the card after 2 seconds
                        setTimeout(() => {
                            card.style.opacity = '0';
                            setTimeout(() => card.remove(), 500);
                        }, 2000);
                        
                        alert(`Document ${action}d successfully!`);
                    } else {
                        alert(`Error: ${result.message}`);
                    }
                } catch (error) {
                    console.error('Error:', error);
                    alert('An error occurred while processing your request.');
                }
            });
        });

        // Charts
        document.addEventListener('DOMContentLoaded', function() {
            // Documents by Type Chart
            const docCtx = document.getElementById('documentsChart').getContext('2d');
            const docChart = new Chart(docCtx, {
                type: 'doughnut',
                data: {
                    labels: ['Police Forum', 'Local Certifications', 'Home Affairs'],
                    datasets: [{
                        data: [
                            <?= count(array_filter($documents, fn($doc) => $doc['document_type'] === 'affidavit')) ?>,
                            <?= count(array_filter($documents, fn($doc) => $doc['document_type'] === 'certified')) ?>,
                            <?= count(array_filter($documents, fn($doc) => $doc['document_type'] === 'home_affairs')) ?>
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
                    plugins: {
                        title: {
                            display: true,
                            text: 'Pending Documents by Type'
                        }
                    }
                }
            });

            // Activity Chart (placeholder - would need real data)
            const actCtx = document.getElementById('activityChart').getContext('2d');
            const actChart = new Chart(actCtx, {
                type: 'bar',
                data: {
                    labels: ['Approvals', 'Rejections', 'Reviews'],
                    datasets: [{
                        label: 'Recent Activities',
                        data: [12, 5, 8],
                        backgroundColor: [
                            '#28a745',
                            '#dc3545',
                            '#17a2b8'
                        ]
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        title: {
                            display: true,
                            text: 'Recent Activities'
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });
        });
    </script>
</body>
</html>