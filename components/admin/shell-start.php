<?php
/**
 * Admin panel layout start (auth + sidebar + topbar).
 * Set $pageTitle before including.
 */
use StudentAttendance\Utils\AdminAuth;
use StudentAttendance\Utils\Assets;

AdminAuth::require();

if (empty($adminFlash)) {
    $adminFlash = AdminAuth::pullFlash();
}

$pageTitle = $pageTitle ?? 'Admin';
$assetContext = 'admin';
$currentPath = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?: '';

require __DIR__ . '/../ui/head.php';
?>
<div class="admin-shell">
    <aside class="admin-sidebar">
        <a href="/pages/admin/dashboard.php" class="admin-sidebar__brand">
            <span class="material-symbols-outlined text-emerald-400">admin_panel_settings</span>
            <?= htmlspecialchars(Assets::APP_NAME) ?>
        </a>
        <nav class="admin-sidebar__nav">
            <?php foreach (Assets::adminNavItems() as $item): ?>
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
            <a class="admin-sidebar__link text-red-300 hover:text-red-200" href="/pages/admin/logout.php">
                <span class="material-symbols-outlined text-lg">logout</span>
                Logout
            </a>
        </nav>
        <div class="admin-sidebar__footer">
            <?= htmlspecialchars($_SESSION['admin_email'] ?? 'Administrator') ?>
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
            <?php if (!empty($adminFlash)): ?>
                <div class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900">
                    <?= htmlspecialchars($adminFlash) ?>
                </div>
            <?php endif; ?>
            <?php if (!empty($adminError)): ?>
                <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
                    <?= htmlspecialchars($adminError) ?>
                </div>
            <?php endif; ?>
