<?php
require_once __DIR__ . '/../../config/bootstrap.php';

use StudentAttendance\Utils\StudentAuth;

StudentAuth::require();
$studentId = StudentAuth::studentId();
$receivedSessions = StudentAuth::getReceivableQrSessions($studentId);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $result = StudentAuth::markQrAttendance($studentId, $_POST['qr_code'] ?? '');
    StudentAuth::redirect(
        '/pages/student/qr-attendance.php',
        $result['message'],
        $result['status'] === 'success' ? 'success' : ($result['status'] === 'warning' ? 'warning' : 'error')
    );
}

$pageTitle = 'QR Attendance';
$pageHeading = 'QR Attendance';
$activeNav = 'qr';
$showBack = true;

require __DIR__ . '/../../components/student/layout-start.php';
?>

<div class="bg-white border border-slate-200 rounded-xl p-6 shadow-sm mb-6">
    <div class="flex items-center justify-between gap-3 mb-4">
        <h2 class="text-lg font-bold text-slate-900 flex items-center gap-2">
            <span class="material-symbols-outlined text-emerald-700">qr_code_scanner</span>
            Received QR sessions
        </h2>
        <span class="text-xs font-semibold text-emerald-700 bg-emerald-50 px-2 py-1 rounded">
            <?= count($receivedSessions) ?> active
        </span>
    </div>

    <?php if (empty($receivedSessions)): ?>
        <p class="text-sm text-slate-600">No active QR sessions from your teacher or admin yet.</p>
    <?php else: ?>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
            <?php foreach ($receivedSessions as $session): ?>
                <div class="border border-slate-200 rounded-lg p-4 bg-slate-50">
                    <div class="flex items-start justify-between gap-3 mb-3">
                        <div>
                            <p class="font-semibold text-slate-900"><?= htmlspecialchars($session['session_name'] ?? 'QR session') ?></p>
                            <p class="text-xs text-slate-500">
                                <?= htmlspecialchars($session['source'] ?? 'Admin') ?>:
                                <?= htmlspecialchars($session['source_name'] ?? 'Administration') ?>
                            </p>
                        </div>
                        <span class="font-mono text-sm font-bold text-emerald-800 bg-white border border-emerald-200 rounded px-2 py-1">
                            <?= htmlspecialchars($session['code'] ?? '') ?>
                        </span>
                    </div>
                    <div class="flex items-center justify-between gap-3">
                        <p class="text-xs text-slate-500">Expires <?= htmlspecialchars($session['expires_at'] ?? '') ?></p>
                        <?php if (!empty($session['already_marked'])): ?>
                            <span class="text-xs font-semibold text-emerald-700">Marked present</span>
                        <?php else: ?>
                            <form method="post">
                                <input type="hidden" name="qr_code" value="<?= htmlspecialchars($session['code'] ?? '') ?>">
                                <button type="submit"
                                        class="px-3 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-lg text-xs">
                                    Mark present
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<div class="bg-white border border-slate-200 rounded-xl p-6 shadow-sm mb-6">
    <h2 class="text-lg font-bold text-slate-900 mb-2 flex items-center gap-2">
        <span class="material-symbols-outlined text-emerald-700">qr_code_2</span>
        Enter session code
    </h2>
    <p class="text-sm text-slate-600 mb-4">Type the code your teacher shows in class (e.g. from Create QR session).</p>
    <form method="post" class="flex flex-col sm:flex-row gap-2">
        <input type="text" name="qr_code" required autocomplete="off"
               class="flex-1 border border-slate-200 rounded-lg px-3 py-2 text-sm font-mono uppercase"
               placeholder="e.g. A1B2C3D4"/>
        <button type="submit"
                class="px-6 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-lg text-sm">
            Mark present
        </button>
    </form>
</div>

<div class="bg-sky-50 border border-sky-200 rounded-xl p-4 text-sm text-sky-900">
    <p class="font-semibold mb-1">How it works</p>
    <ol class="list-decimal list-inside space-y-1 text-sky-800">
        <li>Teacher creates a QR session in the teacher or admin panel.</li>
        <li>You enter the same code here before it expires.</li>
        <li>Today&apos;s attendance is saved as <strong>present</strong>.</li>
    </ol>
</div>

<?php require __DIR__ . '/../../components/student/layout-end.php'; ?>
