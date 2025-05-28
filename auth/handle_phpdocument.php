<?php
session_start();
header('Content-Type: application/json');
require_once '../../config/database.php';

// Check if user is admin
if (!isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);
$docId = $data['doc_id'] ?? null;
$action = $data['action'] ?? null;

if (!$docId || !in_array($action, ['approve', 'reject'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit();
}

try {
    // Update document status
    $status = $action === 'approve' ? 'approved' : 'rejected';
    $stmt = $pdo->prepare("UPDATE documents SET status = ? WHERE id = ?");
    $stmt->execute([$status, $docId]);
    
    // Get user ID for notification
    $stmt = $pdo->prepare("SELECT user_id FROM documents WHERE id = ?");
    $stmt->execute([$docId]);
    $userId = $stmt->fetchColumn();
    
    // Create notification
    $message = $action === 'approve' 
        ? "Your document has been approved" 
        : "Your document has been rejected";
    
    $stmt = $pdo->prepare("
        INSERT INTO notifications (user_id, title, message, type)
        VALUES (?, 'Document Update', ?, ?)
    ");
    $stmt->execute([$userId, $message, $action]);
    
    echo json_encode(['success' => true, 'user_id' => $userId]);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>