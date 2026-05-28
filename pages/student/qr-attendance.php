<?php
require_once __DIR__ . '/../../config/bootstrap.php';

use StudentAttendance\Utils\StudentAuth;

StudentAuth::require();
$studentId = StudentAuth::studentId();

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
