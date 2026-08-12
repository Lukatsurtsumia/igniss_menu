(function () {
  "use strict";

  const state = {
    category: "popular",
    query: "",
    cart: {}, // id -> qty
    activeItem: null,
    sheetQty: 1,
  };

  const $ = (sel) => document.querySelector(sel);
  const $$ = (sel) => Array.from(document.querySelectorAll(sel));

  function fmt(n) {
    return "$" + n.toFixed(2);
  }

  function itemById(id) {
    return MENU_ITEMS.find((i) => i.id === id);
  }

  function cartCount() {
    return Object.values(state.cart).reduce((a, b) => a + b, 0);
  }

  function cartSubtotal() {
    return Object.entries(state.cart).reduce((sum, [id, qty]) => {
      const item = itemById(Number(id));
      return sum + (item ? item.price * qty : 0);
    }, 0);
  }

  // ---------- SCREEN NAVIGATION ----------
  function showScreen(id) {
    $$(".screen").forEach((s) => s.classList.remove("active"));
    $(id).classList.add("active");
    const showTabbar = id === "#screen-menu" || id === "#screen-cart";
    $("#tabbar").style.display = showTabbar ? "flex" : "none";
    if (id === "#screen-cart") setActiveTab("cart");
    if (id === "#screen-menu") setActiveTab("menu");
  }

  function setActiveTab(tab) {
    $$(".tab-btn").forEach((b) => b.classList.toggle("active", b.dataset.tab === tab));
  }

  // ---------- RENDER: CATEGORIES ----------
  function renderCategories() {
    const wrap = $("#categories");
    wrap.innerHTML = "";
    CATEGORIES.forEach((cat) => {
      const btn = document.createElement("button");
      btn.className = "cat-chip" + (cat.id === state.category ? " active" : "");
      btn.innerHTML = `<span>${cat.icon}</span><span>${cat.label}</span>`;
      btn.addEventListener("click", () => {
        state.category = cat.id;
        state.query = "";
        $("#search-input").value = "";
        renderCategories();
        renderMenu();
      });
      wrap.appendChild(btn);
    });
  }

  // ---------- RENDER: MENU LIST ----------
  function renderMenu() {
    const list = $("#menu-list");
    list.innerHTML = "";

    let items;
    if (state.query.trim()) {
      const q = state.query.trim().toLowerCase();
      items = MENU_ITEMS.filter(
        (i) => i.name.toLowerCase().includes(q) || i.desc.toLowerCase().includes(q)
      );
    } else {
      items = MENU_ITEMS.filter((i) => i.categories.includes(state.category));
    }

    if (!items.length) {
      list.innerHTML = `<div class="empty-state"><span class="e">🍽️</span>No dishes found</div>`;
      return;
    }

    if (state.query.trim()) {
      const label = document.createElement("div");
      label.className = "section-label";
      label.textContent = `${items.length} result${items.length > 1 ? "s" : ""}`;
      list.appendChild(label);
    }

    items.forEach((item) => list.appendChild(renderItemCard(item)));
  }

  function renderItemCard(item) {
    const card = document.createElement("div");
    card.className = "item-card";
    card.innerHTML = `
      <div class="item-photo-wrap">
        <img class="item-photo" src="${item.image}" alt="${item.name}" loading="lazy" width="96" height="96" />
      </div>
      <div class="item-info">
        <h4>${item.name}</h4>
        <p>${item.desc}</p>
        <div class="item-meta">
          <span class="item-price">${fmt(item.price)}</span>
          <span class="item-rating">⭐ ${item.rating}</span>
        </div>
      </div>
      <button class="add-btn" aria-label="Add ${item.name}">+</button>
    `;
    card.addEventListener("click", (e) => {
      if (e.target.closest(".add-btn")) {
        addToCart(item.id, 1);
        pulseToast(`Added ${item.name}`);
        return;
      }
      openItemSheet(item.id);
    });
    return card;
  }

  // ---------- ITEM DETAIL SHEET ----------
  function openItemSheet(id) {
    const item = itemById(id);
    state.activeItem = id;
    state.sheetQty = 1;

    $("#item-photo").src = item.image;
    $("#item-photo").alt = item.name;
    $("#item-name").textContent = item.name;
    $("#item-desc").textContent = item.desc;
    $("#item-qty").textContent = state.sheetQty;
    $("#item-total-price").textContent = fmt(item.price * state.sheetQty);

    const tagsWrap = $("#item-tags");
    tagsWrap.innerHTML = "";
    item.tags.forEach((t) => {
      const el = document.createElement("span");
      el.className = "tag";
      el.textContent = t;
      tagsWrap.appendChild(el);
    });

    $("#item-overlay").classList.add("open");
  }

  function closeItemSheet() {
    $("#item-overlay").classList.remove("open");
    state.activeItem = null;
  }

  function updateSheetQty(delta) {
    state.sheetQty = Math.max(1, state.sheetQty + delta);
    const item = itemById(state.activeItem);
    $("#item-qty").textContent = state.sheetQty;
    $("#item-total-price").textContent = fmt(item.price * state.sheetQty);
  }

  // ---------- CART ----------
  function addToCart(id, qty) {
    state.cart[id] = (state.cart[id] || 0) + qty;
    renderCartBadge();
  }

  function setCartQty(id, qty) {
    if (qty <= 0) delete state.cart[id];
    else state.cart[id] = qty;
    renderCartBadge();
    renderCart();
  }

  function renderCartBadge() {
    const n = cartCount();
    const badge = $("#cart-badge");
    badge.textContent = n;
    badge.style.display = n > 0 ? "flex" : "none";
  }

  function renderCart() {
    const list = $("#cart-list");
    const empty = $("#cart-empty");
    const summary = $("#cart-summary");
    list.innerHTML = "";

    const entries = Object.entries(state.cart);
    if (!entries.length) {
      empty.style.display = "flex";
      summary.style.display = "none";
      list.style.display = "none";
      return;
    }
    empty.style.display = "none";
    summary.style.display = "block";
    list.style.display = "flex";

    entries.forEach(([id, qty]) => {
      const item = itemById(Number(id));
      const row = document.createElement("div");
      row.className = "cart-item";
      row.innerHTML = `
        <div class="item-photo-wrap">
          <img class="item-photo" src="${item.image}" alt="${item.name}" loading="lazy" width="64" height="64" />
        </div>
        <div class="cart-item-info">
          <h4>${item.name}</h4>
          <span>${fmt(item.price)} each</span>
        </div>
        <div class="cart-item-controls">
          <div class="stepper">
            <button class="dec">−</button>
            <span>${qty}</span>
            <button class="inc">+</button>
          </div>
        </div>
      `;
      row.querySelector(".inc").addEventListener("click", () => setCartQty(item.id, qty + 1));
      row.querySelector(".dec").addEventListener("click", () => setCartQty(item.id, qty - 1));
      list.appendChild(row);
    });

    const subtotal = cartSubtotal();
    const service = subtotal * 0.1;
    $("#sum-subtotal").textContent = fmt(subtotal);
    $("#sum-service").textContent = fmt(service);
    $("#sum-total").textContent = fmt(subtotal + service);
  }

  // ---------- TOAST ----------
  let toastTimer = null;
  function pulseToast(msg) {
    const toast = $("#toast");
    toast.textContent = msg;
    toast.classList.add("show");
    clearTimeout(toastTimer);
    toastTimer = setTimeout(() => toast.classList.remove("show"), 1600);
  }

  // ---------- CLOCK ----------
  function tickClock() {
    const now = new Date();
    let h = now.getHours();
    const m = now.getMinutes();
    const ampm = h >= 12 ? "PM" : "AM";
    h = h % 12 || 12;
    $("#clock").textContent = `${h}:${String(m).padStart(2, "0")}`;
  }

  // ---------- QR CODE ----------
  function renderQR() {
    const url = window.location.href.split("#")[0];
    $("#qr-url").textContent = url.replace(/^https?:\/\//, "");
    const canvas = $("#qr-canvas");
    if (window.QRCode) {
      QRCode.toCanvas(
        canvas,
        url,
        { width: 220, margin: 1, color: { dark: "#1c1a17", light: "#ffffff" } },
        (err) => {
          if (err) console.error("QR render failed:", err);
        }
      );
    }
  }

  // ---------- ORDER ----------
  function placeOrder() {
    const orderNo = "#" + Math.floor(1000 + Math.random() * 9000);
    $("#order-no").textContent = orderNo;
    $("#order-table").textContent = RESTAURANT.table;
    state.cart = {};
    renderCartBadge();
    renderCart();
    showScreen("#screen-confirm");
  }

  // ---------- EVENT WIRING ----------
  function wireEvents() {
    $("#btn-enter").addEventListener("click", () => showScreen("#screen-menu"));

    $("#search-input").addEventListener("input", (e) => {
      state.query = e.target.value;
      renderMenu();
    });

    $("#item-minus").addEventListener("click", () => updateSheetQty(-1));
    $("#item-plus").addEventListener("click", () => updateSheetQty(1));
    $("#item-overlay").addEventListener("click", (e) => {
      if (e.target === $("#item-overlay")) closeItemSheet();
    });
    $("#btn-add-to-cart").addEventListener("click", () => {
      const item = itemById(state.activeItem);
      addToCart(item.id, state.sheetQty);
      pulseToast(`Added ${state.sheetQty} × ${item.name}`);
      closeItemSheet();
    });

    $("#btn-cart-back").addEventListener("click", () => showScreen("#screen-menu"));
    $("#btn-browse").addEventListener("click", () => showScreen("#screen-menu"));
    $("#btn-place-order").addEventListener("click", placeOrder);
    $("#btn-new-order").addEventListener("click", () => showScreen("#screen-menu"));

    $$(".tab-btn").forEach((btn) => {
      btn.addEventListener("click", () => {
        const tab = btn.dataset.tab;
        if (tab === "menu") showScreen("#screen-menu");
        else if (tab === "cart") {
          renderCart();
          showScreen("#screen-cart");
        } else if (tab === "search") {
          showScreen("#screen-menu");
          setTimeout(() => $("#search-input").focus(), 100);
        } else if (tab === "waiter") {
          pulseToast("🔔 A waiter has been notified");
        }
      });
    });

    $("#btn-profile").addEventListener("click", () => pulseToast(`Table ${RESTAURANT.table} · Bella Vista`));
  }

  // ---------- INIT ----------
  function init() {
    $("#table-num").textContent = RESTAURANT.table;
    $("#table-num-2").textContent = RESTAURANT.table;
    renderQR();
    renderCategories();
    renderMenu();
    renderCartBadge();
    wireEvents();
    tickClock();
    setInterval(tickClock, 30000);
  }

  document.addEventListener("DOMContentLoaded", init);
})();
