// Elements
const loginForm = document.getElementById("login");
const registerForm = document.getElementById("register");
const forgotForm = document.getElementById("forgot-password-form");

const forgotLink = document.getElementById("forgot-password-link");
const backBtn = document.getElementById("back-btn");

const sendCodeBtn = document.getElementById("send-reset-code");
const verificationContainer = document.getElementById("verify-container");
const verificationCodeInput = document.getElementById("verification-code");
const verifyCodeBtn = document.getElementById("verify-code-btn");

const resetContainer = document.getElementById("reset-container");
const newPasswordInput = document.querySelector(".fp-new-password");
const confirmPasswordInput = document.querySelector(".fp-confirm-password");
const resetPasswordBtn = document.querySelector(".fp-reset-btn");

const btn = document.getElementById("btn"); // green toggle selector

const toggleLoginBtn = document.querySelector(".toggle-btn[onclick='login()']");
const toggleRegisterBtn = document.querySelector(".toggle-btn[onclick='register()']");

// Toggle Login/Register
function login() {
    loginForm.style.left = "50px";
    registerForm.style.left = "450px";
    btn.style.left = "0"; // highlight Login
}
function register() {
    loginForm.style.left = "-400px";
    registerForm.style.left = "50px";
    btn.style.left = "110px"; // highlight Register
}

toggleLoginBtn.addEventListener("click", login);
toggleRegisterBtn.addEventListener("click", register);

// Show Forgot Password
forgotLink.addEventListener("click", (e) => {
    e.preventDefault();
    loginForm.style.display = "none";
    forgotForm.style.display = "block";
});

// Back button
backBtn.addEventListener("click", () => {
    forgotForm.style.display = "none";
    loginForm.style.display = "block";
    hideAllForgotFields();
    sendCodeBtn.style.display = "block";
});

// Hide all forgot password fields
function hideAllForgotFields() {
    verificationContainer.style.display = "none";
    resetContainer.style.display = "none";
}

// Send Reset Code
sendCodeBtn.addEventListener("click", (e) => {
    e.preventDefault();
    const email = document.getElementById("forgot-email").value.trim();
    if (!email) return alert("Please enter your email!");

    // Show verification container
    verificationContainer.style.display = "flex";
    sendCodeBtn.style.display = "none";

    fetch("user_loginregis.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: `action=send_code&email=${encodeURIComponent(email)}`
    })
    .then(res => res.json())
    .then(data => {
        alert(data.message);
        if (!data.success) {
            sendCodeBtn.style.display = "block";
            verificationContainer.style.display = "none";
        }
    })
    .catch(err => {
        alert("Error sending code: " + err);
        sendCodeBtn.style.display = "block";
        verificationContainer.style.display = "none";
    });
});

// Verify Code
verifyCodeBtn.addEventListener("click", (e) => {
    e.preventDefault();
    const code = verificationCodeInput.value.trim();
    const email = document.getElementById("forgot-email").value.trim();
    if (!code) return alert("Please enter the verification code!");
    if (!email) return alert("Email is required to verify code!");

    fetch("user_loginregis.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: `action=verify_code&code=${encodeURIComponent(code)}&email=${encodeURIComponent(email)}`
    })
    .then(res => res.json())
    .then(data => {
        alert(data.message);
        if (data.success) {
            verificationContainer.style.display = "none";
            resetContainer.style.display = "flex"; // show new password inputs
        }
    })
    .catch(err => alert("Error verifying code: " + err));
});

// Reset Password
resetPasswordBtn.addEventListener("click", (e) => {
    e.preventDefault();
    const newPassword = newPasswordInput.value.trim();
    const confirmPassword = confirmPasswordInput.value.trim();

    if (!newPassword || !confirmPassword) return alert("Please fill in both password fields!");
    if (newPassword !== confirmPassword) return alert("Passwords do not match!");

    fetch("user_loginregis.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: `action=reset_password&new_password=${encodeURIComponent(newPassword)}&confirm_password=${encodeURIComponent(confirmPassword)}`
    })
    .then(res => res.json())
    .then(data => {
        alert(data.message);
        if (data.success) {
            hideAllForgotFields();
            sendCodeBtn.style.display = "block";
            verificationCodeInput.value = "";
            newPasswordInput.value = "";
            confirmPasswordInput.value = "";
            forgotForm.style.display = "none";
            loginForm.style.display = "block";
        }
    })
    .catch(err => alert("Error resetting password: " + err));
});
