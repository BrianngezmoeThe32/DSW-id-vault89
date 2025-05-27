<?php
if ($_SERVER["REQUEST_METHOD"] == "POST") {
  $message = strtolower(trim($_POST["message"]));

  // Simple keyword-based replies
  $replies = [
    "hello" => "Hi there! 😊",
    "how are you" => "I'm just code, but I'm running well! 💻",
    "what is dsw" => "DSW stands for Digital Student World. We provide awesome digital services!",
    "bye" => "Goodbye! Come chat anytime. 👋",
  ];

  // Check if reply exists
  $found = false;
  foreach ($replies as $key => $reply) {
    if (strpos($message, $key) !== false) {
      echo $reply;
      $found = true;
      break;
    }
  }

  if (!$found) {
    echo "Sorry, I didn't understand that. Try asking something else.";
  }
}
?>
