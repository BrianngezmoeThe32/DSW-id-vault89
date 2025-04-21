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
  }
});


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
          loginPage.style.display = "none";
          confirmationPage.style.display = "flex";
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


document.querySelector(".SignInbtn").addEventListener("click", function () {
  const email = document.getElementById("signInUser").value.trim();
  const password = document.getElementById("signInPass").value.trim();

  fetch("login.php", {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({ email: email, password: password }),
  })
    .then((res) => res.json())
    .then((data) => {
      if (data.status === "success") {
        alert("Login Successful!");
        
      } else {
        document.querySelector(".statusCheck").textContent =
          data.message || "Login failed.";
      }
    })
    .catch((err) => {
      document.querySelector(".statusCheck").textContent =
        "Server error. Try again.";
      console.error(err);
    });
});
