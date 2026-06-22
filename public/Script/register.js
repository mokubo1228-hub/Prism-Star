const registerForm = document.getElementById("registerForm");
const registerError = document.getElementById("registerError");

function showRegisterError(message) {
  registerError.textContent = message;
  registerError.hidden = false;
}

if (registerForm) {
  registerForm.addEventListener("submit", (e) => {
    e.preventDefault();
    registerError.hidden = true;
    registerError.textContent = "";

    const name = document.getElementById("register-name").value.trim();
    const email = document.getElementById("register-email").value.trim();
    const password = document.getElementById("register-password").value;

    if (!name || !email || !password) {
      showRegisterError("すべての項目を入力してください。");
      return;
    }

    fetch("/api/auth.php?action=register", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ name, email, password }),
    })
      .then(res => {
        if (!res.ok) return res.json().then(d => { throw new Error(d.error); });
        return res.json();
      })
      .then(() => {
        window.location.href = "gallery-list.php";
      })
      .catch(err => showRegisterError(err.message));
  });
}
