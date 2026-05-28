<?php
/**
 * Dev UI hub — open screens without hunting URLs.
 */
require_once __DIR__ . '/config/bootstrap.php';

$base = 'http://localhost:8000';

$screens = [
    'Student (login first)' => [
        ['Login', '/pages/student/login.php'],
        ['Splash', '/pages/student/splash.php'],
        ['Dashboard', '/pages/student/dashboard.php'],
        ['Attendance history', '/pages/student/attendance.php'],
        ['QR attendance', '/pages/student/qr-attendance.php'],
        ['Analytics', '/pages/student/analytics.php'],
        ['Reports', '/pages/student/report.php'],
        ['Notifications', '/pages/student/notifications.php'],
        ['Profile', '/pages/student/profile.php'],
        ['Forgot password', '/pages/student/forgot-password.php'],
    ],
    'Admin' => [
        ['Admin login', '/pages/admin/login.php'],
        ['Dashboard', '/pages/admin/dashboard.php'],
        ['Mark attendance', '/pages/admin/attendance.php'],
        ['Students', '/pages/admin/students.php'],
        ['Reports', '/pages/admin/reports.php'],
        ['QR sessions', '/pages/admin/qrs.php'],
        ['Create QR', '/pages/admin/create_qr.php'],
    ],
    'Teacher' => [
        ['Teacher login', '/pages/teacher/login.php'],
        ['Dashboard', '/pages/teacher/dashboard.php'],
        ['Mark attendance', '/pages/teacher/attendance.php'],
        ['My class', '/pages/teacher/students.php'],
        ['Reports', '/pages/teacher/reports.php'],
        ['QR sessions', '/pages/teacher/qrs.php'],
        ['Create QR', '/pages/teacher/create_qr.php'],
    ],
    'Public' => [
        ['Contact', '/pages/contact.php'],
        ['Privacy', '/pages/privacy.php'],
        ['User guide', '/pages/user-guide.php'],
        ['System status', '/pages/status.php'],
    ],
];

$creds = [
    'Student' => 'alex@school.edu / STU2024001 — password: password123',
    'Teacher' => 'teacher@school.edu — password: teacher123 (class Grade 10A)',
    'Admin' => 'admin@school.edu — password: admin123',
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1"/>
    <title>EduAttend — UI Screens</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-50 min-h-screen p-6 md:p-10">
    <div class="max-w-3xl mx-auto">
        <h1 class="text-3xl font-bold text-emerald-800">EduAttend UI Screens</h1>
        <p class="text-slate-600 mt-2">Click a link to open that screen. Student pages need login first.</p>

        <div class="mt-6 flex flex-wrap gap-3">
            <a href="/pages/dev-quick-login.php"
               class="inline-flex items-center gap-2 px-4 py-2 bg-emerald-600 text-white rounded-lg font-medium hover:bg-emerald-700">
                Quick preview (student, no password)
            </a>
            <a href="/pages/student/login.php"
               class="inline-flex items-center gap-2 px-4 py-2 bg-white border border-slate-300 rounded-lg font-medium hover:bg-slate-50">
                Student login
            </a>
            <a href="/pages/admin/login.php"
               class="inline-flex items-center gap-2 px-4 py-2 bg-white border border-slate-300 rounded-lg font-medium hover:bg-slate-50">
                Admin login
            </a>
            <a href="/pages/teacher/login.php"
               class="inline-flex items-center gap-2 px-4 py-2 bg-white border border-slate-300 rounded-lg font-medium hover:bg-slate-50">
                Teacher login
            </a>
        </div>

        <div class="mt-6 p-4 bg-white rounded-lg border border-emerald-100 text-sm">
            <p class="font-semibold text-slate-800 mb-2">Test credentials</p>
            <?php foreach ($creds as $role => $text): ?>
                <p><span class="text-emerald-700 font-medium"><?= htmlspecialchars($role) ?>:</span> <?= htmlspecialchars($text) ?></p>
            <?php endforeach; ?>
        </div>

        <?php foreach ($screens as $group => $links): ?>
            <section class="mt-8">
                <h2 class="text-lg font-semibold text-slate-800 border-b pb-2"><?= htmlspecialchars($group) ?></h2>
                <ul class="mt-3 grid gap-2 sm:grid-cols-2">
                    <?php foreach ($links as [$label, $path]): ?>
                        <li>
                            <a class="block px-4 py-3 bg-white rounded-lg border border-slate-200 hover:border-emerald-500 hover:bg-emerald-50 transition"
                               href="<?= htmlspecialchars($path) ?>" target="_blank" rel="noopener">
                                <?= htmlspecialchars($label) ?>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </section>
        <?php endforeach; ?>

        <p class="mt-10 text-xs text-slate-500">Server: <?= htmlspecialchars($base) ?> — run <code class="bg-slate-200 px-1 rounded">php -S localhost:8000</code></p>
    </div>
</body>
</html>
