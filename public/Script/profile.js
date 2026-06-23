const profileParams = new URLSearchParams(window.location.search);
const profileId = profileParams.get("id");
const profileName = document.getElementById("profileName");
const profileStars = document.getElementById("profileStars");
const profileWorks = document.getElementById("profileWorks");
const profileTemplate = document.getElementById("profile-item-template");
const githubRepos = document.getElementById("githubRepos");

function showProfileMessage(message, className = "profile-error") {
  profileWorks.innerHTML = `<p class="${className}">${message}</p>`;
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

  if (!profileId || !profileTemplate || !profileWorks) {
    if (profileName) profileName.textContent = "ユーザーが見つかりません";
    showProfileMessage("ユーザーが見つかりません。");
    return;
  }

  try {
    const res = await fetch(`/api/users.php?id=${encodeURIComponent(profileId)}`);
    const user = await res.json();
    if (!res.ok) throw new Error(user.error || "ユーザーが見つかりません");

    profileName.textContent = `${user.name} の作品`;
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
