const template = document.getElementById("gallery-item-template");
const gallery = document.getElementById("gallery");

function updateStarButton(button, starCount, starred) {
  button.querySelector(".star-count").textContent = starCount;
  button.classList.toggle("is-starred", starred);
  button.setAttribute("aria-pressed", String(starred));
  button.dataset.starred = String(starred);
}

function toggleStar(item, button) {
  const starred = button.dataset.starred === "true";
  const method = starred ? "DELETE" : "POST";

  fetch(`/api/stars.php?gallery_id=${encodeURIComponent(item.id)}`, { method })
    .then(res => {
      if (res.status === 401) throw new Error("スターを付けるにはログインが必要です。");
      if (!res.ok) return res.json().then(d => { throw new Error(d.error); });
      return res.json();
    })
    .then(data => {
      item.star_count = data.star_count;
      item.starred = data.starred;
      updateStarButton(button, data.star_count, data.starred);
    })
    .catch(err => alert(err.message));
}

function renderItem(item, prepend = false) {
  const clone = template.content.cloneNode(true);

  const link = clone.querySelector(".work-link");
  link.href = `gallery-detail.html?id=${item.id}`;

  const img = clone.querySelector("img");
  img.src = item.src;
  img.alt = item.title;

  const figcaption = clone.querySelector("figcaption");
  figcaption.textContent = item.title;

  const authorLink = clone.querySelector(".author-link");
  if (authorLink) {
    authorLink.href = `profile.html?id=${item.user_id}`;
    authorLink.textContent = `by ${item.author || "Unknown"}`;
  }

  const starButton = clone.querySelector(".star-button");
  if (starButton) {
    starButton.dataset.galleryId = item.id;
    updateStarButton(starButton, item.star_count || 0, Boolean(item.starred));
    starButton.addEventListener("click", () => toggleStar(item, starButton));
  }

  if (prepend) {
    gallery.prepend(clone);
  } else {
    gallery.appendChild(clone);
  }
}

// APIから一覧を取得して描画
if (template && gallery) {
  fetch("/api/gallery.php")
    .then(res => res.json())
    .then(data => data.forEach(renderItem))
    .catch(() => {
      gallery.innerHTML = "<p>作品の読み込みに失敗しました。</p>";
    });
}

// モーダル開閉
const addBtn = document.querySelector(".add-post-btn");
const postModal = document.getElementById("postModal");
const closeBtn = document.querySelector(".modal-close");

if (addBtn && postModal && closeBtn) {
  addBtn.addEventListener("click", (e) => {
    e.preventDefault();
    postModal.style.display = "flex";
  });

  closeBtn.addEventListener("click", () => {
    postModal.style.display = "none";
  });

  postModal.addEventListener("click", (e) => {
    if (e.target === postModal) postModal.style.display = "none";
  });
}

// 投稿フォーム
const postForm = document.getElementById("postForm");

function isSafeUrl(url) {
  try {
    const parsed = new URL(url);
    return ["http:", "https:"].includes(parsed.protocol);
  } catch {
    return false;
  }
}

if (postForm) {
  postForm.addEventListener("submit", (e) => {
    e.preventDefault();

    const title = document.getElementById("postTitle").value.trim();
    const src = document.getElementById("postImage").value.trim();
    const desc = document.getElementById("postDesc").value.trim();

    if (!title || !src || !desc) return;

    if (!isSafeUrl(src)) {
      alert("画像URLはhttp://またはhttps://で始まるURLを入力してください。");
      return;
    }

    fetch("/api/gallery.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ title, src, desc }),
    })
      .then(res => {
        if (!res.ok) return res.json().then(d => { throw new Error(d.error); });
        return res.json();
      })
      .then(newItem => {
        renderItem(newItem, true);
        postModal.style.display = "none";
        postForm.reset();
      })
      .catch(err => alert(err.message));
  });
}
