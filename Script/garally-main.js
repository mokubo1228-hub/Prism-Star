  const gallery = document.getElementById("garally");

  // garally_Items ->  garallyData
  garallyData.forEach(garally_Item => {
    const div = document.createElement("div");
  
    const a = document.createElement("a");
    // a.href = garally_Item.link;
    a.href = `garally-detail.html?id=${garally_Item.id}`;

    const figure = document.createElement("figure");
    figure.classList.add("garally-item");
  
    const img = document.createElement("img");
    img.src = garally_Item.src;
    img.alt = garally_Item.title;
  
    const caption = document.createElement("figcaption");
    caption.textContent = garally_Item.title;
  
    figure.appendChild(img);
    figure.appendChild(caption);
  
    a.appendChild(figure);
  
    div.appendChild(a);
  
    gallery.appendChild(div);
  });
  
  console.log("ギャラリー生成完了！");
  