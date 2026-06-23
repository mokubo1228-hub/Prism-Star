const resetForm = document.getElementById("resetForm");
const resetError = document.getElementById("resetError");

function showResetError(message) {
  resetError.textContent = message;
  resetError.hidden = false;
}

if (resetForm) {
  resetForm.addEventListener("submit", (e) => {
    e.preventDefault();
    resetError.hidden = true;

    const token = document.getElementById("resetToken").value;
    const password = document.getElementById("resetPassword").value;
    const passwordConfirm = document.getElementById("resetPasswordConfirm").value;

    if (password.length < 8) {
      showResetError("パスワードは8文字以上で入力してください。");
      return;
    }
    if (password !== passwordConfirm) {
      showResetError("確認用パスワードが一致しません。");
      return;
    }

    fetch("/api/auth.php?action=reset-complete", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ token, password })
    })
      .then(res => {
        if (!res.ok) return res.json().then(d => { throw new Error(d.error); });
        return res.json();
      })
      .then(() => {
        window.location.href = "login.php";
      })
      .catch(err => showResetError(err.message));
  });
}
