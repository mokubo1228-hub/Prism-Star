const contactForm = document.getElementById("contactForm");

if (contactForm) {
  contactForm.addEventListener("submit", (e) => {
    e.preventDefault();

    const firstName = document.getElementById("first-name").value.trim();
    const lastName = document.getElementById("last-name").value.trim();
    const email = document.getElementById("mail").value.trim();
    const message = document.querySelector("[name='message']").value.trim();

    if (!firstName || !lastName || !email) {
      alert("必須項目を入力してください。");
      return;
    }

    fetch("/api/contact.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({
        first_name: firstName,
        last_name: lastName,
        email,
        message,
      }),
    })
      .then(res => {
        if (!res.ok) return res.json().then(d => { throw new Error(d.error); });
        return res.json();
      })
      .then(() => {
        alert("お問い合わせを受け付けました。");
        contactForm.reset();
      })
      .catch(err => alert(err.message));
  });
}
