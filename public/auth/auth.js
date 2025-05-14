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
const registerBtn = document.getElementById("registerBtn"); // Added missing reference

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
    signIn.classList.remove("admin-mode");
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

// Enhanced registration handler
registerBtn.addEventListener("click", async function () {
  // Validate inputs first
  const name = document.getElementById("FullName").value.trim();
  const email = document.getElementById("email").value.trim();
  const phone = document.getElementById("phonenumber").value.trim();
  const password = pass1.value;
  const confirmPassword = pass2.value;

  // Basic validation
  if (!name || !email || !phone || !password || !confirmPassword) {
    alert("Please fill all fields");
    return;
  }

  if (password !== confirmPassword) {
    alert("Passwords do not match");
    return;
  }

  if (!document.getElementById("terms").checked) {
    alert("Please accept terms and conditions");
    return;
  }

  // Prepare JSON data
  const userData = {
    name: name,
    email: email,
    phone: phone,
    password: password
  };

  try {
    // Show loading state
    registerBtn.disabled = true;
    registerBtn.innerHTML = '<i class="bx bx-loader-alt bx-spin"></i> Processing...';

    const response = await fetch("../auth/register.php", {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
      },
      body: JSON.stringify(userData)
    });

    const data = await response.json();

    if (!response.ok) {
      throw new Error(data.message || "Registration failed");
    }

    // Success case
    document.getElementById("confirmationMsg").textContent = data.message || "Registration successful!";
    document.querySelector(".confirmation").style.display = "flex";
    
    setTimeout(() => {
      window.location.href = "login.html";
    }, 2000);

  } catch (error) {
    console.error("Registration error:", error);
    alert(`Registration failed: ${error.message}`);
  } finally {
    // Reset button state
    registerBtn.disabled = false;
    registerBtn.textContent = "Submit";
  }
});

// Function to simulate email verification (for demo only)
function simulateEmailVerification(userId) {
  fetch("verify_email.php", {
    method: "POST",
    headers: {
      "Content-Type": "application/json",
    },
    body: JSON.stringify({
      userId: userId,
      token: "simulated_token",
    }),
  })
  .then(response => {
    if (!response.ok) {
      throw new Error(`HTTP error! status: ${response.status}`);
    }
    return response.json();
  })
  .then(data => {
    const confirmationMsg = document.getElementById("confirmationMsg");
    if (data.status === "success") {
      confirmationMsg.innerHTML = `
        <h3>Email Verified Successfully!</h3>
        <p>Your email has been verified.</p>
        <a href="verify_identity.html?userId=${userId}" class="btn-verify">
          Proceed to Identity Verification
        </a>
      `;
    } else {
      confirmationMsg.innerHTML += `
        <div class="error-message">
          Verification failed: ${data.message || "Unknown error"}
        </div>
      `;
    }
  })
  .catch(error => {
    console.error("Verification error:", error);
    document.getElementById("confirmationMsg").innerHTML += `
      <div class="error-message">
        Error during verification: ${error.message}
      </div>
    `;
  });
}