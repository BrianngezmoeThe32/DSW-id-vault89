<?php
// ask.php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

$input = json_decode(file_get_contents("php://input"), true);
$query = $input["query"] ?? "";

if (!$query) {
    echo json_encode(["error" => "No query received"]);
    exit;
}

$apiKey = "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";

$data = [
    "model" => "gpt-3.5-turbo",
    "messages" => [
        ["role" => "user", "content" => $query]
    ]
];

$ch = curl_init("https://api.openai.com/v1/chat/completions");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Content-Type: application/json",
    "Authorization: Bearer " . $apiKey
]);

$response = curl_exec($ch);
curl_close($ch);

if ($response === false) {
    echo json_encode(["error" => "API request failed"]);
    exit;
}

$responseData = json_decode($response, true);

if (isset($responseData["choices"][0]["message"]["content"])) {
    echo json_encode(["answer" => $responseData["choices"][0]["message"]["content"]]);
} else {
    echo json_encode(["error" => "Invalid response from OpenAI"]);
}
