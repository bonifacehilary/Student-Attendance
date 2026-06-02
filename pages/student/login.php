<?php
// pages/student/login.php
// Student Login Screen for EduAttend

require_once __DIR__ . '/../../config/bootstrap.php';

use StudentAttendance\Utils\UserManagement;

UserManagement::ensureSchema();

if (!empty($_SESSION['student_id'])) {
    header('Location: /pages/student/dashboard.php');
    exit;
}

// Database is seeded by scratch/setup_local.php
// No need to seed on every login page load

// Handle POST login
$loginError = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $identifier = trim($_POST['identifier'] ?? '');
    $password = $_POST['password'] ?? '';
    $remember = isset($_POST['remember']);

    if ($identifier && $password) {
        try {
            $student = Utility::safeQuery(
                'SELECT id, email, admission_number, password_hash, name, COALESCE(is_active, 1) AS is_active FROM students WHERE email = ? OR admission_number = ? LIMIT 1',
                [$identifier, $identifier],
                'SELECT',
                true
            );

            if ($student && empty($student['is_active'])) {
                $loginError = 'This student account is deactivated. Contact administration.';
            } elseif ($student && password_verify($password, $student['password_hash'])) {
                $_SESSION['student_id'] = (int) $student['id'];
                $_SESSION['student_name'] = (string) $student['name'];

                if ($remember) {
                    setcookie('student_id', (string) $student['id'], time() + 60*60*24*30, '/');
                }

                try {
                    Utility::insert('activity_logs', [
                        'user_id' => (int) $student['id'],
                        'action' => 'student_login',
                        'details' => 'Student logged in',
                        'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'Unknown',
                        'timestamp' => date('Y-m-d H:i:s')
                    ]);
                } catch (\Throwable $e) {
                    error_log('Activity log insert skipped: ' . $e->getMessage());
                }

                header('Location: /pages/student/splash.php');
                exit;
            } else {
                $loginError = $student
                    ? 'Incorrect password. Please try again.'
                    : 'No student account found. Check your email or admission number.';
            }
        } catch (\Throwable $e) {
            error_log('Login error: ' . $e->getMessage());
            $msg = $e->getMessage();
            
            if (str_contains($msg, 'no such table') || str_contains($msg, "doesn't exist")) {
                $loginError = 'Database tables not set up. Run: php scratch/setup_local.php';
            } else {
                $loginError = 'Unable to process login. Please try again later.';
            }
        }
    } else {
        $loginError = 'Please enter both email/admission number and password.';
    }
}

$pageTitle = 'Student Login';
$assetContext = 'student';
$pageStyles = <<<'CSS'
.login-card { box-shadow: 0 2px 4px rgba(15, 23, 42, 0.05); border: 1px solid #e2e8f0; }
.input-focus:focus-within { border-color: #10b981; box-shadow: 0 0 0 2px rgba(16, 185, 129, 0.1); }
CSS;
require __DIR__ . '/../../components/ui/head.php';
?>
<body class="min-h-screen flex flex-col items-center justify-center p-4 md:p-8">
<main class="w-full max-w-md">
    <div class="flex flex-col items-center mb-8 animate-in fade-in duration-700 slide-in-from-bottom-4">
        <img alt="EduAttend Logo" class="h-16 w-auto mb-4" src="https://lh3.googleusercontent.com/aida/ADBb0ujC9zHEev6NNJRZO9EQSofPWF8KdiIhpyJRPj8ERZM34TeeMisaFGv1e3bPD_rKJ6uZIf29bNuKntFqLR9nxKHrq5oljWILi73fSgVGZtRrQppcjA6J82J8BZ00op5nn9NiXeKBwq0IN9w4097lMm8e2Sd2MgyF1HPSdGRJPFU-w3zMJMhtK4gWMNdw7HtPwTFfkY1a_bCS1apr7-62hqazrmDCnah88Z-qaqpUWgvVXWenOWhxtYixt5s"/>
        <h1 class="font-headline-md text-headline-md-mobile md:text-headline-md text-on-surface font-bold tracking-tight">Student Login</h1>
        <p class="font-body-md text-secondary mt-1">Access your attendance records and dashboard</p>
    </div>
    <section class="login-card bg-white rounded-xl p-8 transition-all hover:shadow-md">
        <?php if ($loginError): ?>
            <div class="mb-4 p-3 bg-red-50 border border-red-200 rounded text-red-700 text-sm">
                <strong>Error:</strong> <?php echo htmlspecialchars($loginError); ?>
            </div>
        <?php endif; ?>
        <form class="space-y-6" id="loginForm" method="post" autocomplete="off">
            <div class="space-y-2">
                <label class="block font-label-md text-on-surface-variant" for="identifier">Email or Admission Number</label>
                <input 
                    type="text" 
                    id="identifier" 
                    name="identifier" 
                    placeholder="Email or admission number"
                    required 
                    value="<?php echo htmlspecialchars($_POST['identifier'] ?? ''); ?>"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-emerald-600"
                />
            </div>
            <div class="space-y-2">
                <label class="block font-label-md text-on-surface-variant" for="password">Password</label>
                <input 
                    type="password" 
                    id="password" 
                    name="password" 
                    placeholder="••••••••" 
                    required
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-emerald-600"
                />
            </div>
            <div class="flex items-center">
                <input 
                    type="checkbox" 
                    id="rememberMe" 
                    name="remember"
                    class="w-4 h-4"
                />
                <label class="ml-2 font-label-md text-on-surface-variant cursor-pointer select-none" for="rememberMe">Remember me for 30 days</label>
            </div>
            <button 
                type="submit" 
                class="w-full bg-emerald-600 text-white font-bold py-2 rounded-lg hover:bg-emerald-700 transition"
            >
                Login to Dashboard
            </button>
        </form>
        <div class="mt-6 pt-6 border-t border-gray-200 text-center text-sm text-gray-600">
            <a class="text-emerald-600 hover:underline" href="/pages/student/forgot-password.php">Forgot password?</a>
        </div>
    </section>
    <div class="mt-6 text-center text-sm text-gray-600">
        <a class="hover:underline" href="/pages/contact.php">Contact Support</a> | 
        <a class="hover:underline" href="/pages/privacy.php">Privacy</a> | 
        <a class="hover:underline" href="/pages/user-guide.php">Help</a>
    </div>
</main>
<?php require __DIR__ . '/../../components/ui/scripts.php'; ?>
</body>
</html>
