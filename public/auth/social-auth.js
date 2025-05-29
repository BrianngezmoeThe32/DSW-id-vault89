// Google Login
function handleGoogleLogin() {
  const client = google.accounts.oauth2.initTokenClient({
    client_id: "YOUR_GOOGLE_CLIENT_ID",
    scope: "email profile",
    callback: (response) => {
      if (response.access_token) {
        // Send token to your server for verification
        fetch("../auth/google-auth.php", {
          method: "POST",
          headers: {
            "Content-Type": "application/json",
          },
          body: JSON.stringify({ token: response.access_token }),
        })
          .then((response) => response.json())
          .then((data) => {
            if (data.success) {
              // Redirect or handle successful login
              window.location.href = "home.html";
            } else {
              alert("Google login failed: " + data.message);
            }
          });
      }
    },
  });
  client.requestAccessToken();
}

// Facebook Login
function initFacebookSDK() {
  FB.init({
    appId: "YOUR_FACEBOOK_APP_ID",
    cookie: true,
    xfbml: true,
    version: "v12.0",
  });
}

function handleFacebookLogin() {
  FB.login(
    function (response) {
      if (response.authResponse) {
        // Send token to your server
        fetch("../auth/facebook-auth.php", {
          method: "POST",
          headers: {
            "Content-Type": "application/json",
          },
          body: JSON.stringify({ token: response.authResponse.accessToken }),
        })
          .then((response) => response.json())
          .then((data) => {
            if (data.success) {
              window.location.href = "/dashboard";
            } else {
              alert("Facebook login failed: " + data.message);
            }
          });
      }
    },
    { scope: "public_profile,email" }
  );
}

// LinkedIn Login
function handleLinkedInLogin() {
  IN.User.authorize(function () {
    IN.API.Raw(
      "/people/~:(id,first-name,last-name,email-address)?format=json"
    ).result(function (data) {
      // Send data to your server
      fetch("../auth/linkedin-auth.php", {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
        },
        body: JSON.stringify(data),
      })
        .then((response) => response.json())
        .then((data) => {
          if (data.success) {
            window.location.href = "/dashboard";
          } else {
            alert("LinkedIn login failed: " + data.message);
          }
        });
    });
  });
}

// Forgot Password
function initForgotPassword() {
  const modal = document.getElementById("forgotPasswordModal");
  const btn = document.getElementById("forgotPasswordLink");
  const span = document.getElementsByClassName("close")[0];
  const sendBtn = document.getElementById("sendResetLink");

  btn.onclick = function () {
    modal.style.display = "block";
  };

  span.onclick = function () {
    modal.style.display = "none";
  };

  sendBtn.onclick = function () {
    const email = document.getElementById("resetEmail").value;
    if (!email) {
      alert("Please enter your email address");
      return;
    }

    fetch("../auth/forgot-password.php", {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
      },
      body: JSON.stringify({ email: email }),
    })
      .then((response) => response.json())
      .then((data) => {
        if (data.success) {
          alert("Password reset link sent to your email");
          modal.style.display = "none";
        } else {
          alert("Error: " + data.message);
        }
      });
  };

  window.onclick = function (event) {
    if (event.target == modal) {
      modal.style.display = "none";
    }
  };
}

// Initialize everything when DOM is loaded
document.addEventListener("DOMContentLoaded", function () {
  // Initialize Facebook SDK
  initFacebookSDK();

  // Add event listeners
  document
    .getElementById("googleSignIn")
    .addEventListener("click", handleGoogleLogin);
  document
    .getElementById("facebookSignIn")
    .addEventListener("click", handleFacebookLogin);
  document
    .getElementById("linkedinSignIn")
    .addEventListener("click", handleLinkedInLogin);

  // Initialize forgot password
  initForgotPassword();
});
