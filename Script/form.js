const contactForm = document.getElementById("contactForm");

if (contactForm) {
  contactForm.addEventListener("submit", (e) => {
    e.preventDefault();

    const firstName = document.getElementById("first-name").value.trim();
    const lastName = document.getElementById("last-name").value.trim();
    const mail = document.getElementById("mail").value.trim();

    if (!firstName || !lastName || !mail) {
      alert("必須項目を入力してください。");
      return;
    }

    // TODO: バックエンド導入時に fetch("/api/contact", { ... }) に置き換え
    alert("お問い合わせを受け付けました。");
    contactForm.reset();
  });
}
