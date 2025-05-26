<?php
header("Content-Type: application/json");

$conn = new mysqli("localhost", "root", "", "idvault");
if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(["message" => "Database connection failed."]);
    exit();
}

$data = json_decode(file_get_contents("php://input"), true);
$email = trim($data["email"] ?? '');
$password = trim($data["password"] ?? '');

if (!$email || !$password) {
    http_response_code(400);
    echo json_encode(["message" => "Please enter both email and password."]);
    exit();
}

// Fetch user by email
$stmt = $conn->prepare("SELECT id, name, email, password FROM users WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($user = $result->fetch_assoc()) {
    if (password_verify($password, $user["password"])) {
        echo json_encode([
            "message" => "Login successful.",
            "user" => [
                "id" => $user["id"],
                "name" => $user["name"],
                "email" => $user["email"]
            ]
        ]);
        // After successful password verification:
        session_start();
        $_SESSION['user_id'] = $user['id'];
// Add any other user data you need
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

