const navItems = [
  { href: 'login.html', label: 'Login' },
  { href: 'gallery-list.html', label: 'Gallery' },
  { href: 'form.html', label: 'Contact' },
  { href: 'policy.html', label: 'Privacy Policy' }
];

function renderNavigation() {
  const templates = document.querySelectorAll('#nav-item-template');

  templates.forEach(template => {
    const container = template.parentElement;

    navItems.forEach(item => {
      const clone = template.content.cloneNode(true);
      const link = clone.querySelector('a');

      link.href = item.href;
      link.textContent = item.label;

      container.appendChild(clone);
    });
  });
}

// ログイン状態を取得して UI を出し分ける
async function applyAuthState() {
  let loggedIn = false;
  try {
    const res = await fetch('/api/auth.php?action=status');
    const data = await res.json();
    loggedIn = Boolean(data.loggedIn);
  } catch {
    loggedIn = false;
  }

  document.body.classList.toggle('is-authed', loggedIn);

  // 「＋投稿」ボタンはログイン中のみ表示
  const addBtn = document.querySelector('.add-post-btn');
  if (addBtn) addBtn.style.display = loggedIn ? 'flex' : 'none';

  // ナビの「Login」リンクを、ログイン中は「Logout」に切り替える
  if (loggedIn) {
    document.querySelectorAll('.header-nav a, .footer-nav a').forEach(link => {
      if (link.getAttribute('href') === 'login.html') {
        link.textContent = 'Logout';
        link.href = '#';
        link.addEventListener('click', handleLogout);
      }
    });
  }
}

function handleLogout(e) {
  e.preventDefault();
  fetch('/api/auth.php?action=logout', { method: 'POST' })
    .finally(() => { window.location.href = 'gallery-list.html'; });
}

document.addEventListener('DOMContentLoaded', () => {
  renderNavigation();
  applyAuthState();

  const hamburger = document.getElementById('hamburger');
  const nav = document.querySelector('.header-nav');

  const overlay = document.createElement('div');
  overlay.classList.add('nav-overlay');
  document.body.appendChild(overlay);

  const toggleMenu = () => {
    hamburger.classList.toggle('active');
    nav.classList.toggle('active');
    overlay.classList.toggle('active');
  };

  hamburger.addEventListener('click', toggleMenu);
  overlay.addEventListener('click', toggleMenu);
});
