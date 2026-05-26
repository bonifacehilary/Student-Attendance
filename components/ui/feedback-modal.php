<!-- Floating Feedback Button -->
<div id="feedback-floating-btn" class="fixed bottom-6 right-6 z-40">
    <button 
        onclick="openFeedbackModal()" 
        class="inline-flex items-center justify-center gap-2 rounded-full bg-gradient-to-br from-primary to-cyan-500 px-4 py-3 text-white font-semibold shadow-lg shadow-primary/30 hover:shadow-xl hover:shadow-primary/40 transition-all duration-300 hover:scale-110 group"
        title="Send Feedback"
    >
        <span class="material-symbols-outlined text-xl group-hover:animate-bounce">chat_bubble</span>
        <span class="hidden sm:inline">Feedback</span>
    </button>
</div>

<!-- Feedback Modal Overlay -->
<div id="feedback-modal-overlay" class="hidden fixed inset-0 bg-black/40 backdrop-blur-sm z-50 flex items-center justify-center p-4 transition-opacity duration-300">
    <!-- Modal Content -->
    <div class="bg-white dark:bg-slate-950 rounded-3xl shadow-2xl max-w-md w-full overflow-hidden transform transition-all duration-300 scale-100 opacity-100">
        <!-- Modal Header -->
        <div class="bg-gradient-to-r from-primary/90 to-cyan-500/90 px-6 py-5 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <span class="material-symbols-outlined text-2xl text-white">chat_bubble_outline</span>
                <div>
                    <h2 class="text-lg font-bold text-white">Send Feedback</h2>
                    <p class="text-xs text-white/80">We'd love to hear from you</p>
                </div>
            </div>
            <button 
                onclick="closeFeedbackModal()" 
                class="text-white hover:bg-white/20 p-1 rounded-lg transition"
            >
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>

        <!-- Modal Body -->
        <div class="p-6 space-y-4">
            <form id="modal-customer-feedback-form" class="grid gap-4">
                <div>
                    <label class="block text-sm font-semibold text-slate-700 dark:text-slate-200 mb-2">Your Name</label>
                    <input 
                        name="name" 
                        type="text" 
                        placeholder="Enter your name" 
                        required 
                        class="w-full rounded-2xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-900 px-4 py-3 text-sm text-slate-900 dark:text-white placeholder:text-slate-400 focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/30 transition"
                    />
                </div>

                <div>
                    <label class="block text-sm font-semibold text-slate-700 dark:text-slate-200 mb-2">Your Email</label>
                    <input 
                        name="email" 
                        type="email" 
                        placeholder="Enter your email" 
                        required 
                        class="w-full rounded-2xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-900 px-4 py-3 text-sm text-slate-900 dark:text-white placeholder:text-slate-400 focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/30 transition"
                    />
                </div>

                <div>
                    <label class="block text-sm font-semibold text-slate-700 dark:text-slate-200 mb-2">Message</label>
                    <textarea 
                        name="message" 
                        placeholder="How can we help?" 
                        required 
                        rows="3"
                        class="w-full rounded-2xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-900 px-4 py-3 text-sm text-slate-900 dark:text-white placeholder:text-slate-400 focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/30 transition resize-none"
                    ></textarea>
                </div>

                <div id="modal-feedback-status" class="text-sm text-slate-600 dark:text-slate-400 h-5"></div>

                <div class="flex gap-3 pt-2">
                    <button 
                        type="button"
                        onclick="closeFeedbackModal()" 
                        class="flex-1 rounded-2xl border border-slate-200 dark:border-slate-700 px-4 py-3 text-sm font-semibold text-slate-700 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800 transition"
                    >
                        Cancel
                    </button>
                    <button 
                        type="submit"
                        class="flex-1 inline-flex items-center justify-center gap-2 rounded-2xl bg-gradient-to-r from-primary to-cyan-500 px-4 py-3 text-sm font-semibold text-white hover:shadow-lg hover:shadow-primary/30 transition"
                    >
                        <span>Send</span>
                        <span class="material-symbols-outlined text-base">send</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    function openFeedbackModal() {
        const overlay = document.getElementById('feedback-modal-overlay');
        overlay.classList.remove('hidden');
        document.body.style.overflow = 'hidden';
    }

    function closeFeedbackModal() {
        const overlay = document.getElementById('feedback-modal-overlay');
        overlay.classList.add('hidden');
        document.body.style.overflow = '';
    }

    // Close modal when clicking overlay (outside modal)
    document.getElementById('feedback-modal-overlay')?.addEventListener('click', function(e) {
        if (e.target === this) {
            closeFeedbackModal();
        }
    });

    // Handle form submission
    document.getElementById('modal-customer-feedback-form')?.addEventListener('submit', async function(e) {
        e.preventDefault();
        await submitModalFeedback(this);
    });

    async function submitModalFeedback(form) {
        const status = document.getElementById('modal-feedback-status');
        const submitBtn = form.querySelector('button[type="submit"]');
        
        const name = form.name.value.trim();
        const email = form.email.value.trim();
        const message = form.message.value.trim();

        if (!name || !email || !message) {
            status.textContent = 'Please fill in all fields';
            status.className = 'text-sm text-error h-5';
            return;
        }

        status.textContent = 'Sending...';
        status.className = 'text-sm text-slate-400 h-5';
        submitBtn.disabled = true;
        submitBtn.classList.add('opacity-50');

        try {
            const response = await fetch('/api/comments.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ name, email, message })
            });
            const result = await response.json();

            if (result.status === 'success') {
                status.textContent = 'Thank you! Your feedback has been received ✓';
                status.className = 'text-sm text-teal-600 dark:text-teal-400 font-semibold h-5';
                form.reset();
                setTimeout(() => {
                    closeFeedbackModal();
                    status.textContent = '';
                }, 2000);
            } else {
                status.textContent = result.message || 'Failed to send feedback';
                status.className = 'text-sm text-error h-5';
            }
        } catch (error) {
            console.error('Error:', error);
            status.textContent = 'Failed. Please try again';
            status.className = 'text-sm text-error h-5';
        } finally {
            submitBtn.disabled = false;
            submitBtn.classList.remove('opacity-50');
        }
    }
</script>
