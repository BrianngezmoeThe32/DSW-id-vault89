<?php
require_once '../config/database.php';

session_start();
if (!isset($_SESSION['user_id'])) {
    header("HTTP/1.1 403 Forbidden");
    die(json_encode(['success' => false, 'message' => 'Unauthorized']));
}

// Get input data
$input = json_decode(file_get_contents('php://input'), true);
$docId = $input['doc_id'] ?? null;

if (!$docId || !is_numeric($docId)) {
    header("HTTP/1.1 400 Bad Request");
    die(json_encode(['success' => false, 'message' => 'Invalid document ID']));
}

try {
    // Verify document belongs to user and is approved
    $stmt = $pdo->prepare("
        SELECT id FROM documents 
        WHERE id = ? AND user_id = ? AND status = 'approved'
    ");
    $stmt->execute([$docId, $_SESSION['user_id']]);
    $document = $stmt->fetch();

    if (!$document) {
        header("HTTP/1.1 404 Not Found");
        die(json_encode(['success' => false, 'message' => 'Document not found or not approved']));
    }

    // Generate unique token and store it in database
    $token = bin2hex(random_bytes(32));
    $expiresAt = date('Y-m-d H:i:s', strtotime('+30 days'));

    $stmt = $pdo->prepare("
        INSERT INTO document_shares 
        (document_id, token, user_id, expires_at) 
        VALUES (?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE token = VALUES(token), expires_at = VALUES(expires_at)
    ");
    $stmt->execute([$docId, $token, $_SESSION['user_id'], $expiresAt]);

    // Generate share URL
    $shareUrl = (isset($_SERVER['HTTPS']) ? 'https://' : 'http://') . 
               $_SERVER['HTTP_HOST'] . 
               dirname($_SERVER['PHP_SELF']) . 
               '/share_document.php?id=' . $docId . '&token=' . $token;

    echo json_encode([
        'success' => true,
        'share_url' => $shareUrl
    ]);

} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    header("HTTP/1.1 500 Internal Server Error");
    echo json_encode(['success' => false, 'message' => 'Database error']);
}