<?php
session_start();
header("Content-Type: application/json");

require_once '../config/database.php'; // Include your database configuration

$conn = new mysqli("localhost", "root", "", "idvault");
if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(["message" => "Database connection failed."]);
    exit();
}

$data = json_decode(file_get_contents("php://input"), true);
$email = trim($data["email"] ?? '');
$password = trim($data["password"] ?? '');
$isAdminLogin = $data["isAdminLogin"] ?? false;

if (!$email || !$password) {
    http_response_code(400);
    echo json_encode(["message" => "Please enter both email and password."]);
    exit();
}

// Fetch user by email
$stmt = $conn->prepare("SELECT id, name, email, password, is_admin FROM users WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($user = $result->fetch_assoc()) {
    if (password_verify($password, $user["password"])) {
        // Check admin privileges if admin login was requested
        if ($isAdminLogin && !$user["is_admin"]) {
            http_response_code(403);
            echo json_encode(["message" => "Admin access denied."]);
            exit();
        }
        
        // Store user data in session
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['is_admin'] = $user['is_admin'];  //admin123 b
        
        // Determine redirect URL based on admin status
        $redirectUrl = $user["is_admin"] ? "../admin-dashboard.php" : "../home.php";
        
        echo json_encode([
            "message" => "Login successful.",
            "redirect" => $redirectUrl,
            "is_admin" => $user["is_admin"]
        ]);
    } else {
        http_response_code(401);
        echo json_encode(["message" => "Incorrect password."]);
    }
} else {
    http_response_code(404);
    echo json_encode(["message" => "User not found."]);
}

$stmt->close();
$conn->close();
?>

