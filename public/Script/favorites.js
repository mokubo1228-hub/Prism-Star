const favoritesGrid = document.getElementById("favoritesGrid");
const favoriteTemplate = document.getElementById("favorite-item-template");

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

function showFavoritesMessage(message, className = "profile-empty") {
  favoritesGrid.textContent = "";
  const item = document.createElement("p");
  item.className = className;
  item.textContent = message;
  favoritesGrid.appendChild(item);
}

function renderFavorite(item) {
  const clone = favoriteTemplate.content.cloneNode(true);

  const link = clone.querySelector(".work-link");
  link.href = `gallery-detail.php?id=${item.id}`;

  const img = clone.querySelector("img");
  img.src = item.src;
  img.alt = item.title;

  clone.querySelector("figcaption").textContent = item.title;

  const authorLink = clone.querySelector(".author-link");
  authorLink.href = `profile.php?id=${item.user_id}`;
  authorLink.textContent = `by ${item.author || "Unknown"}`;

  const tagList = clone.querySelector(".tag-list");
  tagList.textContent = (item.tags || []).map(tag => `#${tag}`).join(" ");
  if (item.source === "github") {
    const badge = document.createElement("span");
    badge.className = "source-badge";
    badge.textContent = "GitHub";
    tagList.prepend(badge, " ");
  }

  const starButton = clone.querySelector(".star-button");
  updateStarButton(starButton, item.star_count || 0, Boolean(item.starred));
  starButton.addEventListener("click", () => toggleStar(item, starButton));

  favoritesGrid.appendChild(clone);
}

async function loadFavorites() {
  showFavoritesMessage("読み込み中...");
  try {
    const res = await fetch("/api/gallery.php?starred=1");
    const data = await res.json();
    if (!res.ok) throw new Error(data.error || "お気に入りを読み込めませんでした");
    favoritesGrid.textContent = "";
    if (!data.length) {
      showFavoritesMessage("まだお気に入りがありません。");
      return;
    }
    data.forEach(renderFavorite);
  } catch (err) {
    showFavoritesMessage(err.message, "profile-error");
  }
}

async function initFavorites() {
  await window.PrismAuth.ready;
  if (!await window.PrismAuth.requireAuth(window.location.href)) {
    showFavoritesMessage("ログインするとお気に入りを表示できます。");
    return;
  }
  await loadFavorites();
}

if (favoritesGrid && favoriteTemplate) {
  initFavorites();
}
