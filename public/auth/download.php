<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

$docId = $_GET['id'] ?? null;
if (!$docId) {
    header("Location: status-check.php");
    exit();
}

try {
    // Get document info
    $stmt = $pdo->prepare("
        SELECT d.*, 
               CASE 
                   WHEN d.document_type = 'affidavit' THEN ad.file_path
                   WHEN d.document_type = 'certified' THEN cd.file_path
                   ELSE d.document_path
               END AS file_path
        FROM documents d
        LEFT JOIN certified_documents cd ON d.id = cd.document_id AND d.document_type = 'certified'
        LEFT JOIN affidavit_data ad ON d.id = ad.document_id AND d.document_type = 'affidavit'
        WHERE d.id = ? AND d.user_id = ?
    ");
    $stmt->execute([$docId, $_SESSION['user_id']]);
    $document = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$document || empty($document['file_path'])) {
        header("Location: status-check.php?error=notfound");
        exit();
    }

    $filePath = '../' . ltrim($document['file_path'], '/');
    if (!file_exists($filePath)) {
        header("Location: status-check.php?error=filenotfound");
        exit();
    }

    // Set headers for download
    header('Content-Description: File Transfer');
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="' . basename($filePath) . '"');
    header('Expires: 0');
    header('Cache-Control: must-revalidate');
    header('Pragma: public');
    header('Content-Length: ' . filesize($filePath));
    readfile($filePath);
    exit;

} catch (PDOException $e) {
    header("Location: status-check.php?error=dberror");
    exit();
}
?>