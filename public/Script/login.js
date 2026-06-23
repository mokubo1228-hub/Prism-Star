const loginForm = document.getElementById("loginForm");
const loginError = document.getElementById("loginError");
const loginParams = new URLSearchParams(window.location.search);

function showLoginError(message) {
  if (!loginError) {
    alert(message);
    return;
  }
  loginError.textContent = message;
  loginError.hidden = false;
}

if (loginForm) {
  loginForm.addEventListener("submit", (e) => {
    e.preventDefault();
    if (loginError) loginError.hidden = true;

    const email = document.getElementById("login-id").value.trim();
    const password = document.getElementById("login-pw").value;

    if (!email || !password) {
      showLoginError("IDとパスワードを入力してください。");
      return;
    }

    fetch("/api/auth.php?action=login", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ email, password })
    })
      .then(res => {
        if (!res.ok) return res.json().then(d => { throw new Error(d.error); });
        return res.json();
      })
      .then(() => {
        window.location.href = loginParams.get("return_to") || "gallery-list.php";
      })
      .catch(err => showLoginError(err.message));
  });
}
