// トップ（おすすめ）の描画。/api/gallery.php の { newest, popular } を「新着」「人気」の2レーンに
// 並べるランキング表示（順位 #1〜）。未ログインでも一覧は見えるが（teaser）、作品/作者リンクは
// requireAuth でログインを促す（gated-link）＝[ADR-029]。
const template = document.getElementById("gallery-item-template");
const newestLane = document.getElementById("newestLane");
const popularLane = document.getElementById("popularLane");

function updateStarButton(button, starCount, starred) {
  button.querySelector(".star-count").textContent = starCount;
  button.classList.toggle("is-starred", starred);
  button.setAttribute("aria-pressed", String(starred));
  button.dataset.starred = String(starred);
}

async function toggleStar(item, button) {
  if (!await window.PrismAuth.requireAuth(window.location.href)) return;

  const starred = button.dataset.starred === "true";
  const method = starred ? "DELETE" : "POST";

  try {
    const res = await fetch(`/api/stars.php?gallery_id=${encodeURIComponent(item.id)}`, { method });
    const data = await res.json();
    if (!res.ok) throw new Error(data.error || "スターを更新できませんでした");
    item.star_count = data.star_count;
    item.starred = data.starred;
    updateStarButton(button, data.star_count, data.starred);
  } catch (err) {
    alert(err.message);
  }
}

function showLaneMessage(lane, message, className = "profile-empty") {
  lane.textContent = "";
  const item = document.createElement("p");
  item.className = className;
  item.textContent = message;
  lane.appendChild(item);
}

function renderItem(item, target, rank) {
  const clone = template.content.cloneNode(true);

  const badge = clone.querySelector(".rank-badge");
  badge.textContent = `#${rank}`;

  const link = clone.querySelector(".work-link");
  link.href = `gallery-detail.php?id=${item.id}`;
  link.addEventListener("click", async (e) => {
    e.preventDefault();
    if (await window.PrismAuth.requireAuth(link.href)) {
      window.location.href = link.href;
    }
  });

  const img = clone.querySelector("img");
  img.src = item.src;
  img.alt = item.title;

  clone.querySelector("figcaption").textContent = item.title;

  const authorLink = clone.querySelector(".author-link");
  authorLink.href = `profile.php?id=${item.user_id}`;
  authorLink.textContent = `by ${item.author || "Unknown"}`;
  authorLink.addEventListener("click", async (e) => {
    e.preventDefault();
    if (await window.PrismAuth.requireAuth(authorLink.href)) {
      window.location.href = authorLink.href;
    }
  });

  const tagList = clone.querySelector(".tag-list");
  renderTagLinks(tagList, item.tags || []);
  if (item.source === "github") {
    const badge = document.createElement("span");
    badge.className = "source-badge";
    badge.textContent = "GitHub";
    tagList.prepend(badge, " ");
  }

  const starButton = clone.querySelector(".star-button");
  updateStarButton(starButton, item.star_count || 0, Boolean(item.starred));
  starButton.addEventListener("click", () => toggleStar(item, starButton));

  target.appendChild(clone);
}

function renderLane(lane, items) {
  lane.textContent = "";
  if (!items.length) {
    showLaneMessage(lane, "作品がありません。");
    return;
  }
  items.forEach((item, index) => renderItem(item, lane, index + 1));
}

async function loadRankings() {
  try {
    const res = await fetch("/api/gallery.php");
    const data = await res.json();
    if (!res.ok) throw new Error(data.error || "作品の読み込みに失敗しました。");
    renderLane(newestLane, Array.isArray(data.newest) ? data.newest : []);
    renderLane(popularLane, Array.isArray(data.popular) ? data.popular : []);
  } catch (err) {
    showLaneMessage(newestLane, err.message, "profile-error");
    showLaneMessage(popularLane, err.message, "profile-error");
  }
}

if (template && newestLane && popularLane) {
  loadRankings();
}
