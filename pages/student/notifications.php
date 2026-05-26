<?php
// pages/student/notifications.php
// Student Notifications Center - Alerts, Warnings, and Announcements

require_once __DIR__ . '/../../config/bootstrap.php';
require_once __DIR__ . '/../../utils/Utility.php';

session_start();

// Check if student is logged in
if (!isset($_SESSION['student_id'])) {
    header('Location: /pages/student/login.php');
    exit;
}

$studentId = $_SESSION['student_id'];

// Get filter type from request
$filterType = $_GET['filter'] ?? 'all'; // all, alerts, announcements
$action = $_POST['action'] ?? null;

// Handle mark as read
if ($action === 'mark_read' && isset($_POST['notification_id'])) {
    try {
        Utility::safeQuery(
            'UPDATE notifications SET is_read = 1 WHERE id = ? AND student_id = ?',
            [$_POST['notification_id'], $studentId],
            'UPDATE'
        );
    } catch (\Throwable $e) {
        error_log('Mark notification as read error: ' . $e->getMessage());
    }
}

// Handle dismiss/delete notification
if ($action === 'dismiss' && isset($_POST['notification_id'])) {
    try {
        Utility::safeQuery(
            'DELETE FROM notifications WHERE id = ? AND student_id = ?',
            [$_POST['notification_id'], $studentId],
            'DELETE'
        );
    } catch (\Throwable $e) {
        error_log('Delete notification error: ' . $e->getMessage());
    }
}

// Handle appeal submission
if ($action === 'appeal' && isset($_POST['notification_id'])) {
    try {
        Utility::safeQuery(
            'UPDATE notifications SET appeal_status = "pending" WHERE id = ? AND student_id = ? AND can_appeal = 1',
            [$_POST['notification_id'], $studentId],
            'UPDATE'
        );
    } catch (\Throwable $e) {
        error_log('Appeal submission error: ' . $e->getMessage());
    }
}

// Fetch student data
try {
    $student = Utility::safeQuery(
        'SELECT id, name, admission_number FROM students WHERE id = ? LIMIT 1',
        [$studentId],
        'SELECT',
        true
    );
} catch (\Throwable $e) {
    error_log('Student fetch error: ' . $e->getMessage());
    $student = ['name' => 'Student', 'admission_number' => 'STU' . $studentId];
}

// Fetch notifications based on filter
try {
    if ($filterType === 'alerts') {
        $query = 'SELECT * FROM notifications WHERE student_id = ? AND type IN ("attendance_alert", "warning") ORDER BY created_at DESC';
    } elseif ($filterType === 'announcements') {
        $query = 'SELECT * FROM notifications WHERE student_id = ? AND type IN ("announcement", "confirmation") ORDER BY created_at DESC';
    } else {
        $query = 'SELECT * FROM notifications WHERE student_id = ? ORDER BY created_at DESC LIMIT 50';
    }
    
    $notifications = Utility::safeQuery($query, [$studentId], 'SELECT');
} catch (\Throwable $e) {
    error_log('Notifications fetch error: ' . $e->getMessage());
    $notifications = [];
}

// Count unread notifications
try {
    $unreadCount = Utility::safeQuery(
        'SELECT COUNT(*) as count FROM notifications WHERE student_id = ? AND is_read = 0',
        [$studentId],
        'SELECT',
        true
    );
    $unreadCount = $unreadCount['count'] ?? 0;
} catch (\Throwable $e) {
    $unreadCount = 0;
}

// Format time ago
function getTimeAgo($timestamp) {
    $time = strtotime($timestamp);
    $current = time();
    $diff = $current - $time;
    
    if ($diff < 60) {
        return 'just now';
    } elseif ($diff < 3600) {
        $mins = floor($diff / 60);
        return $mins . 'min ago';
    } elseif ($diff < 86400) {
        $hours = floor($diff / 3600);
        return $hours . 'h ago';
    } elseif ($diff < 604800) {
        $days = floor($diff / 86400);
        return $days === 1 ? 'Yesterday' : $days . ' days ago';
    } else {
        return date('M d', $time);
    }
}

// Map notification types to display data
function getNotificationDisplay($notification) {
    $display = [
        'icon' => 'info',
        'color_class' => 'bg-secondary-container text-on-secondary-container',
        'icon_fill' => true
    ];
    
    switch ($notification['type']) {
        case 'attendance_alert':
            $display['icon'] = 'warning';
            $display['color_class'] = 'bg-error-container text-error';
            break;
        case 'warning':
            $display['icon'] = 'priority_high';
            $display['color_class'] = 'bg-orange-100 text-orange-600';
            break;
        case 'announcement':
            $display['icon'] = 'info';
            $display['color_class'] = 'bg-secondary-container text-on-secondary-container';
            break;
        case 'confirmation':
            $display['icon'] = 'check_circle';
            $display['color_class'] = 'bg-primary-container/20 text-primary';
            break;
    }
    
    return $display;
}
?>
<!DOCTYPE html>
<html class="light" lang="en">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>Notifications | EduAttend</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <style>
        body { font-family: 'Inter', sans-serif; }
        .material-symbols-outlined { font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24; }
        .notification-card { transition: transform 0.2s ease, box-shadow 0.2s ease; }
        .notification-card:active { transform: scale(0.98); }
        ::-webkit-scrollbar { width: 6px; }
        ::-webkit-scrollbar-track { background: #f2f4f6; }
        ::-webkit-scrollbar-thumb { background: #bbcabf; border-radius: 10px; }
    </style>
    <script id="tailwind-config">
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    colors: {
                        "inverse-surface": "#2d3133",
                        "outline-variant": "#bbcabf",
                        "background": "#f7f9fb",
                        "secondary-fixed-dim": "#bec6e0",
                        "on-surface": "#191c1e",
                        "on-secondary-container": "#5c647a",
                        "on-primary-fixed": "#002113",
                        "surface": "#f7f9fb",
                        "tertiary": "#515f74",
                        "secondary": "#565e74",
                        "on-secondary-fixed-variant": "#3f465c",
                        "error-container": "#ffdad6",
                        "tertiary-container": "#95a4bb",
                        "on-tertiary-fixed": "#0d1c2f",
                        "on-surface-variant": "#3c4a42",
                        "tertiary-fixed-dim": "#b9c7e0",
                        "on-error-container": "#93000a",
                        "surface-container-lowest": "#ffffff",
                        "on-tertiary-container": "#2c3a4e",
                        "on-tertiary": "#ffffff",
                        "on-secondary": "#ffffff",
                        "secondary-container": "#dae2fd",
                        "on-background": "#191c1e",
                        "surface-bright": "#f7f9fb",
                        "primary-fixed": "#6ffbbe",
                        "surface-container-low": "#f2f4f6",
                        "secondary-fixed": "#dae2fd",
                        "on-tertiary-fixed-variant": "#3a485c",
                        "on-primary-fixed-variant": "#005236",
                        "surface-container": "#eceef0",
                        "on-primary-container": "#00422b",
                        "error": "#ba1a1a",
                        "tertiary-fixed": "#d5e3fd",
                        "primary-fixed-dim": "#4edea3",
                        "surface-container-high": "#e6e8ea",
                        "on-secondary-fixed": "#131b2e",
                        "surface-tint": "#006c49",
                        "surface-dim": "#d8dadc",
                        "primary-container": "#10b981",
                        "primary": "#006c49",
                        "surface-variant": "#e0e3e5",
                        "inverse-primary": "#4edea3",
                        "on-primary": "#ffffff",
                        "surface-container-highest": "#e0e3e5",
                        "outline": "#6c7a71",
                        "on-error": "#ffffff",
                        "inverse-on-surface": "#eff1f3"
                    },
                    spacing: {
                        "gutter": "24px",
                        "container-padding-desktop": "32px",
                        "sidebar-width": "280px",
                        "container-padding-mobile": "16px",
                        "base": "8px"
                    }
                }
            }
        };
    </script>
</head>
<body class="bg-background text-on-surface font-body-md min-h-screen flex flex-col md:flex-row">
    <!-- Desktop Sidebar Navigation -->
    <aside class="hidden md:flex flex-col h-screen fixed left-0 top-0 z-40 w-sidebar-width border-r border-outline-variant bg-white">
        <div class="p-8">
            <h1 class="font-bold text-2xl text-primary">EduAttend</h1>
        </div>
        <div class="px-4 mb-8 flex items-center gap-4">
            <div class="w-12 h-12 rounded-full overflow-hidden border-2 border-primary">
                <img alt="Student Avatar" class="w-full h-full object-cover" src="https://lh3.googleusercontent.com/aida-public/AB6AXuA5tw_nzWYYae8RS7mj5BvCJQxoRgTwNhI3ispSYUwNPRwNkzXrMikxD8O5SQFYotBulXZmvs17DIf6a2LUPgITZvJcp9llrolqJnwIiLvOTHsaKTIUTK5yPh3a9nV5Q12-4A03j_G7UFhF6tibsfOORy7GBUswixFCyYVzBKEZgnaiEyI7dDAhsyKep4LCQWxAexCQnyxuL_kWC7r2kPs33UEasCSQQDrnNPglME4NZXntKs-Hn7hbitkNEXCqGWy0HpJVFLtdQvo-"/>
            </div>
            <div>
                <p class="font-medium text-gray-900"><?php echo htmlspecialchars($student['name']); ?></p>
                <p class="text-sm text-gray-600">ID: <?php echo htmlspecialchars($student['admission_number']); ?></p>
            </div>
        </div>
        <nav class="flex-1 px-2 space-y-1">
            <a class="text-gray-600 flex items-center px-4 py-3 hover:bg-gray-100 hover:text-green-700 transition-all rounded-lg" href="/pages/student/dashboard.php">
                <span class="material-symbols-outlined mr-3">dashboard</span>
                <span class="font-medium text-sm">Dashboard</span>
            </a>
            <a class="text-gray-600 flex items-center px-4 py-3 hover:bg-gray-100 hover:text-green-700 transition-all rounded-lg" href="/pages/student/attendance.php">
                <span class="material-symbols-outlined mr-3">history</span>
                <span class="font-medium text-sm">History</span>
            </a>
            <a class="text-gray-600 flex items-center px-4 py-3 hover:bg-gray-100 hover:text-green-700 transition-all rounded-lg" href="/pages/student/report.php">
                <span class="material-symbols-outlined mr-3">description</span>
                <span class="font-medium text-sm">Report</span>
            </a>
            <a class="text-green-700 border-l-4 border-green-700 bg-green-50 flex items-center px-4 py-3" href="/pages/student/notifications.php">
                <span class="material-symbols-outlined mr-3" style="font-variation-settings: 'FILL' 1;">notifications</span>
                <span class="font-medium text-sm">Notifications</span>
                <?php if ($unreadCount > 0): ?>
                    <span class="ml-auto bg-red-500 text-white text-xs font-bold px-2 py-0.5 rounded-full"><?php echo $unreadCount; ?></span>
                <?php endif; ?>
            </a>
            <a class="text-gray-600 flex items-center px-4 py-3 hover:bg-gray-100 hover:text-green-700 transition-all rounded-lg" href="/pages/student/profile.php">
                <span class="material-symbols-outlined mr-3">person</span>
                <span class="font-medium text-sm">Profile</span>
            </a>
        </nav>
    </aside>

    <!-- Main Content -->
    <main class="flex-1 md:ml-sidebar-width flex flex-col min-h-screen pb-20 md:pb-0">
        <!-- Top App Bar -->
        <header class="bg-white flex justify-between items-center w-full h-16 px-4 md:px-8 border-b border-gray-200 sticky top-0 z-30">
            <div class="flex items-center gap-3">
                <div class="md:hidden w-8 h-8 rounded-full overflow-hidden">
                    <img alt="Profile" src="https://lh3.googleusercontent.com/aida-public/AB6AXuApXdfe2Hlj8Iwc80BoN8DcvsbAz4j7p04aptlZsw91468t01wtT4VwB9SRvFIIV9tz16nUtVjQgt1MFZhckSe_ZWxq2NOq-kvx9gZP4Zuk_JDfeHe1OpCRznryGPzDdWbuMZ1jM5oNlpC4fd_6c-6Wb39fy6qgnQ3eTwdkCDUuJowx0INR3cYDwboOV4fmaxH84SMs8dF2oUdZSEODALeoTrCyWwua94Or70NSG0pjXJVoKuPkfA9aCe43NrKkoJkEaCkMd4FMGmps"/>
                </div>
                <h2 class="font-bold text-xl text-green-700">Notifications</h2>
            </div>
            <button class="w-10 h-10 flex items-center justify-center rounded-full hover:bg-gray-100 transition-colors text-gray-600" title="More options">
                <span class="material-symbols-outlined">more_vert</span>
            </button>
        </header>

        <!-- Content -->
        <div class="max-w-4xl mx-auto w-full p-4 md:p-8 space-y-6">
            <!-- Filter Tabs -->
            <div class="flex items-center gap-2 overflow-x-auto pb-2">
                <a href="?filter=all" class="px-6 py-2 rounded-full font-medium text-sm transition-all <?php echo $filterType === 'all' ? 'bg-green-700 text-white shadow-sm' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'; ?>">
                    All
                </a>
                <a href="?filter=alerts" class="px-6 py-2 rounded-full font-medium text-sm transition-all <?php echo $filterType === 'alerts' ? 'bg-green-700 text-white shadow-sm' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'; ?>">
                    Alerts
                </a>
                <a href="?filter=announcements" class="px-6 py-2 rounded-full font-medium text-sm transition-all <?php echo $filterType === 'announcements' ? 'bg-green-700 text-white shadow-sm' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'; ?>">
                    Announcements
                </a>
            </div>

            <!-- Notifications Grid -->
            <div class="grid grid-cols-1 gap-4">
                <?php if (!empty($notifications)): ?>
                    <?php foreach ($notifications as $notif): ?>
                        <?php $display = getNotificationDisplay($notif); ?>
                        <div class="notification-card bg-white border border-gray-200 rounded-xl p-4 flex gap-4 hover:shadow-md transition-all group">
                            <!-- Icon -->
                            <div class="flex-shrink-0 w-12 h-12 rounded-lg <?php echo $display['color_class']; ?> flex items-center justify-center">
                                <span class="material-symbols-outlined" style="font-variation-settings: 'FILL' 1;"><?php echo $display['icon']; ?></span>
                            </div>

                            <!-- Content -->
                            <div class="flex-1">
                                <div class="flex justify-between items-start mb-1">
                                    <h3 class="font-bold text-sm text-gray-900"><?php echo htmlspecialchars($notif['title']); ?></h3>
                                    <span class="text-xs uppercase font-bold text-gray-500 tracking-wider"><?php echo getTimeAgo($notif['created_at']); ?></span>
                                </div>
                                <p class="text-sm text-gray-700 mb-3"><?php echo htmlspecialchars($notif['message']); ?></p>

                                <!-- Action Buttons -->
                                <div class="flex gap-2">
                                    <?php if ($notif['can_appeal'] && $notif['appeal_status'] !== 'pending'): ?>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="action" value="appeal">
                                            <input type="hidden" name="notification_id" value="<?php echo $notif['id']; ?>">
                                            <button type="submit" class="bg-green-700 text-white px-4 py-1.5 rounded-lg text-xs font-bold hover:bg-green-800 transition-colors">
                                                Appeal
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                    <?php if ($notif['appeal_status'] === 'pending'): ?>
                                        <span class="bg-yellow-100 text-yellow-800 px-4 py-1.5 rounded-lg text-xs font-bold">
                                            Appeal Pending
                                        </span>
                                    <?php elseif ($notif['appeal_status'] === 'approved'): ?>
                                        <span class="bg-green-100 text-green-800 px-4 py-1.5 rounded-lg text-xs font-bold">
                                            Appeal Approved
                                        </span>
                                    <?php elseif ($notif['appeal_status'] === 'rejected'): ?>
                                        <span class="bg-red-100 text-red-800 px-4 py-1.5 rounded-lg text-xs font-bold">
                                            Appeal Rejected
                                        </span>
                                    <?php endif; ?>
                                    
                                    <?php if (!$notif['is_read']): ?>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="action" value="mark_read">
                                            <input type="hidden" name="notification_id" value="<?php echo $notif['id']; ?>">
                                            <button type="submit" class="text-gray-600 hover:text-gray-900 px-4 py-1.5 rounded-lg text-xs font-bold transition-colors">
                                                Mark Read
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- Delete Button (on hover) -->
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="action" value="dismiss">
                                <input type="hidden" name="notification_id" value="<?php echo $notif['id']; ?>">
                                <button type="submit" class="opacity-0 group-hover:opacity-100 p-2 text-gray-500 hover:text-red-600 transition-all self-start" title="Delete notification">
                                    <span class="material-symbols-outlined text-[20px]">delete</span>
                                </button>
                            </form>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <!-- Empty State -->
                    <div class="mt-8 pt-8 border-t border-gray-200 flex flex-col items-center text-center opacity-60">
                        <div class="w-24 h-24 mb-4 rounded-full bg-gray-100 flex items-center justify-center">
                            <span class="material-symbols-outlined text-5xl text-gray-400">done_all</span>
                        </div>
                        <p class="font-medium text-gray-700">You're all caught up!</p>
                        <p class="text-sm text-gray-600 mt-1">No notifications to display for this filter.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <!-- Mobile Bottom Navigation -->
    <nav class="md:hidden fixed bottom-0 w-full z-50 bg-white border-t border-gray-200 flex justify-around items-center h-16 px-2 shadow-lg">
        <a class="flex flex-col items-center justify-center text-gray-600 px-4 py-2 hover:text-green-700 transition-colors flex-1" href="/pages/student/dashboard.php">
            <span class="material-symbols-outlined">dashboard</span>
            <span class="text-xs font-bold mt-1">Home</span>
        </a>
        <a class="flex flex-col items-center justify-center text-gray-600 px-4 py-2 hover:text-green-700 transition-colors flex-1" href="/pages/student/attendance.php">
            <span class="material-symbols-outlined">history</span>
            <span class="text-xs font-bold mt-1">History</span>
        </a>
        <a class="flex flex-col items-center justify-center text-gray-600 px-4 py-2 hover:text-green-700 transition-colors flex-1" href="/pages/student/qr-attendance.php">
            <span class="material-symbols-outlined">qr_code_2</span>
            <span class="text-xs font-bold mt-1">QR</span>
        </a>
        <a class="flex flex-col items-center justify-center text-green-700 font-bold px-4 py-2 transition-colors flex-1" href="/pages/student/notifications.php">
            <span class="material-symbols-outlined" style="font-variation-settings: 'FILL' 1;">notifications</span>
            <span class="text-xs font-bold mt-1">Alerts</span>
            <?php if ($unreadCount > 0): ?>
                <span class="absolute -top-1 -right-1 bg-red-500 text-white text-xs font-bold px-1.5 py-0.5 rounded-full"><?php echo $unreadCount; ?></span>
            <?php endif; ?>
        </a>
        <a class="flex flex-col items-center justify-center text-gray-600 px-4 py-2 hover:text-green-700 transition-colors flex-1" href="/pages/student/profile.php">
            <span class="material-symbols-outlined">person</span>
            <span class="text-xs font-bold mt-1">Profile</span>
        </a>
    </nav>

    <script>
        // Simple mark as read interaction
        document.querySelectorAll('.notification-card').forEach(card => {
            card.addEventListener('click', function(e) {
                if (e.target.closest('button') || e.target.closest('form')) return;
                this.style.opacity = '0.7';
            });
        });
    </script>
</body>
</html>
