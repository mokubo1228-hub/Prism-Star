const profileParams = new URLSearchParams(window.location.search);
const profileId = profileParams.get("id");
const profileHandle = profileParams.get("u");
const profileUsername = document.getElementById("profileUsername");
const profileName = document.getElementById("profileName");
const profileBio = document.getElementById("profileBio");
const profileStars = document.getElementById("profileStars");
const profileWorks = document.getElementById("profileWorks");
const profileTemplate = document.getElementById("profile-item-template");
const githubRepos = document.getElementById("githubRepos");
const profileEditPanel = document.getElementById("profileEditPanel");
const profileEditForm = document.getElementById("profileEditForm");
const profileEditUsername = document.getElementById("profileEditUsername");
const profileEditName = document.getElementById("profileEditName");
const profileEditBio = document.getElementById("profileEditBio");
const profileEditMessage = document.getElementById("profileEditMessage");
let currentProfileUser = null;

function showProfileMessage(message, className = "profile-error") {
  profileWorks.textContent = "";
  const item = document.createElement("p");
  item.className = className;
  item.textContent = message;
  profileWorks.appendChild(item);
}

function showProfileEditMessage(message, isError = false) {
  profileEditMessage.textContent = message;
  profileEditMessage.hidden = false;
  profileEditMessage.classList.toggle("is-error", isError);
}

function renderProfileIdentity(user) {
  if (user.username) {
    profileUsername.textContent = `@${user.username}`;
    profileUsername.hidden = false;
  } else {
    profileUsername.textContent = "";
    profileUsername.hidden = true;
  }
  profileName.textContent = `${user.name} の作品`;
  if (user.bio) {
    profileBio.textContent = user.bio;
    profileBio.hidden = false;
  } else {
    profileBio.textContent = "";
    profileBio.hidden = true;
  }
}

function applyOwnerControls(user) {
  const owner = Number(window.PrismAuth.status.user?.id) === Number(user.id);
  profileEditPanel.hidden = !owner;
  if (!owner) return;

  profileEditUsername.value = user.username || "";
  profileEditName.value = user.name || "";
  profileEditBio.value = user.bio || "";
}

function renderProfileWork(work) {
  const clone = profileTemplate.content.cloneNode(true);
  const link = clone.querySelector(".work-link");
  const img = clone.querySelector("img");
  const figcaption = clone.querySelector("figcaption");
  const tagList = clone.querySelector(".tag-list");

  link.href = `gallery-detail.php?id=${work.id}`;
  img.src = work.src;
  img.alt = work.title;
  figcaption.textContent = work.title;
  tagList.textContent = (work.tags || []).map(tag => `#${tag}`).join(" ");
  if (work.source === "github") {
    const badge = document.createElement("span");
    badge.className = "source-badge";
    badge.textContent = "GitHub";
    tagList.prepend(badge, " ");
  }

  profileWorks.appendChild(clone);
}

function renderGitHubRepo(repo) {
  const card = document.createElement("article");
  card.className = "github-repo-card";

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

  card.append(title, desc, meta);
  githubRepos.appendChild(card);
}

function loadGitHubRepos(username) {
  githubRepos.innerHTML = "<p class=\"profile-empty\">GitHub repositories loading...</p>";
  fetch(`/api/github.php?user=${encodeURIComponent(username)}`)
    .then(res => {
      if (!res.ok) throw new Error();
      return res.json();
    })
    .then(repos => {
      githubRepos.innerHTML = "";
      if (repos.length === 0) {
        githubRepos.innerHTML = "<p class=\"profile-empty\">GitHubリポジトリがありません。</p>";
        return;
      }
      repos.forEach(renderGitHubRepo);
    })
    .catch(() => {
      githubRepos.innerHTML = "<p class=\"profile-error\">GitHubリポジトリを読み込めませんでした。</p>";
    });
}

async function loadProfile() {
  await window.PrismAuth.ready;
  if (!await window.PrismAuth.requireAuth(window.location.href)) {
    if (profileName) profileName.textContent = "ログインが必要です";
    showProfileMessage("ログインするとユーザーページを表示できます。");
    return;
  }

  if ((!profileId && !profileHandle) || !profileTemplate || !profileWorks) {
    if (profileName) profileName.textContent = "ユーザーが見つかりません";
    showProfileMessage("ユーザーが見つかりません。");
    return;
  }

  try {
    const query = profileHandle
      ? `u=${encodeURIComponent(profileHandle)}`
      : `id=${encodeURIComponent(profileId)}`;
    const res = await fetch(`/api/users.php?${query}`);
    const user = await res.json();
    if (!res.ok) throw new Error(user.error || "ユーザーが見つかりません");

    currentProfileUser = user;
    renderProfileIdentity(user);
    applyOwnerControls(user);
    profileStars.textContent = `獲得スター ${user.total_stars || 0}`;

    if (user.works.length === 0) {
      showProfileMessage("公開作品はまだありません。", "profile-empty");
    } else {
      profileWorks.innerHTML = "";
      user.works.forEach(renderProfileWork);
    }

    if (user.github_username) {
      loadGitHubRepos(user.github_username);
    } else {
      githubRepos.innerHTML = "<p class=\"profile-empty\">GitHub username が未設定です。</p>";
    }
  } catch (err) {
    if (profileName) profileName.textContent = "ユーザーが見つかりません";
    showProfileMessage(err.message);
  }
}

loadProfile();

profileEditForm?.addEventListener("submit", async (e) => {
  e.preventDefault();
  profileEditMessage.hidden = true;

  try {
    const res = await fetch("/api/users.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({
        username: profileEditUsername.value.trim(),
        name: profileEditName.value.trim(),
        bio: profileEditBio.value.trim()
      })
    });
    const data = await res.json();
    if (!res.ok) throw new Error(data.error || "プロフィールを保存できませんでした");
    currentProfileUser = {
      ...currentProfileUser,
      username: data.username || profileEditUsername.value.trim().toLowerCase(),
      name: data.name || profileEditName.value.trim(),
      bio: data.bio || ""
    };
    renderProfileIdentity(currentProfileUser);
    await window.PrismAuth.refresh();
    showProfileEditMessage("保存しました。");
  } catch (err) {
    showProfileEditMessage(err.message, true);
  }
});
