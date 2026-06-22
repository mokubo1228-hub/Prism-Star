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

    fetch("/api/auth.php?action=login", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ email, password }),
    })
      .then(res => {
        if (!res.ok) return res.json().then(d => { throw new Error(d.error); });
        return res.json();
      })
      .then(() => {
        window.location.href = "gallery-list.php";
      })
      .catch(err => alert(err.message));
  });
}
