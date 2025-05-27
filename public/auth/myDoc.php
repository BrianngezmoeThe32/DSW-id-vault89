<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Get user's documents
$stmt = $pdo->prepare("
    SELECT * FROM documents 
    WHERE user_id = ? 
    ORDER BY created_at DESC
");
$stmt->execute([$_SESSION['user_id']]);
$documents = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!-- Use your myDoc.html template but make it dynamic -->
<div class="documents-list">
    <?php foreach ($documents as $doc): ?>
    <div class="document-card">
        <div class="document-icon">
            <i class="fas fa-file-pdf"></i>
        </div>
        <div class="document-info">
            <h3><?= $doc['document_type'] ?></h3>
            <p>Applied: <?= date('d M Y', strtotime($doc['created_at'])) ?></p>
            <span class="status-badge <?= $doc['status'] ?>">
                <?= ucfirst($doc['status']) ?>
            </span>
        </div>
        <div class="document-actions">
            <?php if ($doc['status'] === 'approved'): ?>
            <a href="download.php?id=<?= $doc['id'] ?>" class="action-btn download">
                <i class="fas fa-download"></i> Download
            </a>
            <?php endif; ?>
        </div>
    </div>
    <?php endforeach; ?>
</div>