<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/database.php';

$input = json_decode(file_get_contents('php://input'), true);
$userId = filter_var($input['userId'], FILTER_SANITIZE_NUMBER_INT);
$token = filter_var($input['token'], FILTER_SANITIZE_STRING);

try {
    $db = Database::getConnection();
    
    // Verify the token matches the user
    $stmt = $db->prepare("UPDATE users SET is_verified = 1 WHERE user_id = ? AND verification_token = ?");
    $stmt->execute([$userId, $token]);
    
    if ($stmt->rowCount() > 0) {
        // Create a verification record
        $stmt = $db->prepare("INSERT INTO email_verifications (user_id, verified_at) VALUES (?, NOW())");
        $stmt->execute([$userId]);
        
        echo json_encode([
            'status' => 'success',
            'message' => 'Email verified successfully.'
        ]);
    } else {
        echo json_encode([
            'status' => 'error',
            'message' => 'Invalid verification token or user ID.'
        ]);
    }

} catch (PDOException $e) {
    error_log("Database error in verify_email.php: " . $e->getMessage());
    echo json_encode([
        'status' => 'error',
        'message' => 'Could not verify your email. Please try again later.'
    ]);
}
?>
