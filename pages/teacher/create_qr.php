<?php
require_once __DIR__ . '/../../config/bootstrap.php';

use StudentAttendance\Utils\TeacherAuth;

$assignedClass = TeacherAuth::requireClass();
$teacherId = TeacherAuth::teacherId();

$errors = [];
$generatedCode = $_SESSION['teacher_last_qr_code'] ?? null;
$expiresAt = $_SESSION['teacher_last_qr_expires'] ?? null;
unset($_SESSION['teacher_last_qr_code'], $_SESSION['teacher_last_qr_expires']);

// Helper function to create QR session
$createQrSession = function($sessionName = null, $duration = 60) use ($assignedClass, $teacherId) {
    if (empty($sessionName)) {
        $sessionName = $assignedClass . ' — ' . date('M j, g:i A');
    }

    try {
        $generatedCode = strtoupper(bin2hex(random_bytes(4)));
        $createdDate = date('Y-m-d H:i:s');
        $expiresAt = date('Y-m-d H:i:s', strtotime("+{$duration} minutes"));

        \StudentAttendance\Utils\Utility::safeQuery(
            'INSERT INTO attendance_qr_sessions (code, class_id, session_name, created_by, created_date, expires_at, is_active)
             VALUES (?, ?, ?, ?, ?, ?, 1)',
            [$generatedCode, null, $sessionName, $teacherId, $createdDate, $expiresAt],
            'INSERT'
        );

        return ['code' => $generatedCode, 'expires' => $expiresAt];
    } catch (\Throwable $e) {
        error_log('Teacher create QR: ' . $e->getMessage());
        throw $e;
    }
};

if ($_SERVER['REQUEST_METHOD'] === 'GET' && empty($_GET['generated'])) {
    try {
        $result = $createQrSession();
        $_SESSION['teacher_last_qr_code'] = $result['code'];
        $_SESSION['teacher_last_qr_expires'] = $result['expires'];
        TeacherAuth::redirect('/pages/teacher/create_qr.php?generated=1', 'QR session auto-generated: ' . $result['code']);
    } catch (\Throwable $e) {
        $errors[] = 'Failed to auto-generate QR session.';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'custom';

    try {
        if ($action === 'auto_generate') {
            // Quick auto-generate with defaults (60 minutes, class name + timestamp)
            $result = $createQrSession($assignedClass . ' — ' . date('M j, g:i A'), 60);
            $_SESSION['teacher_last_qr_code'] = $result['code'];
            $_SESSION['teacher_last_qr_expires'] = $result['expires'];
            TeacherAuth::redirect('/pages/teacher/create_qr.php?generated=1', 'QR session auto-generated: ' . $result['code']);
        } else {
            // Custom session from form
            $sessionName = trim($_POST['session_name'] ?? '');
            $duration = max(1, (int) ($_POST['duration_minutes'] ?? 60));

            if ($sessionName === '') {
                $sessionName = $assignedClass . ' — ' . date('M j, g:i A');
            }

            $result = $createQrSession($sessionName, $duration);
            $_SESSION['teacher_last_qr_code'] = $result['code'];
            $_SESSION['teacher_last_qr_expires'] = $result['expires'];
            TeacherAuth::redirect('/pages/teacher/create_qr.php?generated=1', 'QR session created: ' . $result['code']);
        }
    } catch (\Throwable $e) {
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
    <!-- Quick Auto-Generate Button -->
    <form method="post" class="admin-card p-6 flex flex-col justify-between">
        <div>
            <h3 class="font-bold text-slate-900 mb-2">Quick Start</h3>
            <p class="text-sm text-slate-600 mb-6">Generate a QR session instantly with default settings (60 minutes, class name + current time).</p>
        </div>
        <input type="hidden" name="action" value="auto_generate">
        <button type="submit" class="bg-emerald-600 hover:bg-emerald-700 text-white font-bold px-6 py-3 rounded-lg text-sm w-full flex items-center justify-center gap-2">
            <span class="material-symbols-outlined">flash_on</span>
            Auto Generate Session
        </button>
    </form>

    <!-- Custom Session Form -->
    <form method="post" class="admin-card p-6 space-y-4">
        <h3 class="font-bold text-slate-900 mb-2">Custom Settings</h3>
        <input type="hidden" name="action" value="custom">

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
</div>

<?php if ($generatedCode): ?>
    <div class="admin-card p-6 mt-6 border-2 border-emerald-200 bg-emerald-50">
        <div class="flex items-center gap-2 mb-3">
            <span class="material-symbols-outlined text-emerald-700 text-2xl">check_circle</span>
            <h3 class="font-bold text-slate-900">QR Session Created</h3>
        </div>
        <p class="text-2xl font-mono font-bold text-emerald-900 bg-white border-2 border-emerald-300 rounded-lg px-4 py-3 inline-block mb-4">
            <?= htmlspecialchars($generatedCode) ?>
        </p>
        <div class="space-y-2 text-sm">
            <p class="text-slate-700"><strong>Expires:</strong> <?= htmlspecialchars($expiresAt ?? '') ?></p>
            <p class="text-slate-600">Share this code with students or they can enter it on <a href="/pages/student/qr-attendance.php" class="text-emerald-700 font-semibold hover:underline">QR attendance page</a>.</p>
        </div>
    </div>
<?php endif; ?>

<?php require __DIR__ . '/../../components/teacher/shell-end.php'; ?>
