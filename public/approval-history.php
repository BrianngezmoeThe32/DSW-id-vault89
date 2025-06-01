<?php
require_once '../config/database.php';
require_once 'admin-dashboard.php';

// Get filter from query string
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';

// Build query based on filter
$where = "WHERE d.document_type = 'certified'";
$params = [];

switch ($filter) {
    case 'pending':
        $where .= " AND d.status = 'pending'";
        break;
    case 'approved':
        $where .= " AND d.status = 'approved'";
        break;
    case 'rejected':
        $where .= " AND d.status = 'rejected'";
        break;
    case 'review':
        $where .= " AND d.status = 'under_review'";
        break;
    // 'all' shows all
}

try {
   $stmt = $pdo->prepare("
    SELECT d.*, u.name as username, u.email, 
           a.name as admin_username, a.email as admin_email
    FROM documents d
    JOIN users u ON d.user_id = u.id
    LEFT JOIN users a ON d.admin_id = a.id
    $where
    ORDER BY d.submission_date DESC
");
    $stmt->execute($params);
    $requests = $stmt->fetchAll();
} catch (PDOException $e) {
    $error = "Database error: " . $e->getMessage();
}

// Handle document actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && isset($_POST['doc_id'])) {
    $doc_id = (int)$_POST['doc_id'];
    $action = $_POST['action'];
    $notes = isset($_POST['notes']) ? trim($_POST['notes']) : '';
    
    try {
        $new_status = '';
        $description = '';
        
        switch ($action) {
            case 'approve':
                $new_status = 'approved';
                $description = "approved Local Certification request ID $doc_id";
                break;
            case 'reject':
                $new_status = 'rejected';
                $description = "rejected Local Certification request ID $doc_id";
                break;
            case 'review':
                $new_status = 'under_review';
                $description = "marked Local Certification request ID $doc_id as under review";
                break;
        }
        
        if ($new_status) {
            $stmt = $pdo->prepare("
                UPDATE documents 
                SET status = ?, admin_id = ?, processed_date = NOW(), admin_notes = ?
                WHERE id = ?
            ");
            $stmt->execute([$new_status, $_SESSION['user_id'], $notes, $doc_id]);
            
            // Log the action
            logAdminAction($_SESSION['user_id'], "document_$action", $description, $doc_id);
            
            header("Location: local-certification-requests.php?filter=$filter");
            exit;
        }
    } catch (PDOException $e) {
        $error = "Failed to update document: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Local Certifications | Admin Panel</title>
    <link rel="stylesheet" href="../assets/css/home.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/js/all.min.js" crossorigin="anonymous"></script>
</head>
<body>
    <div class="container">
        <nav class="navbar">
            <div class="logo">IdVault | Admin</div>
            <ul>
                <li><a href="police-requests.php">Police Forum Requests</a></li>
                <li><a href="local-certification-requests.php" class="active">Local Certifications</a></li>
                <li><a href="home-affairs-requests.php">Home Affairs</a></li>
                <li><a href="approval-history.php">Approval History</a></li>
            </ul>
            <div class="user-actions">
                <i class="fa-solid fa-magnifying-glass"></i><a href="../search.php">Search</a>
                <i class="fa-solid fa-arrow-right-from-bracket"></i><a href="../logout.php">Log out</a>
            </div>
        </nav>

        <div class="submenu">
            <a href="dashboard.php">Dashboard Overview</a>
            <a href="user-management.php">User Management</a>
        </div>

        <main class="banner">
            <div class="banner-text">
                <h1>Local Certification Requests</h1>
                <p>Review and manage all local certification requests</p>
            </div>
        </main>

        <section class="admin-requests">
            <div class="requests-filter">
                <a href="local-certification-requests.php?filter=all" class="filter-btn <?= $filter === 'all' ? 'active' : '' ?>">All Requests</a>
                <a href="local-certification-requests.php?filter=pending" class="filter-btn <?= $filter === 'pending' ? 'active' : '' ?>">Pending</a>
                <a href="local-certification-requests.php?filter=review" class="filter-btn <?= $filter === 'review' ? 'active' : '' ?>">Under Review</a>
                <a href="local-certification-requests.php?filter=approved" class="filter-btn <?= $filter === 'approved' ? 'active' : '' ?>">Approved</a>
                <a href="local-certification-requests.php?filter=rejected" class="filter-btn <?= $filter === 'rejected' ? 'active' : '' ?>">Rejected</a>
            </div>

            <div class="requests-list">
                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                <?php elseif (empty($requests)): ?>
                    <div class="no-requests">
                        <p>No Local Certification requests found.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($requests as $request): ?>
                    <div class="request-card">
                        <div class="request-header">
                            <span class="request-type local">Local Certifications</span>
                            <span class="request-date">
                                Submitted: <?= date('M d, Y', strtotime($request['submission_date'])) ?>
                                <?php if ($request['processed_date']): ?>
                                    | Processed: <?= date('M d, Y', strtotime($request['processed_date'])) ?>
                                <?php endif; ?>
                            </span>
                        </div>
                        <div class="request-details">
                            <h3>Certified Document Request</h3>
                            <p><strong>User:</strong> <?= htmlspecialchars($request['username']) ?> (<?= htmlspecialchars($request['email']) ?>)</p>
                            <?php if ($request['admin_username']): ?>
                                <p><strong>Processed by:</strong> <?= htmlspecialchars($request['admin_username']) ?> (<?= htmlspecialchars($request['admin_email']) ?>)</p>
                            <?php endif; ?>
                            <?php if ($request['admin_notes']): ?>
                                <p><strong>Admin Notes:</strong> <?= htmlspecialchars($request['admin_notes']) ?></p>
                            <?php endif; ?>
                            <div class="request-files">
                                <?php if ($request['pdf_path']): ?>
                                    <a href="../<?= htmlspecialchars($request['pdf_path']) ?>" class="file-link" target="_blank">
                                        <i class="fas fa-file-pdf"></i> View Document
                                    </a>
                                <?php else: ?>
                                    <span class="file-link"><i class="fas fa-exclamation-circle"></i> No document uploaded</span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="request-status <?= $request['status'] ?>">
                            <span>Status: <?= ucfirst(str_replace('_', ' ', $request['status'])) ?></span>
                        </div>
                        <div class="request-actions">
                            <?php if ($request['status'] !== 'approved'): ?>
                                <form method="POST" class="action-form">
                                    <input type="hidden" name="doc_id" value="<?= $request['id'] ?>">
                                    <input type="hidden" name="action" value="approve">
                                    <button type="submit" class="action-btn approve">
                                        <i class="fas fa-check"></i> Approve
                                    </button>
                                </form>
                            <?php endif; ?>
                            
                            <?php if ($request['status'] !== 'rejected'): ?>
                                <form method="POST" class="action-form">
                                    <input type="hidden" name="doc_id" value="<?= $request['id'] ?>">
                                    <input type="hidden" name="action" value="reject">
                                    <textarea name="notes" placeholder="Reason for rejection (optional)" rows="1"></textarea>
                                    <button type="submit" class="action-btn reject">
                                        <i class="fas fa-times"></i> Reject
                                    </button>
                                </form>
                            <?php endif; ?>
                            
                            <?php if ($request['status'] !== 'under_review'): ?>
                                <form method="POST" class="action-form">
                                    <input type="hidden" name="doc_id" value="<?= $request['id'] ?>">
                                    <input type="hidden" name="action" value="review">
                                    <textarea name="notes" placeholder="Review notes (optional)" rows="1"></textarea>
                                    <button type="submit" class="action-btn review">
                                        <i class="fas fa-search"></i> Mark for Review
                                    </button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </section>
    </div>
</body>
</html>