<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

$docId = $_GET['id'] ?? null;
if (!$docId || !is_numeric($docId)) {
    header("Location: status-check.php?error=invalid_id");
    exit();
}

try {
    // Get document info
    $stmt = $pdo->prepare("
        SELECT d.* 
        FROM documents d
        WHERE d.id = ? AND d.user_id = ? AND d.status = 'approved'
    ");
    $stmt->execute([$docId, $_SESSION['user_id']]);
    $document = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$document || empty($document['pdf_path'])) {
        header("Location: status-check.php?error=notfound");
        exit();
    }

    $filePath = '../' . ltrim($document['pdf_path'], '/');
    
    // Check if file exists
    if (!file_exists($filePath)) {
        header("Location: status-check.php?error=filenotfound");
        exit();
    }

    // Get the file name
    $fileName = basename($filePath);

    // Set headers for download
    header('Content-Description: File Transfer');
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="' . $fileName . '"');
    header('Expires: 0');
    header('Cache-Control: must-revalidate');
    header('Pragma: public');
    header('Content-Length: ' . filesize($filePath));
    
    // Clear output buffer
    ob_clean();
    flush();
    
    // Read the file
    readfile($filePath);
    exit;

} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    header("Location: status-check.php?error=dberror");
    exit();
}