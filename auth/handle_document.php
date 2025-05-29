<?php
session_start();
require_once '../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || !$_SESSION['is_admin']) {
    echo json_encode(['success' => false, 'message' => 'Not authorized']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$docId = $input['doc_id'] ?? null;
$action = $input['action'] ?? null;

if (!$docId || !in_array($action, ['approve', 'reject'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

try {
    $pdo->beginTransaction();
    
    $status = $action === 'approve' ? 'approved' : 'rejected';
    
    // Update document status
    $stmt = $pdo->prepare("
        UPDATE documents 
        SET status = ?, reviewed_by = ?, reviewed_at = NOW() 
        WHERE id = ?
    ");
    $stmt->execute([$status, $_SESSION['user_id'], $docId]);
    
    // Get document info for notification
    $stmt = $pdo->prepare("
        SELECT user_id, document_type FROM documents WHERE id = ?
    ");
    $stmt->execute([$docId]);
    $docInfo = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Add notification
    $stmt = $pdo->prepare("
        INSERT INTO notifications 
        (user_id, title, message, type, document_type, status) 
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    $title = "Document " . ucfirst($status);
    $message = "Your " . $docInfo['document_type'] . " has been " . $status;
    $stmt->execute([
        $docInfo['user_id'],
        $title,
        $message,
        $action,
        $docInfo['document_type'],
        $status
    ]);
    
    $pdo->commit();
    
    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>