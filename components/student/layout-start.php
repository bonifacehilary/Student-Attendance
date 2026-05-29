<?php
/**
 * Student portal layout start — sidebar + top bar + flash (set $pageTitle, $activeNav before include).
 */
use StudentAttendance\Utils\Assets;
use StudentAttendance\Utils\StudentAuth;

StudentAuth::require();

$studentId = StudentAuth::studentId();
$student = StudentAuth::getStudent($studentId);
if (!$student) {
    unset($_SESSION['student_id'], $_SESSION['student_name']);
    header('Location: /pages/student/login.php');
    exit;
}

$flash = StudentAuth::pullFlash();
$pageTitle = $pageTitle ?? 'Student';
$activeNav = $activeNav ?? '';
$assetContext = 'student';

require __DIR__ . '/../ui/head.php';
?>
<body class="bg-slate-50 text-slate-900 min-h-screen">
    <!-- Student Sidebar Navigation -->
    <?php require __DIR__ . '/sidebar.php'; ?>

    <!-- Main Content Area with Top Bar -->
    <div class="lg:ml-64 flex flex-col min-h-screen transition-all duration-300">
        <!-- Top Bar -->
        <header class="bg-white border-b border-slate-200 sticky top-0 z-30 flex items-center justify-between px-4 md:px-6 h-16">
            <!-- Mobile Menu Toggle -->
            <button id="mobileMenuToggle" type="button" class="lg:hidden p-2 rounded-lg hover:bg-slate-100 transition-colors" aria-label="Open sidebar">
                <span class="material-symbols-outlined text-slate-600">menu</span>
            </button>

            <div class="flex items-center gap-3 min-w-0 flex-1">
                <?php if (!empty($showBack)): ?>
                    <a href="<?= htmlspecialchars($backHref ?? '/pages/student/dashboard.php') ?>" class="p-1 text-emerald-700 hover:bg-emerald-50 rounded lg:hidden">
                        <span class="material-symbols-outlined">arrow_back</span>
                    </a>
                <?php endif; ?>
                <span class="font-bold text-lg md:text-xl text-emerald-800 truncate"><?= htmlspecialchars($pageHeading ?? $pageTitle) ?></span>
            </div>

            <!-- Top Bar Actions -->
            <div class="flex items-center gap-2 md:gap-4">
                <a href="/pages/student/notifications.php" class="relative p-2 text-slate-600 hover:bg-slate-100 rounded-lg transition-colors" title="Notifications">
                    <span class="material-symbols-outlined">notifications</span>
                </a>
                <button id="themeToggle" type="button" class="p-2 text-slate-600 hover:bg-slate-100 rounded-lg transition-colors" title="Toggle theme">
                    <span class="material-symbols-outlined">dark_mode</span>
                </button>
            </div>
        </header>

        <!-- Flash Message -->
        <?php if (!empty($flash['message'])): ?>
            <?php
            $flashClass = match ($flash['type'] ?? 'success') {
                'error' => 'border-red-200 bg-red-50 text-red-800',
                'warning' => 'border-amber-200 bg-amber-50 text-amber-900',
                default => 'border-emerald-200 bg-emerald-50 text-emerald-900',
            };
            ?>
            <div class="mx-4 md:mx-6 mt-4 rounded-lg border px-4 py-3 text-sm <?= $flashClass ?>">
                <?= htmlspecialchars($flash['message']) ?>
            </div>
        <?php endif; ?>

        <!-- Main Content -->
        <main class="flex-1 px-4 md:px-6 py-6 max-w-6xl mx-auto w-full">
