<?php
session_start();
header("Content-Type: application/json");

// Hardcoded admin credentials
$admins = [
    'admin1' => ['password' => 'Admin@1234', 'full_name' => 'Mtswene Bian'],
    'admin2' => ['password' => 'Admin@5678', 'full_name' => 'Tumelo'],
    'admin3' => ['password' => 'Admin@9012', 'full_name' => 'Sabelo'],
    'admin4' => ['password' => 'Admin@3456', 'full_name' => 'Charmaine'],
    'superadmin' => ['password' => 'Super@7890', 'full_name' => 'Brian']
];

$data = json_decode(file_get_contents("php://input"), true);
$username = trim($data["username"] ?? '');
$password = trim($data["password"] ?? '');

if (!$username || !$password) {
    http_response_code(400);
    echo json_encode(["message" => "Please enter both username and password."]);
    exit();
}

if (array_key_exists($username, $admins)) {
    if ($password === $admins[$username]['password']) {
        // Store admin data in session
        $_SESSION['admin_id'] = $username; // Using username as ID
        $_SESSION['admin_name'] = $admins[$username]['full_name'];
        $_SESSION['is_super_admin'] = ($username === 'superadmin');
        
        echo json_encode([
            "message" => "Login successful.",
            "admin" => [
                "username" => $username,
                "name" => $admins[$username]['full_name']
            ]
        ]);
    } else {
        http_response_code(401);
        echo json_encode(["message" => "Incorrect password."]);
    }
} else {
    http_response_code(404);
    echo json_encode(["message" => "Admin not found."]);
}
?>