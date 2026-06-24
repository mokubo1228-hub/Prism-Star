const searchHeading = document.getElementById("searchHeading");
const searchResults = document.getElementById("searchResults");
const initialParams = new URLSearchParams(window.location.search);

function showSearchMessage(message, className = "profile-empty") {
  searchResults.textContent = "";
  const item = document.createElement("p");
  item.className = className;
  item.textContent = message;
  searchResults.appendChild(item);
}

function currentSearchLabel(params) {
  if (params.get("type") === "users") {
    return { type: "users", term: params.get("q") || "", label: "ユーザー" };
  }
  if (params.get("tag")) {
    return { type: "works", term: params.get("tag") || "", label: "タグ" };
  }
  return { type: "works", term: params.get("q") || "", label: "キーワード" };
}

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
  if (work.source === "github") {
    const badge = document.createElement("span");
    badge.className = "source-badge";
    badge.textContent = "GitHub";
    tags.prepend(badge, " ");
  }

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
  const search = currentSearchLabel(initialParams);
  if (searchHeading) {
    // 空検索は「全件ブラウズ」。サーバは q/tag が空なら全公開作品（自分以外）/全ユーザーを返す。
    searchHeading.textContent = search.term === ""
      ? (search.type === "users" ? "すべてのユーザー" : "すべての作品")
      : `${search.label}「${search.term}」の検索結果`;
  }

  await window.PrismAuth.ready;
  if (!await window.PrismAuth.requireAuth(window.location.href)) {
    showSearchMessage("ログインすると検索結果を表示できます。");
    return;
  }

  showSearchMessage("検索中...");

  try {
    const res = await fetch(`/api/search.php?${initialParams.toString()}`);
    const data = await res.json();
    if (!res.ok) throw new Error(data.error || "検索に失敗しました");
    searchResults.textContent = "";
    if (!data.results.length) {
      showSearchMessage("結果がありません。");
      return;
    }
    data.results.forEach(data.type === "users" ? renderUser : renderWork);
  } catch (err) {
    showSearchMessage(err.message, "profile-error");
  }
}

runSearch();
