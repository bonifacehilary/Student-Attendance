<?php
// pages/student/dashboard.php
// Student Dashboard - Main hub for viewing attendance and notifications

require_once __DIR__ . '/../../config/bootstrap.php';

use StudentAttendance\Utils\StudentAuth;

StudentAuth::require();
$studentId = StudentAuth::studentId();

// Fetch student and attendance data
$student = StudentAuth::getStudent($studentId);
$stats = StudentAuth::getAttendanceStats($studentId);
$todayStatus = StudentAuth::getTodayStatus($studentId);
$nextClass = StudentAuth::getNextClass($studentId);
$notifications = StudentAuth::getRecentNotifications($studentId, 4);
$qrSessions = StudentAuth::getReceivableQrSessions($studentId);

$attendanceRate = $stats['total_days'] > 0 ? round(($stats['present_days'] / $stats['total_days']) * 100, 0) : 0;
$todayStatusText = $todayStatus ? ucfirst($todayStatus['status']) : 'Not Marked';
$todayStatusColor = match ($todayStatus['status'] ?? null) {
    'present' => 'emerald',
    'late' => 'amber',
    'absent' => 'red',
    default => 'slate'
};

$pageTitle = 'Dashboard';
$pageHeading = 'Welcome, ' . htmlspecialchars($student['name'] ?? 'Student');
$activeNav = 'dashboard';

require __DIR__ . '/../../components/student/layout-start.php';
?>

<div class="space-y-6">
    <section class="bg-white border border-slate-200 rounded-xl p-5 shadow-sm">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <p class="text-sm text-slate-500">
                    <?= htmlspecialchars($student['student_class'] ?: 'Class not assigned') ?>
                    <?php if (!empty($student['department_name'])): ?>
                        <span class="mx-1">/</span><?= htmlspecialchars($student['department_name']) ?>
                    <?php endif; ?>
                </p>
                <h2 class="text-2xl font-bold text-slate-900 mt-1">Student portal</h2>
            </div>
            <a href="/pages/student/qr-attendance.php" class="inline-flex items-center justify-center gap-2 rounded-lg bg-emerald-600 px-4 py-2 text-sm font-bold text-white hover:bg-emerald-700">
                <span class="material-symbols-outlined text-base">qr_code_scanner</span>
                Mark attendance
            </a>
        </div>
    </section>

    <section class="grid grid-cols-2 lg:grid-cols-4 gap-3 text-center text-sm">
        <div class="bg-white border border-slate-200 rounded-lg p-4">
            <p class="text-2xl font-bold text-emerald-700"><?= (int) $stats['present_days'] ?></p>
            <p class="text-slate-500 text-xs mt-1">Present</p>
        </div>
        <div class="bg-white border border-slate-200 rounded-lg p-4">
            <p class="text-2xl font-bold text-amber-600"><?= (int) $stats['late_days'] ?></p>
            <p class="text-slate-500 text-xs mt-1">Late</p>
        </div>
        <div class="bg-white border border-slate-200 rounded-lg p-4">
            <p class="text-2xl font-bold text-red-600"><?= (int) $stats['absent_days'] ?></p>
            <p class="text-slate-500 text-xs mt-1">Absent</p>
        </div>
        <div class="bg-white border border-slate-200 rounded-lg p-4">
            <p class="text-2xl font-bold text-slate-800"><?= (int) $stats['total_days'] ?></p>
            <p class="text-slate-500 text-xs mt-1">Total</p>
        </div>
    </section>

    <section class="grid lg:grid-cols-[1.25fr_0.75fr] gap-6">
        <div class="bg-white border border-slate-200 rounded-xl p-5 shadow-sm">
            <div class="flex items-center justify-between gap-3 mb-3">
                <h3 class="text-sm font-bold text-slate-900">Attendance Rate</h3>
                <span class="text-lg font-bold text-slate-900"><?= $attendanceRate ?>%</span>
            </div>
            <div class="h-2 bg-slate-200 rounded-full overflow-hidden">
                <div class="h-full bg-emerald-600" style="width: <?= $attendanceRate ?>%"></div>
            </div>
            <div class="mt-4 flex items-center justify-between">
                <div>
                    <p class="text-xs text-slate-500">Today, <?= date('M d, Y') ?></p>
                    <p class="text-sm font-bold text-slate-900 mt-1">
                        Status:
                        <span class="text-<?= $todayStatusColor ?>-600"><?= htmlspecialchars($todayStatusText) ?></span>
                    </p>
                </div>
                <span class="material-symbols-outlined text-3xl text-slate-400">
                    <?= match ($todayStatus['status'] ?? null) {
                        'present' => 'check_circle',
                        'late' => 'schedule',
                        'absent' => 'cancel',
                        default => 'help'
                    } ?>
                </span>
            </div>
        </div>

        <div class="bg-white border border-slate-200 rounded-xl p-5 shadow-sm">
            <h3 class="text-sm font-bold text-slate-900 mb-3">Next class</h3>
            <?php if ($nextClass): ?>
                <p class="font-bold text-slate-900"><?= htmlspecialchars($nextClass['course_name']) ?></p>
                <p class="text-sm text-slate-600 mt-1"><?= htmlspecialchars($nextClass['lecture_title'] ?: 'Scheduled lecture') ?></p>
                <p class="text-xs text-slate-500 mt-3"><?= htmlspecialchars($nextClass['starts_at_label'] ?? '') ?></p>
                <p class="text-xs text-slate-500">Room: <?= htmlspecialchars($nextClass['room'] ?: 'TBA') ?></p>
            <?php else: ?>
                <p class="text-sm text-slate-600">No upcoming timetable entries are published yet.</p>
            <?php endif; ?>
        </div>
    </section>

    <section class="grid lg:grid-cols-2 gap-6">
        <div class="bg-white border border-slate-200 rounded-xl p-5 shadow-sm">
            <div class="flex items-center justify-between mb-3">
                <h3 class="text-sm font-bold text-slate-900">Active QR sessions</h3>
                <a href="/pages/student/qr-attendance.php" class="text-xs font-semibold text-emerald-700 hover:underline">Open</a>
            </div>
            <?php if (empty($qrSessions)): ?>
                <p class="text-sm text-slate-600">No active QR sessions are available for your class.</p>
            <?php else: ?>
                <div class="space-y-2">
                    <?php foreach (array_slice($qrSessions, 0, 3) as $session): ?>
                        <div class="flex items-center justify-between gap-3 rounded-lg border border-slate-200 bg-slate-50 px-3 py-2">
                            <div>
                                <p class="text-sm font-semibold text-slate-900"><?= htmlspecialchars($session['session_name'] ?? 'QR session') ?></p>
                                <p class="text-xs text-slate-500"><?= htmlspecialchars($session['source_name'] ?? 'Administration') ?></p>
                            </div>
                            <span class="font-mono text-xs font-bold text-emerald-800"><?= htmlspecialchars($session['code'] ?? '') ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <div class="bg-white border border-slate-200 rounded-xl p-5 shadow-sm">
            <div class="flex items-center justify-between mb-3">
                <h3 class="text-sm font-bold text-slate-900">Notifications</h3>
                <a href="/pages/student/notifications.php" class="text-xs font-semibold text-emerald-700 hover:underline">View all</a>
            </div>
            <?php if (empty($notifications)): ?>
                <p class="text-sm text-slate-600">No notifications yet.</p>
            <?php else: ?>
                <div class="space-y-2">
                    <?php foreach ($notifications as $note): ?>
                        <a href="/pages/student/notifications.php" class="block rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 hover:border-emerald-200">
                            <p class="text-sm font-semibold text-slate-900"><?= htmlspecialchars($note['title'] ?? 'Notification') ?></p>
                            <p class="text-xs text-slate-500 truncate"><?= htmlspecialchars($note['message'] ?? '') ?></p>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <section class="bg-white border border-slate-200 rounded-xl p-5 shadow-sm">
        <h3 class="text-sm font-bold text-slate-900 mb-3">Quick Actions</h3>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-2">
            <a href="/pages/student/attendance.php" class="flex items-center gap-3 p-3 border border-slate-200 rounded-lg hover:bg-slate-50 text-slate-900 text-sm font-semibold">
                <span class="material-symbols-outlined text-blue-700">calendar_today</span>
                View History
            </a>
            <a href="/pages/student/timetable.php" class="flex items-center gap-3 p-3 border border-slate-200 rounded-lg hover:bg-slate-50 text-slate-900 text-sm font-semibold">
                <span class="material-symbols-outlined text-purple-700">schedule</span>
                Timetable
            </a>
            <a href="/pages/student/report.php" class="flex items-center gap-3 p-3 border border-slate-200 rounded-lg hover:bg-slate-50 text-slate-900 text-sm font-semibold">
                <span class="material-symbols-outlined text-amber-700">description</span>
                Generate Report
            </a>
        </div>
    </section>
</div>

<?php require __DIR__ . '/../../components/student/layout-end.php'; ?>
