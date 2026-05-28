<?php
// Admin — create QR attendance session (pure PHP)

require_once __DIR__ . '/../../config/bootstrap.php';

use StudentAttendance\Utils\AdminAuth;

AdminAuth::require();

$errors = [];
$generatedCode = $_SESSION['admin_last_qr_code'] ?? null;
$expiresAt = $_SESSION['admin_last_qr_expires'] ?? null;
unset($_SESSION['admin_last_qr_code'], $_SESSION['admin_last_qr_expires']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $sessionName = trim($_POST['session_name'] ?? '');
    $classId = (int) ($_POST['class_id'] ?? 0) ?: null;
    $duration = max(1, (int) ($_POST['duration_minutes'] ?? 60));

    if ($sessionName === '') {
        $errors[] = 'Session name is required.';
    } else {
        try {
            $generatedCode = strtoupper(bin2hex(random_bytes(4)));
            $createdBy = $_SESSION['admin_id'] ?? 0;
            $createdDate = date('Y-m-d H:i:s');
            $expiresAt = date('Y-m-d H:i:s', strtotime("+{$duration} minutes"));

            Utility::safeQuery(
                'INSERT INTO attendance_qr_sessions (code, class_id, session_name, created_by, created_date, expires_at, is_active)
                 VALUES (?, ?, ?, ?, ?, ?, 1)',
                [$generatedCode, $classId, $sessionName, $createdBy, $createdDate, $expiresAt],
                'INSERT'
            );

            $_SESSION['admin_last_qr_code'] = $generatedCode;
            $_SESSION['admin_last_qr_expires'] = $expiresAt;
            AdminAuth::redirect('/pages/admin/create_qr.php', 'QR session created: ' . $generatedCode);
        } catch (\Throwable $e) {
            error_log('Create QR: ' . $e->getMessage());
            $errors[] = 'Failed to create QR session. Ensure the database is set up.';
        }
    }
}

$pageTitle = 'Create QR Session';
$pageHeading = 'Create QR session';
$pageSubtitle = 'Students enter the code on the QR attendance page';

require __DIR__ . '/../../components/admin/shell-start.php';
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
            <input name="session_name" required
                   class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm"
                   placeholder="e.g. Morning lecture"
                   value="<?= htmlspecialchars($_POST['session_name'] ?? '') ?>">
        </div>
        <div>
            <label class="block text-sm font-semibold text-slate-700 mb-1">Class ID (optional)</label>
            <input name="class_id" type="number" min="0"
                   class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm"
                   value="<?= htmlspecialchars($_POST['class_id'] ?? '') ?>">
        </div>
        <div>
            <label class="block text-sm font-semibold text-slate-700 mb-1">Duration (minutes)</label>
            <input name="duration_minutes" type="number" min="1" value="<?= (int) ($_POST['duration_minutes'] ?? 60) ?>"
                   class="w-32 border border-slate-200 rounded-lg px-3 py-2 text-sm">
        </div>
        <div class="flex flex-wrap gap-3 pt-2">
            <button type="submit" class="bg-emerald-600 hover:bg-emerald-700 text-white font-semibold px-4 py-2 rounded-lg text-sm">
                Create session
            </button>
            <a href="/pages/admin/qrs.php" class="text-sm text-slate-600 hover:underline py-2">View all sessions</a>
        </div>
    </form>

    <?php if ($generatedCode): ?>
        <div class="admin-card p-6">
            <h3 class="font-bold text-slate-900 mb-3">Generated code</h3>
            <p class="text-2xl font-mono font-bold text-emerald-800 bg-slate-50 border rounded-lg px-4 py-3 inline-block">
                <?= htmlspecialchars($generatedCode) ?>
            </p>
            <p class="text-sm text-slate-600 mt-4">Expires: <?= htmlspecialchars($expiresAt ?? '') ?></p>
            <p class="text-sm text-slate-500 mt-2">Students use this on <a href="/pages/student/qr-attendance.php" class="text-emerald-700 font-semibold hover:underline">QR attendance</a>.</p>
            <p class="mt-4">
                <a href="/pages/admin/qrs.php" class="text-sm font-semibold text-emerald-700 hover:underline">Manage sessions →</a>
            </p>
        </div>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/../../components/admin/shell-end.php'; ?>
