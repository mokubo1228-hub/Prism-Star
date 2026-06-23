const searchForm = document.getElementById("searchForm");
const searchQuery = document.getElementById("searchQuery");
const searchTag = document.getElementById("searchTag");
const searchType = document.getElementById("searchType");
const searchResults = document.getElementById("searchResults");
const initialParams = new URLSearchParams(window.location.search);

function renderWork(work) {
  const article = document.createElement("article");
  article.className = "gallery-card";

  const link = document.createElement("a");
  link.className = "work-link";
  link.href = `gallery-detail.php?id=${work.id}`;

  const figure = document.createElement("figure");
  figure.className = "gallery-item";
  const img = document.createElement("img");
  img.src = work.src;
  img.alt = work.title;
  const caption = document.createElement("figcaption");
  caption.textContent = work.title;
  figure.append(img, caption);
  link.appendChild(figure);

  const author = document.createElement("a");
  author.className = "author-link";
  author.href = `profile.php?id=${work.user_id}`;
  author.textContent = `by ${work.author || "Unknown"}`;

  const tags = document.createElement("p");
  tags.className = "tag-list";
  tags.textContent = (work.tags || []).map(tag => `#${tag}`).join(" ");

  article.append(link, author, tags);
  searchResults.appendChild(article);
}

function renderUser(user) {
  const article = document.createElement("article");
  article.className = "user-result-card";

  const link = document.createElement("a");
  link.href = `profile.php?id=${user.id}`;
  link.textContent = user.name;

  const github = document.createElement("p");
  github.textContent = user.github_username ? `GitHub: ${user.github_username}` : "GitHub未設定";

  const meta = document.createElement("span");
  meta.textContent = `公開作品 ${user.public_work_count} / 獲得スター ${user.total_stars}`;

  article.append(link, github, meta);
  searchResults.appendChild(article);
}

async function runSearch() {
  await window.PrismAuth.ready;
  if (!await window.PrismAuth.requireAuth(window.location.href)) {
    searchResults.innerHTML = "<p class=\"profile-empty\">ログインすると検索結果を表示できます。</p>";
    return;
  }

  const params = new URLSearchParams({
    q: searchQuery.value.trim(),
    tag: searchTag.value.trim(),
    type: searchType.value
  });
  history.replaceState(null, "", `search.php?${params.toString()}`);
  searchResults.innerHTML = "<p class=\"profile-empty\">検索中...</p>";

  try {
    const res = await fetch(`/api/search.php?${params.toString()}`);
    const data = await res.json();
    if (!res.ok) throw new Error(data.error || "検索に失敗しました");
    searchResults.innerHTML = "";
    if (!data.results.length) {
      searchResults.innerHTML = "<p class=\"profile-empty\">結果がありません。</p>";
      return;
    }
    data.results.forEach(data.type === "users" ? renderUser : renderWork);
  } catch (err) {
    searchResults.innerHTML = "";
    const message = document.createElement("p");
    message.className = "profile-error";
    message.textContent = err.message;
    searchResults.appendChild(message);
  }
}

searchQuery.value = initialParams.get("q") || "";
searchTag.value = initialParams.get("tag") || "";
searchType.value = initialParams.get("type") || "works";

searchForm.addEventListener("submit", (e) => {
  e.preventDefault();
  runSearch();
});

if (initialParams.toString()) {
  runSearch();
}
