const profileParams = new URLSearchParams(window.location.search);
const profileId = profileParams.get("id");
const profileName = document.getElementById("profileName");
const profileWorks = document.getElementById("profileWorks");
const profileTemplate = document.getElementById("profile-item-template");
const githubSettingsForm = document.getElementById("githubSettingsForm");
const githubUsernameInput = document.getElementById("githubUsername");
const githubSettingsMessage = document.getElementById("githubSettingsMessage");
const githubRepos = document.getElementById("githubRepos");
let currentUser = null;

function showProfileMessage(message, className = "profile-error") {
  profileWorks.innerHTML = `<p class="${className}">${message}</p>`;
}

function renderProfileWork(work) {
  const clone = profileTemplate.content.cloneNode(true);
  const link = clone.querySelector(".work-link");
  const img = clone.querySelector("img");
  const figcaption = clone.querySelector("figcaption");

  link.href = `gallery-detail.html?id=${work.id}`;
  img.src = work.src;
  img.alt = work.title;
  figcaption.textContent = work.title;

  profileWorks.appendChild(clone);
}

function showGitHubMessage(message, isError = false) {
  githubSettingsMessage.textContent = message;
  githubSettingsMessage.hidden = false;
  githubSettingsMessage.classList.toggle("is-error", isError);
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
  const language = repo.language || "No language";
  meta.textContent = `${language} / stars ${repo.stargazers_count}`;

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

function applyGitHubSettings(user) {
  if (!githubSettingsForm || !currentUser?.loggedIn) return;

  if (Number(currentUser.user?.id) === Number(user.id)) {
    githubSettingsForm.hidden = false;
    githubUsernameInput.value = user.github_username || "";
  }
}

if (githubSettingsForm) {
  githubSettingsForm.addEventListener("submit", (e) => {
    e.preventDefault();
    githubSettingsMessage.hidden = true;
    githubSettingsMessage.textContent = "";

    const github_username = githubUsernameInput.value.trim();
    fetch("/api/users.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ github_username }),
    })
      .then(res => {
        if (!res.ok) return res.json().then(d => { throw new Error(d.error); });
        return res.json();
      })
      .then(data => {
        showGitHubMessage("保存しました。");
        if (data.github_username) {
          loadGitHubRepos(data.github_username);
        } else {
          githubRepos.innerHTML = "<p class=\"profile-empty\">GitHub username が未設定です。</p>";
        }
      })
      .catch(err => showGitHubMessage(err.message, true));
  });
}

if (!profileId || !profileTemplate || !profileWorks) {
  if (profileName) profileName.textContent = "ユーザーが見つかりません";
  showProfileMessage("ユーザーが見つかりません。");
} else {
  Promise.all([
    fetch(`/api/users.php?id=${encodeURIComponent(profileId)}`).then(res => {
      if (!res.ok) throw new Error();
      return res.json();
    }),
    fetch("/api/auth.php?action=status").then(res => res.json()).catch(() => ({ loggedIn: false })),
  ])
    .then(([user, status]) => {
      currentUser = status;
      profileName.textContent = `${user.name} の作品`;
      applyGitHubSettings(user);

      if (user.works.length === 0) {
        showProfileMessage("まだ作品がありません。", "profile-empty");
      } else {
        user.works.forEach(renderProfileWork);
      }

      if (user.github_username) {
        loadGitHubRepos(user.github_username);
      } else {
        githubRepos.innerHTML = "<p class=\"profile-empty\">GitHub username が未設定です。</p>";
      }
    })
    .catch(() => {
      if (profileName) profileName.textContent = "ユーザーが見つかりません";
      showProfileMessage("ユーザーが見つかりません。");
    });
}
