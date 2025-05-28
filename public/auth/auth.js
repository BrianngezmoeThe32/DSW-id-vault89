// auth.js - Complete Login/Registration Handling

// DOM Elements
const pass1 = document.getElementById("pass1");
const pass2 = document.getElementById("pass2");
const passStat = document.getElementById("passwordstat");
const loginPage = document.querySelector(".login");
const confirmationPage = document.querySelector(".confirmation");
const btnSwitchLogIn = document.querySelector(".SwitchLoginIn");
const signIn = document.querySelector(".signIn");
const signUp = document.querySelector(".signUp");
const altSign = document.querySelector(".altSignIn");
const altSigntxt = document.querySelector("#altSignIntext");
const Welcometxt = document.querySelector("#Welcometxt");
const welcomebodytxt = document.querySelector("#welcomebodytxt");
const adminToggle = document.getElementById("adminLoginCheck");
const registerBtn = document.getElementById("registerBtn");

// Toggle between login/signup views
btnSwitchLogIn?.addEventListener("click", () => {
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
    adminToggle.checked = false;
    signIn.classList.remove("admin-mode");
    document.getElementById("signInUser").placeholder = "Email Address";
  }
});

// Admin login toggle
adminToggle?.addEventListener("change", () => {
  if (adminToggle.checked) {
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
  if (password.length > 7) strength += 1;
  if (password.length > 11) strength += 1;
  if (/[A-Z]/.test(password)) strength += 1;
  if (/[0-9]/.test(password)) strength += 1;
  if (/[^A-Za-z0-9]/.test(password)) strength += 1;
  return strength;
}

pass1?.addEventListener("input", function () {
  const strength = checkPasswordStrength(this.value);
  const strengthText = ["Weak", "Medium", "Strong"][Math.min(strength, 2)];
  passStat.textContent = `Password Strength: ${strengthText}`;
  passStat.style.color =
    strength < 2 ? "#ff1212" : strength < 4 ? "#ffc107" : "#28a745";
  passStat.style.display = "flex";
});

// Registration Handler
registerBtn?.addEventListener("click", async function () {
  const name = document.getElementById("FullName").value.trim();
  const email = document.getElementById("email").value.trim();
  const phone = document.getElementById("phonenumber").value.trim();
  const password = pass1.value;
  const confirmPassword = pass2.value;

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

  const userData = {
    name: name,
    email: email,
    phone: phone,
    password: password,
  };

  try {
    registerBtn.disabled = true;
    registerBtn.innerHTML =
      '<i class="bx bx-loader-alt bx-spin"></i> Processing...';

    const response = await fetch("../auth/register.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify(userData),
    });

    const data = await response.json();

    if (!response.ok) {
      throw new Error(data.message || "Registration failed");
    }

    document.getElementById("confirmationMsg").textContent =
      data.message || "Registration successful!";
    confirmationPage.style.display = "flex";

    setTimeout(() => {
      window.location.href = "login.html";
    }, 2000);
  } catch (error) {
    alert(`Registration failed: ${error.message}`);
  } finally {
    registerBtn.disabled = false;
    registerBtn.textContent = "Submit";
  }
});

// Login Handler - UPDATED VERSION
document
  .querySelector(".SignInbtn")
  ?.addEventListener("click", async function () {
    const btn = this;
    const email = document.getElementById("signInUser").value.trim();
    const password = document.getElementById("signInPass").value.trim();
    const isAdminLogin = document.getElementById("adminLoginCheck").checked;


    if (!email || !password) {
      alert("Please enter email and password.");
      return;
    }

    try {
      // Set loading state
      btn.disabled = true;
      btn.innerHTML = '<i class="bx bx-loader-alt bx-spin"></i> Logging in...';

      const response = await fetch("../auth/login.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ email, password,isAdminLogin }),
      });

      const data = await response.json();

      if (!response.ok) {
        throw new Error(data.message || "Login failed");
      }

      // Successful login - redirect to PHP home page
      window.location.href = data.redirect;
    } catch (error) {
      alert(`Login failed: ${error.message}`);
    } finally {
      // Reset button state
      btn.disabled = false;
      btn.textContent = "Login";
    }
  });

// Password Reset Handler
document
  .getElementById("submitNewPassword")
  ?.addEventListener("click", function () {
    const token = document.getElementById("resetToken").value;
    const newPassword = document.getElementById("newPassword").value;
    const confirmPassword = document.getElementById("confirmPassword").value;

    if (!token) {
      alert("Invalid reset token");
      return;
    }

    if (newPassword !== confirmPassword) {
      alert("Passwords do not match");
      return;
    }

    if (newPassword.length < 8) {
      alert("Password must be at least 8 characters");
      return;
    }

    fetch("../auth/reset-password-handler.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ token, password: newPassword }),
    })
      .then((response) => response.json())
      .then((data) => {
        if (data.success) {
          alert(
            "Password reset successfully. You can now login with your new password."
          );
          window.location.href = "home.php";
        } else {
          alert("Error: " + data.message);
        }
      });
  });
