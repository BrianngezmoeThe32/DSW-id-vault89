<?php
// Prevent any output before JSON response
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

session_start();
require_once '../config/database.php';

function generateOTP() {
    return str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
}

function sendOTPEmail($email, $otp) {
    $to = $email;
    $subject = "Password Reset OTP";
    
    // HTML email template
    $message = "
    <html>
    <head>
        <title>Password Reset OTP</title>
    </head>
    <body>
        <h2>Password Reset Request</h2>
        <p>Your OTP for password reset is: <strong>{$otp}</strong></p>
        <p>This OTP will expire in 15 minutes.</p>
        <p>If you didn't request this, please ignore this email.</p>
        <br>
        <p>Best regards,<br>Your Application Team</p>
    </body>
    </html>
    ";
    
    // Set content-type header for sending HTML email
    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    $headers .= "From: test@localhost" . "\r\n";
    $headers .= "Reply-To: test@localhost" . "\r\n";
    $headers .= "X-Mailer: PHP/" . phpversion();

    error_log("Attempting to send email to: " . $email);
    $result = mail($to, $subject, $message, $headers);
    error_log("Mail send result: " . ($result ? "success" : "failed"));
    return $result;
}

// Ensure we're sending JSON response
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    error_log("Received email request for: " . $email);
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        error_log("Invalid email format: " . $email);
        echo json_encode(['status' => 'error', 'message' => 'Invalid email format']);
        exit;
    }

    try {
        // Check if user exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        
        if ($stmt->rowCount() === 0) {
            error_log("Email not found in database: " . $email);
            echo json_encode(['status' => 'error', 'message' => 'Email not found']);
            exit;
        }

        // Generate OTP
        $otp = generateOTP();
        $expiry = date('Y-m-d H:i:s', strtotime('+15 minutes'));
        error_log("Generated OTP for " . $email . ": " . $otp);

        // Store OTP in users table
        $stmt = $pdo->prepare("UPDATE users SET reset_token = ?, reset_expires = ? WHERE email = ?");
        $stmt->execute([$otp, $expiry, $email]);
        error_log("Stored OTP in database for: " . $email);

        // Return success with OTP for EmailJS to use
        echo json_encode([
            'status' => 'success',
            'message' => 'OTP generated successfully',
            'otp' => $otp
        ]);

    } catch (PDOException $e) {
        error_log("Database error in generate-otp.php: " . $e->getMessage());
        echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
    } catch (Exception $e) {
        error_log("General error in generate-otp.php: " . $e->getMessage());
        echo json_encode(['status' => 'error', 'message' => 'An error occurred: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
}
?> 