<?php
session_start();
require_once '../public/config/database.php';

if (!isset($_SESSION['user_id']) || !$_SESSION['is_admin']) {
    header("Location: ../auth/login.php");
    exit();
}

// Get filter from URL
$filter = $_GET['filter'] ?? 'pending';

// Get documents
try {
    $where = "WHERE status = ?";
    $params = [$filter];
    
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
        $where
        ORDER BY d.created_at DESC
    ");
    $stmt->execute($params);
    $documents = $stmt->fetchAll();
} catch (PDOException $e) {
    die("Error fetching documents: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document Management | IdVault</title>
    <link rel="stylesheet" href="../public/assets/css/admin.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/js/all.min.js"></script>
</head>
<body>
    <div class="admin-container">
        <!-- Navigation (same as dashboard) -->
        <nav class="admin-nav">
            <!-- Copy the same nav from dashboard.php -->
        </nav>

        <main class="admin-main">
            <div class="dashboard-header">
                <h1><i class="fas fa-file-alt"></i> Document Management</h1>
                <p>Review and process user documents</p>
            </div>

            <div class="documents-filter">
                <a href="documents.php?filter=pending" class="<?= $filter === 'pending' ? 'active' : '' ?>">Pending</a>
                <a href="documents.php?filter=approved" class="<?= $filter === 'approved' ? 'active' : '' ?>">Approved</a>
                <a href="documents.php?filter=rejected" class="<?= $filter === 'rejected' ? 'active' : '' ?>">Rejected</a>
            </div>

            <div class="documents-list">
                <?php if (empty($documents)): ?>
                    <div class="empty-state">
                        <p>No documents found in this category</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($documents as $doc): ?>
                    <div class="document-card">
                        <div class="doc-header">
                            <span class="doc-type <?= strtolower(str_replace(' ', '-', $doc['document_category'])) ?>">
                                <?= htmlspecialchars($doc['document_category']) ?>
                            </span>
                            <span class="doc-date">
                                <?= date('M j, Y', strtotime($doc['created_at'])) ?>
                            </span>
                        </div>
                        <div class="doc-body">
                            <h3><?= htmlspecialchars($doc['name']) ?></h3>
                            <p><?= htmlspecialchars($doc['email']) ?></p>
                            <div class="doc-actions">
                                <a href="view_document.php?id=<?= $doc['id'] ?>" class="btn view">
                                    <i class="fas fa-eye"></i> View
                                </a>
                                <?php if ($filter === 'pending'): ?>
                                <button class="btn approve" data-id="<?= $doc['id'] ?>">
                                    <i class="fas fa-check"></i> Approve
                                </button>
                                <button class="btn reject" data-id="<?= $doc['id'] ?>">
                                    <i class="fas fa-times"></i> Reject
                                </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <script>
        // Document approval/rejection handling
        document.querySelectorAll('.approve, .reject').forEach(btn => {
            btn.addEventListener('click', function() {
                const docId = this.dataset.id;
                const action = this.classList.contains('approve') ? 'approve' : 'reject';
                
                fetch('process_document.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        id: docId,
                        action: action
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        this.closest('.document-card').remove();
                    } else {
                        alert('Error: ' + data.message);
                    }
                });
            });
        });
    </script>
</body>
</html>