const myWorks = document.getElementById("myWorks");
const githubSettingsForm = document.getElementById("githubSettingsForm");
const githubUsernameInput = document.getElementById("githubUsername");
const githubSettingsMessage = document.getElementById("githubSettingsMessage");
const githubImportPanel = document.getElementById("githubImportPanel");
const githubImportMessage = document.getElementById("githubImportMessage");
const githubImportRepos = document.getElementById("githubImportRepos");
const accountNameForm = document.getElementById("accountNameForm");
const accountNameInput = document.getElementById("accountName");
const accountNameMessage = document.getElementById("accountNameMessage");
const passwordChangeForm = document.getElementById("passwordChangeForm");
const currentPasswordInput = document.getElementById("currentPassword");
const newPasswordInput = document.getElementById("newPassword");
const passwordChangeMessage = document.getElementById("passwordChangeMessage");
let currentWorks = [];
let currentGithubUsername = "";

function showGitHubMessage(message, isError = false) {
  githubSettingsMessage.textContent = message;
  githubSettingsMessage.hidden = false;
  githubSettingsMessage.classList.toggle("is-error", isError);
}

function showAccountMessage(element, message, isError = false) {
  element.textContent = message;
  element.hidden = false;
  element.classList.toggle("is-error", isError);
}

function renderWork(work) {
  const article = document.createElement("article");
  article.className = "mywork-card";

  const img = document.createElement("img");
  img.src = work.src;
  img.alt = work.title;

  const body = document.createElement("div");
  body.className = "mywork-body";

  const title = document.createElement("h3");
  title.textContent = work.title;

  const meta = document.createElement("p");
  meta.className = "mywork-meta";
  meta.textContent = `${work.visibility === "private" ? "非公開" : "公開"} / ★ ${work.star_count}`;
  if (work.source === "github") {
    const source = document.createElement("span");
    source.className = "source-badge";
    source.textContent = "GitHub";
    meta.append(" / ", source);
  }

  const tags = document.createElement("p");
  tags.className = "tag-list";
  tags.textContent = (work.tags || []).map(tag => `#${tag}`).join(" ");

  const actions = document.createElement("div");
  actions.className = "mywork-actions";
  const detail = document.createElement("a");
  detail.href = `gallery-detail.php?id=${work.id}`;
  detail.textContent = "詳細";
  const edit = document.createElement("a");
  edit.href = `work-edit.php?id=${work.id}`;
  edit.textContent = "編集";
  const toggle = document.createElement("button");
  toggle.type = "button";
  toggle.textContent = work.visibility === "private" ? "公開にする" : "非公開にする";
  toggle.addEventListener("click", () => toggleVisibility(work));
  const del = document.createElement("button");
  del.type = "button";
  del.className = "danger-action";
  del.textContent = "削除";
  del.addEventListener("click", () => deleteWork(work));
  actions.append(detail, edit, toggle, del);

  body.append(title, meta, tags, actions);
  article.append(img, body);
  myWorks.appendChild(article);
}

async function toggleVisibility(work) {
  const form = new FormData();
  form.append("title", work.title);
  form.append("desc", work.desc || "");
  form.append("visibility", work.visibility === "private" ? "public" : "private");
  form.append("tags", (work.tags || []).join(","));

  const res = await fetch(`/api/gallery.php?id=${work.id}&_method=PATCH`, {
    method: "POST",
    body: form
  });
  const data = await res.json();
  if (!res.ok) {
    alert(data.error || "公開設定を更新できませんでした");
    return;
  }
  await loadWorks();
}

async function deleteWork(work) {
  if (!confirm(`「${work.title}」を削除しますか？`)) return;
  const res = await fetch(`/api/gallery.php?id=${work.id}`, { method: "DELETE" });
  const data = await res.json();
  if (!res.ok) {
    alert(data.error || "削除できませんでした");
    return;
  }
  await loadWorks();
}

async function loadWorks() {
  myWorks.innerHTML = "<p class=\"profile-empty\">読み込み中...</p>";
  const res = await fetch("/api/gallery.php?mine=1");
  const data = await res.json();
  if (!res.ok) {
    myWorks.innerHTML = `<p class="profile-error">${data.error || "読み込みに失敗しました"}</p>`;
    currentWorks = [];
    return currentWorks;
  }
  currentWorks = data;
  myWorks.innerHTML = "";
  if (data.length === 0) {
    myWorks.innerHTML = "<p class=\"profile-empty\">作品がありません。</p>";
    return currentWorks;
  }
  data.forEach(renderWork);
  return currentWorks;
}

async function loadMe() {
  const status = await window.PrismAuth.refresh();
  if (!status.loggedIn) return null;
  const res = await fetch(`/api/users.php?id=${status.user.id}`);
  const user = await res.json();
  if (res.ok) {
    accountNameInput.value = user.name || "";
    githubUsernameInput.value = user.github_username || "";
    currentGithubUsername = user.github_username || "";
    return user;
  }
  return null;
}

function showImportMessage(message, isError = false) {
  githubImportMessage.textContent = message;
  githubImportMessage.classList.toggle("is-error", isError);
}

function importedSourceUrls() {
  return new Set(currentWorks.filter(work => work.source === "github" && work.source_url).map(work => work.source_url));
}

async function importRepo(repo, button) {
  button.disabled = true;
  button.textContent = "取り込み中...";
  try {
    const res = await fetch("/api/gallery.php?action=import-github", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ repo: repo.name })
    });
    const data = await res.json();
    if (!res.ok) throw new Error(data.error || "取り込めませんでした");
    showImportMessage("取り込みました。");
    await loadWorks();
    await loadGitHubImportPanel(currentGithubUsername);
  } catch (err) {
    showImportMessage(err.message, true);
    button.disabled = false;
    button.textContent = "取り込む";
  }
}

function renderImportRepo(repo) {
  const imported = importedSourceUrls().has(repo.html_url);
  const card = document.createElement("article");
  card.className = "github-import-card";

  const title = document.createElement("a");
  title.href = repo.html_url;
  title.target = "_blank";
  title.rel = "noopener noreferrer";
  title.textContent = repo.name;

  const desc = document.createElement("p");
  desc.textContent = repo.description || "No description";

  const meta = document.createElement("p");
  meta.className = "github-repo-meta";
  meta.textContent = `${repo.language || "No language"} / stars ${repo.stargazers_count}`;

  const status = document.createElement("span");
  status.className = "github-import-status";
  status.textContent = imported ? "取り込み済み" : "未取り込み";

  const button = document.createElement("button");
  button.type = "button";
  button.textContent = imported ? "再取り込み" : "取り込む";
  button.addEventListener("click", () => importRepo(repo, button));

  card.append(title, desc, meta, status, button);
  githubImportRepos.appendChild(card);
}

async function loadGitHubImportPanel(username) {
  githubImportPanel.hidden = false;
  githubImportRepos.innerHTML = "";
  if (!username) {
    showImportMessage("GitHub username を保存するとリポジトリを取り込めます。");
    return;
  }

  showImportMessage("GitHub repositories loading...");
  try {
    const res = await fetch(`/api/github.php?user=${encodeURIComponent(username)}`);
    const repos = await res.json();
    if (!res.ok) throw new Error(repos.error || "GitHubリポジトリを読み込めませんでした");
    const importableRepos = repos.filter(repo => !repo.fork);
    githubImportRepos.innerHTML = "";
    if (importableRepos.length === 0) {
      showImportMessage("取り込めるリポジトリがありません。");
      return;
    }
    showImportMessage("");
    importableRepos.forEach(renderImportRepo);
  } catch (err) {
    githubImportRepos.innerHTML = "";
    showImportMessage(err.message, true);
  }
}

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
    showGitHubMessage("保存しました。");
    currentGithubUsername = data.github_username || "";
    await loadGitHubImportPanel(currentGithubUsername);
  } catch (err) {
    showGitHubMessage(err.message, true);
  }
});

// アカウント設定の2フォーム。CSRF トークンは common.js の fetch ラッパが自動付与するので手動で付けない。
// 表示名は保存後 PrismAuth.refresh() でヘッダ等の表示と同期、パスワードは成功時に入力欄をクリアする。
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

async function initMypage() {
  await window.PrismAuth.ready;
  if (!await window.PrismAuth.requireAuth(window.location.href)) {
    myWorks.innerHTML = "<p class=\"profile-empty\">ログインするとマイページを表示できます。</p>";
    return;
  }
  await loadMe();
  await loadWorks();
  await loadGitHubImportPanel(currentGithubUsername);
}

initMypage();
