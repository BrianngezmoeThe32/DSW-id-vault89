<?php
header('Content-Type: application/json');

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database connection
$host = "localhost";
$db = "idvault";
$user = "root";
$pass = "";

try {
    $conn = new PDO("mysql:host=$host;dbname=$db", $user, $pass);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Get email from query parameter
    $email = $_GET['email'] ?? '';
    
    // Log the received email
    error_log("Received email: " . $email);
    
    if (empty($email)) {
        throw new Exception('Email is required');
    }
    
    // Get all locations for the user, ordered by time
    $stmt = $conn->prepare("SELECT id, email, lat, `long`, DATE(time) as date FROM location WHERE email = :email ORDER BY time DESC");
    $stmt->execute([':email' => $email]);
    $locations = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Log the query results
    error_log("Found " . count($locations) . " locations for email: " . $email);
    error_log("Locations: " . json_encode($locations));
    
    echo json_encode([
        'success' => true,
        'locations' => $locations,
        'debug' => [
            'email' => $email,
            'count' => count($locations)
        ]
    ]);
    
} catch(Exception $e) {
    error_log("Error in get_locations.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error retrieving locations: ' . $e->getMessage(),
        'debug' => [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]
    ]);
}
?> 