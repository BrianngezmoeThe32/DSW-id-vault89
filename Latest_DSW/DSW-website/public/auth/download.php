// download.php
<?php
require_once '../config/database.php';

session_start();
if (!isset($_SESSION['user_id'])) {
    header('HTTP/1.0 401 Unauthorized');
    exit;
}

$document_id = $_GET['id'] ?? null;
if (!$document_id) {
    header('HTTP/1.0 400 Bad Request');
    exit;
}

try {
    // Verify the document belongs to the user
    $stmt = $pdo->prepare("
        SELECT d.pdf_path, d.document_type, a.name as affidavit_name, u.name as user_name
        FROM documents d
        LEFT JOIN affidavit_data a ON d.id = a.document_id AND d.document_type = 'affidavit'
        JOIN users u ON d.user_id = u.id
        WHERE d.id = ? AND d.user_id = ? AND d.status = 'approved' AND d.pdf_path IS NOT NULL
    ");
    $stmt->execute([$document_id, $_SESSION['user_id']]);
    $document = $stmt->fetch();
    
    if (!$document) {
        header('HTTP/1.0 404 Not Found');
        exit;
    }
    
    $filepath = $document['pdf_path'];
    $filename = $document['document_type'] === 'affidavit' 
        ? 'Affidavit_' . $document['affidavit_name'] . '.pdf'
        : 'Certified_Document_' . $document['user_name'] . '.pdf';
    
    if (!file_exists($filepath)) {
        header('HTTP/1.0 404 Not Found');
        exit;
    }
    
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Length: ' . filesize($filepath));
    readfile($filepath);
    exit;
} catch (PDOException $e) {
    header('HTTP/1.0 500 Internal Server Error');
    exit;
}
?>