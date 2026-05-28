<?php
// Admin — mark daily attendance (pure PHP forms)

require_once __DIR__ . '/../../config/bootstrap.php';

use StudentAttendance\Utils\AdminAuth;

AdminAuth::require();

$today = $_GET['date'] ?? date('Y-m-d');
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $today)) {
    $today = date('Y-m-d');
}

$searchQ = trim($_GET['q'] ?? '');

// ——— POST actions ———
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $markDate = $_POST['attendance_date'] ?? $today;
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $markDate)) {
        $markDate = $today;
    }

    try {
        if (isset($_POST['bulk_status'])) {
            $bulkStatus = $_POST['bulk_status'];
            if (!in_array($bulkStatus, ['present', 'late', 'absent'], true)) {
                throw new \InvalidArgumentException('Invalid bulk status');
            }
            $allStudents = Utility::safeQuery('SELECT id FROM students', [], 'SELECT');
            foreach ($allStudents as $row) {
                AdminAuth::upsertAttendance((int) $row['id'], $markDate, $bulkStatus);
            }
            $label = ucfirst($bulkStatus);
            AdminAuth::redirect(
                '/pages/admin/attendance.php?date=' . urlencode($markDate) . '&q=' . urlencode($searchQ),
                "All students marked as {$label} for {$markDate}."
            );
        }

        if (isset($_POST['student_id'], $_POST['status'])) {
            $studentId = (int) $_POST['student_id'];
            $status = $_POST['status'];
            if ($studentId > 0 && in_array($status, ['present', 'late', 'absent'], true)) {
                AdminAuth::upsertAttendance($studentId, $markDate, $status);
                AdminAuth::redirect(
                    '/pages/admin/attendance.php?date=' . urlencode($markDate) . '&q=' . urlencode($searchQ),
                    'Attendance saved for ' . ucfirst($status) . '.'
                );
            }
        }
    } catch (\Throwable $e) {
        error_log('Admin attendance POST: ' . $e->getMessage());
        AdminAuth::redirect(
            '/pages/admin/attendance.php?date=' . urlencode($markDate),
            'Could not save attendance. ' . $e->getMessage()
        );
    }

    AdminAuth::redirect(
        '/pages/admin/attendance.php?date=' . urlencode($markDate),
        'Invalid request.'
    );
}

// ——— Load data ———
$students = [];
$attendanceToday = [];
$stats = AdminAuth::getStatsForDate($today);

try {
    $students = Utility::safeQuery(
        'SELECT id, name, admission_number, email FROM students ORDER BY name ASC',
        [],
        'SELECT'
    );
    $records = Utility::safeQuery(
        'SELECT student_id, status FROM attendance WHERE attendance_date = ?',
        [$today],
        'SELECT'
    );
    foreach ($records as $record) {
        $attendanceToday[(int) $record['student_id']] = $record['status'];
    }
} catch (\Throwable $e) {
    error_log('Admin attendance load: ' . $e->getMessage());
}

$students = AdminAuth::filterRows($students, $searchQ, ['name', 'email', 'admission_number']);

$pageTitle = 'Mark Attendance';
$pageHeading = 'Mark attendance';
$pageSubtitle = 'Date: ' . $today . ' — click a status button to save';

require __DIR__ . '/../../components/admin/shell-start.php';
?>

<form method="get" class="admin-card p-4 mb-4 flex flex-wrap items-end gap-4">
    <div>
        <label class="block text-xs font-semibold text-slate-600 mb-1">Date</label>
        <input type="date" name="date" value="<?= htmlspecialchars($today) ?>"
               class="border border-slate-200 rounded-lg px-3 py-2 text-sm"/>
    </div>
    <div class="flex-1 min-w-[12rem]">
        <label class="block text-xs font-semibold text-slate-600 mb-1">Search</label>
        <input type="search" name="q" value="<?= htmlspecialchars($searchQ) ?>"
               placeholder="Name, email, admission #…"
               class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm"/>
    </div>
    <button type="submit" class="bg-slate-800 hover:bg-slate-900 text-white font-semibold px-4 py-2 rounded-lg text-sm">
        Apply
    </button>
</form>

<div class="mb-4 flex flex-wrap items-center justify-between gap-3">
    <p class="text-sm text-slate-600">
        Marked: <span class="font-bold text-slate-900"><?= (int) $stats['marked'] ?></span>
        / <?= (int) $stats['total'] ?>
        (Present <?= (int) $stats['present'] ?>, Late <?= (int) $stats['late'] ?>, Absent <?= (int) $stats['absent'] ?>)
    </p>
    <form method="post" class="flex flex-wrap gap-2">
        <input type="hidden" name="attendance_date" value="<?= htmlspecialchars($today) ?>">
        <button type="submit" name="bulk_status" value="present"
                class="px-3 py-1.5 text-xs font-bold rounded-lg bg-green-600 text-white hover:bg-green-700">
            Mark all present
        </button>
        <button type="submit" name="bulk_status" value="late"
                class="px-3 py-1.5 text-xs font-bold rounded-lg bg-yellow-600 text-white hover:bg-yellow-700">
            Mark all late
        </button>
        <button type="submit" name="bulk_status" value="absent"
                class="px-3 py-1.5 text-xs font-bold rounded-lg bg-red-600 text-white hover:bg-red-700">
            Mark all absent
        </button>
    </form>
</div>

<div class="admin-card overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 border-b border-slate-200">
                <tr>
                    <th class="text-left px-6 py-3 font-semibold">Admission #</th>
                    <th class="text-left px-6 py-3 font-semibold">Name</th>
                    <th class="text-left px-6 py-3 font-semibold">Email</th>
                    <th class="text-center px-6 py-3 font-semibold">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                <?php if (empty($students)): ?>
                    <tr>
                        <td colspan="4" class="px-6 py-8 text-slate-500">
                            <?php if ($searchQ !== ''): ?>
                                No students match your search.
                            <?php else: ?>
                                No students in database. Run <code class="bg-slate-100 px-1 rounded">php scratch/setup_local.php</code>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($students as $student): ?>
                        <?php
                        $sid = (int) $student['id'];
                        $current = $attendanceToday[$sid] ?? '';
                        ?>
                        <tr class="hover:bg-slate-50">
                            <td class="px-6 py-3 text-slate-600"><?= htmlspecialchars($student['admission_number']) ?></td>
                            <td class="px-6 py-3 font-medium"><?= htmlspecialchars($student['name']) ?></td>
                            <td class="px-6 py-3 text-slate-600"><?= htmlspecialchars($student['email']) ?></td>
                            <td class="px-6 py-3">
                                <form method="post" class="flex justify-center gap-2 flex-wrap">
                                    <input type="hidden" name="attendance_date" value="<?= htmlspecialchars($today) ?>">
                                    <input type="hidden" name="student_id" value="<?= $sid ?>">
                                    <button type="submit" name="status" value="present"
                                            class="px-3 py-1 rounded text-xs font-bold <?= $current === 'present' ? 'bg-green-600 text-white ring-2 ring-green-300' : 'bg-green-100 text-green-800 hover:bg-green-600 hover:text-white' ?>">
                                        ✓ Present
                                    </button>
                                    <button type="submit" name="status" value="late"
                                            class="px-3 py-1 rounded text-xs font-bold <?= $current === 'late' ? 'bg-yellow-600 text-white ring-2 ring-yellow-300' : 'bg-yellow-100 text-yellow-800 hover:bg-yellow-600 hover:text-white' ?>">
                                        ⏱ Late
                                    </button>
                                    <button type="submit" name="status" value="absent"
                                            class="px-3 py-1 rounded text-xs font-bold <?= $current === 'absent' ? 'bg-red-600 text-white ring-2 ring-red-300' : 'bg-red-100 text-red-800 hover:bg-red-600 hover:text-white' ?>">
                                        ✗ Absent
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require __DIR__ . '/../../components/admin/shell-end.php'; ?>
