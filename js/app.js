// Cart logic using localStorage
function normalizeCartItem(item) {
    const id = Number.parseInt(item?.id, 10);
    const name = String(item?.name || '').trim();
    const price = Number.parseFloat(item?.price);
    const qty = Math.max(1, Number.parseInt(item?.qty, 10) || 1);
    const image = item?.image || '';

    if (!Number.isFinite(id) || !name || !Number.isFinite(price)) {
        return null;
    }

    const normalizedName = name.toLowerCase().replace(/\s+/g, ' ').trim();
    const key = item?.key || `${id}::${normalizedName}`;

    return {
        key,
        id,
        name,
        price,
        qty,
        image
    };
}

const CART_IMAGE_FALLBACK = 'https://images.unsplash.com/photo-1512436991641-6745cdb1723f?w=400&h=400&fit=crop&crop=center';

function getCart() {
    const rawCart = JSON.parse(localStorage.getItem('cart') || '[]');
    if (!Array.isArray(rawCart)) {
        return [];
    }

    return rawCart
        .map(normalizeCartItem)
        .filter(Boolean);
}

function setCart(cart) {
    const cleanCart = (Array.isArray(cart) ? cart : [])
        .map(normalizeCartItem)
        .filter(Boolean);
    localStorage.setItem('cart', JSON.stringify(cleanCart));
}

async function hydrateCartImages() {
    const cart = getCart();
    const needsImages = cart.some(item => !String(item.image || '').trim());
    if (!needsImages) {
        return false;
    }

    try {
        const response = await fetch('/data/products.json', { cache: 'no-store' });
        if (!response.ok) {
            return false;
        }

        const products = await response.json();
        if (!Array.isArray(products)) {
            return false;
        }

        const byId = new Map();
        const byName = new Map();
        products.forEach(product => {
            if (!product) {
                return;
            }
            const id = Number.parseInt(product.id, 10);
            const nameKey = String(product.name || '').trim().toLowerCase();
            const image = String(product.image_url || '').trim();
            if (Number.isFinite(id) && image) {
                byId.set(id, image);
            }
            if (nameKey && image) {
                byName.set(nameKey, image);
            }
        });

        let changed = false;
        const hydrated = cart.map(item => {
            if (String(item.image || '').trim()) {
                return item;
            }

            const resolvedImage = byId.get(item.id) || byName.get(String(item.name || '').trim().toLowerCase()) || '';
            if (!resolvedImage) {
                return item;
            }

            changed = true;
            return { ...item, image: resolvedImage };
        });

        if (changed) {
            setCart(hydrated);
        }

        return changed;
    } catch (error) {
        console.error('Failed to hydrate cart images:', error);
        return false;
    }
}

function updateCartCount() {
    // Fetch cart count from API if logged in
    fetch('/api/cart_count.php')
        .then(response => response.json())
        .then(data => {
            const count = data.success ? data.count : 0;
            document.querySelectorAll('#cart-count').forEach(el => {
                el.textContent = count > 0 ? count : '';
            });
        })
        .catch(error => {
            console.error('Failed to update cart count:', error);
    // Fallback to localStorage if API fails
            const cart = getCart();
            const count = cart.reduce((sum, item) => sum + item.qty, 0);
            document.querySelectorAll('#cart-count').forEach(el => {
                el.textContent = count > 0 ? count : '';
            });
        });
}

function renderCart() {
    const container = document.getElementById('cart-items');
    if (!container) return;

    const cart = getCart();
    const summarySubtotal = document.getElementById('cart-subtotal');
    const summaryTotal = document.getElementById('cart-total');
    const emptyMessage = document.getElementById('cart-empty-message');
    const shippingAmount = 24.00;
    const taxAmount = 110.55;

    container.innerHTML = '';
    if (emptyMessage) emptyMessage.classList.toggle('hidden', cart.length > 0);

    if (!cart.length) {
        if (summarySubtotal) summarySubtotal.textContent = '$0.00';
        if (summaryTotal) summaryTotal.textContent = '$0.00';
        return;
    }

    let subtotal = 0;
    cart.forEach(item => {
        const itemTotal = item.price * item.qty;
        subtotal += itemTotal;

        const itemElement = document.createElement('div');
        itemElement.className = 'flex flex-col md:flex-row gap-6 p-6 bg-surface-container-lowest rounded-xl group transition-all duration-300 hover:shadow-[0_20px_40px_-15px_rgba(0,0,0,0.05)]';
        itemElement.innerHTML = `
            <div class="w-full md:w-48 h-48 rounded-lg overflow-hidden flex-shrink-0 bg-surface-container-low flex items-center justify-center text-on-surface-variant">
                <img src="${item.image || CART_IMAGE_FALLBACK}" alt="${item.name}" class="w-full h-full object-cover" />
            </div>
            <div class="flex flex-col flex-grow justify-between">
                <div class="flex justify-between items-start">
                    <div>
                        <h3 class="font-headline text-xl font-bold text-primary mb-1">${item.name}</h3>
                        <p class="font-label text-sm text-on-surface-variant">Qty: ${item.qty}</p>
                    </div>
                    <button class="text-outline hover:text-error transition-colors remove-from-cart" type="button" data-key="${item.key}">
                        <span class="material-symbols-outlined">delete</span>
                    </button>
                </div>
                <div class="flex justify-between items-end mt-6">
                    <div class="flex items-center bg-surface-container-high rounded-full px-4 py-2 space-x-4">
                        <button class="text-primary font-bold hover:scale-125 transition-transform quantity-decrease" type="button" data-key="${item.key}">-</button>
                        <span class="font-label font-semibold text-primary">${item.qty}</span>
                        <button class="text-primary font-bold hover:scale-125 transition-transform quantity-increase" type="button" data-key="${item.key}">+</button>
                    </div>
                    <span class="font-headline text-lg font-bold text-primary">$${itemTotal.toFixed(2)}</span>
                </div>
            </div>
        `;
        container.appendChild(itemElement);
    });

    const total = subtotal + shippingAmount + taxAmount;
    if (summarySubtotal) summarySubtotal.textContent = `$${subtotal.toFixed(2)}`;
    if (summaryTotal) summaryTotal.textContent = `$${total.toFixed(2)}`;
    attachCartControls();
    if (typeof updateCheckoutButton === 'function') {
        updateCheckoutButton();
    }
}

function attachCartControls() {
    document.querySelectorAll('.remove-from-cart').forEach(button => {
        button.addEventListener('click', function () {
            const productKey = this.dataset.key;
            if (!productKey) return;
            removeFromCart(productKey);
        });
    });

    document.querySelectorAll('.quantity-decrease').forEach(button => {
        button.addEventListener('click', function () {
            const productKey = this.dataset.key;
            if (!productKey) return;
            changeQty(productKey, -1);
        });
    });

    document.querySelectorAll('.quantity-increase').forEach(button => {
        button.addEventListener('click', function () {
            const productKey = this.dataset.key;
            if (!productKey) return;
            changeQty(productKey, 1);
        });
    });
}

function showCartNotification(message) {
    let toast = document.getElementById('cart-notice');
    if (!toast) {
        toast = document.createElement('div');
        toast.id = 'cart-notice';
        toast.setAttribute('role', 'status');
        toast.setAttribute('aria-live', 'polite');
        Object.assign(toast.style, {
            position: 'fixed',
            bottom: '24px',
            right: '24px',
            zIndex: '9999',
            background: 'rgba(15, 23, 42, 0.95)',
            color: '#ffffff',
            padding: '14px 18px',
            borderRadius: '18px',
            boxShadow: '0 20px 50px rgba(15, 23, 42, 0.24)',
            fontSize: '13px',
            maxWidth: '320px',
            opacity: '0',
            transition: 'opacity 220ms ease, transform 220ms ease',
            transform: 'translateY(12px)'
        });
        document.body.appendChild(toast);
    }
    toast.textContent = message;
    toast.style.opacity = '1';
    toast.style.transform = 'translateY(0)';
    if (toast._timeoutId) {
        clearTimeout(toast._timeoutId);
    }
    toast._timeoutId = setTimeout(() => {
        toast.style.opacity = '0';
        toast.style.transform = 'translateY(12px)';
    }, 2200);
}

function findProductDataFromButton(button) {
    const id = parseInt(button.dataset.id, 10) || parseInt(button.closest('[data-id]')?.dataset.id, 10);
    const name = button.dataset.name || button.closest('[data-name]')?.dataset.name || button.closest('article,div')?.querySelector('h3, h2, .product-name')?.textContent.trim();
    const price = parseFloat(button.dataset.price) || parseFloat(button.closest('[data-price]')?.dataset.price) || parseFloat(button.closest('article,div')?.querySelector('[data-price]')?.dataset.price);
    const image = button.dataset.image || button.closest('[data-image]')?.dataset.image || button.closest('article,div')?.querySelector('img')?.currentSrc || button.closest('article,div')?.querySelector('img')?.src || '';
    if (!id || !name || !price) {
        return null;
    }
    return { id, name, price, image };
}

function setButtonAddedState(button) {
    if (!button.dataset.originalHtml) {
        button.dataset.originalHtml = button.innerHTML;
    }
    button.disabled = true;
    button.classList.add('bg-teal-600', 'cursor-not-allowed');
    button.classList.remove('hover:bg-primary/90');
    button.innerHTML = `<span class="material-symbols-outlined text-lg">check</span> Added`;
    setTimeout(() => {
        button.disabled = false;
        button.classList.remove('bg-teal-600', 'cursor-not-allowed');
        button.innerHTML = button.dataset.originalHtml;
    }, 1400);
}

function addToCart(product) {
    const normalizedProduct = normalizeCartItem({ ...product, qty: 1 });
    if (!normalizedProduct) {
        return;
    }

    // Call API to add to cart
    fetch('/api/cart_add.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            product_id: normalizedProduct.id,
            quantity: 1
        })
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                updateCartCount();
                showCartNotification(`${normalizedProduct.name} added to cart`);
                if (typeof renderCart === 'function') renderCart();
            } else {
                showCartNotification(data.message || 'Failed to add to cart', 'error');
            }
        })
        .catch(error => {
            console.error('Error adding to cart:', error);
            showCartNotification('Failed to add to cart', 'error');
        });
}

function removeFromCart(productKey) {
    // Extract product ID from key (assuming key is 'product-{id}')
    const productId = productKey.replace('product-', '');

    fetch('/api/cart_remove.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            product_id: productId
        })
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                updateCartCount();
                if (typeof renderCart === 'function') renderCart();
            } else {
                showCartNotification(data.message || 'Failed to remove from cart', 'error');
            }
        })
        .catch(error => {
            console.error('Error removing from cart:', error);
            showCartNotification('Failed to remove from cart', 'error');
        });
}

function changeQty(productKey, delta) {
    // Extract product ID from key (assuming key is 'product-{id}')
    const productId = productKey.replace('product-', '');

    // First get current quantity, then update
    fetch('/api/cart_update.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            product_id: productId,
            action: delta > 0 ? 'increase' : 'decrease'
        })
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                updateCartCount();
                if (typeof renderCart === 'function') renderCart();
            } else {
                showCartNotification(data.message || 'Failed to update quantity', 'error');
            }
        })
        .catch(error => {
            console.error('Error updating quantity:', error);
            showCartNotification('Failed to update quantity', 'error');
        });
}
        if (typeof renderCart === 'function') renderCart();
    }
}

function normalizeSearchTerm(value) {
    return String(value || '').trim().toLowerCase();
}

function filterCardsBySearch(input) {
    const selector = input.dataset.searchCardSelector || '.grid > *';
    const query = normalizeSearchTerm(input.value);
    const cards = document.querySelectorAll(selector);

    cards.forEach(card => {
        const text = normalizeSearchTerm(card.textContent);
        card.style.display = !query || text.includes(query) ? '' : 'none';
    });
}

function attachGlobalSearchInputs() {
    document.querySelectorAll('input[data-search-target]').forEach(input => {
        input.addEventListener('input', () => {
            const target = input.dataset.searchTarget;
            if (target === 'cards') {
                filterCardsBySearch(input);
                return;
            }

            if (target === 'products' && typeof window.executeProductSearch === 'function') {
                window.executeProductSearch(input.value);
            }
        });
    });
}

async function submitCustomerFeedback(form) {
    const status = document.getElementById('feedback-form-status');
    const submitBtn = form.querySelector('button[type="submit"]');
    if (!status) return;

    const name = form.name.value.trim();
    const email = form.email.value.trim();
    const message = form.message.value.trim();

    if (!name || !email || !message) {
        status.textContent = 'Please fill in all fields.';
        status.className = 'text-sm text-error';
        return;
    }

    status.textContent = 'Sending...';
    status.className = 'text-sm text-slate-300';

    if (submitBtn) {
        submitBtn.disabled = true;
        submitBtn.classList.add('opacity-50', 'cursor-not-allowed');
    }

    try {
        const response = await fetch('/api/comments.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ name, email, message })
        });
        const result = await response.json();

        if (result.status === 'success') {
            status.textContent = 'Thank you! Your feedback has been received. ✓';
            status.className = 'text-sm text-teal-500 font-semibold';
            form.reset();
            setTimeout(() => {
                status.textContent = '';
            }, 4000);
        } else {
            status.textContent = result.message || 'Unable to send feedback right now.';
            status.className = 'text-sm text-error';
        }
    } catch (error) {
        console.error('Feedback error:', error);
        status.textContent = 'Failed. Please try again.';
        status.className = 'text-sm text-error';
    } finally {
        if (submitBtn) {
            submitBtn.disabled = false;
            submitBtn.classList.remove('opacity-50', 'cursor-not-allowed');
        }
    }
}

function attachFeedbackForm() {
    const feedbackForm = document.getElementById('customer-feedback-form');
    if (!feedbackForm) return;

    feedbackForm.addEventListener('submit', function (event) {
        event.preventDefault();
        submitCustomerFeedback(this);
    });
}

function attachFeedbackReplyLookup() {
    const replyButton = document.getElementById('feedback-view-replies-btn');
    const replyEmail = document.getElementById('feedback-reply-email');
    if (!replyButton || !replyEmail) return;

    replyButton.addEventListener('click', function () {
        const email = replyEmail.value.trim();
        if (!email) {
            renderFeedbackRepliesMessage('Please enter your email to view replies.', 'error');
            return;
        }
        fetchFeedbackReplies(email);
    });

    const savedEmail = localStorage.getItem('feedbackEmail');
    if (savedEmail && replyEmail.value.trim() === '') {
        replyEmail.value = savedEmail;
    }
}

async function fetchFeedbackReplies(email) {
    const resultsContainer = document.getElementById('feedback-reply-results');
    if (!resultsContainer) return;

    resultsContainer.innerHTML = '<p class="text-sm text-slate-400">Loading replies...</p>';

    try {
        const response = await fetch(`/api/comments.php?email=${encodeURIComponent(email)}`);
        const result = await response.json();

        if (result.status === 'success') {
            localStorage.setItem('feedbackEmail', email);
            renderFeedbackReplies(result.comments || []);
        } else {
            renderFeedbackRepliesMessage(result.message || 'No replies found.', 'error');
        }
    } catch (error) {
        console.error('Error fetching feedback replies:', error);
        renderFeedbackRepliesMessage('Unable to load replies at this time.', 'error');
    }
}

function escapeHTML(value) {
    return String(value)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;')
        .replace(/`/g, '&#096;');
}

function renderFeedbackReplies(comments) {
    const resultsContainer = document.getElementById('feedback-reply-results');
    if (!resultsContainer) return;

    if (!comments.length) {
        resultsContainer.innerHTML = '<p class="text-sm text-slate-400">No replies found for this email yet.</p>';
        return;
    }

    resultsContainer.innerHTML = comments.map(comment => {
        const safeMessage = escapeHTML(comment.message || '');
        const safeReply = escapeHTML(comment.reply || '');
        const safeCreatedAt = escapeHTML(comment.created_at || '');
        const safeRepliedAt = escapeHTML(comment.replied_at || safeCreatedAt || 'recently');

        const reply = comment.reply ? `<div class="mt-3 rounded-2xl bg-teal-50 p-4 text-slate-900 border border-teal-200"><p class="text-xs uppercase tracking-[0.2em] text-teal-700 font-bold mb-2">Admin reply</p><p class="text-sm leading-6">${safeReply}</p><p class="mt-2 text-[10px] text-slate-500">Replied ${safeRepliedAt}</p></div>` : '<p class="text-sm text-slate-400">No reply for this message yet.</p>';
        return `<div class="rounded-3xl border border-white/10 bg-slate-950/80 p-4">
            <p class="text-xs text-slate-400">Message sent on ${safeCreatedAt}</p>
            <p class="mt-2 text-sm text-white">${safeMessage}</p>
            ${reply}
        </div>`;
    }).join('');
}

function renderFeedbackRepliesMessage(message, type) {
    const resultsContainer = document.getElementById('feedback-reply-results');
    if (!resultsContainer) return;
    const colorClass = type === 'error' ? 'text-error' : 'text-slate-400';
    resultsContainer.innerHTML = `<p class="text-sm ${colorClass}">${escapeHTML(message)}</p>`;
}

document.addEventListener('DOMContentLoaded', async function () {
    // Feedback form
    attachFeedbackForm();
    attachFeedbackReplyLookup();

    document.querySelectorAll('.add-to-cart').forEach(btn => {
        btn.addEventListener('click', function (event) {
            event.preventDefault();
            const product = findProductDataFromButton(this);
            if (!product) return;
            addToCart(product);
            setButtonAddedState(this);
        });
    });

    document.addEventListener('click', function(event) {
        const button = event.target.closest('.add-to-collection');
        if (button) {
            const productCard = button.closest('.product-card');
            if (!productCard) return;
            const id = parseInt(productCard.dataset.id);
            const name = productCard.dataset.name;
            const price = parseFloat(productCard.dataset.price);
            const image = productCard.dataset.image || productCard.querySelector('img')?.src || '';
            addToCart({ id, name, price, image });
        }
    });

    document.querySelectorAll('#cart-link').forEach(link => {
        link.addEventListener('click', function(event) {
            event.preventDefault();
            window.location.href = this.getAttribute('href') || '/cart';
        });
    });

    await hydrateCartImages();
    updateCartCount();
    if (typeof renderCart === 'function') renderCart();
    attachGlobalSearchInputs();

    const loginForm = document.getElementById('login-form');
    if (loginForm) {
        loginForm.addEventListener('submit', function(e) {
            e.preventDefault();
            alert('Login submitted! (Demo)');
        });
    }
});
