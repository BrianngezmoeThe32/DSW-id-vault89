document.addEventListener("DOMContentLoaded", function () {
  // Get userId from URL
  const urlParams = new URLSearchParams(window.location.search);
  const userId = urlParams.get("userId");
  document.getElementById("userId").value = userId;

  // Setup file upload previews
  setupFileUpload("idDocument", "idPreview");
  setupFileUpload("studentDocument", "studentPreview");
  setupFileUpload("addressDocument", "addressPreview");

  // Form submission
  document
    .getElementById("verificationForm")
    .addEventListener("submit", function (e) {
      e.preventDefault();
      submitVerification(userId);
    });
});

function setupFileUpload(inputId, previewId) {
  const input = document.getElementById(inputId);
  const preview = document.getElementById(previewId);

  input.addEventListener("change", function () {
    if (this.files && this.files[0]) {
      const file = this.files[0];

      if (file.type.match("image.*")) {
        const reader = new FileReader();

        reader.onload = function (e) {
          preview.innerHTML = `
                        <div class="file-preview">
                            <img src="${e.target.result}" alt="Preview">
                            <p>${file.name}</p>
                            <button type="button" class="btn-remove" data-input="${inputId}">Remove</button>
                        </div>
                    `;

          // Add remove functionality
          preview
            .querySelector(".btn-remove")
            .addEventListener("click", function () {
              document.getElementById(inputId).value = "";
              preview.innerHTML = "";
            });
        };

        reader.readAsDataURL(file);
      } else if (file.type === "application/pdf") {
        preview.innerHTML = `
                    <div class="file-preview">
                        <i class="bx bxs-file-pdf"></i>
                        <p>${file.name}</p>
                        <button type="button" class="btn-remove" data-input="${inputId}">Remove</button>
                    </div>
                `;

        // Add remove functionality
        preview
          .querySelector(".btn-remove")
          .addEventListener("click", function () {
            document.getElementById(inputId).value = "";
            preview.innerHTML = "";
          });
      }
    }
  });
}

function submitVerification(userId) {
  const formData = new FormData();
  formData.append("userId", userId);
  formData.append("idDocument", document.getElementById("idDocument").files[0]);
  formData.append(
    "studentDocument",
    document.getElementById("studentDocument").files[0]
  );
  formData.append(
    "addressDocument",
    document.getElementById("addressDocument").files[0]
  );

  fetch("verify_identity.php", {
    method: "POST",
    body: formData,
  })
    .then((response) => response.json())
    .then((data) => {
      if (data.status === "success") {
        alert(
          "Verification submitted successfully! Your account will be activated after review."
        );
        window.location.href = "login.html?verified=true";
      } else {
        alert("Verification failed: " + (data.message || "Unknown error"));
      }
    })
    .catch((error) => {
      console.error("Error:", error);
      alert("An error occurred. Please try again.");
    });
}
