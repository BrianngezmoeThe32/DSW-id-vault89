// approve_document.php
<?php
require_once '../config/database.php';

header('Content-Type: application/json');

session_start();
// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Not authorized']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$document_id = $data['document_id'] ?? null;
$action = $data['action'] ?? null; // 'approve' or 'reject'
$notes = $data['notes'] ?? '';

if (!$document_id || !in_array($action, ['approve', 'reject'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

try {
    // Update document status
    $status = $action === 'approve' ? 'approved' : 'rejected';
    $stmt = $pdo->prepare("UPDATE documents SET status = ?, admin_notes = ? WHERE id = ?");
    $stmt->execute([$status, $notes, $document_id]);
    
    // If approved, generate PDF (you'll need to implement this)
    if ($action === 'approve') {
        $stmt = $pdo->prepare("SELECT document_type FROM documents WHERE id = ?");
        $stmt->execute([$document_id]);
        $doc = $stmt->fetch();
        
        $pdf_path = '';
        if ($doc['document_type'] === 'affidavit') {
            $pdf_path = generate_affidavit_pdf($document_id, $pdo);
        } elseif ($doc['document_type'] === 'certified') {
            $pdf_path = generate_certified_pdf($document_id, $pdo);
        }
        
        if ($pdf_path) {
            $stmt = $pdo->prepare("UPDATE documents SET pdf_path = ? WHERE id = ?");
            $stmt->execute([$pdf_path, $document_id]);
        }
    }
    
    echo json_encode(['success' => true, 'message' => "Document {$action}d successfully"]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}

function generate_affidavit_pdf($document_id, $pdo) {
    // Fetch affidavit data
    $stmt = $pdo->prepare("
        SELECT a.*, u.name as user_name, u.email 
        FROM affidavit_data a
        JOIN documents d ON a.document_id = d.id
        JOIN users u ON d.user_id = u.id
        WHERE a.document_id = ?
    ");
    $stmt->execute([$document_id]);
    $affidavit = $stmt->fetch();
    
    if (!$affidavit) return null;
    
    // Generate PDF (using a library like TCPDF or Dompdf)
    require_once 'tcpdf/tcpdf.php';
    
    $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
    $pdf->SetCreator(PDF_CREATOR);
    $pdf->SetAuthor('IdVault System');
    $pdf->SetTitle('Affidavit - ' . $affidavit['name']);
    $pdf->AddPage();
    
    // Build HTML content
    $html = '<h1>AFFIDAVIT</h1>';
    $html .= '<p>I, ' . htmlspecialchars($affidavit['name']) . ', ID Number: ' . htmlspecialchars($affidavit['id_number']) . '</p>';
    $html .= '<p>Age: ' . htmlspecialchars($affidavit['age']) . '</p>';
    $html .= '<p>Residing at: ' . htmlspecialchars($affidavit['residing_address']) . '</p>';
    // Add all other fields...
    
    $html .= '<p>Declaration: ' . nl2br(htmlspecialchars($affidavit['declaration'])) . '</p>';
    $html .= '<p>Signed at ' . htmlspecialchars($affidavit['place']) . ' on ' . htmlspecialchars($affidavit['date']) . '</p>';
    
    $pdf->writeHTML($html, true, false, true, false, '');
    
    // Save to file
    $pdf_dir = 'pdfs/';
    if (!file_exists($pdf_dir)) {
        mkdir($pdf_dir, 0777, true);
    }
    
    $filename = 'affidavit_' . $document_id . '.pdf';
    $filepath = $pdf_dir . $filename;
    $pdf->Output($filepath, 'F');
    
    return $filepath;
}

function generate_certified_pdf($document_id, $pdo) {
    // Similar implementation for certified documents
    // This would create a PDF with the certified document and approval stamp
}
?>