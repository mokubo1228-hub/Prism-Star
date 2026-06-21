const params = new URLSearchParams(window.location.search);
const id = params.get("id");
const container = document.getElementById("work-detail");
const template = document.getElementById("detail-template");

function showError() {
  container.innerHTML = `
    <p>指定された作品が見つかりません。</p>
    <div class="detail-actions"><a class="back-menu" href="gallery-list.html">ギャラリーへ戻る</a></div>
  `;
}

async function fetchStatus() {
  try {
    const res = await fetch("/api/auth.php?action=status");
    if (!res.ok) return { loggedIn: false };
    return await res.json();
  } catch {
    return { loggedIn: false };
  }
}

async function deleteWork(errorEl) {
  errorEl.hidden = true;
  errorEl.textContent = "";

  try {
    const res = await fetch(`/api/gallery.php?id=${encodeURIComponent(id)}`, {
      method: "DELETE",
    });

    if (!res.ok) {
      const data = await res.json().catch(() => ({}));
      throw new Error(data.error || "削除に失敗しました。");
    }

    window.location.href = "gallery-list.html";
  } catch (err) {
    errorEl.textContent = err.message;
    errorEl.hidden = false;
  }
}

function updateStarButton(button, starCount, starred) {
  button.querySelector(".star-count").textContent = starCount;
  button.classList.toggle("is-starred", starred);
  button.setAttribute("aria-pressed", String(starred));
  button.dataset.starred = String(starred);
}

function toggleStar(work, button) {
  const starred = button.dataset.starred === "true";
  const method = starred ? "DELETE" : "POST";

  fetch(`/api/stars.php?gallery_id=${encodeURIComponent(work.id)}`, { method })
    .then(res => {
      if (res.status === 401) throw new Error("スターを付けるにはログインが必要です。");
      if (!res.ok) return res.json().then(d => { throw new Error(d.error); });
      return res.json();
    })
    .then(data => {
      work.star_count = data.star_count;
      work.starred = data.starred;
      updateStarButton(button, data.star_count, data.starred);
    })
    .catch(err => alert(err.message));
}

if (!id || !template) {
  showError();
} else {
  Promise.all([
    fetch(`/api/gallery.php?id=${encodeURIComponent(id)}`).then(res => {
      if (!res.ok) throw new Error();
      return res.json();
    }),
    fetchStatus(),
  ])
    .then(([work, status]) => {
      const clone = template.content.cloneNode(true);

      clone.querySelector("h1").textContent = work.title;

      const img = clone.querySelector("img");
      img.src = work.src;
      img.alt = work.title;

      clone.querySelector("figcaption h2").textContent = work.title;
      clone.querySelector(".detail-txt").textContent = work.desc;

      const authorLink = clone.querySelector(".detail-author a");
      if (authorLink) {
        authorLink.href = `profile.html?id=${work.user_id}`;
        authorLink.textContent = work.author || "Unknown";
      }

      const starButton = clone.querySelector(".star-button");
      if (starButton) {
        updateStarButton(starButton, work.star_count || 0, Boolean(work.starred));
        starButton.addEventListener("click", () => toggleStar(work, starButton));
      }

      const deleteBtn = clone.querySelector(".delete-work-btn");
      const deleteError = clone.querySelector(".delete-error");
      const userId = Number(status.user?.id);
      const ownerId = Number(work.user_id);

      if (status.loggedIn && userId === ownerId) {
        deleteBtn.hidden = false;
        deleteBtn.addEventListener("click", () => deleteWork(deleteError));
      }

      container.appendChild(clone);
    })
    .catch(showError);
}
