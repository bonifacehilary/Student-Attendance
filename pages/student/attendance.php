<?php
require_once __DIR__ . '/../../config/bootstrap.php';

use StudentAttendance\Utils\StudentAuth;

StudentAuth::require();
$studentId = StudentAuth::studentId();

$month = $_GET['month'] ?? date('Y-m');
if (!preg_match('/^\d{4}-\d{2}$/', $month)) {
    $month = date('Y-m');
}

$records = StudentAuth::getAttendanceHistory($studentId, 60, $month);
$stats = StudentAuth::getAttendanceStats($studentId);

$pageTitle = 'Attendance History';
$pageHeading = 'Attendance history';
$activeNav = 'attendance';
$showBack = true;

require __DIR__ . '/../../components/student/layout-start.php';
?>

<form method="get" class="bg-white border border-slate-200 rounded-lg p-4 mb-4 flex flex-wrap items-end gap-3">
    <div>
        <label class="block text-xs font-semibold text-slate-600 mb-1">Month</label>
        <input type="month" name="month" value="<?= htmlspecialchars($month) ?>"
               class="border border-slate-200 rounded-lg px-3 py-2 text-sm"/>
    </div>
    <button type="submit" class="bg-slate-800 text-white font-semibold px-4 py-2 rounded-lg text-sm">Apply</button>
</form>

<div class="grid grid-cols-3 gap-2 mb-6 text-center text-sm">
    <div class="bg-white border rounded-lg p-3">
        <p class="text-2xl font-bold text-emerald-700"><?= (int) $stats['present_days'] ?></p>
        <p class="text-slate-500">Present</p>
    </div>
    <div class="bg-white border rounded-lg p-3">
        <p class="text-2xl font-bold text-amber-600"><?= (int) $stats['late_days'] ?></p>
        <p class="text-slate-500">Late</p>
    </div>
    <div class="bg-white border rounded-lg p-3">
        <p class="text-2xl font-bold text-red-600"><?= (int) $stats['absent_days'] ?></p>
        <p class="text-slate-500">Absent</p>
    </div>
</div>

<?php if (empty($records)): ?>
    <div class="bg-white border border-slate-200 rounded-lg p-8 text-center text-slate-500">
        <span class="material-symbols-outlined text-5xl text-slate-300 block mb-2">calendar_today</span>
        No records for this period.
    </div>
<?php else: ?>
    <div class="space-y-2">
        <?php foreach ($records as $record): ?>
            <?php
            $status = $record['status'];
            $badge = $status === 'present' ? 'bg-emerald-100 text-emerald-800' : ($status === 'late' ? 'bg-amber-100 text-amber-800' : 'bg-red-100 text-red-800');
            $icon = $status === 'present' ? 'check_circle' : ($status === 'late' ? 'schedule' : 'cancel');
            ?>
            <div class="bg-white border border-slate-200 rounded-lg p-4 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <span class="material-symbols-outlined text-emerald-700"><?= $icon ?></span>
                    <div>
                        <p class="font-semibold"><?= htmlspecialchars(ucfirst($status)) ?></p>
                        <p class="text-sm text-slate-500"><?= htmlspecialchars(date('F j, Y', strtotime($record['attendance_date']))) ?></p>
                    </div>
                </div>
                <span class="px-2 py-0.5 rounded text-xs font-bold <?= $badge ?>"><?= htmlspecialchars(ucfirst($status)) ?></span>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php require __DIR__ . '/../../components/student/layout-end.php'; ?>
