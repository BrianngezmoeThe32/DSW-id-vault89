<?php





session_start();
require_once '../config/database.php';
require_once __DIR__ . '/../../vendor/autoload.php';


header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || !$_SESSION['is_admin']) {
    echo json_encode(['success' => false, 'message' => 'Not authorized']);
    exit;
}

// Enable error logging
ini_set('log_errors', 1);
ini_set('error_log', '../logs/php_errors.log');

$input = json_decode(file_get_contents('php://input'), true);
$docId = $input['doc_id'] ?? null;
$action = $input['action'] ?? null;

if (!$docId || !in_array($action, ['approve', 'reject'])) {
    error_log("Invalid request - docId: $docId, action: $action");
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

try {
    $pdo->beginTransaction();
    
    $status = $action === 'approve' ? 'approved' : 'rejected';
    
    // Get document details with error handling
    $stmt = $pdo->prepare("
        SELECT d.*, u.name as user_name, u.email as user_email,
               d.document_type,
               CASE 
                   WHEN d.document_type = 'affidavit' THEN ad.file_path
                   WHEN d.document_type = 'certified' THEN cd.file_path
                   ELSE d.document_path
               END AS document_path
        FROM documents d
        JOIN users u ON d.user_id = u.id
        LEFT JOIN certified_documents cd ON d.id = cd.document_id AND d.document_type = 'certified'
        LEFT JOIN affidavit_data ad ON d.id = ad.document_id AND d.document_type = 'affidavit'
        WHERE d.id = ?
    ");
    
    if (!$stmt->execute([$docId])) {
        throw new Exception("Failed to execute document query");
    }
    
    $document = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$document) {
        throw new Exception("Document not found");
    }

    // Generate PDF only for approval
    $pdfPath = null;
    if ($action === 'approve') {
        try {
            $pdfPath = generateApprovalPdf($document, $_SESSION['user_name']);
        } catch (Exception $e) {
            error_log("PDF Generation Error: " . $e->getMessage());
            throw new Exception("Failed to generate approval PDF");
        }
    }

    // Update document status
    $updateStmt = $pdo->prepare("
        UPDATE documents 
        SET status = ?, reviewed_by = ?, reviewed_at = NOW(), pdf_path = ?
        WHERE id = ?
    ");
    
    if (!$updateStmt->execute([
        $status,
        $_SESSION['user_id'],
        $pdfPath,
        $docId
    ])) {
        throw new Exception("Failed to update document status");
    }
    
    // Get document info for notification
    $notifStmt = $pdo->prepare("SELECT user_id, document_type FROM documents WHERE id = ?");
    if (!$notifStmt->execute([$docId])) {
        throw new Exception("Failed to get document info for notification");
    }
    $docInfo = $notifStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$docInfo) {
        throw new Exception("Failed to fetch document info");
    }
    
    // Add notification
    $notifInsert = $pdo->prepare("
        INSERT INTO notifications 
        (user_id, title, message, type, document_type, status) 
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    
    $title = "Document " . ucfirst($status);
    $message = "Your " . $docInfo['document_type'] . " has been " . $status;
    
    if (!$notifInsert->execute([
        $docInfo['user_id'],
        $title,
        $message,
        $action,
        $docInfo['document_type'],
        $status
    ])) {
        throw new Exception("Failed to create notification");
    }
    
    // Add admin log
    $logStmt = $pdo->prepare("
        INSERT INTO admin_logs 
        (admin_id, action_type, description, document_id, user_id) 
        VALUES (?, ?, ?, ?, ?)
    ");
    
    if (!$logStmt->execute([
        $_SESSION['user_id'],
        $action,
        "Document {$docId} {$status}",
        $docId,
        $docInfo['user_id']
    ])) {
        error_log("Failed to create admin log, but proceeding anyway");
        // Don't fail the whole operation just because logging failed
    }
    
    $pdo->commit();
    
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    $pdo->rollBack();
    error_log("Error in handle_document: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => 'An error occurred: ' . $e->getMessage()
    ]);
}

function generateApprovalPdf($document, $approvedBy) {
    require_once('../lib/tcpdf/tcpdf.php');
    
    try {
        // Create new PDF document
        $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
        
        // Set document information
        $pdf->SetCreator('IdVault Online');
        $pdf->SetAuthor('IdVault Online');
        $pdf->SetTitle('Approved ' . $document['document_type']);
        $pdf->SetSubject('Approved Document');
        
        // Add a page
        $pdf->AddPage();
        
        // Set font
        $pdf->SetFont('helvetica', 'B', 16);
        
        // Add logo (using JPG)
        $logoPath = '../assets/images/logo.jpg';
        if (!file_exists($logoPath)) {
            throw new Exception("Logo file not found at $logoPath");
        }
        $pdf->Image($logoPath, 10, 10, 30, '', 'JPG', '', 'T', false, 300, '', false, false, 0, false, false, false);
        
        // Document title
        $pdf->SetY(40);
        $pdf->Cell(0, 10, 'Approved ' . ucfirst($document['document_type']), 0, 1, 'C');
        $pdf->SetFont('helvetica', '', 12);
        $pdf->Cell(0, 10, 'Certificate of Approval', 0, 1, 'C');
        $pdf->Ln(10);
        
        // Document details
        $html = '<h3>Document Details</h3>';
        $html .= '<table border="0" cellpadding="4">';
        $html .= '<tr><td width="30%"><strong>Document ID:</strong></td><td>' . $document['id'] . '</td></tr>';
        $html .= '<tr><td><strong>User Name:</strong></td><td>' . $document['user_name'] . '</td></tr>';
        $html .= '<tr><td><strong>User Email:</strong></td><td>' . $document['user_email'] . '</td></tr>';
        $html .= '<tr><td><strong>Submission Date:</strong></td><td>' . $document['submission_date'] . '</td></tr>';
        $html .= '<tr><td><strong>Approval Date:</strong></td><td>' . date('Y-m-d H:i:s') . '</td></tr>';
        $html .= '<tr><td><strong>Approved By:</strong></td><td>' . $approvedBy . '</td></tr>';
        $html .= '<tr><td><strong>Organization:</strong></td><td>IdVault Online</td></tr>';
        $html .= '</table>';
        
        $pdf->writeHTML($html, true, false, true, false, '');
        
        // Approval stamp
        $pdf->Ln(20);
        $pdf->SetFont('helvetica', 'B', 12);
        $pdf->Cell(0, 10, 'Approval Stamp', 0, 1, 'C');
        $pdf->SetFont('helvetica', '', 10);
        $pdf->Cell(0, 5, 'This document has been reviewed and approved by IdVault Online', 0, 1, 'C');
        $pdf->Cell(0, 5, 'Approval Date: ' . date('Y-m-d H:i:s'), 0, 1, 'C');
        $pdf->Cell(0, 5, 'Approved By: ' . $approvedBy, 0, 1, 'C');
        
        // Create directory if it doesn't exist
        $dir = '../public/uploads/approved_docs/';
        if (!file_exists($dir)) {
            if (!mkdir($dir, 0777, true)) {
                throw new Exception("Failed to create directory $dir");
            }
        }
        
        // Generate unique filename
        $filename = 'approved_doc_' . $document['id'] . '_' . time() . '.pdf';
        $filepath = $dir . $filename;
        
        // Save PDF file
        $pdf->Output($filepath, 'F');
        
        return 'uploads/approved_docs/' . $filename;
    } catch (Exception $e) {
        error_log("PDF Generation Error: " . $e->getMessage());
        throw $e;
    }
}