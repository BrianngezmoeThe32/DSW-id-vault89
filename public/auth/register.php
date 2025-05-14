<?php
header('Content-Type: application/json');

// Enable maximum error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../../logs/php_errors.log');

// Create logs directory if it doesn't exist
if (!file_exists(__DIR__ . '/../../logs')) {
    mkdir(__DIR__ . '/../../logs', 0777, true);
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/encryption.php';

// Log the raw input for debugging
$rawInput = file_get_contents('php://input');
error_log("[DEBUG] Raw input received: " . $rawInput);

// Verify we received data
if (empty($rawInput)) {
    error_log("[ERROR] Empty input received");
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'No data received',
        'debug' => [
            'input' => $rawInput,
            'headers' => getallheaders()
        ]
    ]);
    exit;
}

// Decode JSON input
$input = json_decode($rawInput, true);

// Check for JSON decoding errors
if (json_last_error() !== JSON_ERROR_NONE) {
    $jsonError = json_last_error_msg();
    error_log("[ERROR] JSON decode failed: " . $jsonError);
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Invalid JSON data: ' . $jsonError,
        'debug' => [
            'raw_input' => $rawInput,
            'json_error' => $jsonError
        ]
    ]);
    exit;
}

// Log decoded input for debugging
error_log("[DEBUG] Decoded input: " . print_r($input, true));

// Validate required fields
$required = ['name', 'email', 'phone', 'password'];
$missingFields = [];

foreach ($required as $field) {
    if (empty($input[$field])) {
        $missingFields[] = $field;
    }
}

if (!empty($missingFields)) {
    error_log("[ERROR] Missing fields: " . implode(', ', $missingFields));
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Missing required fields: ' . implode(', ', $missingFields),
        'debug' => [
            'received_fields' => array_keys($input),
            'missing_fields' => $missingFields
        ]
    ]);
    exit;
}

// Sanitize inputs
$name = filter_var($input['name'], FILTER_SANITIZE_STRING);
$email = filter_var($input['email'], FILTER_SANITIZE_EMAIL);
$phone = filter_var($input['phone'], FILTER_SANITIZE_STRING);
$password = $input['password'];

// Validate email format
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    error_log("[ERROR] Invalid email format: " . $email);
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Invalid email format',
        'debug' => [
            'email_received' => $email
        ]
    ]);
    exit;
}

// Validate password strength
if (strlen($password) < 8) {
    error_log("[ERROR] Password too short");
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Password must be at least 8 characters',
        'debug' => [
            'password_length' => strlen($password)
        ]
    ]);
    exit;
}

try {
    // Get database connection
    $db = Database::getConnection();
    error_log("[DEBUG] Database connection established");

    // Check for existing email
    $stmt = $db->prepare("SELECT user_id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    
    if ($stmt->fetch()) {
        error_log("[ERROR] Email already exists: " . $email);
        http_response_code(409);
        echo json_encode([
            'success' => false,
            'message' => 'Email already registered',
            'debug' => [
                'email' => $email
            ]
        ]);
        exit;
    }
    
    // Hash password
    $salt = bin2hex(random_bytes(16));
    $passwordHash = hashPassword($password, $salt);
    error_log("[DEBUG] Password hashed successfully");
    
    // Generate verification token
    $verificationToken = bin2hex(random_bytes(32));
    error_log("[DEBUG] Verification token generated");

    // Insert user
    $stmt = $db->prepare("INSERT INTO users (full_name, email, phone, password_hash, salt, verification_token) 
                         VALUES (?, ?, ?, ?, ?, ?)");
    $insertResult = $stmt->execute([$name, $email, $phone, $passwordHash, $salt, $verificationToken]);
    
    if (!$insertResult) {
        throw new PDOException("Insert statement failed");
    }

    $userId = $db->lastInsertId();
    error_log("[SUCCESS] User registered successfully. ID: " . $userId);

    // Success response
    echo json_encode([
        'success' => true,
        'message' => 'Registration successful. Please check your email to verify your account.',
        'debug' => [
            'user_id' => $userId,
            'email_sent_to' => $email
        ]
    ]);

} catch (PDOException $e) {
    error_log("[DATABASE ERROR] " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database error occurred',
        'debug' => [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]
    ]);
} catch (Exception $e) {
    error_log("[GENERAL ERROR] " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'An unexpected error occurred',
        'debug' => [
            'error' => $e->getMessage()
        ]
    ]);
}