const searchHeading = document.getElementById("searchHeading");
const searchCount = document.getElementById("searchCount");
const searchResults = document.getElementById("searchResults");
const searchPager = document.getElementById("searchPager");
const searchPerPageSelect = document.getElementById("searchPerPage");
const searchAllowedPerPages = ["10", "30", "50"];
let searchIsLoading = false;
let searchState = { page: 1, perPage: 30, totalPages: 1 };

function normalizeSearchPage(value) {
  const page = Number.parseInt(value, 10);
  return Number.isFinite(page) && page > 0 ? page : 1;
}

function normalizeSearchPerPage(value) {
  const perPage = String(value || "30");
  return searchAllowedPerPages.includes(perPage) ? Number(perPage) : 30;
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

function readSearchParams() {
  const params = new URLSearchParams(window.location.search);
  return {
    params,
    page: normalizeSearchPage(params.get("page")),
    perPage: normalizeSearchPerPage(params.get("per_page")),
  };
}

function syncSearchUrl(params, data, mode) {
  if (mode === "none") return;
  const nextParams = new URLSearchParams(params);
  nextParams.set("page", String(data.page));
  nextParams.set("per_page", String(data.perPage));
  const nextUrl = `${window.location.pathname}?${nextParams.toString()}`;
  if (mode === "replace") {
    window.history.replaceState(null, "", nextUrl);
  } else {
    window.history.pushState(null, "", nextUrl);
  }
}

function showSearchMessage(message, className = "profile-empty") {
  searchResults.textContent = "";
  const item = document.createElement("p");
  item.className = className;
  item.textContent = message;
  searchResults.appendChild(item);
}

function renderWork(work) {
  const article = document.createElement("article");
  article.className = "gallery-card";
  article.dataset.resultId = String(work.id);

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
  avatar.src = work.author_avatar || "image/default-avatar.svg";
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
  article.dataset.resultId = String(user.id);

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

function updateSearchSummary(params, data) {
  const search = currentSearchLabel(params);
  if (searchHeading) {
    // 空検索は「全件ブラウズ」。サーバは q/tag が空なら全公開作品（自分以外）/全ユーザーを返す。
    searchHeading.textContent = search.term === ""
      ? (search.type === "users" ? "すべてのユーザー" : "すべての作品")
      : `${search.label}「${search.term}」の検索結果`;
  }
  if (searchCount) {
    searchCount.textContent = `該当 ${data.total} 件`;
  }
  if (searchPerPageSelect) {
    searchPerPageSelect.value = String(data.perPage);
  }
}

function createPagerButton(label, targetPage, disabled, isCurrent = false) {
  const button = document.createElement("button");
  button.type = "button";
  button.textContent = label;
  button.disabled = disabled;
  if (isCurrent) {
    button.className = "is-current";
    button.setAttribute("aria-current", "page");
  }
  button.addEventListener("click", () => {
    loadSearchPage(targetPage, { mode: "push" });
  });
  return button;
}

function searchPageNumbers(currentPage, totalPages) {
  if (totalPages <= 7) {
    return Array.from({ length: totalPages }, (_unused, index) => index + 1);
  }

  const pages = [1];
  const start = Math.max(2, currentPage - 1);
  const end = Math.min(totalPages - 1, currentPage + 1);
  if (start > 2) pages.push("...");
  for (let page = start; page <= end; page++) {
    pages.push(page);
  }
  if (end < totalPages - 1) pages.push("...");
  pages.push(totalPages);
  return pages;
}

function renderSearchPager(data) {
  if (!searchPager) return;
  searchPager.textContent = "";
  if (data.total === 0) {
    searchPager.hidden = true;
    return;
  }

  searchPager.hidden = false;
  searchPager.appendChild(createPagerButton("前へ", data.page - 1, !data.hasPrev));
  searchPageNumbers(data.page, data.totalPages).forEach((item) => {
    if (item === "...") {
      const ellipsis = document.createElement("span");
      ellipsis.className = "pager-ellipsis";
      ellipsis.textContent = "...";
      searchPager.appendChild(ellipsis);
      return;
    }
    searchPager.appendChild(createPagerButton(String(item), item, false, item === data.page));
  });
  searchPager.appendChild(createPagerButton("次へ", data.page + 1, !data.hasNext));
}

function findRenderedResult(resultId) {
  if (!resultId) return null;
  return Array.from(searchResults.querySelectorAll("[data-result-id]"))
    .find((item) => item.dataset.resultId === String(resultId)) || null;
}

function scrollToRenderedResult(resultId) {
  const item = findRenderedResult(resultId);
  if (item) {
    item.scrollIntoView({ block: "start" });
  }
}

async function loadSearchPage(targetPage, options = {}) {
  if (searchIsLoading) return;
  searchIsLoading = true;
  if (searchPerPageSelect) {
    searchPerPageSelect.disabled = true;
  }

  const mode = options.mode || "push";
  const { params, perPage } = readSearchParams();
  const requestPerPage = options.perPage || perPage;
  const requestPage = normalizeSearchPage(targetPage || params.get("page"));
  params.set("page", String(requestPage));
  params.set("per_page", String(requestPerPage));

  try {
    showSearchMessage("検索中...");
    const res = await fetch(`/api/search.php?${params.toString()}`);
    const data = await res.json();
    if (!res.ok) throw new Error(data.error || "検索に失敗しました");

    searchState = {
      page: data.page,
      perPage: data.perPage,
      totalPages: data.totalPages,
    };
    syncSearchUrl(params, data, mode);
    updateSearchSummary(params, data);

    searchResults.textContent = "";
    if (!data.results.length) {
      showSearchMessage("結果がありません。");
    } else {
      data.results.forEach(data.type === "users" ? renderUser : renderWork);
    }
    renderSearchPager(data);
    if (options.anchorId && data.page > 1 && data.page === requestPage) {
      scrollToRenderedResult(options.anchorId);
    }
  } catch (err) {
    showSearchMessage(err.message, "profile-error");
    if (searchPager) searchPager.hidden = true;
    if (searchCount) searchCount.textContent = "";
  } finally {
    searchIsLoading = false;
    if (searchPerPageSelect) {
      searchPerPageSelect.disabled = false;
    }
  }
}

async function runSearch() {
  await window.PrismAuth.ready;
  const { page } = readSearchParams();
  await loadSearchPage(page, { mode: "replace" });
}

if (searchPerPageSelect) {
  searchPerPageSelect.addEventListener("change", () => {
    const newPerPage = normalizeSearchPerPage(searchPerPageSelect.value);
    const firstResult = searchResults.querySelector("[data-result-id]");
    const anchorId = firstResult ? firstResult.dataset.resultId : "";
    // 表示件数変更で先頭へ戻ると閲覧位置を失うため、直前の先頭作品を含むページへ移す。
    // その結果として半端な最終ページへ着地することは、検索中の文脈維持を優先する仕様。
    const offset = (searchState.page - 1) * searchState.perPage;
    const nextPage = Math.floor(offset / newPerPage) + 1;
    loadSearchPage(nextPage, { mode: "push", perPage: newPerPage, anchorId });
  });
}

window.addEventListener("popstate", () => {
  const { page } = readSearchParams();
  loadSearchPage(page, { mode: "none" });
});

runSearch();
