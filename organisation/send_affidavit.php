<?php
require_once '../public/config/database.php'; // Your database connection file

header('Content-Type: application/json');

// Get user ID from session (you'll need to implement session handling)
session_start();
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

$user_id = $_SESSION['user_id'];

try {
    // Begin transaction
    $pdo->beginTransaction();
    
    // Insert into documents table
    $stmt = $pdo->prepare("INSERT INTO documents (user_id, document_type, status) VALUES (?, 'affidavit', 'pending')");
    $stmt->execute([$user_id]);
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
} catch (PDOException $e) {
    $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>