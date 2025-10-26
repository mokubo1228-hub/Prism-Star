const template = document.getElementById("gallery-item-template");
const gallery = document.getElementById("gallery");

if (template && gallery) {
  galleryData.forEach(item => {
    const clone = template.content.cloneNode(true);
    
    const link = clone.querySelector("a");
    link.href = `gallery-detail.html?id=${item.id}`;
    
    const img = clone.querySelector("img");
    img.src = item.src;
    img.alt = item.title;
    
    const figcaption = clone.querySelector("figcaption");
    figcaption.textContent = item.title;
    
    gallery.appendChild(clone);
  });
  
  console.log("ギャラリー生成完了！");
}
  