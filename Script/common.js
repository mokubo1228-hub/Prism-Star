// ナビゲーション設定（一箇所で管理）
const navItems = [
  { href: 'login.html', label: 'Login' },
  { href: 'gallery-list.html', label: 'Gallery' },
  { href: 'form.html', label: 'Contact' },
  { href: 'policy.html', label: 'Privacy Policy' }
];

// ナビゲーションを生成する関数
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

// ページロード時に実行
document.addEventListener('DOMContentLoaded', renderNavigation);