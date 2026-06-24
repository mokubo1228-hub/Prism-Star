const navItems = [
  { href: "gallery-list.php", label: "おすすめ" },
  { href: "search.php", label: "検索" },
  { href: "mypage.php", label: "マイページ", auth: true },
  { href: "form.php", label: "Contact" },
  { href: "policy.php", label: "Privacy Policy" }
];

const PrismAuth = {
  status: { loggedIn: false },
  async refresh() {
    try {
      const res = await fetch("/api/auth.php?action=status");
      this.status = await res.json();
    } catch {
      this.status = { loggedIn: false };
    }
    applyAuthState(this.status);
    return this.status;
  },
  async requireAuth(destination = window.location.href) {
    const status = this.status || await this.refresh();
    if (status.loggedIn) return true;
    showAuthGate(destination);
    return false;
  }
};

window.PrismAuth = PrismAuth;

function renderNavigation() {
  const templates = document.querySelectorAll("#nav-item-template");

  templates.forEach(template => {
    const container = template.parentElement;
    navItems.forEach(item => {
      const clone = template.content.cloneNode(true);
      const link = clone.querySelector("a");

      link.href = item.href;
      link.textContent = item.label;
      if (item.auth) {
        link.dataset.requireAuth = "true";
      }
      container.appendChild(clone);
    });
  });
}

function applyAuthState(status) {
  const loggedIn = Boolean(status.loggedIn);
  document.body.classList.toggle("is-authed", loggedIn);

  const guestActions = document.querySelector(".auth-guest-actions");
  const logoutButton = document.querySelector(".auth-action-logout");
  const mypageLink = document.querySelector(".auth-action-mypage");

  if (guestActions) guestActions.hidden = loggedIn;
  if (mypageLink) mypageLink.hidden = !loggedIn;
  if (logoutButton) {
    logoutButton.hidden = !loggedIn;
    logoutButton.removeEventListener("click", handleLogout);
    logoutButton.addEventListener("click", handleLogout);
  }
}

function handleLogout(e) {
  e.preventDefault();
  fetch("/api/auth.php?action=logout", { method: "POST" })
    .finally(() => { window.location.href = "gallery-list.php"; });
}

function showAuthGate(destination) {
  const modal = document.getElementById("authGateModal");
  if (!modal) {
    window.location.href = `login.php?return_to=${encodeURIComponent(destination)}`;
    return;
  }
  modal.dataset.destination = destination;
  modal.hidden = false;
  document.getElementById("authGateEmail")?.focus();
}

function hideAuthGate() {
  const modal = document.getElementById("authGateModal");
  if (modal) modal.hidden = true;
}

function bindAuthGate() {
  const modal = document.getElementById("authGateModal");
  const form = document.getElementById("authGateLoginForm");
  const close = document.querySelector(".auth-gate-close");
  const error = document.getElementById("authGateError");
  if (!modal || !form) return;

  close?.addEventListener("click", hideAuthGate);
  modal.addEventListener("click", (e) => {
    if (e.target === modal) hideAuthGate();
  });

  form.addEventListener("submit", async (e) => {
    e.preventDefault();
    if (error) {
      error.hidden = true;
      error.textContent = "";
    }

    const email = document.getElementById("authGateEmail").value.trim();
    const password = document.getElementById("authGatePassword").value;
    try {
      const res = await fetch("/api/auth.php?action=login", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ email, password })
      });
      const data = await res.json();
      if (!res.ok) throw new Error(data.error || "ログインに失敗しました");
      await PrismAuth.refresh();
      window.location.href = modal.dataset.destination || "gallery-list.php";
    } catch (err) {
      if (error) {
        error.textContent = err.message;
        error.hidden = false;
      }
    }
  });
}

function bindHeaderSearch() {
  const form = document.getElementById("headerSearchForm");
  const type = document.getElementById("headerSearchType");
  const input = document.getElementById("headerSearchInput");
  if (!form || !type || !input) return;

  // search.php 上ではバーに現在の検索を復元する。タグ検索（URL に tag）は対象=作品のまま
  // 入力を "#タグ" の形に戻し、入力の見た目と検索条件を一致させる。
  const currentParams = new URLSearchParams(window.location.search);
  if (window.location.pathname.endsWith("search.php")) {
    if (currentParams.get("type") === "users") {
      type.value = "users";
      input.value = currentParams.get("q") || "";
    } else if (currentParams.get("tag")) {
      type.value = "works";
      input.value = "#" + currentParams.get("tag");
    } else {
      type.value = "works";
      input.value = currentParams.get("q") || "";
    }
  }

  form.addEventListener("submit", async (e) => {
    e.preventDefault();
    const raw = input.value.trim();
    // 対象はプルダウン（作品/ユーザー）。作品では入力が # 始まりならタグ検索、それ以外は
    // キーワード（タイトル/説明）。空でも遷移する＝空検索は全件ブラウズ。
    let destination;
    if (type.value === "users") {
      destination = `search.php?type=users&q=${encodeURIComponent(raw)}`;
    } else if (raw.startsWith("#") || raw.startsWith("＃")) {
      const tag = raw.replace(/^[#＃]+\s*/, "");
      destination = `search.php?tag=${encodeURIComponent(tag)}`;
    } else {
      destination = `search.php?q=${encodeURIComponent(raw)}`;
    }

    if (await PrismAuth.requireAuth(destination)) {
      window.location.href = destination;
    }
  });
}

function bindAuthLinks() {
  document.addEventListener("click", async (e) => {
    const link = e.target.closest("a[data-require-auth]");
    if (!link) return;
    e.preventDefault();
    if (await PrismAuth.requireAuth(link.href)) {
      window.location.href = link.href;
    }
  });
}

async function initCommon() {
  renderNavigation();
  bindAuthGate();
  bindHeaderSearch();
  bindAuthLinks();

  const hamburger = document.getElementById("hamburger");
  const nav = document.querySelector(".header-nav");
  const overlay = document.createElement("div");
  overlay.classList.add("nav-overlay");
  document.body.appendChild(overlay);

  const toggleMenu = () => {
    hamburger?.classList.toggle("active");
    nav?.classList.toggle("active");
    overlay.classList.toggle("active");
  };

  hamburger?.addEventListener("click", toggleMenu);
  overlay.addEventListener("click", toggleMenu);

  await PrismAuth.refresh();
}

PrismAuth.ready = (document.readyState === "loading"
  ? new Promise(resolve => document.addEventListener("DOMContentLoaded", resolve, { once: true }))
  : Promise.resolve()).then(initCommon);
