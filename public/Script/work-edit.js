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
let loadedWork = null;

function showWorkError(message) {
  workEditError.textContent = message;
  workEditError.hidden = false;
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
}

workEditForm.addEventListener("submit", async (e) => {
  e.preventDefault();
  workEditError.hidden = true;

  const form = new FormData();
  form.append("title", workTitle.value.trim());
  form.append("desc", workDesc.value.trim());
  form.append("visibility", workVisibility.value);
  form.append("tags", workTags.value.trim());
  if (workImage.files[0]) {
    form.append("image", workImage.files[0]);
  }
  if (workImageUrl.value.trim()) {
    form.append("image_url", workImageUrl.value.trim());
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
