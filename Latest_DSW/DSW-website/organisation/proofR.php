<!-- index.html -->
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Geo Tracker</title>
</head>
<body>
  <h1>Tracking location...</h1>

  <script>
    async function sendLocationData() {
      const payload = {
        homeMobileCountryCode: 310,
        homeMobileNetworkCode: 410,
        radioType: "gsm",
        carrier: "Vodafone",
        considerIp: true,
        user_id: 1  // Example user ID
      };

      try {
        const response = await fetch("track.php", {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify(payload)
        });

        const data = await response.json();
        console.log("Location saved:", data);
      } catch (error) {
        console.error("Error tracking location:", error);
      }
    }

    // Send location every 5 minutes
    sendLocationData();
    setInterval(sendLocationData, 5 * 60 * 1000);
  </script>
</body>
</html>

<?php
session_start();

// Database credentials
$host = "localhost";
$db = "idvault_db";
$user = "root";
$pass = "";

// Google API Key
$GOOGLE_API_KEY = "IdVault";

// Create connection
$conn = mysqli_connect($host, $user, $pass, $db);
if($conn->connect_error){
    die("Connection failed:".$conn->connect_error);
}

// Prepare the payload
$payload = array(
    "homeMobileCountryCode" => 310,
    "homeMobileNetworkCode" => 410,
    "radioType" => "gsm",
    "carrier" => "Vodafone",
    "considerIp" => true
);

// Initialize cURL session
$ch = curl_init();

// Set cURL options
curl_setopt($ch, CURLOPT_URL, "https://www.googleapis.com/geolocation/v1/geolocate?key=IdVault");
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));

// Execute cURL request
$response = curl_exec($ch);

// Check for cURL errors
if(curl_errno($ch)) {
    die("cURL Error: " . curl_error($ch));
}

// Close cURL session
curl_close($ch);

// Decode the response
$geoData = json_decode($response, true);

// Check if location data was received
if (!isset($geoData["location"])) {
    die("Error: Location data not found in response");
}

// Extract location data
$lat = $geoData["location"]["lat"];
$lng = $geoData["location"]["lng"];
$accuracy = $geoData["accuracy"];
$user_id = 1; // You can modify this to use actual user ID

// Store in database
$query = "INSERT INTO proofofres VALUES ('$user_id', '$lat', '$lng', '$accuracy')";
$stmt = mysqli_query($conn, $query);

if($stmt) {
    echo "Location data stored successfully";
} else {
    echo "Error storing location data: " . mysqli_error($conn);
}

mysqli_close($conn);
?>