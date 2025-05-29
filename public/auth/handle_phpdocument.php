<?php
session_start();
header('Content-Type: application/json');
require_once '../public/config/database.php';

// Check if admin is logged in
if (!isset($_SESSION['user_id']) || !$_SESSION['is_admin']) {
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
    $stmt = $pdo->prepare("UPDATE documents SET status = ?, reviewed_by = ?, reviewed_at = NOW() WHERE id = ?");
    $stmt->execute([$status, $_SESSION['user_id'], $docId]);
    
    // Get user ID for notification
    $stmt = $pdo->prepare("SELECT user_id FROM documents WHERE id = ?");
    $stmt->execute([$docId]);
    $userId = $stmt->fetchColumn();
    
    // Create notification
    $message = $action === 'approve' 
        ? "Your document has been approved" 
        : "Your document has been rejected";
    
    $stmt = $pdo->prepare("
        INSERT INTO notifications (user_id, title, message, type, document_type)
        VALUES (?, 'Document Update', ?, ?, 
               (SELECT document_type FROM documents WHERE id = ?))
    ");
    $stmt->execute([$userId, $message, $action, $docId]);
    
    echo json_encode(['success' => true]);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>