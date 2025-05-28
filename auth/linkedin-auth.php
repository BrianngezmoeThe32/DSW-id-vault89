<?php
header("Content-Type: application/json");

$data = json_decode(file_get_contents('php://input'), true);

if (isset($data['emailAddress'])) {
    // Check if user exists in your database
    $conn = new mysqli("localhost", "root", "", "idvault");
    $email = $conn->real_escape_string($data['emailAddress']);
    
    $stmt = $conn->prepare("SELECT id, name, email FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($user = $result->fetch_assoc()) {
        // User exists - log them in
        echo json_encode([
            "success" => true,
            "user" => $user
        ]);
    } else {
        // Create new user
        $name = ($data['firstName'] ?? '') . ' ' . ($data['lastName'] ?? '');
        $stmt = $conn->prepare("INSERT INTO users (name, email, password, auth_provider) VALUES (?, ?, '', 'linkedin')");
        $stmt->bind_param("ss", $name, $email);
        
        if ($stmt->execute()) {
            $user_id = $conn->insert_id;
            echo json_encode([
                "success" => true,
                "user" => [
                    "id" => $user_id,
                    "name" => $name,
                    "email" => $email
                ]
            ]);
        } else {
            echo json_encode(["success" => false, "message" => "Failed to create user"]);
        }
    }
} else {
    echo json_encode(["success" => false, "message" => "Invalid LinkedIn data"]);
}
?>