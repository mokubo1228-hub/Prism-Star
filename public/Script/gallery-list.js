const template = document.getElementById("gallery-item-template");
const gallery = document.getElementById("gallery");

function renderItem(item) {
  const clone = template.content.cloneNode(true);

  const link = clone.querySelector("a");
  link.href = `gallery-detail.html?id=${item.id}`;

  const img = clone.querySelector("img");
  img.src = item.src;
  img.alt = item.title;

  const figcaption = clone.querySelector("figcaption");
  figcaption.textContent = item.title;

  gallery.appendChild(clone);
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
        renderItem(newItem);
        postModal.style.display = "none";
        postForm.reset();
      })
      .catch(err => alert(err.message));
  });
}
