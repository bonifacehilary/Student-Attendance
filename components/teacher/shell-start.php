<?php
/**
 * Teacher panel layout start (auth + sidebar + topbar).
 */
use StudentAttendance\Utils\Assets;
use StudentAttendance\Utils\TeacherAuth;

TeacherAuth::require();

if (empty($teacherFlash)) {
    $teacherFlash = TeacherAuth::pullFlash();
}

$pageTitle = $pageTitle ?? 'Teacher';
$assetContext = 'teacher';
$currentPath = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?: '';
$assignedClass = TeacherAuth::assignedClass();

require __DIR__ . '/../ui/head.php';
?>
<div class="admin-shell teacher-shell">
    <aside class="admin-sidebar">
        <a href="/pages/teacher/dashboard.php" class="admin-sidebar__brand">
            <span class="material-symbols-outlined text-sky-400">co_present</span>
            <?= htmlspecialchars(Assets::APP_NAME) ?> Teacher
        </a>
        <nav class="admin-sidebar__nav">
            <?php foreach (Assets::teacherNavItems() as $item): ?>
                <?php $active = ($currentPath === $item['href']) ? ' active' : ''; ?>
                <a class="admin-sidebar__link<?= $active ?>" href="<?= htmlspecialchars($item['href']) ?>">
                    <span class="material-symbols-outlined text-lg"><?= htmlspecialchars($item['icon']) ?></span>
                    <?= htmlspecialchars($item['label']) ?>
                </a>
            <?php endforeach; ?>
            <a class="admin-sidebar__link" href="/pages/student/login.php">
                <span class="material-symbols-outlined text-lg">school</span>
                Student portal
            </a>
            <a class="admin-sidebar__link text-red-300 hover:text-red-200" href="/pages/teacher/logout.php">
                <span class="material-symbols-outlined text-lg">logout</span>
                Logout
            </a>
        </nav>
        <div class="admin-sidebar__footer">
            <p class="text-slate-300 font-medium truncate"><?= htmlspecialchars($_SESSION['teacher_name'] ?? 'Teacher') ?></p>
            <?php if ($assignedClass !== ''): ?>
                <p class="mt-1 text-sky-300/90">Class: <?= htmlspecialchars($assignedClass) ?></p>
            <?php endif; ?>
        </div>
    </aside>
    <div class="admin-main">
        <header class="admin-topbar">
            <div>
                <h1 class="text-lg font-bold text-slate-900"><?= htmlspecialchars($pageHeading ?? $pageTitle) ?></h1>
                <?php if (!empty($pageSubtitle)): ?>
                    <p class="text-sm text-slate-500"><?= htmlspecialchars($pageSubtitle) ?></p>
                <?php endif; ?>
            </div>
            <p class="text-sm text-slate-600 shrink-0"><?= date('l, M j, Y') ?></p>
        </header>
        <div class="admin-content">
            <?php if (!empty($teacherFlash)): ?>
                <div class="mb-4 rounded-lg border border-sky-200 bg-sky-50 px-4 py-3 text-sm text-sky-900">
                    <?= htmlspecialchars($teacherFlash) ?>
                </div>
            <?php endif; ?>
            <?php if (!empty($teacherError)): ?>
                <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
                    <?= htmlspecialchars($teacherError) ?>
                </div>
            <?php endif; ?>
