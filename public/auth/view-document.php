<?php
session_start();
require_once '../config/database.php';

// Check if admin is logged in
if (!isset($_SESSION['user_id']) || !$_SESSION['is_admin']) {
    header("Location: ../auth/login.php");
    exit();
}

$docId = $_GET['id'] ?? null;
if (!$docId) {
    header("Location: admin-dashboard.php");
    exit();
}

try {
    $stmt = $pdo->prepare("
        SELECT d.*, u.name, u.email,
               CASE 
                   WHEN d.document_type = 'affidavit' THEN ad.file_path
                   WHEN d.document_type = 'certified' THEN cd.file_path
                   ELSE d.document_path
               END AS document_path
        FROM documents d
        JOIN users u ON d.user_id = u.id
        LEFT JOIN certified_documents cd ON d.id = cd.document_id AND d.document_type = 'certified'
        LEFT JOIN affidavit_data ad ON d.id = ad.document_id AND d.document_type = 'affidavit'
        WHERE d.id = ?
    ");
    $stmt->execute([$docId]);
    $document = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error fetching document: " . $e->getMessage());
}

if (!$document) {
    header("Location: admin-dashboard.php");
    exit();
}

// Handle file viewing
if (isset($_GET['file'])) {
    if (!empty($document['document_path'])) {
        $filePath = '../' . ltrim($document['document_path'], '/');
        if (file_exists($filePath)) {
            $mime = mime_content_type($filePath);
            header("Content-Type: $mime");
            readfile($filePath);
            exit;
        }
    }
    header("HTTP/1.0 404 Not Found");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Document - Admin</title>
    <link rel="stylesheet" href="../assets/css/home.css">
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
                <i class="fa-solid fa-user"></i><span><?= htmlspecialchars($_SESSION['user_name']) ?></span>
                <i class="fa-solid fa-arrow-right-from-bracket"></i>
                <a href="admin_logout.php">Log out</a>
            </div>
        </nav>

        <main class="document-view">
            <h1>Document Details</h1>
            
            <div class="document-info">
                <div class="document-header">
                    <span class="document-type <?= strtolower(str_replace(' ', '-', $document['document_type'])) ?>">
                        <?= htmlspecialchars($document['document_type']) ?>
                    </span>
                    <span class="document-status <?= $document['status'] ?>">
                        <?= htmlspecialchars($document['status']) ?>
                    </span>
                </div>
                
                <div class="document-meta">
                    <p><strong>User:</strong> <?= htmlspecialchars($document['name']) ?> (<?= htmlspecialchars($document['email']) ?>)</p>
                    <p><strong>Submitted:</strong> <?= date('d M Y H:i', strtotime($document['created_at'])) ?></p>
                    <?php if ($document['reviewed_at']): ?>
                        <p><strong>Reviewed:</strong> <?= date('d M Y H:i', strtotime($document['reviewed_at'])) ?></p>
                    <?php endif; ?>
                </div>
                     <!-- Add this after the document-meta section in view-document.php -->
                <?php if ($document['document_type'] === 'affidavit'): ?>
                    <?php
                    // Get affidavit data
                    $stmt = $pdo->prepare("SELECT * FROM affidavit_data WHERE document_id = ?");
                    $stmt->execute([$docId]);
                    $affidavit = $stmt->fetch(PDO::FETCH_ASSOC);
                    ?>
    
                    <div class="affidavit-details">
                        <h3>Affidavit Details</h3>
                        <div class="affidavit-content">
                            <p><strong>Name:</strong> <?= htmlspecialchars($affidavit['name'] ?? '') ?></p>
                            <p><strong>ID Number:</strong> <?= htmlspecialchars($affidavit['id_number'] ?? '') ?></p>
                            <p><strong>Age:</strong> <?= htmlspecialchars($affidavit['age'] ?? '') ?></p>
                            <p><strong>Residing Address:</strong> <?= htmlspecialchars($affidavit['residing_address'] ?? '') ?></p>
                            <p><strong>Working Address:</strong> <?= htmlspecialchars($affidavit['working_address'] ?? '') ?></p>
                            <p><strong>Declaration:</strong></p>
                            <div class="declaration-box"><?= nl2br(htmlspecialchars($affidavit['declaration'] ?? '')) ?></div>
                            <p><strong>Place:</strong> <?= htmlspecialchars($affidavit['place'] ?? '') ?></p>
                            <p><strong>Date:</strong> <?= htmlspecialchars($affidavit['date'] ?? '') ?></p>
                            <p><strong>Time:</strong> <?= htmlspecialchars($affidavit['time'] ?? '') ?></p>
            
                            <?php if (!empty($affidavit['file_path'])): ?>
                                <p><strong>Attached Document:</strong> 
                                    <a href="view-document.php?id=<?= $docId ?>&file=1" target="_blank">
                                    View Document
                                    </a>
                                </p>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
                
                
                <div class="document-content">
                    <?php if (!empty($document['document_path'])): ?>
                        <?php
                        $filePath = '../public/' . ltrim($document['document_path'], '/');
                        $fileExtension = pathinfo($document['document_path'], PATHINFO_EXTENSION);
                        $viewableTypes = ['pdf', 'jpg', 'jpeg', 'png', 'gif'];
                        ?>
            
                        <?php if (in_array(strtolower($fileExtension), $viewableTypes)): ?>
                            <?php if (strtolower($fileExtension) === 'pdf'): ?>
                                <iframe src="view-document.php?id=<?= $docId ?>&file=1" style="width:100%; height:600px;"></iframe>
                            <?php else: ?>
                                <img src="view-document.php?id=<?= $docId ?>&file=1" style="max-width: 100%;">
                            <?php endif; ?>
                        <?php else: ?>
                            <p>File type cannot be displayed inline. <a href="view-document.php?id=<?= $docId ?>&file=1" download>Download Document</a></p>
                        <?php endif; ?>
                    <?php else: ?>
                        <p>No document file attached.</p>
                    <?php endif; ?>
                </div>
                
                <?php if ($document['status'] === 'pending'): ?>
                <div class="document-actions">
                    <button class="action-btn approve" data-doc-id="<?= $document['id'] ?>">
                        <i class="fas fa-check"></i> Approve
                    </button>
                    <button class="action-btn reject" data-doc-id="<?= $document['id'] ?>">
                        <i class="fas fa-times"></i> Reject
                    </button>
                </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <script>
    // Handle approval/rejection in view page
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
                    alert(`Document ${action}d successfully!`);
                    location.reload(); // Refresh to show updated status
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