const myWorks = document.getElementById("myWorks");
const githubSettingsForm = document.getElementById("githubSettingsForm");
const githubUsernameInput = document.getElementById("githubUsername");
const githubSettingsMessage = document.getElementById("githubSettingsMessage");

function showGitHubMessage(message, isError = false) {
  githubSettingsMessage.textContent = message;
  githubSettingsMessage.hidden = false;
  githubSettingsMessage.classList.toggle("is-error", isError);
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
  meta.textContent = `${work.visibility === "private" ? "非公開" : "公開"} / ⭐ ${work.star_count}`;

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
    return;
  }
  myWorks.innerHTML = "";
  if (data.length === 0) {
    myWorks.innerHTML = "<p class=\"profile-empty\">作品がありません。</p>";
    return;
  }
  data.forEach(renderWork);
}

async function loadMe() {
  const status = await window.PrismAuth.refresh();
  if (!status.loggedIn) return;
  const res = await fetch(`/api/users.php?id=${status.user.id}`);
  const user = await res.json();
  if (res.ok) {
    githubUsernameInput.value = user.github_username || "";
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
  } catch (err) {
    showGitHubMessage(err.message, true);
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
}

initMypage();
