const forgotForm = document.getElementById("forgotForm");
const forgotMessage = document.getElementById("forgotMessage");
const forgotError = document.getElementById("forgotError");

function showForgotMessage(message) {
  forgotMessage.textContent = message;
  forgotMessage.hidden = false;
}

function showForgotError(message) {
  forgotError.textContent = message;
  forgotError.hidden = false;
}

if (forgotForm) {
  forgotForm.addEventListener("submit", (e) => {
    e.preventDefault();
    forgotMessage.hidden = true;
    forgotError.hidden = true;

    const email = document.getElementById("forgot-email").value.trim();
    if (!email) {
      showForgotError("メールアドレスを入力してください。");
      return;
    }

    fetch("/api/auth.php?action=reset-request", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ email })
    })
      .then(res => {
        if (!res.ok) return res.json().then(d => { throw new Error(d.error); });
        return res.json();
      })
      .then(data => {
        showForgotMessage(data.message || "登録済みの場合は再設定メールを送信しました。");
        forgotForm.reset();
      })
      .catch(err => showForgotError(err.message));
  });
}
