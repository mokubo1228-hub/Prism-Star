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

document.addEventListener('DOMContentLoaded', () => {
  renderNavigation();

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
