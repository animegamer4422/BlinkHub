const CART_KEY = "blinkhub_cart";
let cartCount = 0;

function loadCart() {
    try {
        const raw = localStorage.getItem(CART_KEY);
        if (!raw) return [];
        const parsed = JSON.parse(raw);
        return Array.isArray(parsed) ? parsed : [];
    } catch {
        return [];
    }
}

function saveCart(cart) {
    localStorage.setItem(CART_KEY, JSON.stringify(cart));
}

function getCartCount(cart) {
    return cart.reduce((sum, item) => sum + (item.qty || 0), 0);
}

function updateCartBadge(cart) {
    const badge = document.getElementById("cart-count");
    if (!badge) return;
    const count = cart ? getCartCount(cart) : cartCount;
    badge.textContent = count;
}

function showToast(message) {
    let toast = document.querySelector(".toast");
    if (!toast) {
        toast = document.createElement("div");
        toast.className = "toast";
        document.body.appendChild(toast);
    }
    toast.textContent = message;
    toast.classList.add("toast-visible");
    setTimeout(() => toast.classList.remove("toast-visible"), 1800);
}

function renderCart(cart) {
    const empty = document.getElementById("cart-empty");
    const filled = document.getElementById("cart-filled");
    const tbody = document.getElementById("cart-items");
    const subtitle = document.getElementById("cart-subtitle");
    const subtotalEl = document.getElementById("cart-subtotal");
    const deliveryEl = document.getElementById("cart-delivery");
    const totalEl = document.getElementById("cart-total");

    if (!empty || !filled || !tbody || !subtitle) return;

    if (!cart.length) {
        empty.classList.remove("hidden");
        filled.classList.add("hidden");
        subtitle.textContent = "Your cart is empty.";
        updateCartBadge(cart);
        return;
    }

    empty.classList.add("hidden");
    filled.classList.remove("hidden");

    tbody.innerHTML = "";
    let subtotal = 0;
    cart.forEach((item, idx) => {
        const lineTotal = (item.price || 0) * (item.qty || 0);
        subtotal += lineTotal;

        const tr = document.createElement("tr");
        tr.innerHTML = `
            <td>${idx + 1}</td>
            <td>${item.name}</td>
            <td>₹${item.price}</td>
            <td>${item.qty}</td>
            <td>₹${lineTotal}</td>
        `;
        tbody.appendChild(tr);
    });

    const count = getCartCount(cart);
    subtitle.textContent = `${count} item${count !== 1 ? "s" : ""} in your cart`;

    const delivery = subtotal ? 15 : 0;
    const total = subtotal + delivery;

    if (subtotalEl) subtotalEl.textContent = `₹${subtotal}`;
    if (subtotalEl) subtotalEl.textContent = `₹${subtotal}`;
    if (deliveryEl) deliveryEl.textContent = `₹${delivery}`;
    if (totalEl) totalEl.textContent = `₹${total}`;


    updateCartBadge(cart);
}

document.addEventListener("DOMContentLoaded", () => {
    let cart = loadCart();
    updateCartBadge(cart);

        // ====== SMOOTH SCROLL ON LOGO (home page) ======
    if (document.body.classList.contains("home-page")) {
        const logo = document.querySelector(".logo");
        if (logo) {
            logo.addEventListener("click", (e) => {
                // prevent reload, just smooth-scroll to top
                e.preventDefault();
                window.scrollTo({
                    top: 0,
                    behavior: "smooth"
                });
            });
        }
    }


    // ====== PRODUCT LIST / STEPPER SETUP ======
    document.querySelectorAll(".qty-controls").forEach(ctrl => {
        const id = Number(ctrl.dataset.id);
        const name = ctrl.dataset.name;
        const price = Number(ctrl.dataset.price);

        const addBtn = ctrl.querySelector(".add-btn");
        const stepper = ctrl.querySelector(".stepper");
        const qtySpan = ctrl.querySelector(".stepper-qty");

        let existing = cart.find(item => item.id === id);

        if (existing) {
            addBtn.classList.add("hidden");
            stepper.classList.remove("hidden");
            qtySpan.textContent = existing.qty;
        }

        // ADD button → convert to stepper
        addBtn.addEventListener("click", () => {
            existing = cart.find(item => item.id === id);
            if (existing) {
                existing.qty += 1;
            } else {
                existing = { id, name, price, qty: 1 };
                cart.push(existing);
            }

            saveCart(cart);
            updateCartBadge(cart);

            qtySpan.textContent = existing.qty;
            addBtn.classList.add("hidden");
            stepper.classList.remove("hidden");

            showToast(`Added ${name}`);
        });

        // PLUS
        ctrl.querySelector(".stepper-plus").addEventListener("click", () => {
            const existingItem = cart.find(item => item.id === id);
            if (!existingItem) return;

            existingItem.qty += 1;
            qtySpan.textContent = existingItem.qty;
            saveCart(cart);
            updateCartBadge(cart);
        });

        // MINUS
        ctrl.querySelector(".stepper-minus").addEventListener("click", () => {
            const existingItem = cart.find(item => item.id === id);
            if (!existingItem) return;

            existingItem.qty -= 1;

            if (existingItem.qty <= 0) {
                cart = cart.filter(item => item.id !== id);
                saveCart(cart);
                updateCartBadge(cart);
                stepper.classList.add("hidden");
                addBtn.classList.remove("hidden");
            } else {
                qtySpan.textContent = existingItem.qty;
                saveCart(cart);
                updateCartBadge(cart);
            }
        });
    });

    // ====== CART PAGE ======
    if (document.body.classList.contains("cart-page")) {
        // Render current contents from localStorage
        renderCart(cart);

        const checkoutBtn = document.getElementById("cart-checkout-btn");
        const statusEl    = document.getElementById("cart-payment-status");

        if (checkoutBtn && statusEl) {
            checkoutBtn.addEventListener("click", () => {
                if (!cart.length) {
                    showToast("Cart is empty");
                    return;
                }

                // Require login before proceeding
                const isLoggedIn = !!window.BLINKHUB_IS_LOGGED_IN;
                if (!isLoggedIn) {
                    showToast("Please login to place your order.");
                    setTimeout(() => {
                        window.location.href = "login.php";
                    }, 600);
                    return;
                }

                checkoutBtn.disabled = true;
                checkoutBtn.textContent = "Processing payment…";

                setTimeout(() => {
                    const orderId = "BH" + Math.floor(100000 + Math.random() * 900000);
                    statusEl.textContent = `Payment successful. Your mock order ID is ${orderId}.`;
                    showToast(`Payment successful · Order ${orderId}`);

                    cart = [];
                    saveCart(cart);
                    renderCart(cart);
                    updateCartBadge(cart);

                    checkoutBtn.disabled = false;
                    checkoutBtn.textContent = "Proceed to payment";
                }, 1200);
            });
        }
    }

    // ====== SEARCH + CATEGORY FILTERS ======
    const searchInput = document.getElementById("search-input");
    const productCards = Array.from(document.querySelectorAll(".product-card"));
    const categoryButtons = Array.from(document.querySelectorAll(".category-pill"));

    let currentSearchTerm = "";
    let currentCategory = ""; // empty = "All"

    function applyFilters() {
        const term = currentSearchTerm.toLowerCase();

        productCards.forEach(card => {
            const name = (card.dataset.name || "").toLowerCase();
            const category = (card.dataset.category || "").toLowerCase();

            const matchesSearch =
                term === "" ||
                name.includes(term) ||
                category.includes(term);

            const matchesCategory =
                currentCategory === "" ||
                category === currentCategory;

            const visible = matchesSearch && matchesCategory;
            card.classList.toggle("hidden-card", !visible);
        });
    }

    if (searchInput) {
        searchInput.addEventListener("input", function () {
            const q = this.value.toLowerCase().trim();

            document.querySelectorAll(".product-card").forEach(card => {
                const name = card.dataset.name.toLowerCase();
                const category = card.dataset.category.toLowerCase();
                const tags = (card.dataset.tags || "").toLowerCase();

                if (
                    name.includes(q) ||
                    category.includes(q) ||
                    tags.includes(q)
                ) {
                    card.classList.remove("hidden-card");
                } else {
                    card.classList.add("hidden-card");
                }
            });
        });

    }

    if (categoryButtons.length) {
        categoryButtons.forEach(btn => {
            btn.addEventListener("click", () => {
                // Set currentCategory from data-category (case-insensitive)
                const cat = (btn.dataset.category || "").toLowerCase();
                currentCategory = cat;

                // Update active class
                categoryButtons.forEach(b => b.classList.remove("active"));
                btn.classList.add("active");

                applyFilters();
            });
        });
    }
});

// Toast styles
const style = document.createElement("style");
style.textContent = `
.toast {
  position: fixed;
  bottom: 20px;
  left: 50%;
  transform: translateX(-50%) translateY(20px);
  background: #111;
  color: #fff;
  padding: 8px 16px;
  border-radius: 999px;
  border: 1px solid #333;
  font-size: 12px;
  opacity: 0;
  pointer-events: none;
  transition: all 0.25s ease-out;
  z-index: 99;
}
.toast-visible {
  opacity: 1;
  transform: translateX(-50%) translateY(0);
}
`;
document.head.appendChild(style);

document.addEventListener("DOMContentLoaded", () => {
    const locPill   = document.getElementById("location-pill");
    const modal     = document.getElementById("address-modal");
    const cancelBtn = document.getElementById("address-cancel");
    const form      = document.getElementById("address-form");
    const statusEl  = document.getElementById("address-status");
    const locText   = document.getElementById("loc-main-text");

    if (!locPill || !modal || !form) return;

    function openModal() {
        modal.classList.remove("hidden");
        statusEl && (statusEl.textContent = "");
    }

    function closeModal() {
        modal.classList.add("hidden");
    }

    locPill.addEventListener("click", openModal);
    cancelBtn && cancelBtn.addEventListener("click", (e) => {
        e.preventDefault();
        closeModal();
    });

    form.addEventListener("submit", async (e) => {
        e.preventDefault();
        statusEl && (statusEl.textContent = "Saving address…");

        try {
            const formData = new FormData(form);
                const res = await fetch("api/save_address.php", {
                    method: "POST",
                    body: formData
                });


            const data = await res.json();
            if (data.ok) {
                if (locText && data.short) {
                    locText.textContent = data.short;
                }
                closeModal();
            } else {
                statusEl && (statusEl.textContent = data.error || "Could not save address.");
            }
        } catch (err) {
            statusEl && (statusEl.textContent = "Network error. Try again.");
        }
    });
});
