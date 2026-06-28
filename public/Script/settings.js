const passwordChangeForm = document.getElementById("passwordChangeForm");
const currentPasswordInput = document.getElementById("currentPassword");
const newPasswordInput = document.getElementById("newPassword");
const passwordChangeMessage = document.getElementById("passwordChangeMessage");
const githubSettingsForm = document.getElementById("githubSettingsForm");
const githubUsernameInput = document.getElementById("githubUsername");
const githubSettingsMessage = document.getElementById("githubSettingsMessage");
const accountDeleteForm = document.getElementById("accountDeleteForm");
const deleteCurrentPasswordInput = document.getElementById("deleteCurrentPassword");
const accountDeleteMessage = document.getElementById("accountDeleteMessage");

function showAccountMessage(element, message, isError = false) {
  element.textContent = message;
  element.hidden = false;
  element.classList.toggle("is-error", isError);
}

function showGitHubMessage(message, isError = false) {
  githubSettingsMessage.textContent = message;
  githubSettingsMessage.hidden = false;
  githubSettingsMessage.classList.toggle("is-error", isError);
}

async function loadMe() {
  const status = await window.PrismAuth.refresh();
  if (!status.loggedIn) return null;
  const res = await fetch(`/api/users.php?id=${status.user.id}`);
  const user = await res.json();
  if (res.ok) {
    githubUsernameInput.value = user.github_username || "";
    return user;
  }
  return null;
}

passwordChangeForm.addEventListener("submit", async (e) => {
  e.preventDefault();
  passwordChangeMessage.hidden = true;
  try {
    const res = await fetch("/api/auth.php?action=change-password", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({
        current_password: currentPasswordInput.value,
        new_password: newPasswordInput.value
      })
    });
    const data = await res.json();
    if (!res.ok) throw new Error(data.error || "パスワードを変更できませんでした");
    currentPasswordInput.value = "";
    newPasswordInput.value = "";
    showAccountMessage(passwordChangeMessage, data.message || "パスワードを変更しました。");
  } catch (err) {
    showAccountMessage(passwordChangeMessage, err.message, true);
  }
});

githubSettingsForm.addEventListener("submit", async (e) => {
  e.preventDefault();
  githubSettingsMessage.hidden = true;
  const github_username = githubUsernameInput.value.trim();
  try {
    const res = await fetch("/api/users.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ github_username })
    });
    const data = await res.json();
    if (!res.ok) throw new Error(data.error || "保存できませんでした");
    githubUsernameInput.value = data.github_username || "";
    showGitHubMessage("保存しました。");
  } catch (err) {
    showGitHubMessage(err.message, true);
  }
});

accountDeleteForm.addEventListener("submit", async (e) => {
  e.preventDefault();
  accountDeleteMessage.hidden = true;

  if (!confirm("アカウントを削除します。作品・スター・プロフィールは元に戻せません。よろしいですか？")) {
    return;
  }

  try {
    const res = await fetch("/api/auth.php?action=delete-account", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ current_password: deleteCurrentPasswordInput.value })
    });
    const data = await res.json();
    if (!res.ok) throw new Error(data.error || "アカウントを削除できませんでした");
    window.location.href = "login.php";
  } catch (err) {
    showAccountMessage(accountDeleteMessage, err.message, true);
  }
});

async function initSettings() {
  await window.PrismAuth.ready;
  if (!await window.PrismAuth.requireAuth(window.location.href)) {
    return;
  }
  await loadMe();
}

initSettings();
