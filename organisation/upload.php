<?php
require_once '../public/config/database.php';

header('Content-Type: application/json');

session_start();
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_FILES['certificate'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

$allowed_types = ['application/pdf', 'image/jpeg', 'image/png'];
if (!in_array($_FILES['certificate']['type'], $allowed_types)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Only PDF, JPG, and PNG files are allowed']);
    exit;
}

// Define consistent upload directory structure
$baseDir = $_SERVER['DOCUMENT_ROOT'] . '/DSW-website/public/uploads/';
$certDir = $baseDir . 'certifications/';

// Create directories if they don't exist
if (!file_exists($certDir)) {
    mkdir($certDir, 0755, true);
}

// Generate unique filename
$extension = pathinfo($_FILES['certificate']['name'], PATHINFO_EXTENSION);
$filename = 'cert_' . $user_id . '_' . time() . '.' . $extension;
$target_path = $certDir . $filename;

// Relative path for database (from public directory)
$relative_path = 'uploads/certifications/' . $filename;

if (!move_uploaded_file($_FILES['certificate']['tmp_name'], $target_path)) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to upload file']);
    exit;
}

try {
    $pdo->beginTransaction();
    
    // Insert into documents table
    $stmt = $pdo->prepare("INSERT INTO documents (user_id, document_type, status, document_path) VALUES (?, 'certified', 'pending', ?)");
    $stmt->execute([$user_id, $relative_path]);
    $document_id = $pdo->lastInsertId();
    
    // Insert certified document data
    $stmt = $pdo->prepare("INSERT INTO certified_documents (document_id, original_filename, file_path) VALUES (?, ?, ?)");
    $stmt->execute([$document_id, $_FILES['certificate']['name'], $relative_path]);
    
    $pdo->commit();
    
    echo json_encode(['success' => true, 'message' => 'Document uploaded successfully!']);
} catch (PDOException $e) {
    // Delete the uploaded file if database operation fails
    if (file_exists($target_path)) {
        unlink($target_path);
    }
    
    $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>