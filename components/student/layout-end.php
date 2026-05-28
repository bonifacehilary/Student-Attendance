</main>

<nav class="fixed bottom-0 left-0 w-full flex justify-around items-center px-1 py-2 bg-white border-t border-slate-200 shadow-lg z-50">
    <?php
    $navItems = [
        ['id' => 'dashboard', 'href' => '/pages/student/dashboard.php', 'icon' => 'home', 'label' => 'Home'],
        ['id' => 'attendance', 'href' => '/pages/student/attendance.php', 'icon' => 'calendar_today', 'label' => 'History'],
        ['id' => 'qr', 'href' => '/pages/student/qr-attendance.php', 'icon' => 'qr_code_2', 'label' => 'QR'],
        ['id' => 'report', 'href' => '/pages/student/report.php', 'icon' => 'description', 'label' => 'Report'],
        ['id' => 'profile', 'href' => '/pages/student/profile.php', 'icon' => 'person', 'label' => 'Profile'],
    ];
    foreach ($navItems as $item):
        $active = ($activeNav === $item['id']) ? ' text-emerald-700 font-bold' : ' text-slate-500';
        $fill = ($activeNav === $item['id']) ? " style=\"font-variation-settings: 'FILL' 1;\"" : '';
    ?>
        <a class="flex flex-col items-center justify-center flex-1 py-1 text-[10px]<?= $active ?>" href="<?= htmlspecialchars($item['href']) ?>">
            <span class="material-symbols-outlined text-xl"<?= $fill ?>><?= htmlspecialchars($item['icon']) ?></span>
            <?= htmlspecialchars($item['label']) ?>
        </a>
    <?php endforeach; ?>
</nav>

<?php require __DIR__ . '/../ui/scripts.php'; ?>
</body>
</html>
