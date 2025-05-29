<?php
session_start();
require_once '../public/config/database.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit();
}

// Get all police-related documents
try {
    $stmt = $pdo->prepare("
        SELECT d.*, u.name, u.email 
        FROM documents d
        JOIN users u ON d.user_id = u.id
        WHERE d.document_type IN ('Affidavit', 'Certified Document')
        ORDER BY d.created_at DESC
    ");
    $stmt->execute();
    $documents = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error fetching documents: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Police Forum Requests</title>
    <link rel="stylesheet" href="../public/assets/css/admin.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/js/all.min.js" crossorigin="anonymous"></script>
</head>
<body>
    <div class="container">
        <nav class="navbar">
            <div class="logo">IdVault | Admin</div>
            <ul>
                <li><a href="police-request.php">Police Forum Requests</a></li>
                <li><a href="local-certification-request.php">Local Certifications</a></li>
                <li><a href="home-affairs-request.php">Home Affairs</a></li>
                <li><a href="approval-history.php">Approval History</a></li>
            </ul>
            <div class="user-actions">
                <i class="fa-solid fa-user"></i><span><?= htmlspecialchars($_SESSION['admin_name']) ?></span>
                <i class="fa-solid fa-arrow-right-from-bracket"></i>
                <a href="admin_logout.php">Log out</a>
            </div>
        </nav>

        <main class="banner">
            <div class="banner-text">
                <h1>Police Forum Requests</h1>
                <p>View and manage all affidavit and certified document requests.</p>
            </div>
        </main>

        <section class="admin-requests">
            <div class="requests-list">
                <?php foreach ($documents as $document): ?>
                <div class="request-card" data-doc-id="<?= $document['id'] ?>">
                    <div class="request-header">
                        <span class="request-type <?= strtolower(str_replace(' ', '-', $document['document_type'])) ?>">
                            <?= htmlspecialchars($document['document_type']) ?>
                        </span>
                        <span class="request-date">
                            Submitted: <?= date('d M Y', strtotime($document['created_at'])) ?>
                        </span>
                    </div>
                    <div class="request-details">
                        <h3><?= htmlspecialchars($document['document_type']) ?> Request</h3>
                        <p><strong>User:</strong> <?= htmlspecialchars($document['name']) ?> (<?= htmlspecialchars($document['email']) ?>)</p>
                        <p><strong>Status:</strong> <span class="status-badge <?= $document['status'] ?>"><?= htmlspecialchars($document['status']) ?></span></p>
                    </div>
                    <div class="request-actions">
                        <?php if ($document['status'] === 'pending'): ?>
                            <button class="action-btn approve" data-doc-id="<?= $document['id'] ?>">
                                <i class="fas fa-check"></i> Approve
                            </button>
                            <button class="action-btn reject" data-doc-id="<?= $document['id'] ?>">
                                <i class="fas fa-times"></i> Reject
                            </button>
                        <?php endif; ?>
                        <a href="view-document.php?id=<?= $document['id'] ?>" class="action-btn view">
                            <i class="fas fa-eye"></i> View Details
                        </a>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </section>
    </div>

    <script>
    // Handle document approval/rejection
    document.querySelectorAll('.action-btn.approve, .action-btn.reject').forEach(btn => {
        btn.addEventListener('click', async function() {
            const docId = this.dataset.docId;
            const action = this.classList.contains('approve') ? 'approve' : 'reject';
            
            try {
                const response = await fetch('handle_document.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        doc_id: docId,
                        action: action
                    })
                });
                
                const result = await response.json();
                
                if (result.success) {
                    // Update the UI
                    const card = this.closest('.request-card');
                    card.querySelector('.status-badge').textContent = action === 'approve' ? 'approved' : 'rejected';
                    card.querySelector('.status-badge').className = `status-badge ${action === 'approve' ? 'approved' : 'rejected'}`;
                    
                    // Disable action buttons
                    card.querySelectorAll('.request-actions button').forEach(btn => {
                        btn.disabled = true;
                    });
                    
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
    </script>
</body>
</html>