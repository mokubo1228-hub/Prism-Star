const searchHeading = document.getElementById("searchHeading");
const searchResults = document.getElementById("searchResults");
const searchLoadMoreButton = document.getElementById("searchLoadMore");
const initialParams = new URLSearchParams(window.location.search);
let page = 1;
let loading = false;

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

  const thumb = document.createElement("div");
  thumb.className = "card-thumb";

  const link = document.createElement("a");
  link.className = "work-link";
  link.href = `gallery-detail.php?id=${work.id}`;
  link.addEventListener("click", async (e) => {
    e.preventDefault();
    if (await window.PrismAuth.requireAuth(link.href)) {
      window.location.href = link.href;
    }
  });

  const figure = document.createElement("figure");
  figure.className = "gallery-item";
  const img = document.createElement("img");
  img.src = work.src;
  img.alt = work.title;
  figure.appendChild(img);
  link.appendChild(figure);

  const starButton = document.createElement("button");
  starButton.type = "button";
  starButton.className = "star-button";
  starButton.dataset.galleryId = String(work.id);
  starButton.setAttribute("aria-pressed", "false");

  const starIcon = document.createElement("span");
  starIcon.className = "star-icon";
  starIcon.textContent = "★";
  const starCount = document.createElement("span");
  starCount.className = "star-count";
  starButton.append(starIcon, starCount);
  updateStarButton(starButton, work.star_count || 0, Boolean(work.starred));
  starButton.addEventListener("click", (e) => {
    e.preventDefault();
    toggleStar(work, starButton);
  });

  thumb.append(link, starButton);

  const body = document.createElement("div");
  body.className = "card-body";

  const title = document.createElement("h3");
  title.className = "card-title";
  title.textContent = work.title;

  const meta = document.createElement("div");
  meta.className = "card-meta";

  const metaText = document.createElement("div");
  metaText.className = "card-meta-text";

  const author = document.createElement("a");
  author.className = "author-link";
  author.href = `profile.php?id=${work.user_id}`;
  author.textContent = `by ${work.author || "Unknown"}`;
  author.addEventListener("click", async (e) => {
    e.preventDefault();
    if (await window.PrismAuth.requireAuth(author.href)) {
      window.location.href = author.href;
    }
  });

  const tags = document.createElement("p");
  tags.className = "tag-list";
  renderTagLinks(tags, work.tags || []);
  if (work.source === "github") {
    const badge = document.createElement("span");
    badge.className = "source-badge";
    badge.textContent = "GitHub";
    tags.prepend(badge, " ");
  }

  const avatar = document.createElement("img");
  avatar.className = "card-avatar";
  avatar.src = work.author_avatar || "Image/default-avatar.svg";
  avatar.alt = "";

  metaText.append(author, tags);
  meta.append(metaText, avatar);
  body.append(title, meta);
  article.append(thumb, body);
  searchResults.appendChild(article);
}

function renderUser(user) {
  const article = document.createElement("article");
  article.className = "user-result-card";

  const link = document.createElement("a");
  link.href = `profile.php?id=${user.id}`;
  link.textContent = user.name;
  link.addEventListener("click", async (e) => {
    e.preventDefault();
    if (await window.PrismAuth.requireAuth(link.href)) {
      window.location.href = link.href;
    }
  });

  const github = document.createElement("p");
  github.textContent = user.github_username ? `GitHub: ${user.github_username}` : "GitHub未設定";

  const meta = document.createElement("span");
  meta.textContent = `作品 ${user.public_work_count} / 獲得スター ${user.total_stars}`;

  article.append(link, github, meta);
  searchResults.appendChild(article);
}

// 検索結果の段階読み込み。現在の検索条件（q/tag/type）を保ったまま page だけ増やして追記する。
// 新規検索はヘッダーからの画面遷移＝ページ再読込で page=1 から引き直す（[ADR-029]）。
async function loadSearchPage(targetPage) {
  if (loading) return;
  loading = true;
  if (searchLoadMoreButton) {
    searchLoadMoreButton.disabled = true;
  }

  const requestParams = new URLSearchParams(initialParams);
  requestParams.set("page", String(targetPage));

  try {
    const res = await fetch(`/api/search.php?${requestParams.toString()}`);
    const data = await res.json();
    if (!res.ok) throw new Error(data.error || "検索に失敗しました");
    if (targetPage === 1) {
      searchResults.textContent = "";
    }
    if (!data.results.length) {
      if (targetPage === 1) {
        showSearchMessage("結果がありません。");
      }
      if (searchLoadMoreButton) searchLoadMoreButton.hidden = true;
      return;
    }
    data.results.forEach(data.type === "users" ? renderUser : renderWork);
    page = targetPage;
    if (searchLoadMoreButton) {
      searchLoadMoreButton.hidden = data.type !== "works" || !data.hasMore;
    }
  } catch (err) {
    if (targetPage === 1) {
      showSearchMessage(err.message, "profile-error");
    } else {
      alert(err.message);
    }
  } finally {
    loading = false;
    if (searchLoadMoreButton) {
      searchLoadMoreButton.disabled = false;
    }
  }
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
  showSearchMessage("検索中...");
  await loadSearchPage(1);
}

runSearch();

if (searchLoadMoreButton) {
  searchLoadMoreButton.addEventListener("click", () => loadSearchPage(page + 1));
}
