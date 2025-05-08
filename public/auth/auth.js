const pass1 = document.getElementById("pass1");
const pass2 = document.getElementById("pass2");
const passStat = document.getElementById("passwordstat");

const loginPage = document.querySelector(".login");
const confirmationPage = document.querySelector(".confirmation");

const btnSwitchLogIn = document.querySelector(".SwitchLoginIn");
const btnSubmit = document.querySelector(".Submit");

const signIn = document.querySelector(".signIn");
const signUp = document.querySelector(".signUp");
const altSign = document.querySelector(".altSignIn");
const altSigntxt = document.querySelector("#altSignIntext");

const Welcometxt = document.querySelector("#Welcometxt");
const welcomebodytxt = document.querySelector("#welcomebodytxt");

// Admin mode elements
const adminToggle = document.getElementById("adminLoginCheck");

class UserData {
  #_name;
  #_emailAddress;
  #_phoneNumber;
  #_password;

  constructor(name, email, phone, pass) {
    this.#_name = name;
    this.#_emailAddress = email;
    this.#_phoneNumber = phone;
    this.#_password = pass;
  }
}

// Toggle between login and signup forms
btnSwitchLogIn.addEventListener("click", function () {
  if (btnSwitchLogIn.textContent === "Sign Up") {
    Welcometxt.textContent = "Create a New Account";
    welcomebodytxt.textContent =
      "If you already have an account, please log in.";
    btnSwitchLogIn.textContent = "Login";

    signIn.style.display = "none";
    signUp.style.display = "flex";
    altSign.style.display = "none";
    altSigntxt.style.display = "none";
  } else {
    Welcometxt.textContent = "Hi! Welcome Back";
    welcomebodytxt.textContent =
      "If you don't have an existing account, please sign up.";
    btnSwitchLogIn.textContent = "Sign Up";

    signIn.style.display = "flex";
    signUp.style.display = "none";
    altSign.style.display = "flex";
    altSigntxt.style.display = "flex";

    // Reset admin toggle when switching back to login
    adminToggle.checked = false;
    loginForm.classList.remove("admin-mode");
    document.getElementById("signInUser").placeholder = "Email Address";
  }
});

// Admin mode toggle functionality
adminToggle.addEventListener("change", function () {
  if (this.checked) {
    signIn.classList.add("admin-mode");
    document.getElementById("signInUser").placeholder = "Admin Email";
  } else {
    signIn.classList.remove("admin-mode");
    document.getElementById("signInUser").placeholder = "Email Address";
  }
});

// Password strength checker
function checkPasswordStrength(password) {
  let strength = 0;

  // Length check
  if (password.length > 7) strength += 1;
  if (password.length > 11) strength += 1;

  // Character variety
  if (/[A-Z]/.test(password)) strength += 1;
  if (/[0-9]/.test(password)) strength += 1;
  if (/[^A-Za-z0-9]/.test(password)) strength += 1;

  return strength;
}

// Real-time password strength feedback
pass1.addEventListener("input", function () {
  const strength = checkPasswordStrength(this.value);
  const strengthText = ["Weak", "Medium", "Strong"][Math.min(strength, 2)];
  passStat.textContent = `Password Strength: ${strengthText}`;
  passStat.style.color =
    strength < 2 ? "#ff1212" : strength < 4 ? "#ffc107" : "#28a745";
  passStat.style.display = "flex";
});

// Sign up function
// ... (keep all your existing code above until the SignUp function)

// Modified SignUp function with confirmation flow
function SignUp() {
  const name = document.querySelector("#FullName").value.trim();
  const email = document.querySelector("#email").value.trim();
  const phone = document.querySelector("#phonenumber").value.trim();
  const password1 = pass1.value.trim();
  const password2 = pass2.value.trim();

  if (name && email && phone && password1 && password2) {
    if (password1 !== password2) {
      passStat.textContent = "Passwords do not match.";
      passStat.style.display = "flex";
      return;
    }

    if (checkPasswordStrength(password1) < 2) {
      passStat.textContent =
        "Password is too weak. Please use a stronger password.";
      passStat.style.display = "flex";
      return;
    }

    fetch("signup.php", {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
      },
      body: JSON.stringify({
        fullname: name,
        email: email,
        phone: phone,
        password: password1,
      }),
    })
      .then((res) => res.json())
      .then((data) => {
        if (data.status === "success") {
          // Hide the login/signup forms
          loginPage.style.display = "none";
          window.location.href = "../home.html";
          


          // Create confirmation message with verification link
          const confirmationMsg = document.getElementById("confirmationMsg");
          confirmationMsg.innerHTML = `
            <h3>Account Registration Successful!</h3>
            <p>We've sent a confirmation email to ${email}. Please verify your email to continue.</p>
            <div class="verification-steps">
              <p><strong>Next Steps:</strong></p>
              <ol>
                <li>Check your email for the verification link</li>
                <li>Click the link to verify your account</li>
                <li>Complete your identity verification</li>
              </ol>
              <div class="resend-link">
                Didn't receive the email? <a href="#" id="resendEmail">Resend verification email</a>
              </div>
            </div>
          `;

          // Show the confirmation page
          confirmationPage.style.display = "flex";

          // Add resend email functionality
          document
            .getElementById("resendEmail")
            .addEventListener("click", function (e) {
              e.preventDefault();
              resendVerificationEmail(email);
            });

          // For demo purposes, we'll simulate email verification
          // In production, remove this and use real email verification
          setTimeout(() => {
            simulateEmailVerification(data.userId);
          }, 3000);
        } else {
          passStat.textContent = data.message || "Signup failed.";
          passStat.style.display = "flex";
        }
      })
      .catch((err) => {
        passStat.textContent = "Something went wrong. Try again later.";
        console.error(err);
      });
  } else {
    passStat.textContent = "Please fill in all the fields.";
    passStat.style.display = "flex";
  }
}

// Function to resend verification email
function resendVerificationEmail(email) {
  fetch("resend_verification.php", {
    method: "POST",
    headers: {
      "Content-Type": "application/json",
    },
    body: JSON.stringify({
      email: email,
    }),
  })
    .then((response) => response.json())
    .then((data) => {
      if (data.status === "success") {
        alert("Verification email resent successfully!");
      } else {
        alert(
          "Failed to resend verification email: " +
            (data.message || "Unknown error")
        );
      }
    })
    .catch((error) => {
      console.error("Error resending verification email:", error);
      alert("Failed to resend verification email. Please try again later.");
    });
}

// Function to simulate email verification (for demo only)
function simulateEmailVerification(userId) {
  // In a real app, this would happen when user clicks the email link
  fetch("verify_email.php", {
    method: "POST",
    headers: {
      "Content-Type": "application/json",
    },
    body: JSON.stringify({
      userId: userId,
      token: "simulated_token", // In real app, this would come from the email link
    }),
  })
    .then((response) => response.json())
    .then((data) => {
      if (data.status === "success") {
        // Update the confirmation message to show verification success
        const confirmationMsg = document.getElementById("confirmationMsg");
        confirmationMsg.innerHTML = `
        <h3>Email Verified Successfully!</h3>
        <p>Your email has been verified. Please complete your identity verification to finish setting up your account.</p>
        <div class="verification-steps">
          <p><strong>Required Documents:</strong></p>
          <ul>
            <li>Government-issued ID (Passport, Driver's License, etc.)</li>
            <li>Student Registration Proof</li>
            <li>Proof of Address (Utility bill, Bank statement, etc.)</li>
          </ul>
          <div class="document-upload">
            <a href="verify_identity.html?userId=${userId}" class="btn-verify">
              Proceed to Identity Verification
            </a>
          </div>
        </div>
      `;
      } else {
        confirmationMsg.innerHTML += `
        <div class="error-message">
          Verification failed: ${data.message || "Unknown error"}
        </div>
      `;
      }
    })
    .catch((error) => {
      console.error("Verification error:", error);
      confirmationMsg.innerHTML += `
      <div class="error-message">
        Error during verification. Please try again later.
      </div>
    `;
    });
}

// ... (keep all your existing code below)
