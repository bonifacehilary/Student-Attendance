<?php
// Admin dashboard — overview & quick actions

require_once __DIR__ . '/../../config/bootstrap.php';

use StudentAttendance\Utils\AdminAuth;

$today = date('Y-m-d');
$stats = AdminAuth::getStatsForDate($today);

$recentMarks = [];
try {
    $recentMarks = Utility::safeQuery(
        'SELECT s.name, s.admission_number, a.status, a.attendance_date
         FROM attendance a
         JOIN students s ON s.id = a.student_id
         WHERE a.attendance_date = ?
         ORDER BY s.name ASC
         LIMIT 10',
        [$today],
        'SELECT'
    );
} catch (\Throwable $e) {
    error_log('Admin dashboard recent: ' . $e->getMessage());
}

$activeQr = 0;
try {
    $qrRow = Utility::safeQuery(
        'SELECT COUNT(*) AS cnt FROM attendance_qr_sessions WHERE is_active = 1',
        [],
        'SELECT',
        true
    );
    $activeQr = (int) ($qrRow['cnt'] ?? 0);
} catch (\Throwable $e) {
    // table may be missing
}

$pageTitle = 'Admin Dashboard';
$pageHeading = 'Dashboard';
$pageSubtitle = 'Attendance overview for today';

require __DIR__ . '/../../components/admin/shell-start.php';
?>

<div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-8">
    <div class="admin-card admin-stat">
        <p class="admin-stat__label">Total students</p>
        <p class="admin-stat__value"><?= (int) $stats['total'] ?></p>
    </div>
    <div class="admin-card admin-stat">
        <p class="admin-stat__label">Marked today</p>
        <p class="admin-stat__value text-blue-600"><?= (int) $stats['marked'] ?></p>
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
    <a href="/pages/admin/attendance.php" class="admin-card p-6 block hover:border-emerald-400 transition group">
        <span class="material-symbols-outlined text-3xl text-emerald-600">fact_check</span>
        <h2 class="text-lg font-bold text-slate-900 mt-3 group-hover:text-emerald-700">Mark attendance</h2>
        <p class="text-sm text-slate-600 mt-1">Record present, late, or absent for each student.</p>
    </a>
    <a href="/pages/admin/create_qr.php" class="admin-card p-6 block hover:border-emerald-400 transition group">
        <span class="material-symbols-outlined text-3xl text-emerald-600">qr_code_2</span>
        <h2 class="text-lg font-bold text-slate-900 mt-3 group-hover:text-emerald-700">QR session</h2>
        <p class="text-sm text-slate-600 mt-1"><?= (int) $activeQr ?> active session(s). Create a code for students to scan.</p>
    </a>
    <a href="/pages/admin/students.php" class="admin-card p-6 block hover:border-emerald-400 transition group">
        <span class="material-symbols-outlined text-3xl text-emerald-600">groups</span>
        <h2 class="text-lg font-bold text-slate-900 mt-3 group-hover:text-emerald-700">Students</h2>
        <p class="text-sm text-slate-600 mt-1">View registered students and admission numbers.</p>
    </a>
    <a href="/pages/admin/reports.php" class="admin-card p-6 block hover:border-emerald-400 transition group">
        <span class="material-symbols-outlined text-3xl text-emerald-600">summarize</span>
        <h2 class="text-lg font-bold text-slate-900 mt-3 group-hover:text-emerald-700">Reports</h2>
        <p class="text-sm text-slate-600 mt-1">Attendance summary by date range.</p>
    </a>
</div>

<div class="admin-card overflow-hidden">
    <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between">
        <h2 class="font-bold text-slate-900">Today&apos;s marks</h2>
        <a href="/pages/admin/attendance.php" class="text-sm font-medium text-emerald-700 hover:underline">View all →</a>
    </div>
    <?php if (empty($recentMarks)): ?>
        <p class="px-6 py-8 text-slate-500 text-sm">No attendance recorded yet today.</p>
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

<?php require __DIR__ . '/../../components/admin/shell-end.php'; ?>
