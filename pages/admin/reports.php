<?php
// Admin — attendance reports by date range (pure PHP)

require_once __DIR__ . '/../../config/bootstrap.php';

use StudentAttendance\Utils\AdminAuth;

AdminAuth::require();

$from = $_GET['from'] ?? date('Y-m-d', strtotime('-7 days'));
$to = $_GET['to'] ?? date('Y-m-d');

if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $from)) {
    $from = date('Y-m-d', strtotime('-7 days'));
}
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $to)) {
    $to = date('Y-m-d');
}

if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    try {
        $rows = Utility::safeQuery(
            'SELECT s.name, s.admission_number,
                    SUM(CASE WHEN a.status = ? THEN 1 ELSE 0 END) AS present,
                    SUM(CASE WHEN a.status = ? THEN 1 ELSE 0 END) AS late,
                    SUM(CASE WHEN a.status = ? THEN 1 ELSE 0 END) AS absent,
                    COUNT(a.id) AS total_marked
             FROM students s
             LEFT JOIN attendance a ON a.student_id = s.id AND a.attendance_date BETWEEN ? AND ?
             GROUP BY s.id, s.name, s.admission_number
             ORDER BY s.name ASC',
            ['present', 'late', 'absent', $from, $to],
            'SELECT'
        );
    } catch (\Throwable $e) {
        $rows = [];
    }

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="attendance-report-' . $from . '-to-' . $to . '.csv"');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['Name', 'Admission #', 'Present', 'Late', 'Absent', 'Total marked']);
    foreach ($rows as $row) {
        fputcsv($out, [
            $row['name'],
            $row['admission_number'],
            (int) $row['present'],
            (int) $row['late'],
            (int) $row['absent'],
            (int) $row['total_marked'],
        ]);
    }
    fclose($out);
    exit;
}

$summary = [];
$byStudent = [];

try {
    $summary = Utility::safeQuery(
        'SELECT attendance_date,
                SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) AS present,
                SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) AS late,
                SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) AS absent
         FROM attendance
         WHERE attendance_date BETWEEN ? AND ?
         GROUP BY attendance_date
         ORDER BY attendance_date DESC',
        ['present', 'late', 'absent', $from, $to],
        'SELECT'
    );

    $byStudent = Utility::safeQuery(
        'SELECT s.name, s.admission_number,
                SUM(CASE WHEN a.status = ? THEN 1 ELSE 0 END) AS present,
                SUM(CASE WHEN a.status = ? THEN 1 ELSE 0 END) AS late,
                SUM(CASE WHEN a.status = ? THEN 1 ELSE 0 END) AS absent,
                COUNT(a.id) AS total_marked
         FROM students s
         LEFT JOIN attendance a ON a.student_id = s.id AND a.attendance_date BETWEEN ? AND ?
         GROUP BY s.id, s.name, s.admission_number
         ORDER BY s.name ASC',
        ['present', 'late', 'absent', $from, $to],
        'SELECT'
    );
} catch (\Throwable $e) {
    error_log('Admin reports: ' . $e->getMessage());
}

$pageTitle = 'Reports';
$pageHeading = 'Attendance reports';
$pageSubtitle = htmlspecialchars($from) . ' — ' . htmlspecialchars($to);

require __DIR__ . '/../../components/admin/shell-start.php';
?>

<form method="get" class="admin-card p-4 mb-6 flex flex-wrap items-end gap-4">
    <div>
        <label class="block text-xs font-semibold text-slate-600 mb-1">From</label>
        <input type="date" name="from" value="<?= htmlspecialchars($from) ?>"
               class="border border-slate-200 rounded-lg px-3 py-2 text-sm"/>
    </div>
    <div>
        <label class="block text-xs font-semibold text-slate-600 mb-1">To</label>
        <input type="date" name="to" value="<?= htmlspecialchars($to) ?>"
               class="border border-slate-200 rounded-lg px-3 py-2 text-sm"/>
    </div>
    <button type="submit" class="bg-emerald-600 hover:bg-emerald-700 text-white font-semibold px-4 py-2 rounded-lg text-sm">
        Apply
    </button>
    <a href="/pages/admin/reports.php?export=csv&amp;from=<?= urlencode($from) ?>&amp;to=<?= urlencode($to) ?>"
       class="bg-slate-800 hover:bg-slate-900 text-white font-semibold px-4 py-2 rounded-lg text-sm inline-flex items-center">
        Export CSV
    </a>
</form>

<div class="grid lg:grid-cols-2 gap-6">
    <div class="admin-card overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-200 font-bold text-slate-900">By day</div>
        <?php if (empty($summary)): ?>
            <p class="px-6 py-6 text-sm text-slate-500">No records in this range.</p>
        <?php else: ?>
            <table class="w-full text-sm">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-4 py-2 text-left">Date</th>
                        <th class="px-4 py-2 text-right">Present</th>
                        <th class="px-4 py-2 text-right">Late</th>
                        <th class="px-4 py-2 text-right">Absent</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php foreach ($summary as $day): ?>
                        <tr>
                            <td class="px-4 py-2"><?= htmlspecialchars($day['attendance_date']) ?></td>
                            <td class="px-4 py-2 text-right text-emerald-700"><?= (int) $day['present'] ?></td>
                            <td class="px-4 py-2 text-right text-amber-700"><?= (int) $day['late'] ?></td>
                            <td class="px-4 py-2 text-right text-red-700"><?= (int) $day['absent'] ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>

    <div class="admin-card overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-200 font-bold text-slate-900">By student</div>
        <div class="overflow-x-auto max-h-96 overflow-y-auto">
            <table class="w-full text-sm">
                <thead class="bg-slate-50 sticky top-0">
                    <tr>
                        <th class="px-4 py-2 text-left">Student</th>
                        <th class="px-4 py-2 text-right">P</th>
                        <th class="px-4 py-2 text-right">L</th>
                        <th class="px-4 py-2 text-right">A</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php foreach ($byStudent as $row): ?>
                        <tr>
                            <td class="px-4 py-2">
                                <span class="font-medium"><?= htmlspecialchars($row['name']) ?></span>
                                <span class="text-xs text-slate-500 block"><?= htmlspecialchars($row['admission_number']) ?></span>
                            </td>
                            <td class="px-4 py-2 text-right"><?= (int) $row['present'] ?></td>
                            <td class="px-4 py-2 text-right"><?= (int) $row['late'] ?></td>
                            <td class="px-4 py-2 text-right"><?= (int) $row['absent'] ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../../components/admin/shell-end.php'; ?>
