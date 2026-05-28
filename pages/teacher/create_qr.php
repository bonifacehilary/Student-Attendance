<?php
require_once __DIR__ . '/../../config/bootstrap.php';

use StudentAttendance\Utils\TeacherAuth;

$assignedClass = TeacherAuth::requireClass();
$teacherId = TeacherAuth::teacherId();

$errors = [];
$generatedCode = $_SESSION['teacher_last_qr_code'] ?? null;
$expiresAt = $_SESSION['teacher_last_qr_expires'] ?? null;
unset($_SESSION['teacher_last_qr_code'], $_SESSION['teacher_last_qr_expires']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $sessionName = trim($_POST['session_name'] ?? '');
    $duration = max(1, (int) ($_POST['duration_minutes'] ?? 60));

    if ($sessionName === '') {
        $sessionName = $assignedClass . ' — ' . date('M j, g:i A');
    }

    try {
        $generatedCode = strtoupper(bin2hex(random_bytes(4)));
        $createdDate = date('Y-m-d H:i:s');
        $expiresAt = date('Y-m-d H:i:s', strtotime("+{$duration} minutes"));

        Utility::safeQuery(
            'INSERT INTO attendance_qr_sessions (code, class_id, session_name, created_by, created_date, expires_at, is_active)
             VALUES (?, ?, ?, ?, ?, ?, 1)',
            [$generatedCode, null, $sessionName, $teacherId, $createdDate, $expiresAt],
            'INSERT'
        );

        $_SESSION['teacher_last_qr_code'] = $generatedCode;
        $_SESSION['teacher_last_qr_expires'] = $expiresAt;
        TeacherAuth::redirect('/pages/teacher/create_qr.php', 'QR session created: ' . $generatedCode);
    } catch (\Throwable $e) {
        error_log('Teacher create QR: ' . $e->getMessage());
        $errors[] = 'Failed to create QR session.';
    }
}

$pageTitle = 'Create QR Session';
$pageHeading = 'Create QR session';
$pageSubtitle = 'For class: ' . $assignedClass;

require __DIR__ . '/../../components/teacher/shell-start.php';
?>

<?php if ($errors): ?>
    <div class="mb-4 rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-800">
        <?php foreach ($errors as $err): ?>
            <p><?= htmlspecialchars($err) ?></p>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<div class="grid lg:grid-cols-2 gap-6">
    <form method="post" class="admin-card p-6 space-y-4">
        <div>
            <label class="block text-sm font-semibold text-slate-700 mb-1">Session name</label>
            <input name="session_name"
                   class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm"
                   placeholder="<?= htmlspecialchars($assignedClass) ?> — lecture"
                   value="<?= htmlspecialchars($_POST['session_name'] ?? '') ?>">
        </div>
        <div>
            <label class="block text-sm font-semibold text-slate-700 mb-1">Duration (minutes)</label>
            <input name="duration_minutes" type="number" min="1" value="<?= (int) ($_POST['duration_minutes'] ?? 60) ?>"
                   class="w-32 border border-slate-200 rounded-lg px-3 py-2 text-sm">
        </div>
        <div class="flex flex-wrap gap-3 pt-2">
            <button type="submit" class="bg-sky-600 hover:bg-sky-700 text-white font-semibold px-4 py-2 rounded-lg text-sm">
                Create session
            </button>
            <a href="/pages/teacher/qrs.php" class="text-sm text-slate-600 hover:underline py-2">View my sessions</a>
        </div>
    </form>

    <?php if ($generatedCode): ?>
        <div class="admin-card p-6">
            <h3 class="font-bold text-slate-900 mb-3">Generated code</h3>
            <p class="text-2xl font-mono font-bold text-sky-800 bg-slate-50 border rounded-lg px-4 py-3 inline-block">
                <?= htmlspecialchars($generatedCode) ?>
            </p>
            <p class="text-sm text-slate-600 mt-4">Expires: <?= htmlspecialchars($expiresAt ?? '') ?></p>
            <p class="text-sm text-slate-500 mt-2">Students enter this on
                <a href="/pages/student/qr-attendance.php" class="text-sky-700 font-semibold hover:underline">QR attendance</a>.
            </p>
        </div>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/../../components/teacher/shell-end.php'; ?>
