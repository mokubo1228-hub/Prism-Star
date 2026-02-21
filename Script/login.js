const loginForm = document.getElementById("loginForm");

if (loginForm) {
  loginForm.addEventListener("submit", (e) => {
    e.preventDefault();

    const email = document.getElementById("login-id").value.trim();
    const password = document.getElementById("login-pw").value;

    if (!email || !password) {
      alert("IDとパスワードを入力してください。");
      return;
    }

    // TODO: バックエンド導入時に fetch("/api/login", { ... }) に置き換え
    window.location.href = "gallery-list.html";
  });
}
