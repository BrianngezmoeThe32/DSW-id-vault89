<?php
/**
 * handle_document.php - Document Approval/Rejection Handler
 * 
 * Handles admin actions for document approval/rejection with:
 * - Secure session validation
 * - Input sanitization
 * - Database transactions
 * - Notification system
 * - Activity logging
 */

// =============================================
// INITIALIZATION & SECURITY
// =============================================

// Strict error reporting
declare(strict_types=1);
error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');
ini_set('error_log', __DIR__ . '/../../logs/document_errors.log');

// Session management
session_start();
header('Content-Type: application/json');

// Validate admin session
if (!isset($_SESSION['user_id']) || !$_SESSION['is_admin']) {
    http_response_code(403);
    exit(json_encode([
        'success' => false,
        'message' => 'Access denied: Admin privileges required'
    ]));
}

// =============================================
// DEPENDENCIES
// =============================================

require_once __DIR__ . '/../config/database.php';

// =============================================
// REQUEST VALIDATION
// =============================================

// Get and validate JSON input
$input = json_decode(file_get_contents('php://input'), true);
if ($input === null || json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(400);
    exit(json_encode([
        'success' => false,
        'message' => 'Invalid request format'
    ]));
}

// Validate required parameters
$docId = filter_var($input['doc_id'] ?? null, FILTER_VALIDATE_INT);
$action = isset($input['action']) && in_array($input['action'], ['approve', 'reject']) 
    ? $input['action'] 
    : null;

if (!$docId || !$action) {
    http_response_code(400);
    exit(json_encode([
        'success' => false,
        'message' => 'Missing or invalid parameters'
    ]));
}

// =============================================
// DOCUMENT PROCESSING
// =============================================

try {
    $pdo->beginTransaction();
    
    // 1. Verify document exists and get details
    $stmt = $pdo->prepare("
        SELECT d.id, d.user_id, d.document_type, u.email, u.name 
        FROM documents d
        JOIN users u ON d.user_id = u.id
        WHERE d.id = ? AND d.status = 'pending'
        FOR UPDATE
    ");
    
    if (!$stmt->execute([$docId]) || !$document = $stmt->fetch()) {
        throw new RuntimeException("Document not found or already processed");
    }

    // 2. Update document status
    $status = $action === 'approve' ? 'approved' : 'rejected';
    $updateStmt = $pdo->prepare("
        UPDATE documents 
        SET status = ?,
            reviewed_by = ?,
            reviewed_at = CURRENT_TIMESTAMP
        WHERE id = ?
    ");
    
    if (!$updateStmt->execute([$status, $_SESSION['user_id'], $docId])) {
        throw new RuntimeException("Failed to update document status");
    }

    // 3. Create user notification
    $notificationStmt = $pdo->prepare("
        INSERT INTO notifications 
        (user_id, title, message, document_type, status)
        VALUES (?, ?, ?, ?, ?)
    ");
    
    $notificationData = [
        $document['user_id'],
        "Document {$status}",
        "Your {$document['document_type']} document has been {$status}",
        $document['document_type'],
        $status
    ];
    
    if (!$notificationStmt->execute($notificationData)) {
        throw new RuntimeException("Failed to create notification");
    }

    // 4. Log admin activity
    $logStmt = $pdo->prepare("
        INSERT INTO admin_logs 
        (admin_id, action_type, document_id, user_id, description)
        VALUES (?, ?, ?, ?, ?)
    ");
    
    $logData = [
        $_SESSION['user_id'],
        $action,
        $docId,
        $document['user_id'],
        "{$action} document #{$docId}"
    ];
    
    if (!$logStmt->execute($logData)) {
        error_log("Failed to log admin action");
        // Non-critical error, don't rollback
    }

    $pdo->commit();

    // Success response
    echo json_encode([
        'success' => true,
        'message' => "Document successfully {$status}",
        'data' => [
            'document_id' => $docId,
            'new_status' => $status,
            'processed_at' => date('Y-m-d H:i:s')
        ]
    ]);

} catch (Throwable $e) {
    $pdo->rollBack();
    error_log("Processing Error [Document {$docId}]: " . $e->getMessage());
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Document processing failed',
        'error' => $e->getMessage()
    ]);
}