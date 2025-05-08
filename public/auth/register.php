<?php
require('login.html');
header('Content-Type: application/json');
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/encryption.php';

$input = json_decode(file_get_contents('php://input'), true);


$required = ['name', 'email', 'phone', 'password'];
foreach ($required as $field) {
    if (empty($input[$field])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => "$field is required"]);
        exit;
    }
}

$name = filter_var($input['name'], FILTER_SANITIZE_STRING);
$email = filter_var($input['email'], FILTER_SANITIZE_EMAIL);
$phone = filter_var($input['phone'], FILTER_SANITIZE_STRING);
$password = $input['password'];


if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid email format']);
    exit;
}

if (strlen($password) < 8) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Password must be at least 8 characters']);
    exit;
}

try {
    $db = Database::getConnection();
    
    
    $stmt = $db->prepare("SELECT user_id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    
    if ($stmt->fetch()) {
        http_response_code(409);
        echo json_encode(['success' => false, 'message' => 'Email already registered']);
        exit;
    }
    
    
    $salt = bin2hex(random_bytes(16));
    $passwordHash = hashPassword($password, $salt);
    
    
    $verificationToken = bin2hex(random_bytes(32));
    
    
    $stmt = $db->prepare("INSERT INTO users (full_name, email, phone, password_hash, salt, verification_token) 
                         VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$name, $email, $phone, $passwordHash, $salt, $verificationToken]);
    
    $userId = $db->lastInsertId();
    
    // Send verification email (implementation omitted)
    // sendVerificationEmail($email, $name, $verificationToken);
    
    echo json_encode([
        'success' => true,
        'message' => 'Registration successful. Please check your email to verify your account.'
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>