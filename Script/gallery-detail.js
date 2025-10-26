// URLパラメータからID取得
const params = new URLSearchParams(window.location.search);
const id = Number(params.get("id"));

// データの中から一致する作品を探す
const work = galleryData.find(item => item.id === id);

// 表示領域を取得
const container = document.getElementById("work-detail");
const template = document.getElementById("detail-template");

if (work && template) {
  const clone = template.content.cloneNode(true);
  
  // プレースホルダーを置換
  const h1 = clone.querySelector("h1");
  h1.textContent = work.title;
  
  const img = clone.querySelector("img");
  img.src = work.src;
  img.alt = work.title;
  
  const figcaptionH2 = clone.querySelector("figcaption h2");
  figcaptionH2.textContent = work.title;
  
  const p = clone.querySelector("p");
  p.textContent = work.desc;
  
  container.appendChild(clone);
} else {
  // IDが存在しない場合の処理
  container.innerHTML = `
    <p>指定された作品が見つかりません。</p>
    <div class="back-menu-btn"><a class="back-menu" href="gallery-list.html">ギャラリーへ戻る</a></div>
  `;
}

console.log("詳細ページ生成完了！");
