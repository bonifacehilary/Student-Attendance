<?php
use StudentAttendance\Utils\Assets;

$currentPath = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?: '';
?>
<header class="bg-white border-b border-gray-200 sticky top-0 z-40">
    <div class="max-w-6xl mx-auto px-4 h-14 flex items-center justify-between gap-4">
        <a href="/pages/admin/dashboard.php" class="flex items-center gap-2 eduattend-brand">
            <span class="material-symbols-outlined text-emerald-600">admin_panel_settings</span>
            <span><?= htmlspecialchars(Assets::APP_NAME) ?> Admin</span>
        </a>
        <nav class="flex items-center gap-1 flex-wrap justify-end">
            <?php foreach (Assets::adminNavItems() as $item): ?>
                <?php $active = $currentPath === $item['href'] ? ' active' : ''; ?>
                <a class="eduattend-nav-link<?= $active ?>" href="<?= htmlspecialchars($item['href']) ?>">
                    <span class="material-symbols-outlined text-base"><?= htmlspecialchars($item['icon']) ?></span>
                    <?= htmlspecialchars($item['label']) ?>
                </a>
            <?php endforeach; ?>
            <a class="eduattend-nav-link text-red-600" href="/pages/admin/logout.php">
                <span class="material-symbols-outlined text-base">logout</span>
                Logout
            </a>
        </nav>
    </div>
</header>
