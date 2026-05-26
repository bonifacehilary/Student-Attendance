<?php
// pages/student/login.php
// Student Login Screen for EduAttend

require_once __DIR__ . '/../../config/bootstrap.php';
require_once __DIR__ . '/../../utils/Database.php';
require_once __DIR__ . '/../../utils/Utility.php';
require_once __DIR__ . '/../../utils/Router.php';

session_start();

// Handle POST login
$loginError = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $identifier = trim($_POST['identifier'] ?? '');
    $password = $_POST['password'] ?? '';
    $remember = isset($_POST['remember']);

    if ($identifier && $password) {
        try {
            $student = Utility::safeQuery(
                'SELECT id, email, admission_number, password_hash, name FROM students WHERE email = ? OR admission_number = ? LIMIT 1',
                [$identifier, $identifier],
                'SELECT',
                true
            );

            if ($student && password_verify($password, $student['password_hash'])) {
                $_SESSION['student_id'] = $student['id'];
                $_SESSION['student_name'] = $student['name'];
                if ($remember) {
                    setcookie('student_id', $student['id'], time() + 60*60*24*30, '/');
                }
                
                // Log activity
                try {
                    Utility::insert('activity_logs', [
                        'user_id' => $student['id'],
                        'action' => 'student_login',
                        'details' => 'Student logged in',
                        'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'Unknown',
                        'timestamp' => date('Y-m-d H:i:s')
                    ]);
                } catch (\Throwable $e) {
                    // Log activity table might not exist, continue anyway
                }
                
                // Redirect to splash screen for smooth loading experience
                header('Location: /pages/student/splash.php');
                exit;
            } else {
                $loginError = 'Invalid credentials. Please try again.';
            }
        } catch (\Throwable $e) {
            error_log('Login error: ' . $e->getMessage());
            $loginError = 'An error occurred. Please try again later.';
        }
    } else {
        $loginError = 'Please fill in all fields.';
    }
}

?><!DOCTYPE html>
<html class="light" lang="en">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>Student Login | EduAttend</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <style>
        .material-symbols-outlined {
            font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
        }
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f7f9fb;
        }
        .login-card {
            box-shadow: 0 2px 4px rgba(15, 23, 42, 0.05);
            border: 1px solid #e2e8f0;
        }
        .input-focus:focus-within {
            border-color: #10b981;
            box-shadow: 0 0 0 2px rgba(16, 185, 129, 0.1);
        }
    </style>
</head>
<body class="min-h-screen flex flex-col items-center justify-center p-container-padding-mobile md:p-container-padding-desktop">
<main class="w-full max-w-md">
    <div class="flex flex-col items-center mb-8 animate-in fade-in duration-700 slide-in-from-bottom-4">
        <img alt="EduAttend Logo" class="h-16 w-auto mb-4" src="https://lh3.googleusercontent.com/aida/ADBb0ujC9zHEev6NNJRZO9EQSofPWF8KdiIhpyJRPj8ERZM34TeeMisaFGv1e3bPD_rKJ6uZIf29bNuKntFqLR9nxKHrq5oljWILi73fSgVGZtRrQppcjA6J82J8BZ00op5nn9NiXeKBwq0IN9w4097lMm8e2Sd2MgyF1HPSdGRJPFU-w3zMJMhtK4gWMNdw7HtPwTFfkY1a_bCS1apr7-62hqazrmDCnah88Z-qaqpUWgvVXWenOWhxtYixt5s"/>
        <h1 class="font-headline-md text-headline-md-mobile md:text-headline-md text-on-surface font-bold tracking-tight">Student Login</h1>
        <p class="font-body-md text-secondary mt-1">Access your attendance records and dashboard</p>
    </div>
    <section class="login-card bg-white rounded-xl p-8 transition-all hover:shadow-md">
        <?php if ($loginError): ?>
            <div class="mb-4 text-red-600 text-center font-bold"><?php echo htmlspecialchars($loginError); ?></div>
        <?php endif; ?>
        <form class="space-y-6" id="loginForm" method="post" autocomplete="off">
            <div class="space-y-2">
                <label class="block font-label-md text-on-surface-variant" for="identifier">Email or Admission Number</label>
                <div class="relative flex items-center border border-outline-variant rounded-lg bg-white input-focus transition-all group">
                    <span class="material-symbols-outlined absolute left-3 text-secondary group-focus-within:text-primary">person</span>
                    <input class="w-full py-3 pl-10 pr-4 bg-transparent border-none rounded-lg focus:ring-0 text-on-surface font-body-md placeholder:text-outline" id="identifier" name="identifier" placeholder="e.g. STU12345" required type="text" value="<?php echo htmlspecialchars($_POST['identifier'] ?? ''); ?>"/>
                </div>
            </div>
            <div class="space-y-2">
                <div class="flex justify-between items-center">
                    <label class="block font-label-md text-on-surface-variant" for="password">Password</label>
                    <a class="font-label-sm text-primary hover:underline transition-colors" href="/pages/student/forgot-password.php">Forgot password?</a>
                </div>
                <div class="relative flex items-center border border-outline-variant rounded-lg bg-white input-focus transition-all group">
                    <span class="material-symbols-outlined absolute left-3 text-secondary group-focus-within:text-primary">lock</span>
                    <input class="w-full py-3 pl-10 pr-12 bg-transparent border-none rounded-lg focus:ring-0 text-on-surface font-body-md" id="password" name="password" placeholder="••••••••" required type="password"/>
                    <button class="absolute right-3 flex items-center justify-center p-1 text-secondary hover:text-primary transition-colors" id="togglePassword" type="button">
                        <span class="material-symbols-outlined" id="eyeIcon">visibility</span>
                    </button>
                </div>
            </div>
            <div class="flex items-center">
                <input class="w-4 h-4 rounded border-outline-variant text-primary focus:ring-primary-container" id="rememberMe" name="remember" type="checkbox"/>
                <label class="ml-2 font-label-md text-on-surface-variant cursor-pointer select-none" for="rememberMe">Remember me for 30 days</label>
            </div>
            <button class="w-full bg-primary-container text-on-primary-container font-label-md font-bold py-3 rounded-lg hover:brightness-95 active:scale-95 transition-all shadow-sm" type="submit">
                Login to Dashboard
            </button>
        </form>
        <div class="relative my-8">
            <div class="absolute inset-0 flex items-center">
                <span class="w-full border-t border-surface-variant"></span>
            </div>
            <div class="relative flex justify-center text-xs">
                <span class="bg-white px-2 text-outline font-label-sm">OR SIGN IN WITH</span>
            </div>
        </div>
        <button class="w-full flex items-center justify-center gap-2 border border-outline-variant py-3 rounded-lg font-label-md text-secondary hover:bg-surface transition-colors mb-4" type="button" disabled>
            <span class="material-symbols-outlined">google</span>
            School Google Account
        </button>
    </section>
    <footer class="mt-8 text-center space-y-4">
        <p class="font-label-sm text-on-surface-variant">
            Facing issues logging in? 
            <a class="text-primary font-bold hover:underline" href="/pages/contact.php">Contact Administration</a>
        </p>
        <div class="flex justify-center space-x-6 text-outline font-label-sm">
            <a class="hover:text-secondary transition-colors" href="/pages/privacy.php">Privacy Policy</a>
            <a class="hover:text-secondary transition-colors" href="/pages/user-guide.php">User Guide</a>
            <a class="hover:text-secondary transition-colors" href="/pages/status.php">System Status</a>
        </div>
    </footer>
</main>
<div class="fixed top-0 left-0 w-full h-full -z-10 overflow-hidden pointer-events-none">
    <div class="absolute top-[-10%] right-[-5%] w-96 h-96 bg-primary opacity-5 blur-[120px] rounded-full"></div>
    <div class="absolute bottom-[-10%] left-[-5%] w-96 h-96 bg-secondary opacity-5 blur-[120px] rounded-full"></div>
</div>
<script>
    // Password Visibility Toggle
    const passwordInput = document.getElementById('password');
    const toggleBtn = document.getElementById('togglePassword');
    const eyeIcon = document.getElementById('eyeIcon');
    if (toggleBtn) {
        toggleBtn.addEventListener('click', () => {
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);
            eyeIcon.textContent = type === 'password' ? 'visibility' : 'visibility_off';
        });
    }
</script>
</body>
</html>
