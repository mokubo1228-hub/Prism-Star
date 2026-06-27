const myWorks = document.getElementById("myWorks");
const githubImportPanel = document.getElementById("githubImportPanel");
const githubImportMessage = document.getElementById("githubImportMessage");
const githubImportRepos = document.getElementById("githubImportRepos");
let currentWorks = [];
let currentGithubUsername = "";

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
    currentGithubUsername = user.github_username || "";
    return user;
  }
  return null;
}

function showImportMessage(message, isError = false) {
  githubImportMessage.textContent = message;
  githubImportMessage.classList.toggle("is-error", isError);
}

function showGitHubSettingsPrompt() {
  githubImportMessage.textContent = "";
  githubImportMessage.classList.remove("is-error");
  githubImportMessage.append("設定で GitHub username を登録すると取り込めます。");
  const link = document.createElement("a");
  link.href = "settings.php";
  link.textContent = "設定へ";
  githubImportMessage.append(" ", link);
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
    showGitHubSettingsPrompt();
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
