let pass1 = document.getElementById("pass1");
let pass2 = document.getElementById("pass2");
let passStat = document.getElementById("passwordstat");
let name = document.querySelector("#FullName").value;
let email = document.querySelector("#email").value;
let phone = document.querySelector("#phonenumber").value;
let loginPage = document.querySelector(".login");
let confirmationPage = document.querySelector(".confirmation");

let btnSwitchLogIn = document.querySelector(".SwitchLoginIn");
let btnSubmit = document.querySelector(".Submit");

let signIn = document.querySelector(".signIn");
let signUp = document.querySelector(".signUp");
let altSign = document.querySelector(".altSignIn");
let altSigntxt = document.querySelector("#altSignIntext");
let Welcometxt = document.querySelector("#Welcometxt");
let welcomebodytxt = document.querySelector("#welcomebodytxt");

class userData {
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
function SignUp() {
  //check fields
  if (
    name.trim() !== "" &&
    phone.trim() !== "" &&
    email.trim() !== "" &&
    pass1.value.trim() !== "" &&
    pass2.value.trim() !== ""
  ) {
    if (pass1.value != pass2.value) {
      passStat.textContent = "Passwords Do not Match";
      passStat.style.display = "flex";
      pass1.value = "";
      pass2.value = "";
    } else {
      let newUser = new userData(
        name.trim(),
        email.trim(),
        phone.trim(),
        pass1.value.trim()
      );
      loginPage.style.display = "none";
      confirmationPage.style.display = "flex";
    }
  } else {
    passStat.textContent = "Please fill in all the fields";
    passStat.style.display = "flex";
  }
}

btnSwitchLogIn.addEventListener("click", function () {
  if (btnSwitchLogIn.textContent == "Sign Up") {
    Welcometxt.textContent = "Create a New Account";
    welcomebodytxt.textContent =
      "If you already have an acount please log in to your account.";
    btnSwitchLogIn.textContent = "Login";
    signIn.style.display = "none";
    signUp.style.display = "flex";
    altSign.style.display = "none";
    altSigntxt.style.display = "none";
  } else {
    Welcometxt.textContent = "Hi! Welcome Back";
    welcomebodytxt.textContent =
      "If you don't have an existing account please sign Up.";
    btnSwitchLogIn.textContent = "Sign Up";
    signIn.style.display = "flex";
    signUp.style.display = "none";
    altSign.style.display = "flex";
    altSigntxt.style.display = "flex";
  }
});
