<?php
// pages/student/notifications.php
// Student Notifications Center - Alerts, Warnings, and Announcements

require_once __DIR__ . '/../../config/bootstrap.php';

use StudentAttendance\Utils\StudentAuth;

StudentAuth::require();
$studentId = StudentAuth::studentId();

$filterType = $_GET['filter'] ?? 'all';
if (!in_array($filterType, ['all', 'alerts', 'announcements'], true)) {
    $filterType = 'all';
}

$action = $_POST['action'] ?? null;
$notificationId = isset($_POST['notification_id']) ? (int) $_POST['notification_id'] : 0;

if ($notificationId > 0 && $action === 'mark_read') {
    try {
        Utility::safeQuery(
            'UPDATE notifications SET is_read = 1 WHERE id = ? AND student_id = ?',
            [$notificationId, $studentId],
            'UPDATE'
        );
    } catch (\Throwable $e) {
        error_log('Mark notification as read error: ' . $e->getMessage());
    }
}

if ($notificationId > 0 && $action === 'dismiss') {
    try {
        Utility::safeQuery(
            'DELETE FROM notifications WHERE id = ? AND student_id = ?',
            [$notificationId, $studentId],
            'DELETE'
        );
    } catch (\Throwable $e) {
        error_log('Delete notification error: ' . $e->getMessage());
    }
}

if ($notificationId > 0 && $action === 'appeal') {
    try {
        Utility::safeQuery(
            'UPDATE notifications SET appeal_status = "pending" WHERE id = ? AND student_id = ? AND can_appeal = 1',
            [$notificationId, $studentId],
            'UPDATE'
        );
    } catch (\Throwable $e) {
        error_log('Appeal submission error: ' . $e->getMessage());
    }
}

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

try {
    $unreadCount = Utility::safeQuery(
        'SELECT COUNT(*) as count FROM notifications WHERE student_id = ? AND is_read = 0',
        [$studentId],
        'SELECT',
        true
    );
    $unreadCount = (int) ($unreadCount['count'] ?? 0);
} catch (\Throwable $e) {
    $unreadCount = 0;
}

function getTimeAgo($timestamp) {
    $time = strtotime($timestamp);
    $diff = time() - $time;

    if ($diff < 60) {
        return 'just now';
    } elseif ($diff < 3600) {
        return floor($diff / 60) . 'min ago';
    } elseif ($diff < 86400) {
        return floor($diff / 3600) . 'h ago';
    } elseif ($diff < 604800) {
        $days = floor($diff / 86400);
        return $days === 1 ? 'Yesterday' : $days . ' days ago';
    }

    return date('M d', $time);
}

function getNotificationDisplay(array $notification): array {
    $display = [
        'icon' => 'info',
        'color_class' => 'bg-blue-100 text-blue-700',
    ];

    switch ($notification['type'] ?? '') {
        case 'attendance_alert':
            $display['icon'] = 'warning';
            $display['color_class'] = 'bg-red-100 text-red-700';
            break;
        case 'warning':
            $display['icon'] = 'priority_high';
            $display['color_class'] = 'bg-orange-100 text-orange-700';
            break;
        case 'confirmation':
            $display['icon'] = 'check_circle';
            $display['color_class'] = 'bg-emerald-100 text-emerald-700';
            break;
    }

    return $display;
}

$pageTitle = 'Notifications';
$pageHeading = 'Notifications';
$activeNav = 'notifications';
$showBack = true;

require __DIR__ . '/../../components/student/layout-start.php';
?>

<div class="space-y-6">
    <section class="bg-white border border-slate-200 rounded-xl p-5 shadow-sm">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <h2 class="text-xl font-bold text-slate-900">Notification center</h2>
                <p class="text-sm text-slate-600 mt-1">
                    <?php if ($unreadCount > 0): ?>
                        <?= $unreadCount ?> unread <?= $unreadCount === 1 ? 'notification' : 'notifications' ?>
                    <?php else: ?>
                        You are all caught up.
                    <?php endif; ?>
                </p>
            </div>
            <a href="/pages/student/settings.php" class="inline-flex items-center justify-center gap-2 rounded-lg border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                <span class="material-symbols-outlined text-base">settings</span>
                Settings
            </a>
        </div>
    </section>

    <nav class="flex items-center gap-2 overflow-x-auto pb-1" aria-label="Notification filters">
        <?php
        $filters = [
            'all' => 'All',
            'alerts' => 'Alerts',
            'announcements' => 'Announcements',
        ];
        ?>
        <?php foreach ($filters as $filter => $label): ?>
            <a href="?filter=<?= htmlspecialchars($filter) ?>"
               class="whitespace-nowrap px-4 py-2 rounded-lg font-semibold text-sm transition-colors <?= $filterType === $filter ? 'bg-emerald-600 text-white' : 'bg-white border border-slate-200 text-slate-700 hover:bg-slate-50' ?>">
                <?= htmlspecialchars($label) ?>
            </a>
        <?php endforeach; ?>
    </nav>

    <?php if (!empty($notifications)): ?>
        <div class="grid grid-cols-1 gap-4">
            <?php foreach ($notifications as $notif): ?>
                <?php $display = getNotificationDisplay($notif); ?>
                <article class="notification-card bg-white border border-slate-200 rounded-xl p-4 flex gap-4 hover:shadow-md transition-all group">
                    <div class="flex-shrink-0 w-12 h-12 rounded-lg <?= htmlspecialchars($display['color_class']) ?> flex items-center justify-center">
                        <span class="material-symbols-outlined" style="font-variation-settings: 'FILL' 1;"><?= htmlspecialchars($display['icon']) ?></span>
                    </div>

                    <div class="flex-1 min-w-0">
                        <div class="flex flex-col sm:flex-row sm:justify-between gap-1 mb-1">
                            <h3 class="font-bold text-sm text-slate-900"><?= htmlspecialchars($notif['title'] ?? 'Notification') ?></h3>
                            <span class="text-xs uppercase font-bold text-slate-500 tracking-wide"><?= htmlspecialchars(getTimeAgo($notif['created_at'] ?? 'now')) ?></span>
                        </div>
                        <p class="text-sm text-slate-700 mb-3"><?= htmlspecialchars($notif['message'] ?? '') ?></p>

                        <div class="flex flex-wrap gap-2">
                            <?php if (!empty($notif['can_appeal']) && ($notif['appeal_status'] ?? null) !== 'pending'): ?>
                                <form method="post">
                                    <input type="hidden" name="action" value="appeal">
                                    <input type="hidden" name="notification_id" value="<?= (int) $notif['id'] ?>">
                                    <button type="submit" class="bg-emerald-600 text-white px-4 py-1.5 rounded-lg text-xs font-bold hover:bg-emerald-700 transition-colors">
                                        Appeal
                                    </button>
                                </form>
                            <?php endif; ?>

                            <?php if (($notif['appeal_status'] ?? null) === 'pending'): ?>
                                <span class="bg-amber-100 text-amber-800 px-4 py-1.5 rounded-lg text-xs font-bold">Appeal Pending</span>
                            <?php elseif (($notif['appeal_status'] ?? null) === 'approved'): ?>
                                <span class="bg-emerald-100 text-emerald-800 px-4 py-1.5 rounded-lg text-xs font-bold">Appeal Approved</span>
                            <?php elseif (($notif['appeal_status'] ?? null) === 'rejected'): ?>
                                <span class="bg-red-100 text-red-800 px-4 py-1.5 rounded-lg text-xs font-bold">Appeal Rejected</span>
                            <?php endif; ?>

                            <?php if (empty($notif['is_read'])): ?>
                                <form method="post">
                                    <input type="hidden" name="action" value="mark_read">
                                    <input type="hidden" name="notification_id" value="<?= (int) $notif['id'] ?>">
                                    <button type="submit" class="border border-slate-200 text-slate-700 hover:bg-slate-50 px-4 py-1.5 rounded-lg text-xs font-bold transition-colors">
                                        Mark Read
                                    </button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>

                    <form method="post">
                        <input type="hidden" name="action" value="dismiss">
                        <input type="hidden" name="notification_id" value="<?= (int) $notif['id'] ?>">
                        <button type="submit" class="opacity-100 sm:opacity-0 sm:group-hover:opacity-100 p-2 text-slate-500 hover:text-red-600 transition-all" title="Delete notification">
                            <span class="material-symbols-outlined text-[20px]">delete</span>
                        </button>
                    </form>
                </article>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="bg-white border border-slate-200 rounded-xl p-8 text-center text-slate-500">
            <span class="material-symbols-outlined text-5xl text-slate-300 block mb-2">done_all</span>
            <p class="font-semibold text-slate-700">You're all caught up!</p>
            <p class="text-sm text-slate-600 mt-1">No notifications to display for this filter.</p>
        </div>
    <?php endif; ?>
</div>

<script>
    document.querySelectorAll('.notification-card').forEach(card => {
        card.addEventListener('click', function(e) {
            if (e.target.closest('button') || e.target.closest('form')) return;
            this.classList.add('opacity-80');
        });
    });
</script>

<?php require __DIR__ . '/../../components/student/layout-end.php'; ?>
