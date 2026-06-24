const template = document.getElementById("gallery-item-template");
const gallery = document.getElementById("gallery");

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

function renderItem(item) {
  const clone = template.content.cloneNode(true);

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

  gallery.appendChild(clone);
}

if (template && gallery) {
  fetch("/api/gallery.php")
    .then(res => res.json())
    .then(data => {
      gallery.innerHTML = "";
      if (!Array.isArray(data) || data.length === 0) {
        gallery.innerHTML = "<p class=\"profile-empty\">公開作品がありません。</p>";
        return;
      }
      data.forEach(renderItem);
    })
    .catch(() => {
      gallery.innerHTML = "<p class=\"profile-error\">作品の読み込みに失敗しました。</p>";
    });
}
