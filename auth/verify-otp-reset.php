<?php
session_start();
require_once '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $otp = filter_var($_POST['otp'], FILTER_SANITIZE_STRING);
    $newPassword = $_POST['new_password'];
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid email format']);
        exit;
    }

    try {
        // Verify OTP
        $stmt = $pdo->prepare("SELECT id FROM users 
                               WHERE email = ? AND reset_token = ? AND reset_expires > NOW()");
        $stmt->execute([$email, $otp]);
        
        if ($stmt->rowCount() === 0) {
            echo json_encode(['status' => 'error', 'message' => 'Invalid or expired OTP']);
            exit;
        }

        // Hash the new password
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

        // Update user's password and clear reset token
        $stmt = $pdo->prepare("UPDATE users SET password = ?, reset_token = NULL, reset_expires = NULL WHERE email = ?");
        $stmt->execute([$hashedPassword, $email]);

        echo json_encode([
            'status' => 'success',
            'message' => 'Password has been reset successfully'
        ]);

    } catch (PDOException $e) {
        error_log("Database error in verify-otp-reset.php: " . $e->getMessage());
        echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
    }
}
?> 