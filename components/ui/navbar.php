<?php
$mainMenuRoutes = \Mpemba\Utils\Router::getMenuRoutes('main');
$currentRoute = \Mpemba\Utils\Router::getCurrentRoute();

function renderMenuItems(array $routes, string $currentRoute, int $level = 0, bool $isMobile = false) {
    $html = '';
    foreach ($routes as $route) {
        $hasChildren = !empty($route['children']);
        $isActive = \Mpemba\Utils\Router::isRouteActive($route, $currentRoute);
        $disabled = $route['disabled'] ?? false;

        $itemId = 'menu-' . str_replace(['/', ' '], ['-', '-'], $route['key']);
        $submenuId = $itemId . '-submenu';

        $baseLinkClasses = 'inline-flex items-center gap-2 rounded-full transition duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-primary/20';
        $activeBaseClasses = 'bg-primary/10 text-primary shadow-sm ring-1 ring-primary/20';
        $defaultBaseClasses = 'text-slate-600 dark:text-slate-300 hover:text-primary dark:hover:text-white';
        $disabledBaseClasses = 'pointer-events-none opacity-50 text-slate-400 dark:text-slate-600';

        if ($isMobile) {
            $linkClasses = $disabled
                ? 'pointer-events-none opacity-50 text-slate-400 dark:text-slate-600'
                : ($isActive
                    ? $baseLinkClasses . ' ' . $activeBaseClasses . ' px-4 py-3 text-sm font-semibold'
                    : $baseLinkClasses . ' text-slate-700 dark:text-slate-200 px-4 py-3 text-sm font-medium hover:bg-slate-100 dark:hover:bg-slate-900');

            $html .= '<div class="space-y-1">';
            $html .= '<div class="flex items-center justify-between gap-2">';
            $html .= '<a class="block w-full ' . $linkClasses . '" href="' . htmlspecialchars($route['href']) . '"' . ($isActive ? ' aria-current="page"' : '') . '>' . htmlspecialchars($route['label']) . '</a>';
            if ($hasChildren) {
                $html .= '<button type="button" class="mobile-submenu-toggle inline-flex h-11 w-11 items-center justify-center rounded-full border border-slate-200 bg-white text-slate-700 shadow-sm hover:bg-slate-100 transition dark:border-slate-800 dark:bg-slate-950 dark:text-slate-200" aria-expanded="false" aria-controls="' . $submenuId . '" data-submenu-id="' . $submenuId . '">';
                $html .= '<span class="material-symbols-outlined">expand_more</span>';
                $html .= '</button>';
            }
            $html .= '</div>';

            if ($hasChildren) {
                $html .= '<div id="' . $submenuId . '" class="mobile-submenu hidden space-y-1 pl-4">';
                $html .= renderMenuItems($route['children'], $currentRoute, $level + 1, true);
                $html .= '</div>';
            }

            $html .= '</div>';
            continue;
        }

        $linkClasses = $disabled
            ? $disabledBaseClasses . ' ' . $baseLinkClasses . ' px-4 py-2 text-sm'
            : ($isActive
                ? $baseLinkClasses . ' ' . $activeBaseClasses . ' px-4 py-2 text-sm font-semibold'
                : $baseLinkClasses . ' ' . $defaultBaseClasses . ' px-4 py-2 text-sm hover:bg-slate-100 dark:hover:bg-slate-900');

        $html .= '<div class="relative group">';
        $html .= '<div class="flex items-center gap-2">';
        $html .= '<a class="' . $linkClasses . '" href="' . htmlspecialchars($route['href']) . '"' . ($isActive ? ' aria-current="page"' : '') . '>' . htmlspecialchars($route['label']) . '</a>';
        if ($hasChildren) {
            $html .= '<button type="button" class="desktop-submenu-toggle inline-flex h-10 w-10 items-center justify-center rounded-full border border-slate-200 bg-white text-slate-700 shadow-sm hover:bg-slate-100 transition dark:border-slate-800 dark:bg-slate-950 dark:text-slate-200" aria-expanded="false" aria-controls="' . $submenuId . '" data-submenu-id="' . $submenuId . '">';
            $html .= '<span class="material-symbols-outlined">expand_more</span>';
            $html .= '</button>';
        }
        $html .= '</div>';

        if ($hasChildren) {
            $html .= '<div id="' . $submenuId . '" class="desktop-submenu hidden absolute left-0 top-full z-50 mt-2 min-w-[14rem] overflow-hidden rounded-3xl border border-slate-200 bg-white p-3 shadow-xl transition duration-200 dark:border-slate-800 dark:bg-slate-950">';
            $html .= renderMenuItems($route['children'], $currentRoute, $level + 1, false);
            $html .= '</div>';
        }

        $html .= '</div>';
    }

    return $html;
}
?>
<nav class="fixed top-0 left-0 right-0 z-50 bg-white/80 dark:bg-slate-950/95 backdrop-blur-xl border-b border-slate-200/70 dark:border-slate-800 shadow-sm">
    <div class="max-w-screen-2xl mx-auto px-6 py-4 flex items-center justify-between gap-4">
        <a href="/home" class="flex items-center gap-3">
            <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-primary text-white text-lg font-black">M</div>
            <div>
                <p class="text-base font-semibold text-slate-700 dark:text-slate-100">Mpemba</p>
                <p class="text-xs tracking-[0.35em] uppercase text-slate-500 dark:text-slate-400">Marketplace</p>
            </div>
        </a>

        <div class="hidden lg:flex items-center gap-6">
            <?php echo renderMenuItems($mainMenuRoutes, $currentRoute); ?>
        </div>

        <div class="hidden lg:flex items-center gap-4" id="auth-section">
            <!-- This will be populated by JavaScript -->
        </div>

        <button id="mobileMenuButton" class="lg:hidden inline-flex h-11 w-11 items-center justify-center rounded-full border border-slate-200 bg-white text-slate-700 shadow-sm hover:bg-slate-50 transition dark:border-slate-800 dark:bg-slate-950 dark:text-slate-200" aria-expanded="false" aria-label="Toggle navigation menu">
            <span class="material-symbols-outlined">menu</span>
        </button>
    </div>

    <div id="mobileMenu" class="lg:hidden hidden border-t border-slate-200/70 dark:border-slate-800 bg-white/95 dark:bg-slate-950/95 backdrop-blur-xl">
        <div class="px-6 py-5 space-y-3">
            <?php echo renderMenuItems($mainMenuRoutes, $currentRoute, 0, true); ?>
            <div class="flex flex-col gap-3 pt-2 border-t border-slate-200/70 dark:border-slate-800" id="mobile-auth-section">
                <!-- This will be populated by JavaScript -->
            </div>
        </div>
    </div>
    <script>
        const mobileMenuButton = document.getElementById('mobileMenuButton');
        const mobileMenu = document.getElementById('mobileMenu');

        mobileMenuButton.addEventListener('click', () => {
            const expanded = mobileMenuButton.getAttribute('aria-expanded') === 'true';
            mobileMenuButton.setAttribute('aria-expanded', String(!expanded));
            mobileMenu.classList.toggle('hidden');
        });

        document.querySelectorAll('.desktop-submenu-toggle, .mobile-submenu-toggle').forEach((toggleButton) => {
            toggleButton.addEventListener('click', (event) => {
                event.preventDefault();
                event.stopPropagation();
                const submenuId = toggleButton.getAttribute('data-submenu-id');
                const submenu = document.getElementById(submenuId);
                if (!submenu) return;

                const isExpanded = toggleButton.getAttribute('aria-expanded') === 'true';
                toggleButton.setAttribute('aria-expanded', String(!isExpanded));
                submenu.classList.toggle('hidden');
                toggleButton.classList.toggle('rotate-180');
            });
        });

        document.addEventListener('click', (event) => {
            const isToggle = event.target.closest('.desktop-submenu-toggle, .mobile-submenu-toggle');
            const isSubmenu = event.target.closest('.desktop-submenu, .mobile-submenu');
            if (!isToggle && !isSubmenu) {
                document.querySelectorAll('.desktop-submenu, .mobile-submenu').forEach((submenu) => {
                    submenu.classList.add('hidden');
                });
                document.querySelectorAll('.desktop-submenu-toggle, .mobile-submenu-toggle').forEach((button) => {
                    button.setAttribute('aria-expanded', 'false');
                    button.classList.remove('rotate-180');
                });
            }
        });

        // Check authentication status and update navbar
        async function updateAuthStatus() {
            const authSection = document.getElementById('auth-section');
            const mobileAuthSection = document.getElementById('mobile-auth-section');
            try {
                const response = await fetch('/api/auth.php?action=check');
                const result = await response.json();

                if (result.success && result.user) {
                    // User is logged in
                    const user = result.user;
                    const replyCount = Number(result.reply_count || 0);
                    const feedbackReplyCount = Number(result.feedback_reply_count || 0);
                    const messageReplyCount = Number(result.message_reply_count || 0);
                    const replyBadgeClass = replyCount > 0
                        ? 'bg-emerald-500 text-white'
                        : 'bg-slate-200 text-slate-700 dark:bg-slate-800 dark:text-slate-200';
                    const replyBadge = `<span class="inline-flex min-w-[1.35rem] items-center justify-center rounded-full px-1.5 py-0.5 text-[10px] font-bold ${replyBadgeClass}">${replyCount}</span>`;

                    // Update FAB badges
                    const fabFbBtn = document.getElementById('fab-feedback-btn');
                    const fabMsgBtn = document.getElementById('fab-message-btn');
                    const fabFbBadge = document.getElementById('fab-feedback-badge');
                    const fabMsgBadge = document.getElementById('fab-message-badge');
                    if (fabFbBtn) fabFbBtn.href = '/user#feedback-center';
                    if (fabMsgBtn) fabMsgBtn.href = '/user#message-center';
                    if (fabFbBadge) {
                        fabFbBadge.textContent = feedbackReplyCount > 0 ? feedbackReplyCount : '';
                        fabFbBadge.classList.toggle('hidden', feedbackReplyCount === 0);
                    }
                    if (fabMsgBadge) {
                        fabMsgBadge.textContent = messageReplyCount > 0 ? messageReplyCount : '';
                        fabMsgBadge.classList.toggle('hidden', messageReplyCount === 0);
                    }

                    authSection.innerHTML = `
                        <a href="/user#feedback-center" class="relative inline-flex h-11 w-11 items-center justify-center rounded-full border border-slate-200 bg-white text-slate-700 shadow-sm hover:-translate-y-0.5 hover:border-primary hover:text-primary transition dark:border-slate-800 dark:bg-slate-950 dark:text-slate-200 dark:hover:text-white" title="My Account">
                            <span class="material-symbols-outlined" style="font-variation-settings: 'FILL' 1; font-size: 20px;">person</span>
                            <span class="absolute -top-1 -right-1">${replyBadge}</span>
                        </a>
                        <a href="/order-status" class="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-700 shadow-sm hover:-translate-y-0.5 hover:border-primary hover:text-primary transition dark:border-slate-800 dark:bg-slate-950 dark:text-slate-200 dark:hover:text-white">
                            <span class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-gradient-to-br from-primary to-secondary text-white shadow-sm shadow-primary/20">
                                <span class="material-symbols-outlined" style="font-variation-settings: 'FILL' 1; font-size: 18px;">inventory_2</span>
                            </span>
                            <span>My Orders</span>
                        </a>
                        <div class="inline-flex items-center gap-3 rounded-full border border-slate-200 bg-white px-3 py-2 shadow-sm dark:border-slate-800 dark:bg-slate-950">
                            <span class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-slate-100 text-slate-700 dark:bg-slate-900 dark:text-slate-200">${(user.first_name || user.username || 'U').charAt(0).toUpperCase()}</span>
                            <span class="text-slate-600 dark:text-slate-300 text-sm">${user.first_name || user.username}</span>
                            <button onclick="logout()" class="inline-flex h-9 w-9 items-center justify-center rounded-full border border-slate-200 bg-white text-slate-700 shadow-sm hover:border-red-300 hover:text-red-600 focus:outline-none focus:ring-2 focus:ring-red-200 transition dark:border-slate-800 dark:bg-slate-950 dark:text-slate-200 dark:hover:text-red-300 dark:focus:ring-red-700" title="Logout">
                                <span class="material-symbols-outlined">logout</span>
                            </button>
                        </div>
                        <a href="/cart" class="relative inline-flex items-center justify-center rounded-full border border-slate-200 bg-white px-3 py-2 text-slate-700 shadow-sm hover:-translate-y-0.5 hover:border-primary hover:text-primary transition dark:border-slate-800 dark:bg-slate-950 dark:text-slate-200 dark:hover:text-white">
                            <span class="material-symbols-outlined">shopping_cart</span>
                            <span id="cart-count" class="absolute -top-1 -right-1 inline-flex h-5 min-w-[1.25rem] items-center justify-center rounded-full bg-secondary text-[10px] font-bold text-on-secondary px-1.5"></span>
                        </a>
                    `;

                    mobileAuthSection.innerHTML = `
                        <div class="px-4 py-2 text-sm text-slate-600 dark:text-slate-400 border-b border-slate-200/70 dark:border-slate-800">
                            Hello, ${user.first_name || user.username}
                        </div>
                        <a href="/user#feedback-center" class="flex items-center justify-center gap-2 rounded-3xl border border-slate-200 px-4 py-3 text-sm font-medium text-slate-700 hover:border-primary hover:text-primary transition dark:border-slate-800 dark:text-slate-200">
                            <span class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-slate-100 text-primary dark:bg-slate-900">
                                <span class="material-symbols-outlined" style="font-variation-settings: 'FILL' 1; font-size: 18px;">person</span>
                            </span>
                            My Account
                            ${replyBadge}
                        </a>
                        <a href="/order-status" class="flex items-center justify-center gap-2 rounded-3xl border border-slate-200 px-4 py-3 text-sm font-medium text-slate-700 hover:border-primary hover:text-primary transition dark:border-slate-800 dark:text-slate-200">
                            <span class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-gradient-to-br from-primary to-secondary text-white shadow-sm shadow-primary/20">
                                <span class="material-symbols-outlined" style="font-variation-settings: 'FILL' 1; font-size: 18px;">inventory_2</span>
                            </span>
                            My Orders
                        </a>
                        <button onclick="logout()" class="flex w-full items-center justify-center gap-2 rounded-3xl bg-red-600 px-4 py-3 text-sm font-semibold text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-200 transition dark:focus:ring-red-700" title="Logout">
                            <span class="material-symbols-outlined">logout</span>
                            <span>Logout</span>
                        </button>
                        <a href="/cart" class="flex items-center justify-center gap-2 rounded-3xl border border-slate-200 px-4 py-3 text-sm font-medium text-slate-700 hover:border-primary hover:text-primary transition dark:border-slate-800 dark:text-slate-200">
                            <span class="material-symbols-outlined">shopping_cart</span>
                            Cart
                        </a>
                    `;

                } else {
                    // User is not logged in
                    authSection.innerHTML = `
                        <a href="/login?next=%2Fuser%23feedback-center" class="inline-flex h-11 w-11 items-center justify-center rounded-full border border-slate-200 bg-white text-slate-700 shadow-sm hover:-translate-y-0.5 hover:border-primary hover:text-primary transition dark:border-slate-800 dark:bg-slate-950 dark:text-slate-200 dark:hover:text-white" title="Send feedback">
                            <span class="material-symbols-outlined" style="font-variation-settings: 'FILL' 1; font-size: 20px;">rate_review</span>
                        </a>
                        <a href="/contact" class="inline-flex h-11 w-11 items-center justify-center rounded-full border border-slate-200 bg-white text-slate-700 shadow-sm hover:-translate-y-0.5 hover:border-primary hover:text-primary transition dark:border-slate-800 dark:bg-slate-950 dark:text-slate-200 dark:hover:text-white" title="Send message">
                            <span class="material-symbols-outlined" style="font-variation-settings: 'FILL' 1; font-size: 20px;">mail</span>
                        </a>
                        <a href="/login" class="rounded-full bg-primary px-5 py-2.5 text-sm font-semibold text-white shadow-sm shadow-primary/20 hover:bg-primary/90 transition">Login</a>
                        <a href="/cart" class="relative inline-flex items-center justify-center rounded-full border border-slate-200 bg-white px-3 py-2 text-slate-700 shadow-sm hover:border-primary hover:text-primary transition dark:border-slate-800 dark:bg-slate-950 dark:text-slate-200 dark:hover:text-white">
                            <span class="material-symbols-outlined">shopping_cart</span>
                            <span id="cart-count" class="absolute -top-1 -right-1 inline-flex h-5 min-w-[1.25rem] items-center justify-center rounded-full bg-secondary text-[10px] font-bold text-on-secondary px-1.5"></span>
                        </a>
                    `;

                    mobileAuthSection.innerHTML = `
                        <div class="grid grid-cols-2 gap-3">
                            <a href="/login?next=%2Fuser%23feedback-center" class="flex items-center justify-center gap-2 rounded-3xl border border-slate-200 px-4 py-3 text-sm font-medium text-slate-700 hover:border-primary hover:text-primary transition dark:border-slate-800 dark:text-slate-200">
                                <span class="material-symbols-outlined" style="font-variation-settings: 'FILL' 1; font-size: 20px;">rate_review</span>
                                Feedback
                            </a>
                            <a href="/contact" class="flex items-center justify-center gap-2 rounded-3xl border border-slate-200 px-4 py-3 text-sm font-medium text-slate-700 hover:border-primary hover:text-primary transition dark:border-slate-800 dark:text-slate-200">
                                <span class="material-symbols-outlined" style="font-variation-settings: 'FILL' 1; font-size: 20px;">mail</span>
                                Message
                            </a>
                        </div>
                        <a href="/login" class="block rounded-3xl bg-primary px-4 py-3 text-center text-sm font-semibold text-white hover:bg-primary/90 transition">Login</a>
                        <a href="/cart" class="flex items-center justify-center gap-2 rounded-3xl border border-slate-200 px-4 py-3 text-sm font-medium text-slate-700 hover:border-primary hover:text-primary transition dark:border-slate-800 dark:text-slate-200">
                            <span class="material-symbols-outlined">shopping_cart</span>
                            Cart
                        </a>
                    `;
                }
            } catch (error) {
                console.error('Auth check failed:', error);
                // Default to logged out state
                authSection.innerHTML = `
                    <a href="/login?next=%2Fuser%23feedback-center" class="inline-flex h-11 w-11 items-center justify-center rounded-full border border-slate-200 bg-white text-slate-700 shadow-sm hover:-translate-y-0.5 hover:border-primary hover:text-primary transition dark:border-slate-800 dark:bg-slate-950 dark:text-slate-200 dark:hover:text-white" title="Send feedback">
                        <span class="material-symbols-outlined" style="font-variation-settings: 'FILL' 1; font-size: 20px;">rate_review</span>
                    </a>
                    <a href="/contact" class="inline-flex h-11 w-11 items-center justify-center rounded-full border border-slate-200 bg-white text-slate-700 shadow-sm hover:-translate-y-0.5 hover:border-primary hover:text-primary transition dark:border-slate-800 dark:bg-slate-950 dark:text-slate-200 dark:hover:text-white" title="Send message">
                        <span class="material-symbols-outlined" style="font-variation-settings: 'FILL' 1; font-size: 20px;">mail</span>
                    </a>
                    <a href="/login" class="rounded-full bg-primary px-5 py-2.5 text-sm font-semibold text-white shadow-sm shadow-primary/20 hover:bg-primary/90 transition">Login</a>
                    <a href="/cart" class="relative inline-flex items-center justify-center rounded-full border border-slate-200 bg-white px-3 py-2 text-slate-700 shadow-sm hover:border-primary hover:text-primary transition dark:border-slate-800 dark:bg-slate-950 dark:text-slate-200 dark:hover:text-white">
                        <span class="material-symbols-outlined">shopping_cart</span>
                        <span id="cart-count" class="absolute -top-1 -right-1 inline-flex h-5 min-w-[1.25rem] items-center justify-center rounded-full bg-secondary text-[10px] font-bold text-on-secondary px-1.5"></span>
                    </a>
                `;
                mobileAuthSection.innerHTML = `
                    <div class="grid grid-cols-2 gap-3">
                        <a href="/login?next=%2Fuser%23feedback-center" class="flex items-center justify-center gap-2 rounded-3xl border border-slate-200 px-4 py-3 text-sm font-medium text-slate-700 hover:border-primary hover:text-primary transition dark:border-slate-800 dark:text-slate-200">
                            <span class="material-symbols-outlined" style="font-variation-settings: 'FILL' 1; font-size: 20px;">rate_review</span>
                            Feedback
                        </a>
                        <a href="/contact" class="flex items-center justify-center gap-2 rounded-3xl border border-slate-200 px-4 py-3 text-sm font-medium text-slate-700 hover:border-primary hover:text-primary transition dark:border-slate-800 dark:text-slate-200">
                            <span class="material-symbols-outlined" style="font-variation-settings: 'FILL' 1; font-size: 20px;">mail</span>
                            Message
                        </a>
                    </div>
                    <a href="/login" class="block rounded-3xl bg-primary px-4 py-3 text-center text-sm font-semibold text-white hover:bg-primary/90 transition">Login</a>
                    <a href="/cart" class="flex items-center justify-center gap-2 rounded-3xl border border-slate-200 px-4 py-3 text-sm font-medium text-slate-700 hover:border-primary hover:text-primary transition dark:border-slate-800 dark:text-slate-200">
                        <span class="material-symbols-outlined">shopping_cart</span>
                        Cart
                    </a>
                `;
            }
        }

        async function logout() {
            try {
                const response = await fetch('/api/auth.php?action=logout', {
                    method: 'POST'
                });
                const result = await response.json();

                if (result.success) {
                    localStorage.removeItem('user');
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            title: 'Success',
                            text: 'You have successfully logged out.',
                            icon: 'success',
                            timer: 1500,
                            showConfirmButton: false
                        });
                    }
                    setTimeout(() => {
                        window.location.href = '/home';
                    }, 1000);
                }
            } catch (error) {
                console.error('Logout failed:', error);
                // Force logout on client side
                localStorage.removeItem('user');
                window.location.href = '/home';
            }
        }

        function updateCartCount() {
            const cart = JSON.parse(localStorage.getItem('cart') || '[]');
            const count = cart.reduce((sum, item) => sum + item.qty, 0);
            document.querySelectorAll('#cart-count').forEach(el => {
                el.textContent = count > 0 ? count : '';
            });
        }

        // Initialize auth status on page load
        document.addEventListener('DOMContentLoaded', function() {
            updateAuthStatus();
            updateCartCount();
        });
    </script>
</nav>

<!-- FAB: Feedback & Message — bottom-right corner (shown on all pages) -->
<div class="fixed bottom-6 right-6 z-[60] flex flex-col-reverse items-end gap-3" id="fab-support-dock">
    <!-- Message button -->
    <a id="fab-message-btn" href="/contact"
       class="group relative flex items-center justify-center rounded-full bg-slate-900 text-white shadow-xl hover:bg-primary hover:scale-110 active:scale-95 transition-all duration-200"
       style="width:3.25rem;height:3.25rem;"
       title="Send Message">
        <span class="material-symbols-outlined" style="font-variation-settings:'FILL' 1;font-size:22px;">mail</span>
        <span id="fab-message-badge" class="hidden absolute -top-1.5 -right-1.5 inline-flex min-w-[1.35rem] items-center justify-center rounded-full bg-blue-500 px-1.5 py-0.5 text-[10px] font-bold text-white shadow"></span>
        <span class="pointer-events-none absolute right-full mr-3 whitespace-nowrap rounded-full bg-slate-900 px-3 py-1.5 text-xs font-semibold text-white opacity-0 group-hover:opacity-100 transition-opacity shadow-lg">Message</span>
    </a>
    <!-- Feedback button -->
    <a id="fab-feedback-btn" href="/login?next=%2Fuser%23feedback-center"
       class="group relative flex items-center justify-center rounded-full bg-primary text-white shadow-xl hover:bg-primary/90 hover:scale-110 active:scale-95 transition-all duration-200"
       style="width:3.25rem;height:3.25rem;"
       title="Send Feedback">
        <span class="material-symbols-outlined" style="font-variation-settings:'FILL' 1;font-size:22px;">rate_review</span>
        <span id="fab-feedback-badge" class="hidden absolute -top-1.5 -right-1.5 inline-flex min-w-[1.35rem] items-center justify-center rounded-full bg-emerald-500 px-1.5 py-0.5 text-[10px] font-bold text-white shadow"></span>
        <span class="pointer-events-none absolute right-full mr-3 whitespace-nowrap rounded-full bg-slate-900 px-3 py-1.5 text-xs font-semibold text-white opacity-0 group-hover:opacity-100 transition-opacity shadow-lg">Feedback</span>
    </a>
</div>


