<?php
header("Content-Type: application/json");

// Verify Facebook token
$token = $_POST['token'];

// Initialize cURL
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "https://graph.facebook.com/me?fields=id,name,email&access_token=" . $token);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
$response = curl_exec($ch);
curl_close($ch);

$data = json_decode($response, true);

if (isset($data['email'])) {
    // Check if user exists in your database
    $conn = new mysqli("localhost", "root", "", "idvault");
    $email = $conn->real_escape_string($data['email']);
    
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
        $name = $data['name'] ?? 'Facebook User';
        $stmt = $conn->prepare("INSERT INTO users (name, email, password, auth_provider) VALUES (?, ?, '', 'facebook')");
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
    echo json_encode(["success" => false, "message" => "Invalid Facebook token"]);
}
?>