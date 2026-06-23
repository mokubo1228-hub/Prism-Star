const registerForm = document.getElementById("registerForm");
const registerError = document.getElementById("registerError");
const registerMessage = document.getElementById("registerMessage");

function showRegisterError(message) {
  registerError.textContent = message;
  registerError.hidden = false;
}

function showRegisterMessage(message) {
  registerMessage.textContent = message;
  registerMessage.hidden = false;
}

if (registerForm) {
  registerForm.addEventListener("submit", (e) => {
    e.preventDefault();
    registerError.hidden = true;
    registerMessage.hidden = true;

    const email = document.getElementById("register-email").value.trim();
    if (!email) {
      showRegisterError("メールアドレスを入力してください。");
      return;
    }

    fetch("/api/auth.php?action=register-request", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ email })
    })
      .then(res => {
        if (!res.ok) return res.json().then(d => { throw new Error(d.error); });
        return res.json();
      })
      .then(data => {
        showRegisterMessage(data.message || "確認メールを送信しました。");
        registerForm.reset();
      })
      .catch(err => showRegisterError(err.message));
  });
}
