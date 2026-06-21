const profileParams = new URLSearchParams(window.location.search);
const profileId = profileParams.get("id");
const profileName = document.getElementById("profileName");
const profileWorks = document.getElementById("profileWorks");
const profileTemplate = document.getElementById("profile-item-template");

function showProfileMessage(message, className = "profile-error") {
  profileWorks.innerHTML = `<p class="${className}">${message}</p>`;
}

function renderProfileWork(work) {
  const clone = profileTemplate.content.cloneNode(true);
  const link = clone.querySelector(".work-link");
  const img = clone.querySelector("img");
  const figcaption = clone.querySelector("figcaption");

  link.href = `gallery-detail.html?id=${work.id}`;
  img.src = work.src;
  img.alt = work.title;
  figcaption.textContent = work.title;

  profileWorks.appendChild(clone);
}

if (!profileId || !profileTemplate || !profileWorks) {
  if (profileName) profileName.textContent = "ユーザーが見つかりません";
  showProfileMessage("ユーザーが見つかりません。");
} else {
  fetch(`/api/users.php?id=${encodeURIComponent(profileId)}`)
    .then(res => {
      if (!res.ok) throw new Error();
      return res.json();
    })
    .then(user => {
      profileName.textContent = `${user.name} の作品`;

      if (user.works.length === 0) {
        showProfileMessage("まだ作品がありません。", "profile-empty");
        return;
      }

      user.works.forEach(renderProfileWork);
    })
    .catch(() => {
      if (profileName) profileName.textContent = "ユーザーが見つかりません";
      showProfileMessage("ユーザーが見つかりません。");
    });
}
