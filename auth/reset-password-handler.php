<?php
header("Content-Type: application/json");

$data = json_decode(file_get_contents('php://input'), true);
$token = trim($data['token'] ?? '');
$password = trim($data['password'] ?? '');

if (!$token || !$password) {
    echo json_encode(["success" => false, "message" => "Token and password are required"]);
    exit();
}

$conn = new mysqli("localhost", "root", "", "idvault");
if ($conn->connect_error) {
    echo json_encode(["success" => false, "message" => "Database connection failed"]);
    exit();
}

// Check if token is valid and not expired
$stmt = $conn->prepare("SELECT id FROM users WHERE reset_token = ? AND reset_expires > NOW()");
$stmt->bind_param("s", $token);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(["success" => false, "message" => "Invalid or expired token"]);
    exit();
}

// Update password and clear reset token
$user = $result->fetch_assoc();
$hashedPassword = password_hash($password, PASSWORD_DEFAULT);

$stmt = $conn->prepare("UPDATE users SET password = ?, reset_token = NULL, reset_expires = NULL WHERE id = ?");
$stmt->bind_param("si", $hashedPassword, $user['id']);

if ($stmt->execute()) {
    echo json_encode(["success" => true, "message" => "Password updated successfully"]);
} else {
    echo json_encode(["success" => false, "message" => "Failed to update password"]);
}
?>