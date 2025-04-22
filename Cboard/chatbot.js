function sendMessage() {
  const input = document.getElementById("user-input");
  const msg = input.value.trim();
  if (msg === "") return;

  const chatBox = document.getElementById("chat-box");

  // Add user message
  const userMsg = document.createElement("div");
  userMsg.className = "user-msg";
  userMsg.textContent = msg;
  chatBox.appendChild(userMsg);

  // Scroll down
  chatBox.scrollTop = chatBox.scrollHeight;

  // Clear input
  input.value = "";

  // Send message to PHP
  fetch("chatbot.php", {
    method: "POST",
    headers: {
      "Content-Type": "application/x-www-form-urlencoded",
    },
    body: "message=" + encodeURIComponent(msg),
  })
    .then((response) => response.text())
    .then((data) => {
      const botMsg = document.createElement("div");
      botMsg.className = "bot-msg";
      botMsg.textContent = data;
      chatBox.appendChild(botMsg);
      chatBox.scrollTop = chatBox.scrollHeight;
    });
}
