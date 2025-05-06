<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/encryption.php';

session_start();

$input = json_decode(file_get_contents('php://input'), true);

// Validate input
if (!isset($input['email']) || !isset($input['password'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Email and password are required']);
    exit;
}

$email = filter_var($input['email'], FILTER_SANITIZE_EMAIL);
$password = $input['password'];
$remember = isset($input['remember']) && $input['remember'];

try {
    $db = Database::getConnection();
    
    // Get user from database
    $stmt = $db->prepare("SELECT user_id, full_name, email, password_hash, salt, role FROM users WHERE email = ? AND is_verified = 1");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user || !verifyPassword($password, $user['password_hash'], $user['salt'])) {
        // Log failed attempt
        logFailedAttempt($email, $_SERVER['REMOTE_ADDR']);
        
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Invalid email or password']);
        exit;
    }

    // Regenerate session ID to prevent fixation
    session_regenerate_id(true);
    
    // Set session variables
    $_SESSION['user_id'] = $user['user_id'];
    $_SESSION['email'] = $user['email'];
    $_SESSION['full_name'] = $user['full_name'];
    $_SESSION['role'] = $user['role'];
    $_SESSION['logged_in'] = true;
    $_SESSION['last_activity'] = time();

    // Set remember me cookie if requested
    if ($remember) {
        $token = bin2hex(random_bytes(32));
        $expiry = time() + 60 * 60 * 24 * 30; // 30 days
        
        setcookie(
            'remember_token',
            $token,
            $expiry,
            '/',
            '',
            true,  // HTTPS only
            true   // HTTP only
        );
        
        // Store hashed token in database
        $hashedToken = hash('sha256', $token);
        $stmt = $db->prepare("INSERT INTO auth_tokens (user_id, token_hash, expires_at) VALUES (?, ?, ?)");
        $stmt->execute([$user['user_id'], $hashedToken, date('Y-m-d H:i:s', $expiry)]);
    }

    // Update last login
    $stmt = $db->prepare("UPDATE users SET last_login = NOW() WHERE user_id = ?");
    $stmt->execute([$user['user_id']]);

    // Return success with role for redirection
    echo json_encode([
        'success' => true,
        'role' => $user['role'],
        'message' => 'Login successful'
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}

function logFailedAttempt($email, $ip) {
    try {
        $db = Database::getConnection();
        $stmt = $db->prepare("INSERT INTO login_attempts (email, ip_address, attempt_time) VALUES (?, ?, NOW())");
        $stmt->execute([$email, $ip]);
    } catch (PDOException $e) {
        error_log("Failed to log login attempt: " . $e->getMessage());
    }
}
?>
