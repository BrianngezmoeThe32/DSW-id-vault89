<?php
session_start();

// Database connection
$host = "localhost";
$db = "idvault";
$user = "root";
$pass = "";

try {
    $conn = new PDO("mysql:host=$host;dbname=$db", $user, $pass);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Handle file uploads
    $acceptance_letter_path = "";
    $financial_proof_path = "";
    
    // Process acceptance letter
    if(isset($_FILES['acceptance_letter']) && $_FILES['acceptance_letter']['error'] == 0) {
        $allowed = ['pdf', 'doc', 'docx'];
        $filename = $_FILES['acceptance_letter']['name'];
        $filetype = pathinfo($filename, PATHINFO_EXTENSION);
        
        if(in_array(strtolower($filetype), $allowed)) {
            $new_filename = uniqid() . '_acceptance.' . $filetype;
            $upload_path = '../uploads/study_permit/' . $new_filename;
            
            // Create directory if it doesn't exist
            if (!file_exists('../uploads/study_permit/')) {
                mkdir('../uploads/study_permit/', 0777, true);
            }
            
            if(move_uploaded_file($_FILES['acceptance_letter']['tmp_name'], $upload_path)) {
                $acceptance_letter_path = $upload_path;
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
            $upload_path = '../uploads/study_permit/' . $new_filename;
            
            if(move_uploaded_file($_FILES['financial_proof']['tmp_name'], $upload_path)) {
                $financial_proof_path = $upload_path;
            }
        }
    }

    // Prepare SQL statement
    $sql = "INSERT INTO study_permit_applications (
        fullname,
        id_number,
        date_of_birth,
        nationality,
        institution,
        course,
        duration,
        acceptance_letter_path,
        financial_proof_path,
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
        :institution,
        :course,
        :duration,
        :acceptance_letter_path,
        :financial_proof_path,
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
    $stmt->bindParam(':institution', $_POST['institution']);
    $stmt->bindParam(':course', $_POST['course']);
    $stmt->bindParam(':duration', $_POST['duration']);
    $stmt->bindParam(':acceptance_letter_path', $acceptance_letter_path);
    $stmt->bindParam(':financial_proof_path', $financial_proof_path);
    $stmt->bindParam(':address', $_POST['address']);
    $stmt->bindParam(':contact', $_POST['contact']);
    $stmt->bindParam(':email', $_POST['email']);

    // Execute the statement
    $stmt->execute();

    // Set success message
    $_SESSION['message'] = "Study permit application submitted successfully!";
    $_SESSION['message_type'] = "success";
    
    // Redirect back to study permit application page
    header("Location: studyPermit.php");
    exit();

} catch(PDOException $e) {
    // Set error message
    $_SESSION['message'] = "Error: " . $e->getMessage();
    $_SESSION['message_type'] = "error";
    
    // Redirect back to study permit application page
    header("Location: studyPermit.php");
    exit();
}
?> 