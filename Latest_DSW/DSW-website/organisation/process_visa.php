<?php
session_start();

// Database connection
$host = "localhost";
$db = "idvault_db";
$user = "root";
$pass = "";

try {
    $conn = new PDO("mysql:host=$host;dbname=$db", $user, $pass);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Handle file uploads
    $passport_path = "";
    $financial_proof_path = "";
    $accommodation_path = "";
    
    // Process passport copy
    if(isset($_FILES['passport']) && $_FILES['passport']['error'] == 0) {
        $allowed = ['pdf', 'jpg', 'jpeg', 'png'];
        $filename = $_FILES['passport']['name'];
        $filetype = pathinfo($filename, PATHINFO_EXTENSION);
        
        if(in_array(strtolower($filetype), $allowed)) {
            $new_filename = uniqid() . '_passport.' . $filetype;
            $upload_path = '../uploads/visa/' . $new_filename;
            
            // Create directory if it doesn't exist
            if (!file_exists('../uploads/visa/')) {
                mkdir('../uploads/visa/', 0777, true);
            }
            
            if(move_uploaded_file($_FILES['passport']['tmp_name'], $upload_path)) {
                $passport_path = $upload_path;
            }
        }
    }

    // Process financial proof
    if(isset($_FILES['financial_proof']) && $_FILES['financial_proof']['error'] == 0) {
        $allowed = ['pdf', 'doc', 'docx'];
        $filename = $_FILES['financial_proof']['name'];
        $filetype = pathinfo($filename, PATHINFO_EXTENSION);
        
        if(in_array(strtolower($filetype), $allowed)) {
            $new_filename = uniqid() . '_financial.' . $filetype;
            $upload_path = '../uploads/visa/' . $new_filename;
            
            if(move_uploaded_file($_FILES['financial_proof']['tmp_name'], $upload_path)) {
                $financial_proof_path = $upload_path;
            }
        }
    }

    // Process accommodation proof
    if(isset($_FILES['accommodation']) && $_FILES['accommodation']['error'] == 0) {
        $allowed = ['pdf', 'doc', 'docx'];
        $filename = $_FILES['accommodation']['name'];
        $filetype = pathinfo($filename, PATHINFO_EXTENSION);
        
        if(in_array(strtolower($filetype), $allowed)) {
            $new_filename = uniqid() . '_accommodation.' . $filetype;
            $upload_path = '../uploads/visa/' . $new_filename;
            
            if(move_uploaded_file($_FILES['accommodation']['tmp_name'], $upload_path)) {
                $accommodation_path = $upload_path;
            }
        }
    }

    // Prepare SQL statement
    $sql = "INSERT INTO visa_applications (
        fullname,
        id_number,
        date_of_birth,
        nationality,
        visa_type,
        destination_country,
        purpose,
        duration,
        arrival_date,
        departure_date,
        passport_path,
        financial_proof_path,
        accommodation_path,
        address,
        contact_number,
        email,
        application_date,
        status
    ) VALUES (
        :fullname,
        :id_number,
        :dob,
        :nationality,
        :visa_type,
        :destination,
        :purpose,
        :duration,
        :arrival_date,
        :departure_date,
        :passport_path,
        :financial_proof_path,
        :accommodation_path,
        :address,
        :contact,
        :email,
        NOW(),
        'pending'
    )";

    $stmt = $conn->prepare($sql);

    // Bind parameters
    $stmt->bindParam(':fullname', $_POST['fullname']);
    $stmt->bindParam(':id_number', $_POST['id_number']);
    $stmt->bindParam(':dob', $_POST['dob']);
    $stmt->bindParam(':nationality', $_POST['nationality']);
    $stmt->bindParam(':visa_type', $_POST['visa_type']);
    $stmt->bindParam(':destination', $_POST['destination']);
    $stmt->bindParam(':purpose', $_POST['purpose']);
    $stmt->bindParam(':duration', $_POST['duration']);
    $stmt->bindParam(':arrival_date', $_POST['arrival_date']);
    $stmt->bindParam(':departure_date', $_POST['departure_date']);
    $stmt->bindParam(':passport_path', $passport_path);
    $stmt->bindParam(':financial_proof_path', $financial_proof_path);
    $stmt->bindParam(':accommodation_path', $accommodation_path);
    $stmt->bindParam(':address', $_POST['address']);
    $stmt->bindParam(':contact', $_POST['contact']);
    $stmt->bindParam(':email', $_POST['email']);

    // Execute the statement
    $stmt->execute();

    // Set success message
    $_SESSION['message'] = "Visa application submitted successfully!";
    $_SESSION['message_type'] = "success";
    
    // Redirect back to visa application page
    header("Location: visa.php");
    exit();

} catch(PDOException $e) {
    // Set error message
    $_SESSION['message'] = "Error: " . $e->getMessage();
    $_SESSION['message_type'] = "error";
    
    // Redirect back to visa application page
    header("Location: visa.php");
    exit();
}
?> 