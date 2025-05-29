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

    // Handle file upload
    $photo_path = "";
    if(isset($_FILES['photo']) && $_FILES['photo']['error'] == 0) {
        $allowed = ['jpg', 'jpeg', 'png'];
        $filename = $_FILES['photo']['name'];
        $filetype = pathinfo($filename, PATHINFO_EXTENSION);
        
        if(in_array(strtolower($filetype), $allowed)) {
            $new_filename = uniqid() . '.' . $filetype;
            $upload_path = '../uploads/passport_photos/' . $new_filename;
            
            // Create directory if it doesn't exist
            if (!file_exists('../uploads/passport_photos/')) {
                mkdir('../uploads/passport_photos/', 0777, true);
            }
            
            if(move_uploaded_file($_FILES['photo']['tmp_name'], $upload_path)) {
                $photo_path = $upload_path;
            }
        }
    }

    // Prepare SQL statement
    $sql = "INSERT INTO passport_applications (
        fullname, 
        id_number, 
        date_of_birth, 
        nationality, 
        passport_type, 
        photo_path, 
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
        :passport_type,
        :photo_path,
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
    $stmt->bindParam(':passport_type', $_POST['passport_type']);
    $stmt->bindParam(':photo_path', $photo_path);
    $stmt->bindParam(':address', $_POST['address']);
    $stmt->bindParam(':contact', $_POST['contact']);
    $stmt->bindParam(':email', $_POST['email']);

    // Execute the statement
    $stmt->execute();

    // Set success message
    $_SESSION['message'] = "Passport application submitted successfully!";
    $_SESSION['message_type'] = "success";
    
    // Redirect back to passport application page
    header("Location: passport.php");
    exit();

} catch(PDOException $e) {
    // Set error message
    $_SESSION['message'] = "Error: " . $e->getMessage();
    $_SESSION['message_type'] = "error";
    
    // Redirect back to passport application page
    header("Location: passport.php");
    exit();
}
?> 