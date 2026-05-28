<?php
// pages/admin/login.php
// Admin Login Page

require_once __DIR__ . '/../../config/bootstrap.php';

use StudentAttendance\Utils\AdminAuth;

if (AdminAuth::check()) {
    header('Location: /pages/admin/dashboard.php');
    exit;
}

$error = '';

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');
    
    if (empty($email) || empty($password)) {
        $error = 'Please enter both email and password';
    } else {
        // For demo purposes, we'll use a simple admin account
        // In production, use a separate admin table or proper auth service
        $adminEmail = 'admin@school.edu';
        $adminPassword = 'admin123'; // In production: password_hash()
        
        if ($email === $adminEmail && $password === $adminPassword) {
            $_SESSION['admin_id'] = 'admin_001';
            $_SESSION['admin_email'] = $email;
            $_SESSION['admin_logged_in'] = true;
            
            header('Location: /pages/admin/dashboard.php');
            exit;
        } else {
            $error = 'Invalid email or password';
        }
    }
}

$pageTitle = 'Admin Login';
$assetContext = 'admin';
$pageStyles = 'body { background: linear-gradient(135deg, #059669 0%, #047857 100%); }';
require __DIR__ . '/../../components/ui/head.php';
?>
<body class="flex items-center justify-center min-h-screen p-4">
    <div class="w-full max-w-md">
        <!-- Card -->
        <div class="bg-white rounded-lg shadow-2xl overflow-hidden">
            <!-- Header -->
            <div class="bg-gradient-to-r from-green-600 to-green-700 px-6 py-8 text-center">
                <div class="flex justify-center mb-4">
                    <span class="material-symbols-outlined text-5xl text-white">admin_panel_settings</span>
                </div>
                <h1 class="text-2xl font-bold text-white">EduAttend Admin</h1>
                <p class="text-green-100 text-sm mt-2">Staff Access Portal</p>
            </div>

            <!-- Form -->
            <form method="POST" class="px-6 py-8 space-y-6">
                <!-- Error Message -->
                <?php if (!empty($error)): ?>
                <div class="bg-red-50 border border-red-200 rounded-lg p-4 flex gap-3">
                    <span class="material-symbols-outlined text-red-600">error</span>
                    <div>
                        <p class="font-bold text-red-900">Error</p>
                        <p class="text-sm text-red-700"><?php echo htmlspecialchars($error); ?></p>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Email Input -->
                <div>
                    <label for="email" class="block text-sm font-bold text-gray-900 mb-2">
                        Email Address
                    </label>
                    <input 
                        type="email" 
                        id="email"
                        name="email" 
                        placeholder="admin@school.edu"
                        value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-600 focus:border-transparent transition"
                        required
                    />
                </div>

                <!-- Password Input -->
                <div>
                    <label for="password" class="block text-sm font-bold text-gray-900 mb-2">
                        Password
                    </label>
                    <div class="relative">
                        <input 
                            type="password" 
                            id="password"
                            name="password"
                            placeholder="••••••••"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-600 focus:border-transparent transition"
                            required
                        />
                        <button 
                            type="button" 
                            class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-600 hover:text-gray-900"
                            onclick="togglePassword()"
                        >
                            <span class="material-symbols-outlined" id="toggle-icon">visibility</span>
                        </button>
                    </div>
                </div>

                <!-- Login Button -->
                <button 
                    type="submit"
                    class="w-full bg-green-600 hover:bg-green-700 active:scale-95 text-white font-bold py-2.5 rounded-lg transition duration-150 flex items-center justify-center gap-2"
                >
                    <span class="material-symbols-outlined">login</span>
                    Sign In
                </button>

                <!-- Demo Credentials -->
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                    <p class="text-sm font-bold text-blue-900 mb-2">Demo Credentials:</p>
                    <p class="text-xs text-blue-700 mb-1"><strong>Email:</strong> admin@school.edu</p>
                    <p class="text-xs text-blue-700"><strong>Password:</strong> admin123</p>
                </div>
            </form>

            <!-- Footer -->
            <div class="bg-gray-50 px-6 py-4 border-t border-gray-200 text-center text-sm text-gray-600">
                <p>For staff attendance marking only</p>
                <a href="/pages/student/login.php" class="text-green-600 font-bold hover:underline mt-2 block">
                    Student Login →
                </a>
                <a href="/pages/teacher/login.php" class="text-sky-600 font-bold hover:underline mt-1 block">
                    Teacher Login →
                </a>
            </div>
        </div>
    </div>

    <script>
        function togglePassword() {
            const input = document.getElementById('password');
            const icon = document.getElementById('toggle-icon');
            
            if (input.type === 'password') {
                input.type = 'text';
                icon.textContent = 'visibility_off';
            } else {
                input.type = 'password';
                icon.textContent = 'visibility';
            }
        }
    </script>
<?php require __DIR__ . '/../../components/ui/scripts.php'; ?>
</body>
</html>
