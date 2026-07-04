const params = new URLSearchParams(window.location.search);
const id = params.get("id");
const container = document.getElementById("work-detail");
const template = document.getElementById("detail-template");

function showError(message = "指定された作品が見つかりません。") {
  container.innerHTML = `
    <a class="back-menu" href="gallery-list.php">← 戻る</a>
    <p>${message}</p>
  `;
}

function formatCreatedDate(value) {
  const match = /^(\d{4})-(\d{2})-(\d{2})/.exec(value || "");
  return match ? `${match[1]}/${match[2]}/${match[3]}` : "";
}

async function loadDetail() {
  await window.PrismAuth.ready;
  if (!await window.PrismAuth.requireAuth(window.location.href)) {
    showError("ログインすると作品詳細を表示できます。");
    return;
  }

  if (!id || !template) {
    showError();
    return;
  }

  try {
    const res = await fetch(`/api/gallery.php?id=${encodeURIComponent(id)}`);
    const work = await res.json();
    if (!res.ok) throw new Error(work.error || "作品が見つかりません");

    const clone = template.content.cloneNode(true);
    clone.querySelector("h1").textContent = work.title;

    const img = clone.querySelector("img");
    img.src = work.src;
    img.alt = work.title;

    clone.querySelector(".detail-txt").textContent = work.desc || "";

    const authorLink = clone.querySelector(".detail-author a");
    const authorAvatar = clone.querySelector(".author-avatar");
    authorAvatar.src = work.author_avatar || "image/default-avatar.svg";
    authorLink.href = `profile.php?id=${work.user_id}`;
    authorLink.textContent = work.author || "Unknown";

    const meta = clone.querySelector(".detail-meta");
    const createdDate = formatCreatedDate(work.created_at);
    meta.textContent = work.visibility === "private" ? "非公開" : "";
    if (createdDate) {
      if (meta.textContent) meta.append(" / ");
      meta.append(`作成日 ${createdDate}`);
    }
    if ((work.tags || []).length > 0 && meta.textContent) {
      meta.append(" / ");
    }
    renderTagLinks(meta, work.tags || []);
    if (work.source === "github") {
      const badge = document.createElement("span");
      badge.className = "source-badge";
      badge.textContent = "GitHub";
      meta.append(" ", badge);
    }

    const starButton = clone.querySelector(".star-button");
    updateStarButton(starButton, work.star_count || 0, Boolean(work.starred));
    starButton.addEventListener("click", (e) => {
      e.preventDefault();
      toggleStar(work, starButton);
    });

    // 詳細は閲覧専用（[ADR-027]）。所有者でも編集導線は出さない＝編集はマイページから行う。
    if (work.source === "github" && work.source_url) {
      const sourceLink = document.createElement("a");
      sourceLink.className = "source-link";
      sourceLink.href = work.source_url;
      sourceLink.target = "_blank";
      sourceLink.rel = "noopener noreferrer";
      sourceLink.textContent = "リポジトリを見る";
      clone.querySelector(".detail-actions").appendChild(sourceLink);
    }

    container.innerHTML = "";
    container.appendChild(clone);
  } catch (err) {
    showError(err.message);
  }
}

loadDetail();
