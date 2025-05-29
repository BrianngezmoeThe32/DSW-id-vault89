<?php
header('Content-Type: application/json');

// Database connection
$host = "localhost";
$db = "idvault";
$user = "root";
$pass = "";

try {
    $conn = new PDO("mysql:host=$host;dbname=$db", $user, $pass);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Get POST data
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['latitude']) || !isset($data['longitude']) || !isset($data['email'])) {
        throw new Exception('Missing required data');
    }
    
    // Prepare and execute the insert statement
    $stmt = $conn->prepare("INSERT INTO location (email, lat, `long`) VALUES (:email, :lat, :long)");
    $stmt->execute([
        ':email' => $data['email'],
        ':lat' => $data['latitude'],
        ':long' => $data['longitude']
    ]);
    
    // Get the inserted ID
    $id = $conn->lastInsertId();
    
    echo json_encode([
        'success' => true,
        'message' => 'Location stored successfully',
        'id' => $id
    ]);
    
} catch(Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error storing location: ' . $e->getMessage()
    ]);
}
?> 