<?php
require 'db.php';


$data = json_decode(file_get_contents("php://input"));

$fullname = $conn->real_escape_string($data->fullname);
$email = $conn->real_escape_string($data->email);
$phone = $conn->real_escape_string($data->phone);
$password = password_hash($data->password, PASSWORD_DEFAULT); // encrypt password


$check = $conn->query("SELECT * FROM users WHERE email='$email'");
if ($check->num_rows > 0) {
  echo json_encode(["status" => "error", "message" => "Email already exists"]);
  exit;
}


$sql = "INSERT INTO users (fullname, email, phone, password) 
        VALUES ('$fullname', '$email', '$phone', '$password')";

if ($conn->query($sql) === TRUE) {
  echo json_encode(["status" => "success", "message" => "Account created"]);
} else {
  echo json_encode(["status" => "error", "message" => $conn->error]);
}

$conn->close();
?>
