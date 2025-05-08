<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/database.php';

$input = json_decode(file_get_contents('php://input'), true);
$email = filter_var($input['email'], FILTER_SANITIZE_EMAIL);

try {
    $db = Database::getConnection();
    
    
    $stmt = $db->prepare("SELECT user_id, verification_token FROM users WHERE email = ? AND is_verified = 0");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        echo json_encode([
            'status' => 'error',
            'message' => 'No pending verification found for this email.'
        ]);
        exit;
    }

    
    $verificationLink = "https://bngezmoe@gmail.com/verify_email.php?token=" . $user['verification_token'];
    
    // This would be the actual email sending code in production:
    /*
    $to = $email;
    $subject = "IdVault - Resend Verification Email";
    $message = "Please click the following link to verify your account:\n\n$verificationLink";
    $headers = "From: no-reply@yourdomain.com";
    mail($to, $subject, $message, $headers);
    */

    echo json_encode([
        'status' => 'success',
        'message' => 'Verification email resent successfully.'
    ]);

} catch (PDOException $e) {
    error_log("Database error in resend_verification.php: " . $e->getMessage());
    echo json_encode([
        'status' => 'error',
        'message' => 'Could not process your request. Please try again later.'
    ]);
}
?>