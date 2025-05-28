<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit();
}

// Database connection for documents (keeping this part database-driven)
$conn = new mysqli("localhost", "root", "", "idvault");
if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

// Handle document approval/rejection
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        $document_id = intval($_POST['document_id']);
        $admin_id = $_SESSION['admin_id'];
        
        if ($_POST['action'] === 'approve') {
            $stmt = $conn->prepare("UPDATE user_documents SET status = 'approved', reviewed_by = ?, reviewed_at = NOW() WHERE document_id = ?");
            $stmt->bind_param("si", $admin_id, $document_id);
            $stmt->execute();
            $stmt->close();
            $message = "Document approved successfully!";
        } 
        elseif ($_POST['action'] === 'reject') {
            $reason = trim($_POST['rejection_reason']);
            $stmt = $conn->prepare("UPDATE user_documents SET status = 'rejected', reviewed_by = ?, reviewed_at = NOW(), rejection_reason = ? WHERE document_id = ?");
            $stmt->bind_param("isi", $admin_id, $reason, $document_id);
            $stmt->execute();
            $stmt->close();
            $message = "Document rejected successfully!";
        }
    }
}

// Get pending documents
$pending_docs = [];
$stmt = $conn->prepare("
    SELECT d.document_id, d.document_type, d.document_path, d.submitted_at, 
           u.id as user_id, u.name as user_name, u.email as user_email
    FROM user_documents d
    JOIN users u ON d.user_id = u.id
    WHERE d.status = 'pending'
    ORDER BY d.submitted_at ASC
");
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $pending_docs[] = $row;
}
$stmt->close();

// Get document statistics
$stats = [];
$stmt = $conn->prepare("
    SELECT 
        COUNT(*) as total,
        SUM(status = 'pending') as pending,
        SUM(status = 'approved') as approved,
        SUM(status = 'rejected') as rejected
    FROM user_documents
");
$stmt->execute();
$result = $stmt->get_result();
$stats = $result->fetch_assoc();
$stmt->close();

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Document Approval</title>
    <link rel="stylesheet" href="../public/assets/css/admin.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/js/all.min.js" crossorigin="anonymous"></script>
    <style>
        /* (Keep all your existing CSS styles) */
        .admin-info {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <nav class="admin-navbar">
            <div class="logo">IdVault Admin |</div>
            <div class="admin-actions">
                <i class="fa-solid fa-user"></i>
                <span><?php echo htmlspecialchars($_SESSION['admin_name']); ?></span>
                <i class="fa-solid fa-arrow-right-from-bracket"></i>
                <a href="admin_logout.php">Log out</a>
            </div>
        </nav>

        <main class="admin-main">
            <div class="admin-info">
                <h2>Welcome, <?php echo htmlspecialchars($_SESSION['admin_name']); ?></h2>
                <p>You are logged in as: <?php echo htmlspecialchars($_SESSION['admin_id']); ?></p>
                <?php if ($_SESSION['is_super_admin'] ?? false): ?>
                    <p class="badge">Super Administrator</p>
                <?php endif; ?>
            </div>
            
            <!-- Rest of your admin dashboard HTML (same as before) -->
            <!-- ... -->
            
        </main>
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