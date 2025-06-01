<?php
require_once '../config/database.php';

session_start();
if (!isset($_SESSION['user_id'])) {
    header("HTTP/1.1 403 Forbidden");
    die(json_encode(['success' => false, 'message' => 'Unauthorized']));
}

$input = json_decode(file_get_contents('php://input'), true);
$docId = $input['doc_id'] ?? null;

if (!$docId) {
    header("HTTP/1.1 400 Bad Request");
    die(json_encode(['success' => false, 'message' => 'Invalid document ID']));
}

try {
    // Verify document belongs to user
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

    // Generate unique token (you might want to store this in database for validation)
    $token = bin2hex(random_bytes(16));
    $shareUrl = "https://yourdomain.com/share_document.php?id=$docId&token=$token";

    // In a real implementation, you would store this token in database with expiration
    // $stmt = $pdo->prepare("INSERT INTO document_shares ...");

    echo json_encode([
        'success' => true,
        'share_url' => $shareUrl
    ]);
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    header("HTTP/1.1 500 Internal Server Error");
    echo json_encode(['success' => false, 'message' => 'Database error']);
}