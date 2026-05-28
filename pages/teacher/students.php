<?php
require_once __DIR__ . '/../../config/bootstrap.php';

use StudentAttendance\Utils\TeacherAuth;

$assignedClass = TeacherAuth::requireClass();
$searchQ = trim($_GET['q'] ?? '');

if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    try {
        $rows = Utility::safeQuery(
            'SELECT name, admission_number, email, student_class, phone, created_at
             FROM students WHERE student_class = ? ORDER BY name ASC',
            [$assignedClass],
            'SELECT'
        );
        $rows = TeacherAuth::filterRows($rows, $searchQ, ['name', 'email', 'admission_number', 'phone']);
    } catch (\Throwable $e) {
        $rows = [];
    }

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="class-' . preg_replace('/[^a-z0-9]+/i', '-', $assignedClass) . '-' . date('Y-m-d') . '.csv"');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['Name', 'Admission #', 'Email', 'Class', 'Phone', 'Registered']);
    foreach ($rows as $row) {
        fputcsv($out, [
            $row['name'],
            $row['admission_number'],
            $row['email'],
            $row['student_class'] ?? $assignedClass,
            $row['phone'] ?? '',
            $row['created_at'] ?? '',
        ]);
    }
    fclose($out);
    exit;
}

$students = [];
try {
    $students = Utility::safeQuery(
        'SELECT id, name, email, admission_number, student_class, phone, created_at
         FROM students WHERE student_class = ? ORDER BY name ASC',
        [$assignedClass],
        'SELECT'
    );
} catch (\Throwable $e) {
    error_log('Teacher students: ' . $e->getMessage());
}

$students = TeacherAuth::filterRows($students, $searchQ, ['name', 'email', 'admission_number', 'phone']);

$pageTitle = 'My Class';
$pageHeading = 'My class';
$pageSubtitle = $assignedClass . ' — ' . count($students) . ' student(s)';

require __DIR__ . '/../../components/teacher/shell-start.php';
?>

<form method="get" class="admin-card p-4 mb-4 flex flex-wrap items-end gap-4">
    <div class="flex-1 min-w-[12rem]">
        <label class="block text-xs font-semibold text-slate-600 mb-1">Search</label>
        <input type="search" name="q" value="<?= htmlspecialchars($searchQ) ?>"
               placeholder="Name, email, admission #…"
               class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm"/>
    </div>
    <button type="submit" class="bg-slate-800 hover:bg-slate-900 text-white font-semibold px-4 py-2 rounded-lg text-sm">
        Search
    </button>
    <a href="/pages/teacher/students.php?export=csv&amp;q=<?= urlencode($searchQ) ?>"
       class="bg-sky-600 hover:bg-sky-700 text-white font-semibold px-4 py-2 rounded-lg text-sm">
        Export CSV
    </a>
    <?php if ($searchQ !== ''): ?>
        <a href="/pages/teacher/students.php" class="text-sm text-slate-600 hover:underline py-2">Clear</a>
    <?php endif; ?>
</form>

<div class="admin-card overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 border-b border-slate-200">
                <tr>
                    <th class="text-left px-6 py-3 font-semibold">Name</th>
                    <th class="text-left px-6 py-3 font-semibold">Admission #</th>
                    <th class="text-left px-6 py-3 font-semibold">Email</th>
                    <th class="text-left px-6 py-3 font-semibold">Phone</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                <?php if (empty($students)): ?>
                    <tr><td colspan="4" class="px-6 py-8 text-slate-500">No students in this class.</td></tr>
                <?php else: ?>
                    <?php foreach ($students as $s): ?>
                        <tr class="hover:bg-slate-50">
                            <td class="px-6 py-3 font-medium"><?= htmlspecialchars($s['name']) ?></td>
                            <td class="px-6 py-3 font-mono text-slate-600"><?= htmlspecialchars($s['admission_number']) ?></td>
                            <td class="px-6 py-3 text-slate-600"><?= htmlspecialchars($s['email']) ?></td>
                            <td class="px-6 py-3 text-slate-600"><?= htmlspecialchars($s['phone'] ?? '—') ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require __DIR__ . '/../../components/teacher/shell-end.php'; ?>
