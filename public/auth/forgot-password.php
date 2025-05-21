<?php
header("Content-Type: application/json");

$data = json_decode(file_get_contents('php://input'), true);
$email = trim($data['email'] ?? '');

if (!$email) {
    echo json_encode(["success" => false, "message" => "Email is required"]);
    exit();
}

$conn = new mysqli("localhost", "root", "", "idvault");
if ($conn->connect_error) {
    echo json_encode(["success" => false, "message" => "Database connection failed"]);
    exit();
}

// Check if email exists
$stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(["success" => false, "message" => "Email not found"]);
    exit();
}

// Generate reset token
$token = bin2hex(random_bytes(32));
$expires = date("Y-m-d H:i:s", time() + 3600); // 1 hour expiration

// Store token in database
$stmt = $conn->prepare("UPDATE users SET reset_token = ?, reset_expires = ? WHERE email = ?");
$stmt->bind_param("sss", $token, $expires, $email);
$stmt->execute();

// In a real application, you would send an email with the reset link
// For this example, we'll just return the token (in production, don't do this)
$reset_link = "http://yourdomain.com/reset-password.php?token=$token";

// Here you would normally send an email with $reset_link
// mail($email, "Password Reset", "Click here to reset your password: $reset_link");

echo json_encode([
    "success" => true,
    "message" => "Reset link sent to your email",
    "debug_link" => $reset_link // Remove this in production
]);
?>