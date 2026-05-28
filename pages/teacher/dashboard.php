<?php
require_once __DIR__ . '/../../config/bootstrap.php';

use StudentAttendance\Utils\TeacherAuth;

TeacherAuth::require();
$assignedClass = TeacherAuth::assignedClass();
$today = date('Y-m-d');
$stats = $assignedClass !== ''
    ? TeacherAuth::getStatsForDate($today, $assignedClass)
    : ['marked' => 0, 'total' => 0, 'present' => 0, 'late' => 0, 'absent' => 0];

$recentMarks = [];
if ($assignedClass !== '') {
    try {
        $recentMarks = Utility::safeQuery(
            'SELECT s.name, s.admission_number, a.status
             FROM attendance a
             INNER JOIN students s ON s.id = a.student_id
             WHERE a.attendance_date = ? AND s.student_class = ?
             ORDER BY s.name ASC
             LIMIT 10',
            [$today, $assignedClass],
            'SELECT'
        );
    } catch (\Throwable $e) {
        error_log('Teacher dashboard: ' . $e->getMessage());
    }
}

$activeQr = 0;
try {
    $qrRow = Utility::safeQuery(
        'SELECT COUNT(*) AS cnt FROM attendance_qr_sessions WHERE is_active = 1 AND created_by = ?',
        [TeacherAuth::teacherId()],
        'SELECT',
        true
    );
    $activeQr = (int) ($qrRow['cnt'] ?? 0);
} catch (\Throwable $e) {
    error_log('Teacher dashboard QR count: ' . $e->getMessage());
}

$pageTitle = 'Teacher Dashboard';
$pageHeading = 'Dashboard';
$pageSubtitle = $assignedClass !== ''
    ? 'Class: ' . $assignedClass . ' — today\'s overview'
    : 'No class assigned — contact administration';

require __DIR__ . '/../../components/teacher/shell-start.php';
?>

<?php if ($assignedClass === ''): ?>
    <div class="mb-6 rounded-lg border border-amber-200 bg-amber-50 px-4 py-4 text-sm text-amber-900">
        Your account has no assigned class. Attendance and reports are unavailable until an administrator updates your profile.
    </div>
<?php else: ?>

<div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-8">
    <div class="admin-card admin-stat">
        <p class="admin-stat__label">Students in class</p>
        <p class="admin-stat__value"><?= (int) $stats['total'] ?></p>
    </div>
    <div class="admin-card admin-stat">
        <p class="admin-stat__label">Marked today</p>
        <p class="admin-stat__value text-sky-600"><?= (int) $stats['marked'] ?></p>
    </div>
    <div class="admin-card admin-stat">
        <p class="admin-stat__label">Present</p>
        <p class="admin-stat__value text-emerald-600"><?= (int) $stats['present'] ?></p>
    </div>
    <div class="admin-card admin-stat">
        <p class="admin-stat__label">Late</p>
        <p class="admin-stat__value text-amber-600"><?= (int) $stats['late'] ?></p>
    </div>
    <div class="admin-card admin-stat">
        <p class="admin-stat__label">Absent</p>
        <p class="admin-stat__value text-red-600"><?= (int) $stats['absent'] ?></p>
    </div>
</div>

<div class="grid md:grid-cols-2 gap-6 mb-8">
    <a href="/pages/teacher/attendance.php" class="admin-card p-6 block hover:border-sky-400 transition group">
        <span class="material-symbols-outlined text-3xl text-sky-600">fact_check</span>
        <h2 class="text-lg font-bold text-slate-900 mt-3 group-hover:text-sky-700">Mark attendance</h2>
        <p class="text-sm text-slate-600 mt-1">Record present, late, or absent for your class.</p>
    </a>
    <a href="/pages/teacher/create_qr.php" class="admin-card p-6 block hover:border-sky-400 transition group">
        <span class="material-symbols-outlined text-3xl text-sky-600">qr_code_2</span>
        <h2 class="text-lg font-bold text-slate-900 mt-3 group-hover:text-sky-700">QR session</h2>
        <p class="text-sm text-slate-600 mt-1"><?= (int) $activeQr ?> active session(s) you created.</p>
    </a>
    <a href="/pages/teacher/students.php" class="admin-card p-6 block hover:border-sky-400 transition group">
        <span class="material-symbols-outlined text-3xl text-sky-600">groups</span>
        <h2 class="text-lg font-bold text-slate-900 mt-3 group-hover:text-sky-700">My class</h2>
        <p class="text-sm text-slate-600 mt-1">View students in <?= htmlspecialchars($assignedClass) ?>.</p>
    </a>
    <a href="/pages/teacher/reports.php" class="admin-card p-6 block hover:border-sky-400 transition group">
        <span class="material-symbols-outlined text-3xl text-sky-600">summarize</span>
        <h2 class="text-lg font-bold text-slate-900 mt-3 group-hover:text-sky-700">Reports</h2>
        <p class="text-sm text-slate-600 mt-1">Attendance summary for your class.</p>
    </a>
</div>

<div class="admin-card overflow-hidden">
    <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between">
        <h2 class="font-bold text-slate-900">Today&apos;s marks</h2>
        <a href="/pages/teacher/attendance.php" class="text-sm font-medium text-sky-700 hover:underline">Mark attendance →</a>
    </div>
    <?php if (empty($recentMarks)): ?>
        <p class="px-6 py-8 text-slate-500 text-sm">No attendance recorded yet today for this class.</p>
    <?php else: ?>
        <table class="w-full text-left text-sm">
            <thead class="bg-slate-50 border-b border-slate-200">
                <tr>
                    <th class="px-6 py-3 font-semibold">Student</th>
                    <th class="px-6 py-3 font-semibold">Admission #</th>
                    <th class="px-6 py-3 font-semibold">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                <?php foreach ($recentMarks as $row): ?>
                    <tr>
                        <td class="px-6 py-3 font-medium"><?= htmlspecialchars($row['name']) ?></td>
                        <td class="px-6 py-3 text-slate-600"><?= htmlspecialchars($row['admission_number']) ?></td>
                        <td class="px-6 py-3">
                            <span class="inline-flex px-2 py-0.5 rounded text-xs font-bold uppercase
                                <?= $row['status'] === 'present' ? 'bg-emerald-100 text-emerald-800' : ($row['status'] === 'late' ? 'bg-amber-100 text-amber-800' : 'bg-red-100 text-red-800') ?>">
                                <?= htmlspecialchars($row['status']) ?>
                            </span>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>
<?php endif; ?>

<?php require __DIR__ . '/../../components/teacher/shell-end.php'; ?>
