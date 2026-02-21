const params = new URLSearchParams(window.location.search);
const id = params.get("id");
const container = document.getElementById("work-detail");
const template = document.getElementById("detail-template");

function showError() {
  container.innerHTML = `
    <p>指定された作品が見つかりません。</p>
    <div class="back-menu-btn"><a class="back-menu" href="gallery-list.html">ギャラリーへ戻る</a></div>
  `;
}

if (!id || !template) {
  showError();
} else {
  fetch(`/api/gallery.php?id=${encodeURIComponent(id)}`)
    .then(res => {
      if (!res.ok) throw new Error();
      return res.json();
    })
    .then(work => {
      const clone = template.content.cloneNode(true);

      clone.querySelector("h1").textContent = work.title;

      const img = clone.querySelector("img");
      img.src = work.src;
      img.alt = work.title;

      clone.querySelector("figcaption h2").textContent = work.title;
      clone.querySelector("p").textContent = work.desc;

      container.appendChild(clone);
    })
    .catch(showError);
}
