<?php
header("Content-Type: application/json");

$conn = new mysqli("localhost", "root", "", "idvault");
if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(["message" => "Database connection failed."]);
    exit();
}

$data = json_decode(file_get_contents("php://input"), true);
$name = trim($data["name"] ?? '');
$email = trim($data["email"] ?? '');
$password = trim($data["password"] ?? '');

if (!$name || !$email || !$password) {
    http_response_code(400);
    echo json_encode(["message" => "Please fill in all fields."]);
    exit();
}

// Check if user already exists
$stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows > 0) {
    http_response_code(409); // Conflict
    echo json_encode(["message" => "User already exists."]);
    $stmt->close();
    $conn->close();
    exit();
}
$stmt->close();

// Hash the password
$hashedPassword = password_hash($password, PASSWORD_DEFAULT);

// Insert new user
$stmt = $conn->prepare("INSERT INTO users (name, email, password) VALUES (?, ?, ?)");
$stmt->bind_param("sss", $name, $email, $hashedPassword);

if ($stmt->execute()) {
    echo json_encode(["message" => "Registration successful."]);
} else {
    http_response_code(500);
    echo json_encode(["message" => "Registration failed."]);
}

$stmt->close();
$conn->close();
?>

