<?php
require 'db.php';

$data = json_decode(file_get_contents("php://input"));

$fullname = $conn->real_escape_string($data->fullname);
$email = $conn->real_escape_string($data->email);
$phone = $conn->real_escape_string($data->phone);
$password = password_hash($data->password, PASSWORD_DEFAULT);

// Check if email exists
$check = $conn->query("SELECT * FROM users WHERE email='$email'");
if ($check->num_rows > 0) {
  echo json_encode(["status" => "error", "message" => "Email already exists"]);
  exit;
}

// Insert user
$sql = "INSERT INTO users (fullname, email, phone, password) 
        VALUES ('$fullname', '$email', '$phone', '$password')";

if ($conn->query($sql) === TRUE) {
  
  // Generate a random 6-digit code
  $code = rand(100000, 999999);

  // Set up email
  $subject = "Your Verification Code";
  $message = "Hello $fullname,\n\nYour verification code is: $code\n\nPlease keep it safe.";
  $headers = "From: noreply@yourdomain.com\r\n"; // Make sure this is valid on your host

  // Send the email
  if (mail($email, $subject, $message, $headers)) {
    echo json_encode(["status" => "success", "message" => "Account created, email sent", "code" => $code]);
  } else {
    echo json_encode(["status" => "error", "message" => "Account created but email failed to send"]);
  }

} else {
  echo json_encode(["status" => "error", "message" => $conn->error]);
}

$conn->close();
?>
