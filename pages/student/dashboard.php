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

<div class="grid grid-cols-2 gap-3 mb-6 text-center text-sm">
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
</div>

<div class="bg-white border border-slate-200 rounded-lg p-4 mb-6">
    <h3 class="text-sm font-bold text-slate-900 mb-2">Attendance Rate</h3>
    <div class="flex items-end gap-3">
        <div class="flex-1">
            <div class="h-2 bg-slate-200 rounded-full overflow-hidden">
                <div class="h-full bg-emerald-600" style="width: <?= $attendanceRate ?>%"></div>
            </div>
        </div>
        <p class="text-lg font-bold text-slate-900 min-w-max"><?= $attendanceRate ?>%</p>
    </div>
</div>

<div class="bg-white border border-slate-200 rounded-lg p-4 mb-6">
    <h3 class="text-sm font-bold text-slate-900 mb-3">Today's Status</h3>
    <div class="flex items-center justify-between">
        <div>
            <p class="text-xs text-slate-500">Today, <?= date('M d, Y') ?></p>
            <p class="text-sm font-bold text-slate-900 mt-1">Status: <span class="text-<?= $todayStatusColor ?>-600"><?= htmlspecialchars($todayStatusText) ?></span></p>
        </div>
        <span class="material-symbols-outlined text-2xl text-slate-400">
            <?= match ($todayStatus['status'] ?? null) {
                'present' => 'check_circle',
                'late' => 'schedule',
                'absent' => 'cancel',
                default => 'help'
            } ?>
        </span>
    </div>
</div>

<div class="bg-white border border-slate-200 rounded-lg p-4">
    <h3 class="text-sm font-bold text-slate-900 mb-3">Quick Actions</h3>
    <div class="grid grid-cols-1 gap-2">
        <a href="/pages/student/qr-attendance.php" class="flex items-center gap-3 p-3 border border-slate-200 rounded-lg hover:bg-slate-50 text-slate-900 text-sm font-semibold">
            <span class="material-symbols-outlined text-emerald-700">qr_code_2</span>
            Mark QR Attendance
        </a>
        <a href="/pages/student/attendance.php" class="flex items-center gap-3 p-3 border border-slate-200 rounded-lg hover:bg-slate-50 text-slate-900 text-sm font-semibold">
            <span class="material-symbols-outlined text-blue-700">calendar_today</span>
            View History
        </a>
        <a href="/pages/student/report.php" class="flex items-center gap-3 p-3 border border-slate-200 rounded-lg hover:bg-slate-50 text-slate-900 text-sm font-semibold">
            <span class="material-symbols-outlined text-amber-700">description</span>
            Generate Report
        </a>
    </div>
</div>

<?php require __DIR__ . '/../../components/student/layout-end.php'; ?>
