const editParams = new URLSearchParams(window.location.search);
const editId = editParams.get("id");
const workEditTitle = document.getElementById("workEditTitle");
const workEditForm = document.getElementById("workEditForm");
const workTitle = document.getElementById("workTitle");
const workDesc = document.getElementById("workDesc");
const workImage = document.getElementById("workImage");
const workImageUrl = document.getElementById("workImageUrl");
const workTags = document.getElementById("workTags");
const workVisibility = document.getElementById("workVisibility");
const workEditError = document.getElementById("workEditError");
const deleteWorkButton = document.getElementById("deleteWorkButton");
const workSourceNote = document.getElementById("workSourceNote");
const workImageLabel = document.querySelector("label[for=\"workImage\"]");
const workImageUrlLabel = document.querySelector("label[for=\"workImageUrl\"]");
let loadedWork = null;

function showWorkError(message) {
  workEditError.textContent = message;
  workEditError.hidden = false;
}

function applySourceMode(work) {
  const isGithubWork = work.source === "github";
  workTitle.readOnly = isGithubWork;
  workDesc.readOnly = isGithubWork;
  workImage.hidden = isGithubWork;
  workImageUrl.hidden = isGithubWork;
  if (workImageLabel) workImageLabel.hidden = isGithubWork;
  if (workImageUrlLabel) workImageUrlLabel.hidden = isGithubWork;
  workSourceNote.hidden = !isGithubWork;
  workSourceNote.textContent = "";

  if (!isGithubWork) return;

  workSourceNote.append("GitHub から取り込んだ作品です。タイトル・説明・画像は取り込み時に更新されます。ここでは公開設定とタグを編集できます。");
  if (work.source_url) {
    const sourceLink = document.createElement("a");
    sourceLink.href = work.source_url;
    sourceLink.target = "_blank";
    sourceLink.rel = "noopener noreferrer";
    sourceLink.textContent = "リポジトリを見る";
    workSourceNote.append(" ", sourceLink);
  }
}

async function loadEditWork() {
  if (!editId) return;
  workEditTitle.textContent = "作品編集";
  deleteWorkButton.hidden = false;

  const res = await fetch(`/api/gallery.php?id=${encodeURIComponent(editId)}`);
  const work = await res.json();
  if (!res.ok || !work.is_owner) {
    showWorkError(work.error || "編集できる作品が見つかりません");
    workEditForm.querySelectorAll("input, textarea, select, button").forEach(el => { el.disabled = true; });
    return;
  }

  loadedWork = work;
  workTitle.value = work.title;
  workDesc.value = work.desc || "";
  workImageUrl.value = work.src.startsWith("http://") || work.src.startsWith("https://") ? work.src : "";
  workTags.value = (work.tags || []).join(", ");
  workVisibility.value = work.visibility;
  applySourceMode(work);
}

workEditForm.addEventListener("submit", async (e) => {
  e.preventDefault();
  workEditError.hidden = true;

  const form = new FormData();
  form.append("visibility", workVisibility.value);
  form.append("tags", workTags.value.trim());
  if (!loadedWork || loadedWork.source !== "github") {
    form.append("title", workTitle.value.trim());
    form.append("desc", workDesc.value.trim());
    if (workImage.files[0]) {
      form.append("image", workImage.files[0]);
    }
    if (workImageUrl.value.trim()) {
      form.append("image_url", workImageUrl.value.trim());
    }
  }

  const url = editId ? `/api/gallery.php?id=${encodeURIComponent(editId)}&_method=PATCH` : "/api/gallery.php";
  try {
    const res = await fetch(url, { method: "POST", body: form });
    const data = await res.json();
    if (!res.ok) throw new Error(data.error || "保存できませんでした");
    window.location.href = `gallery-detail.php?id=${data.id}`;
  } catch (err) {
    showWorkError(err.message);
  }
});

deleteWorkButton.addEventListener("click", async () => {
  if (!loadedWork || !confirm(`「${loadedWork.title}」を削除しますか？`)) return;
  const res = await fetch(`/api/gallery.php?id=${loadedWork.id}`, { method: "DELETE" });
  const data = await res.json();
  if (!res.ok) {
    showWorkError(data.error || "削除できませんでした");
    return;
  }
  window.location.href = "mypage.php";
});

async function initWorkEdit() {
  await window.PrismAuth.ready;
  if (!await window.PrismAuth.requireAuth(window.location.href)) {
    showWorkError("ログインすると作品を作成・編集できます。");
    return;
  }
  await loadEditWork();
}

initWorkEdit();
