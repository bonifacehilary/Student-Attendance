<?php
require_once __DIR__ . '/../../config/bootstrap.php';

use StudentAttendance\Utils\TeacherAuth;

if (TeacherAuth::check()) {
    header('Location: /pages/teacher/dashboard.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $loginError = '';

    if (TeacherAuth::attemptLogin($email, $password, $loginError)) {
        header('Location: /pages/teacher/dashboard.php');
        exit;
    }

    $error = $loginError !== ''
        ? $loginError
        : 'Invalid email or password.';
}

$pageTitle = 'Teacher Login';
$assetContext = 'teacher';
$pageStyles = 'body { background: linear-gradient(135deg, #0284c7 0%, #0369a1 100%); }';
require __DIR__ . '/../../components/ui/head.php';
?>
<body class="flex items-center justify-center min-h-screen p-4">
    <div class="w-full max-w-md">
        <div class="bg-white rounded-lg shadow-2xl overflow-hidden">
            <div class="bg-gradient-to-r from-sky-600 to-sky-700 px-6 py-8 text-center">
                <div class="flex justify-center mb-4">
                    <span class="material-symbols-outlined text-5xl text-white">co_present</span>
                </div>
                <h1 class="text-2xl font-bold text-white">EduAttend Teacher</h1>
                <p class="text-sky-100 text-sm mt-2">Class attendance portal</p>
            </div>

            <form method="post" class="px-6 py-8 space-y-6">
                <?php if ($error !== ''): ?>
                <div class="bg-red-50 border border-red-200 rounded-lg p-4 flex gap-3">
                    <span class="material-symbols-outlined text-red-600">error</span>
                    <div>
                        <p class="font-bold text-red-900">Error</p>
                        <p class="text-sm text-red-700"><?= htmlspecialchars($error) ?></p>
                    </div>
                </div>
                <?php endif; ?>

                <div>
                    <label for="email" class="block text-sm font-bold text-gray-900 mb-2">Email</label>
                    <input type="email" id="email" name="email" required
                           placeholder="teacher@school.edu"
                           value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-sky-600"/>
                </div>

                <div>
                    <label for="password" class="block text-sm font-bold text-gray-900 mb-2">Password</label>
                    <div class="relative">
                        <input type="password" id="password" name="password" required
                               placeholder="••••••••"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-sky-600"/>
                        <button type="button" class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-600"
                                onclick="var i=document.getElementById('password');i.type=i.type==='password'?'text':'password';">
                            <span class="material-symbols-outlined text-xl">visibility</span>
                        </button>
                    </div>
                </div>

                <button type="submit"
                        class="w-full bg-sky-600 hover:bg-sky-700 text-white font-bold py-2.5 rounded-lg flex items-center justify-center gap-2">
                    <span class="material-symbols-outlined">login</span>
                    Sign in
                </button>
            </form>

            <div class="bg-gray-50 px-6 py-4 border-t border-gray-200 text-center text-sm text-gray-600 space-y-2">
                <a href="/pages/student/login.php" class="text-sky-600 font-bold hover:underline block">Student login →</a>
                <a href="/pages/admin/login.php" class="text-slate-600 hover:underline block">Admin login →</a>
            </div>
        </div>
    </div>
<?php require __DIR__ . '/../../components/ui/scripts.php'; ?>
</body>
</html>
