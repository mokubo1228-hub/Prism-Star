  const gallery = document.getElementById("gallery");

  // galleryData
  galleryData.forEach(galleryItem => {
    const div = document.createElement("div");
  
    const a = document.createElement("a");
    a.href = `gallery-detail.html?id=${galleryItem.id}`;

    const figure = document.createElement("figure");
    figure.classList.add("gallery-item");
  
    const img = document.createElement("img");
    img.src = galleryItem.src;
    img.alt = galleryItem.title;
  
    const caption = document.createElement("figcaption");
    caption.textContent = galleryItem.title;
  
    figure.appendChild(img);
    figure.appendChild(caption);
  
    a.appendChild(figure);
  
    div.appendChild(a);
  
    gallery.appendChild(div);
  });
  
  console.log("ギャラリー生成完了！");
  