<?php
require 'db.php';

$data = json_decode(file_get_contents("php://input"));

$email = $conn->real_escape_string($data->email);
$password = $data->password;


$sql = "SELECT * FROM users WHERE email='$email'";
$result = $conn->query($sql);

if ($result->num_rows == 1) {
  $user = $result->fetch_assoc();
  if (password_verify($password, $user['password'])) {
    echo json_encode(["status" => "success", "message" => "Logged in"]);
  } else {
    echo json_encode(["status" => "error", "message" => "Wrong password"]);
  }
} else {
  echo json_encode(["status" => "error", "message" => "User not found"]);
}

$conn->close();
?>
