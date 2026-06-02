<?php
/**
 * Student sidebar component - Responsive left vertical navigation
 * Includes all menu items: dashboard, attendance, timetable, courses, notifications, profile, settings, help, report, qr, logout
 */

use StudentAttendance\Utils\StudentAuth;

$studentId = StudentAuth::studentId();
$student = StudentAuth::getStudent($studentId);
$currentPage = basename($_SERVER['PHP_SELF']);

// Sidebar menu items with icons
$sidebarItems = [
    ['id' => 'dashboard', 'label' => 'Dashboard', 'icon' => 'home', 'href' => '/pages/student/dashboard.php', 'page' => 'dashboard.php'],
    ['id' => 'qr', 'label' => 'Mark Attendance', 'icon' => 'qr_code_scanner', 'href' => '/pages/student/qr-attendance.php', 'page' => 'qr-attendance.php'],
    ['id' => 'history', 'label' => 'Attendance History', 'icon' => 'history', 'href' => '/pages/student/attendance.php', 'page' => 'attendance.php'],
    ['id' => 'timetable', 'label' => 'Timetable', 'icon' => 'schedule', 'href' => '/pages/student/timetable.php', 'page' => 'timetable.php'],
    ['id' => 'courses', 'label' => 'Courses/Subjects', 'icon' => 'school', 'href' => '/pages/student/courses.php', 'page' => 'courses.php'],
    ['id' => 'notifications', 'label' => 'Notifications', 'icon' => 'notifications', 'href' => '/pages/student/notifications.php', 'page' => 'notifications.php'],
    ['id' => 'profile', 'label' => 'Profile', 'icon' => 'person', 'href' => '/pages/student/profile.php', 'page' => 'profile.php'],
    ['id' => 'settings', 'label' => 'Settings', 'icon' => 'settings', 'href' => '/pages/student/settings.php', 'page' => 'settings.php'],
    ['id' => 'help', 'label' => 'Help/Support', 'icon' => 'help', 'href' => '/pages/student/help.php', 'page' => 'help.php'],
    ['id' => 'report', 'label' => 'Download Report', 'icon' => 'download', 'href' => '/pages/student/report.php', 'page' => 'report.php'],
];
?>
<div id="studentSidebar" class="fixed left-0 top-0 h-screen w-64 bg-white border-r border-slate-200 shadow-lg transform -translate-x-full lg:translate-x-0 transition-transform duration-300 ease-in-out z-40 overflow-y-auto">
    <!-- Sidebar Header with Logo -->
    <div class="sticky top-0 bg-white border-b border-slate-200 px-6 py-4">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-2">
                <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-emerald-600 text-white font-bold text-lg">E</div>
                <div>
                    <p class="text-sm font-bold text-slate-900">EduAttend</p>
                    <p class="text-xs text-slate-500">Student Portal</p>
                </div>
            </div>
            <button id="sidebarToggle" type="button" class="lg:hidden p-1 rounded hover:bg-slate-100" aria-label="Close sidebar">
                <span class="material-symbols-outlined text-slate-600">close</span>
            </button>
        </div>
    </div>

    <!-- Student Info Card -->
    <div class="mx-4 mt-4 p-3 bg-emerald-50 rounded-lg border border-emerald-200">
        <p class="text-xs font-semibold text-slate-900"><?= htmlspecialchars($student['name'] ?? 'Student') ?></p>
        <p class="text-xs text-slate-600 mt-1">ID: <?= htmlspecialchars($student['admission_number'] ?? 'N/A') ?></p>
    </div>

    <!-- Navigation Menu -->
    <nav class="flex-1 px-3 py-6 space-y-2">
        <?php foreach ($sidebarItems as $item): ?>
            <?php $isActive = ($currentPage === $item['page']) ? true : false; ?>
            <a href="<?= htmlspecialchars($item['href']) ?>" 
               class="flex items-center gap-3 px-4 py-3 rounded-lg transition-all duration-200 group <?= $isActive ? 'bg-emerald-100 text-emerald-700 font-semibold shadow-sm' : 'text-slate-700 hover:bg-slate-100' ?>"
               title="<?= htmlspecialchars($item['label']) ?>">
                <span class="material-symbols-outlined text-lg flex-shrink-0 <?= $isActive ? 'text-emerald-700' : 'text-slate-600 group-hover:text-slate-900' ?>">
                    <?= htmlspecialchars($item['icon']) ?>
                </span>
                <span class="text-sm font-medium"><?= htmlspecialchars($item['label']) ?></span>
                <?php if ($isActive): ?>
                    <div class="ml-auto w-1 h-6 bg-emerald-700 rounded-full"></div>
                <?php endif; ?>
            </a>
        <?php endforeach; ?>
    </nav>

    <!-- Sidebar Footer with Logout -->
    <div class="sticky bottom-0 border-t border-slate-200 bg-white px-3 py-4 space-y-2">
        <a href="/pages/student/logout.php" 
           class="flex items-center gap-3 px-4 py-2 rounded-lg text-red-600 hover:bg-red-50 transition-all duration-200 text-sm font-semibold"
           title="Logout">
            <span class="material-symbols-outlined text-lg flex-shrink-0">logout</span>
            <span>Logout</span>
        </a>
    </div>
</div>

<!-- Mobile Sidebar Overlay -->
<div id="sidebarOverlay" class="fixed inset-0 bg-black bg-opacity-0 lg:hidden transition-all duration-300 ease-in-out pointer-events-none z-30"></div>

<style>
    /* Sidebar animations */
    #studentSidebar.sidebar-open {
        @apply translate-x-0;
    }

    #sidebarOverlay.overlay-visible {
        @apply bg-opacity-50 pointer-events-auto;
    }

    /* Prevent body scroll when sidebar is open on mobile */
    body.sidebar-active {
        overflow: hidden;
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const sidebar = document.getElementById('studentSidebar');
        const overlay = document.getElementById('sidebarOverlay');
        const toggleBtn = document.getElementById('sidebarToggle');
        const menuToggle = document.getElementById('mobileMenuToggle');

        // Toggle sidebar on mobile
        function openSidebar() {
            sidebar.classList.add('sidebar-open');
            overlay.classList.add('overlay-visible');
            document.body.classList.add('sidebar-active');
        }

        function closeSidebar() {
            sidebar.classList.remove('sidebar-open');
            overlay.classList.remove('overlay-visible');
            document.body.classList.remove('sidebar-active');
        }

        toggleBtn?.addEventListener('click', closeSidebar);
        overlay?.addEventListener('click', closeSidebar);
        menuToggle?.addEventListener('click', openSidebar);

        // Close sidebar when clicking on a menu item (mobile)
        if (window.innerWidth < 1024) {
            sidebar.querySelectorAll('nav a').forEach(link => {
                link.addEventListener('click', closeSidebar);
            });
        }

        // Close sidebar on window resize if going from mobile to desktop
        window.addEventListener('resize', function() {
            if (window.innerWidth >= 1024) {
                closeSidebar();
            }
        });
    });
</script>
