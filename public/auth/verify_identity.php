<?php
// verify_identity.php

header("Content-Type: application/json");

$uploadDir = "uploads/";
$maxFileSize = 5 * 1024 * 1024; // 5MB

$allowedTypes = ['image/jpeg', 'image/png', 'application/pdf'];

function saveFile($file, $name) {
    global $uploadDir, $allowedTypes, $maxFileSize;

    if ($file['error'] === UPLOAD_ERR_OK) {
        if ($file['size'] > $maxFileSize) {
            return ["error" => "$name is too large."];
        }

        if (!in_array($file['type'], $allowedTypes)) {
            return ["error" => "$name has an unsupported format."];
        }

        $filename = uniqid() . "_" . basename($file["name"]);
        $destination = $uploadDir . $filename;

        if (move_uploaded_file($file["tmp_name"], $destination)) {
            return ["success" => "$name uploaded", "path" => $destination];
        } else {
            return ["error" => "$name upload failed."];
        }
    } else {
        return ["error" => "$name not uploaded properly."];
    }
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $userId = $_POST["userId"] ?? null;

    if (!$userId) {
        echo json_encode(["status" => "error", "message" => "Missing user ID"]);
        exit;
    }

    $results = [];

    $results["idDocument"] = saveFile($_FILES["idDocument"], "ID Document");
    $results["studentDocument"] = saveFile($_FILES["studentDocument"], "Student Document");
    $results["addressDocument"] = saveFile($_FILES["addressDocument"], "Proof of Address");

    echo json_encode(["status" => "success", "uploads" => $results]);
}
?>
