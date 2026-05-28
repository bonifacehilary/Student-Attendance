<?php
// pages/student/splash.php
// Splash Screen - Shown on initial load

require_once __DIR__ . '/../../config/bootstrap.php';

// If already logged in, redirect to dashboard after brief delay
// Otherwise redirect to login
$redirectUrl = isset($_SESSION['student_id']) 
    ? '/pages/student/dashboard.php' 
    : '/pages/student/login.php';

$pageTitle = 'Loading';
$assetContext = 'student';
$pageStyles = <<<'CSS'
body, .brand-bg { background-color: #0f172a; }
@keyframes custom-spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }
.animate-emerald-spin { animation: custom-spin 1s linear infinite; }
.fade-in { animation: fadeIn 0.8s ease-out forwards; }
@keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
CSS;
require __DIR__ . '/../../components/ui/head.php';
?>
<body class="brand-bg flex items-center justify-center min-h-screen overflow-hidden antialiased">
    <!-- Splash Container -->
    <main class="flex flex-col items-center justify-between h-screen py-16 px-4">
        <!-- Spacer for top balance -->
        <div class="h-12 w-full"></div>

        <!-- Center Branding Section -->
        <div class="flex flex-col items-center space-y-6 fade-in">
            <!-- Logo Section -->
            <div class="relative group">
                <div class="absolute -inset-1 bg-green-600/20 rounded-full blur transition duration-1000 group-hover:duration-200"></div>
                <img alt="EduAttend Logo" class="relative w-32 h-32 md:w-40 md:h-40 object-contain rounded-md cursor-pointer hover:scale-105 transition-transform" src="data:image/png;base64,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"/>
            </div>

            <!-- Brand Name & Tagline -->
            <div class="text-center space-y-2">
                <h1 class="font-bold text-3xl md:text-4xl text-white tracking-tight">
                    EduAttend
                </h1>
                <p class="text-sm text-slate-400 tracking-widest uppercase opacity-80">
                    Smart • Fast • Reliable
                </p>
            </div>
        </div>

        <!-- Bottom Loading Section -->
        <div class="flex flex-col items-center space-y-4 fade-in" style="animation-delay: 0.3s;">
            <div class="flex flex-col items-center space-y-3">
                <div class="w-6 h-6 border-2 border-green-600/20 border-t-green-600 rounded-full animate-emerald-spin"></div>
                <span class="text-sm text-slate-500 tracking-wide">
                    Loading students...
                </span>
            </div>
        </div>
    </main>

    <!-- Subtle Background Atmosphere -->
    <div class="fixed inset-0 pointer-events-none overflow-hidden">
        <div class="absolute top-[-10%] left-[-10%] w-[40%] h-[40%] bg-green-600/5 rounded-full blur-[120px]"></div>
        <div class="absolute bottom-[-10%] right-[-10%] w-[40%] h-[40%] bg-green-600/5 rounded-full blur-[120px]"></div>
    </div>

    <script>
        // Logo click interaction
        document.querySelector('img').addEventListener('click', function() {
            this.classList.add('scale-95');
            setTimeout(() => this.classList.remove('scale-95'), 150);
        });

        // Simulate loading text change
        setTimeout(() => {
            const loadingText = document.querySelector('span.text-slate-500');
            if(loadingText) {
                loadingText.style.opacity = '0';
                setTimeout(() => {
                    loadingText.innerText = 'Initializing dashboard...';
                    loadingText.style.opacity = '1';
                }, 300);
            }
        }, 2500);

        // Redirect after delay
        setTimeout(() => {
            window.location.href = '<?php echo $redirectUrl; ?>';
        }, 3500);
    </script>
<?php require __DIR__ . '/../../components/ui/scripts.php'; ?>
</body>
</html>
