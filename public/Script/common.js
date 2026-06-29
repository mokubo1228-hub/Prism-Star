(function () {
  const meta = document.querySelector('meta[name="csrf-token"]');
  const token = meta ? meta.content : "";
  const nativeFetch = window.fetch.bind(window);
  const mutating = /^(POST|PUT|PATCH|DELETE)$/i;

  window.fetch = function (input, init = {}) {
    const url = typeof input === "string" ? input : input.url;
    const method = (init.method || (typeof input !== "string" && input.method) || "GET").toUpperCase();
    const sameOrigin = !/^https?:\/\//i.test(url) || url.startsWith(window.location.origin);

    if (token && sameOrigin && mutating.test(method)) {
      const headers = new Headers(init.headers || (typeof input !== "string" ? input.headers : undefined));
      if (!headers.has("X-CSRF-Token")) {
        headers.set("X-CSRF-Token", token);
      }
      init = { ...init, headers };
    }

    return nativeFetch(input, init);
  };
})();

const navItems = [
  { href: "gallery-list.php", label: "おすすめ", group: "global" },
  { href: "search.php", label: "検索", group: "global" },
  { href: "mypage.php", label: "作品管理", auth: true, group: "personal" },
  { href: "favorites.php", label: "お気に入り", auth: true, group: "personal" },
  { href: "profile.php", label: "プロフィール", auth: true, group: "personal", selfProfile: true },
  { href: "settings.php", label: "設定", auth: true, group: "personal" },
  { href: "form.php", label: "Contact", group: "support" },
  { href: "policy.php", label: "Privacy Policy", group: "support" }
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

function renderTagLinks(container, tags) {
  tags.forEach((tag, index) => {
    if (index > 0) {
      container.append(" ");
    }

    const link = document.createElement("a");
    link.className = "tag-link";
    link.href = `search.php?tag=${encodeURIComponent(tag)}`;
    link.textContent = `#${tag}`;
    container.appendChild(link);
  });
}

function renderNavigation() {
  const templates = document.querySelectorAll("#nav-item-template");

  templates.forEach(template => {
    const container = template.parentElement;
    const isHeaderNav = container.classList.contains("header-nav");
    let previousGroup = null;

    navItems.forEach(item => {
      if (isHeaderNav && previousGroup !== null && previousGroup !== item.group) {
        const separator = document.createElement("li");
        separator.className = "nav-separator";
        separator.setAttribute("aria-hidden", "true");
        if (previousGroup === "personal" || item.group === "personal") {
          separator.dataset.authNav = "true";
          separator.hidden = true;
        }
        container.appendChild(separator);
      }

      const clone = template.content.cloneNode(true);
      const listItem = clone.querySelector("li");
      const link = clone.querySelector("a");

      link.href = item.href;
      link.textContent = item.label;
      if (item.auth) {
        link.dataset.requireAuth = "true";
        if (listItem) {
          listItem.dataset.authNav = "true";
          listItem.hidden = true;
        }
      }
      if (item.selfProfile) {
        link.dataset.navProfile = "self";
      }
      container.appendChild(clone);
      previousGroup = item.group;
    });

    if (isHeaderNav) {
      const logoutItem = container.querySelector(".nav-logout");
      if (logoutItem) container.appendChild(logoutItem);
    }
  });
}

function applyAuthState(status) {
  const loggedIn = Boolean(status.loggedIn);
  document.body.classList.toggle("is-authed", loggedIn);

  const guestActions = document.querySelector(".auth-guest-actions");
  const logoutButton = document.querySelector(".auth-action-logout");
  const mypageLink = document.querySelector(".auth-action-mypage");
  const headerAvatar = document.querySelector(".header-avatar img");
  const profileHref = loggedIn && status.user?.id ? `profile.php?id=${status.user.id}` : "profile.php";

  document.querySelectorAll("[data-auth-nav]").forEach(item => {
    item.hidden = !loggedIn;
  });
  document.querySelectorAll("[data-nav-profile=\"self\"]").forEach(link => {
    link.href = profileHref;
  });

  if (guestActions) guestActions.hidden = loggedIn;
  if (mypageLink) mypageLink.hidden = !loggedIn;
  if (headerAvatar) {
    headerAvatar.src = loggedIn && status.user?.avatar ? status.user.avatar : "Image/default-avatar.svg";
  }
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

function bindBackMenu() {
  document.addEventListener("click", (e) => {
    const back = e.target.closest("a.back-menu");
    if (!back) return;

    let fromApp = false;
    try {
      fromApp = Boolean(document.referrer) &&
        new URL(document.referrer).origin === window.location.origin;
    } catch {
      fromApp = false;
    }

    // referrer は同一アプリ判定にだけ使い、遷移先は履歴か固定 href に限定する。
    if (fromApp && window.history.length > 1) {
      e.preventDefault();
      window.history.back();
    }
  });
}

async function initCommon() {
  renderNavigation();
  bindAuthGate();
  bindHeaderSearch();
  bindAuthLinks();
  bindBackMenu();

  const hamburger = document.getElementById("hamburger");
  const nav = document.querySelector(".header-nav");
  const overlay = document.createElement("div");
  overlay.classList.add("nav-overlay");
  document.body.appendChild(overlay);

  const toggleMenu = () => {
    hamburger?.classList.toggle("active");
    nav?.classList.toggle("active");
    overlay.classList.toggle("active");
    hamburger?.setAttribute("aria-expanded", String(nav?.classList.contains("active")));
  };

  hamburger?.addEventListener("click", toggleMenu);
  overlay.addEventListener("click", toggleMenu);

  await PrismAuth.refresh();
}

PrismAuth.ready = (document.readyState === "loading"
  ? new Promise(resolve => document.addEventListener("DOMContentLoaded", resolve, { once: true }))
  : Promise.resolve()).then(initCommon);
