const params = new URLSearchParams(window.location.search);
const id = params.get("id");
const container = document.getElementById("work-detail");
const template = document.getElementById("detail-template");

function showError(message = "指定された作品が見つかりません。") {
  container.innerHTML = `
    <p>${message}</p>
    <div class="detail-actions"><a class="back-menu" href="gallery-list.php">ギャラリーへ戻る</a></div>
  `;
}

document.addEventListener("click", (e) => {
  const back = e.target.closest("a.back-menu");
  if (!back) return;

  let fromApp = false;
  try {
    fromApp = Boolean(document.referrer) &&
      new URL(document.referrer).origin === window.location.origin;
  } catch {
    fromApp = false;
  }

  // 詳細は検索・プロフィール等からも開くため、同一アプリ内なら履歴で直前画面の状態ごと戻す。
  // 直接アクセスや外部流入では固定 href（おすすめ）をフォールバックとして使い、referrer 自体へは遷移しない。
  if (fromApp && window.history.length > 1) {
    e.preventDefault();
    window.history.back();
  }
});

function updateStarButton(button, starCount, starred) {
  button.querySelector(".star-count").textContent = starCount;
  button.classList.toggle("is-starred", starred);
  button.setAttribute("aria-pressed", String(starred));
  button.dataset.starred = String(starred);
}

async function toggleStar(work, button) {
  if (!await window.PrismAuth.requireAuth(window.location.href)) return;

  const starred = button.dataset.starred === "true";
  const method = starred ? "DELETE" : "POST";

  try {
    const res = await fetch(`/api/stars.php?gallery_id=${encodeURIComponent(work.id)}`, { method });
    const data = await res.json();
    if (!res.ok) throw new Error(data.error || "スターを更新できませんでした");
    work.star_count = data.star_count;
    work.starred = data.starred;
    updateStarButton(button, data.star_count, data.starred);
  } catch (err) {
    alert(err.message);
  }
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

    clone.querySelector("figcaption h2").textContent = work.title;
    clone.querySelector(".detail-txt").textContent = work.desc || "";

    const authorLink = clone.querySelector(".detail-author a");
    authorLink.href = `profile.php?id=${work.user_id}`;
    authorLink.textContent = work.author || "Unknown";

    const meta = clone.querySelector(".detail-meta");
    const tags = (work.tags || []).map(tag => `#${tag}`).join(" ");
    meta.textContent = `${work.visibility === "private" ? "非公開" : "公開"} ${tags}`;
    if (work.source === "github") {
      const badge = document.createElement("span");
      badge.className = "source-badge";
      badge.textContent = "GitHub";
      meta.append(" ", badge);
    }

    const starButton = clone.querySelector(".star-button");
    updateStarButton(starButton, work.star_count || 0, Boolean(work.starred));
    starButton.addEventListener("click", () => toggleStar(work, starButton));

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
