<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Log file setup
$log_file = '../logs/study_permit.log';
if (!file_exists('../logs')) {
    mkdir('../logs', 0777, true);
}

function writeLog($message) {
    global $log_file;
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($log_file, "[$timestamp] $message\n", FILE_APPEND);
}

try {
    writeLog("Starting study permit application processing");
    
    if (!file_exists('../public/config/database.php')) {
        throw new Exception("Database configuration file not found");
    }
    
    require_once '../public/config/database.php';
    writeLog("Database configuration loaded");
                                                                                                        
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception("Invalid request method: " . $_SERVER['REQUEST_METHOD']);
    }

    writeLog("POST data received: " . print_r($_POST, true));
    writeLog("FILES data received: " . print_r($_FILES, true));

    // Create upload directory if it doesn't exist
    $upload_dir = '../uploads/study_permit/';
    if (!file_exists($upload_dir)) {
        if (!mkdir($upload_dir, 0777, true)) {
            throw new Exception("Failed to create upload directory");
        }
        writeLog("Created upload directory: $upload_dir");
    }

    // Handle file uploads
    $accept_letter_path = '';
    $proof_of_financial_path = '';

    // Process acceptance letter
    if (isset($_FILES['acceptance_letter'])) {
        writeLog("Processing acceptance letter upload");
        if ($_FILES['acceptance_letter']['error'] !== 0) {
            throw new Exception("Acceptance letter upload error: " . $_FILES['acceptance_letter']['error']);
        }
        
        $file_extension = strtolower(pathinfo($_FILES['acceptance_letter']['name'], PATHINFO_EXTENSION));
        $new_filename = uniqid() . '_acceptance.' . $file_extension;
        $target_path = $upload_dir . $new_filename;
        
        if (!move_uploaded_file($_FILES['acceptance_letter']['tmp_name'], $target_path)) {
            throw new Exception("Failed to move uploaded acceptance letter");
        }
        $accept_letter_path = $new_filename;
        writeLog("Acceptance letter uploaded successfully: $new_filename");
    }

    // Process financial proof
    if (isset($_FILES['financial_proof'])) {
        writeLog("Processing financial proof upload");
        if ($_FILES['financial_proof']['error'] !== 0) {
            throw new Exception("Financial proof upload error: " . $_FILES['financial_proof']['error']);
        }
        
        $file_extension = strtolower(pathinfo($_FILES['financial_proof']['name'], PATHINFO_EXTENSION));
        $new_filename = uniqid() . '_financial.' . $file_extension;
        $target_path = $upload_dir . $new_filename;
        
        if (!move_uploaded_file($_FILES['financial_proof']['tmp_name'], $target_path)) {
            throw new Exception("Failed to move uploaded financial proof");
        }
        $proof_of_financial_path = $new_filename;
        writeLog("Financial proof uploaded successfully: $new_filename");
    }

    // Validate required fields
    $required_fields = ['fullname', 'id_number', 'dob', 'nationality', 'institution', 'course', 'duration', 'address', 'contact', 'email'];
    foreach ($required_fields as $field) {
        if (empty($_POST[$field])) {
            throw new Exception("Missing required field: $field");
        }
    }

    writeLog("All required fields validated");

    // Insert data into database
    $stmt = $conn->prepare("INSERT INTO study_permit (
        full_name, Id, date_birth, nationality, institution,  
        course, duration, accept_letter, proof_of_financial, 
        address, number, email
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

    $params = [
        $_POST['fullname'],
        $_POST['id_number'],
        $_POST['dob'],
        $_POST['nationality'],
        $_POST['institution'],
        $_POST['course'],
        $_POST['duration'],
        $accept_letter_path,
        $proof_of_financial_path,
        $_POST['address'],
        $_POST['contact'],
        $_POST['email']
    ];

    writeLog("Attempting database insertion with parameters: " . print_r($params, true));

    if (!$stmt->execute($params)) {
        throw new Exception("Database insertion failed: " . implode(" ", $stmt->errorInfo()));
    }

    writeLog("Database insertion successful");
    echo json_encode(['success' => true, 'message' => 'Study permit application submitted successfully']);

} catch (Exception $e) {
    writeLog("ERROR: " . $e->getMessage());
    writeLog("Stack trace: " . $e->getTraceAsString());
    echo json_encode([
        'success' => false, 
        'message' => 'Error submitting application: ' . $e->getMessage(),
        'debug_info' => [
            'error' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine()
        ]
    ]);
}
?>