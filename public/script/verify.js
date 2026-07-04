const verifyForm = document.getElementById("verifyForm");
const verifyError = document.getElementById("verifyError");

function showVerifyError(message) {
  verifyError.textContent = message;
  verifyError.hidden = false;
}

if (verifyForm) {
  verifyForm.addEventListener("submit", (e) => {
    e.preventDefault();
    verifyError.hidden = true;

    const token = document.getElementById("verifyToken").value;
    const name = document.getElementById("verifyName").value.trim();
    const password = document.getElementById("verifyPassword").value;

    fetch("/api/auth.php?action=register-complete", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ token, name, password })
    })
      .then(res => {
        if (!res.ok) return res.json().then(d => { throw new Error(d.error); });
        return res.json();
      })
      .then(() => {
        window.location.href = "gallery-list.php";
      })
      .catch(err => showVerifyError(err.message));
  });
}
