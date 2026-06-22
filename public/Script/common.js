const navItems = [
  { href: 'gallery-list.php', label: 'Gallery' },
  { href: 'form.php', label: 'Contact' },
  { href: 'policy.php', label: 'Privacy Policy' }
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

  // ヘッダーの認証アクションは常時見える位置で状態に応じて出し分ける
  const guestActions = document.querySelector('.auth-guest-actions');
  const logoutButton = document.querySelector('.auth-action-logout');

  if (guestActions) guestActions.hidden = loggedIn;
  if (logoutButton) {
    logoutButton.hidden = !loggedIn;
    logoutButton.removeEventListener('click', handleLogout);
    logoutButton.addEventListener('click', handleLogout);
  }
}

function handleLogout(e) {
  e.preventDefault();
  fetch('/api/auth.php?action=logout', { method: 'POST' })
    .finally(() => { window.location.href = 'gallery-list.php'; });
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
