// URLパラメータからID取得
const params = new URLSearchParams(window.location.search);
const id = Number(params.get("id"));

// データの中から一致する作品を探す
const work = galleryData.find(item => item.id === id);

// 表示領域を取得
const container = document.getElementById("work-detail");

// DOMを組み立てて差し込む
if (work) {
  // タイトル
  const h1 = document.createElement("h1");
  h1.className = "gallery-title";
  h1.textContent = work.title;

  // 画像とキャプション
  const figure = document.createElement("figure");
  figure.className = "detail-img";

  const img = document.createElement("img");
  img.src = work.src;
  img.alt = work.title;

  const figcaption = document.createElement("figcaption");
  const h2 = document.createElement("h2");
  h2.textContent = work.title;
  figcaption.appendChild(h2);

  figure.append(img, figcaption);

  // 説明文
  const section = document.createElement("section");
  const p = document.createElement("p");
  p.className = "detail-txt";
  p.textContent = work.desc;
  section.appendChild(p);

  // 戻るボタン
  const backDiv = document.createElement("div");
  backDiv.className = "back-menu-btn";

  const backA = document.createElement("a");
  backA.href = "gallery-list.html";
  backA.className = "back-menu";
  backA.textContent = "戻る";

  backDiv.appendChild(backA);

  // まとめて追加
  container.append(h1, figure, section, backDiv);
} else {
  // IDが存在しない場合の処理
  container.innerHTML = `<p>指定された作品が見つかりません。</p>
  <div class="back-menu-btn"><a class="back-menu" href="gallery-list.html">ギャラリーへ戻る</a></div>`;
}

console.log("詳細ページ生成完了！");
