<?php
session_start();
require_once '../public/config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

$user_id = $_SESSION['user_id'];

try {
    $pdo->beginTransaction();
    
    // Handle file upload if exists
    $file_path = null;
    if (isset($_FILES['document']) && $_FILES['document']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = '../public/uploads/documents/affidavits/';
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        $affidavitDir = $uploadDir . 'affidavits/';
        if (!file_exists($affidavitDir)) {
            mkdir($affidavitDir, 0755, true);
        }
        
        $filename = uniqid() . '_' . basename($_FILES['document']['name']);
        $target_path = $uploadDir . $filename;
        $relative_path = 'uploads/documents/affidavits/' . $filename;
        
        if (move_uploaded_file($_FILES['document']['tmp_name'], $target_path)) {
            $file_path = $relative_path;
        } else {
            throw new Exception("Failed to upload file");
        }
    }
    if ($file_path) {
    // Update affidavit_data with file path
        $stmt = $pdo->prepare("UPDATE affidavit_data SET file_path = ? WHERE document_id = ?");
        $stmt->execute([$file_path, $document_id]);
    }
    
    // Insert into documents table
    $stmt = $pdo->prepare("INSERT INTO documents (user_id, document_type, status, document_path) VALUES (?, 'affidavit', 'pending', ?)");
    $stmt->execute([$user_id, $file_path]);
    $document_id = $pdo->lastInsertId();
    
    // Insert affidavit data
    $stmt = $pdo->prepare("INSERT INTO affidavit_data (
        document_id, name, id_number, age, residing_address, working_address, 
        tel_w, tel_h, tel_cell, declaration, place, date, time, signature
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    
    $stmt->execute([
        $document_id,
        $_POST['name'],
        $_POST['idNumber'],
        $_POST['age'],
        $_POST['residingAddress'],
        $_POST['workingAddress'],
        $_POST['telW'],
        $_POST['telH'],
        $_POST['telCell'],
        $_POST['declaration'],
        $_POST['place'],
        $_POST['date'],
        $_POST['time'],
        $_POST['signature']
    ]);
    
    $pdo->commit();
    
    echo json_encode(['success' => true, 'message' => 'Affidavit submitted successfully!']);
} catch (Exception $e) {
    $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>