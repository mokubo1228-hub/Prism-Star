const template = document.getElementById("gallery-item-template");
const gallery = document.getElementById("gallery");

if (template && gallery) {
  galleryData.forEach(item => {
    const clone = template.content.cloneNode(true);
    
    const link = clone.querySelector("a");
    link.href = `gallery-detail.html?id=${item.id}`;
    
    const img = clone.querySelector("img");
    img.src = item.src;
    img.alt = item.title;
    
    const figcaption = clone.querySelector("figcaption");
    figcaption.textContent = item.title;
    
    gallery.appendChild(clone);
  });

}
  
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

    const newId = galleryData.length ? galleryData[galleryData.length - 1].id + 1 : 1;

    const newItem = { id: newId, src, title, desc };

    galleryData.push(newItem);

    // DOMにも即反映
    const template = document.getElementById("gallery-item-template");
    const gallery = document.getElementById("gallery");
    const clone = template.content.cloneNode(true);

    const link = clone.querySelector("a");
    link.href = `gallery-detail.html?id=${newItem.id}`;

    const img = clone.querySelector("img");
    img.src = newItem.src;
    img.alt = newItem.title;

    const caption = clone.querySelector("figcaption");
    caption.textContent = newItem.title;

    gallery.appendChild(clone);

    postModal.style.display = "none";
    postForm.reset();
  });
}