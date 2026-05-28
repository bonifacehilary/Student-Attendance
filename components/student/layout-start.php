<?php
/**
 * Student portal layout start — top bar + flash (set $pageTitle, $activeNav before include).
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
<body class="bg-slate-50 text-slate-900 min-h-screen pb-20">
<header class="bg-white border-b border-slate-200 sticky top-0 z-40 flex items-center justify-between px-4 h-14">
    <div class="flex items-center gap-3 min-w-0">
        <?php if (!empty($showBack)): ?>
            <a href="<?= htmlspecialchars($backHref ?? '/pages/student/dashboard.php') ?>" class="p-1 text-emerald-700">
                <span class="material-symbols-outlined">arrow_back</span>
            </a>
        <?php endif; ?>
        <span class="font-bold text-emerald-800 truncate"><?= htmlspecialchars($pageHeading ?? $pageTitle) ?></span>
    </div>
    <a href="/pages/student/notifications.php" class="relative p-2 text-slate-600" title="Notifications">
        <span class="material-symbols-outlined">notifications</span>
    </a>
</header>

<?php if (!empty($flash['message'])): ?>
    <?php
    $flashClass = match ($flash['type'] ?? 'success') {
        'error' => 'border-red-200 bg-red-50 text-red-800',
        'warning' => 'border-amber-200 bg-amber-50 text-amber-900',
        default => 'border-emerald-200 bg-emerald-50 text-emerald-900',
    };
    ?>
    <div class="mx-4 mt-3 rounded-lg border px-4 py-3 text-sm <?= $flashClass ?>">
        <?= htmlspecialchars($flash['message']) ?>
    </div>
<?php endif; ?>

<main class="max-w-2xl mx-auto px-4 py-6 w-full">
