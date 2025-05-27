<?php
session_start();
require_once '../../config/database.php';

if (!isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    header("Location: ../login.php");
    exit;
}

// Get pending documents
$stmt = $pdo->prepare("
    SELECT d.*, u.name, u.email 
    FROM documents d
    JOIN users u ON d.user_id = u.id
    WHERE d.status = 'pending'
");
$stmt->execute();
$documents = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!-- Use your admin.html template but make it dynamic -->
<div class="requests-list">
    <?php foreach ($documents as $doc): ?>
    <div class="request-card">
        <div class="request-header">
            <span class="request-type <?= strtolower(str_replace(' ', '-', $doc['document_type'])) ?>">
                <?= $doc['document_type'] ?>
            </span>
            <span class="request-date">
                Submitted: <?= date('d M Y', strtotime($doc['created_at'])) ?>
            </span>
        </div>
        <div class="request-details">
            <h3><?= $doc['document_type'] ?> Request</h3>
            <p><strong>User:</strong> <?= $doc['name'] ?> (<?= $doc['email'] ?>)</p>
            <!-- Add more dynamic details here -->
        </div>
        <div class="request-actions">
            <button class="action-btn approve" data-doc-id="<?= $doc['id'] ?>">
                <i class="fas fa-check"></i> Approve
            </button>
            <button class="action-btn reject" data-doc-id="<?= $doc['id'] ?>">
                <i class="fas fa-times"></i> Reject
            </button>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<script>
// Handle approval/rejection
document.querySelectorAll('.action-btn').forEach(btn => {
    btn.addEventListener('click', async function() {
        const docId = this.dataset.docId;
        const action = this.classList.contains('approve') ? 'approve' : 'reject';
        
        try {
            const response = await fetch('handle_document.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({
                    doc_id: docId,
                    action: action
                })
            });
            
            const result = await response.json();
            if (result.success) {
                // Update UI
                const card = this.closest('.request-card');
                card.querySelector('.request-status').innerHTML = 
                    `<span>Status: ${action === 'approve' ? 'Approved' : 'Rejected'}</span>`;
                
                // Create notification
                await fetch('create_notification.php', {
                    method: 'POST',
                    body: JSON.stringify({
                        user_id: result.user_id,
                        message: `Your document has been ${action}d`,
                        type: action
                    })
                });
            }
        } catch (error) {
            console.error('Error:', error);
        }
    });
});
</script>