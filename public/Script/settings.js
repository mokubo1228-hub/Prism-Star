const accountNameForm = document.getElementById("accountNameForm");
const accountNameInput = document.getElementById("accountName");
const accountNameMessage = document.getElementById("accountNameMessage");
const passwordChangeForm = document.getElementById("passwordChangeForm");
const currentPasswordInput = document.getElementById("currentPassword");
const newPasswordInput = document.getElementById("newPassword");
const passwordChangeMessage = document.getElementById("passwordChangeMessage");
const githubSettingsForm = document.getElementById("githubSettingsForm");
const githubUsernameInput = document.getElementById("githubUsername");
const githubSettingsMessage = document.getElementById("githubSettingsMessage");

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
    accountNameInput.value = user.name || "";
    githubUsernameInput.value = user.github_username || "";
    return user;
  }
  return null;
}

// CSRF は common.js の fetch ラッパに集約済み。ここで手動付与しないことで状態変更の実装を一箇所に保つ。
accountNameForm.addEventListener("submit", async (e) => {
  e.preventDefault();
  accountNameMessage.hidden = true;
  const name = accountNameInput.value.trim();
  try {
    const res = await fetch("/api/users.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ name })
    });
    const data = await res.json();
    if (!res.ok) throw new Error(data.error || "表示名を保存できませんでした");
    accountNameInput.value = data.name || name;
    await window.PrismAuth.refresh();
    showAccountMessage(accountNameMessage, "保存しました。");
  } catch (err) {
    showAccountMessage(accountNameMessage, err.message, true);
  }
});

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

async function initSettings() {
  await window.PrismAuth.ready;
  if (!await window.PrismAuth.requireAuth(window.location.href)) {
    return;
  }
  await loadMe();
}

initSettings();
