<?php
require_once '../config/database.php';

session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$doc_id = $_GET['id'] ?? null;
if (!$doc_id) {
    header('Location: my-documents.php');
    exit;
}

try {
    // Fetch document details
    $stmt = $pdo->prepare("
        SELECT d.*, u.name as user_name, u.email as user_email,
               CASE 
                   WHEN d.document_type = 'affidavit' THEN 'Affidavit Certificate'
                   WHEN d.document_type = 'certified' THEN 'Certified Document'
                   ELSE 'Document'
               END as document_name
        FROM documents d
        JOIN users u ON d.user_id = u.id
        WHERE d.id = ? AND d.user_id = ? AND d.status = 'approved'
    ");
    $stmt->execute([$doc_id, $_SESSION['user_id']]);
    $document = $stmt->fetch();

    if (!$document || empty($document['pdf_path'])) {
        header('Location: my-documents.php');
        exit;
    }

    // Generate PDF path
    $filePath = $_SERVER['DOCUMENT_ROOT'] . '/DSW-website/public/' . ltrim($document['pdf_path'], '/');
    
    if (!file_exists($filePath)) {
        header('Location: my-documents.php');
        exit;
    }

    // Set headers for download
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="' . basename($filePath) . '"');
    header('Content-Length: ' . filesize($filePath));
    
    // Output the file
    readfile($filePath);
    exit;

} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    header('Location: my-documents.php');
    exit;
}