const favoritesGrid = document.getElementById("favoritesGrid");
const favoriteTemplate = document.getElementById("favorite-item-template");

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

  clone.querySelector(".card-title").textContent = item.title;

  const authorLink = clone.querySelector(".author-link");
  authorLink.href = `profile.php?id=${item.user_id}`;
  authorLink.textContent = `by ${item.author || "Unknown"}`;

  const tagList = clone.querySelector(".tag-list");
  renderTagLinks(tagList, item.tags || []);
  if (item.source === "github") {
    const badge = document.createElement("span");
    badge.className = "source-badge";
    badge.textContent = "GitHub";
    tagList.prepend(badge, " ");
  }

  const avatar = clone.querySelector(".card-avatar");
  avatar.src = item.author_avatar || "image/default-avatar.svg";

  const starButton = clone.querySelector(".star-button");
  updateStarButton(starButton, item.star_count || 0, Boolean(item.starred));
  starButton.addEventListener("click", (e) => {
    e.preventDefault();
    toggleStar(item, starButton);
  });

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
