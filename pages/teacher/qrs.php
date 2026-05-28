<?php
require_once __DIR__ . '/../../config/bootstrap.php';

use StudentAttendance\Utils\TeacherAuth;

TeacherAuth::require();
$teacherId = TeacherAuth::teacherId();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $id = (int) ($_POST['id'] ?? 0);
    $flash = 'Action completed.';

    if ($id && TeacherAuth::ownsQrSession($id)) {
        try {
            if ($action === 'deactivate') {
                Utility::safeQuery('UPDATE attendance_qr_sessions SET is_active = 0 WHERE id = ?', [$id], 'UPDATE');
                $flash = 'Session deactivated.';
            } elseif ($action === 'activate') {
                Utility::safeQuery('UPDATE attendance_qr_sessions SET is_active = 1 WHERE id = ?', [$id], 'UPDATE');
                $flash = 'Session activated.';
            } elseif ($action === 'delete') {
                Utility::safeQuery('DELETE FROM attendance_qr_sessions WHERE id = ?', [$id], 'DELETE');
                $flash = 'Session deleted.';
            } else {
                $flash = 'Invalid action.';
            }
        } catch (\Throwable $e) {
            $flash = 'Action failed: ' . $e->getMessage();
        }
    } else {
        $flash = 'You can only manage your own QR sessions.';
    }

    TeacherAuth::redirect('/pages/teacher/qrs.php', $flash);
}

$sessions = [];
try {
    $sessions = Utility::safeQuery(
        'SELECT id, code, session_name, created_date, expires_at, is_active
         FROM attendance_qr_sessions
         WHERE created_by = ?
         ORDER BY created_date DESC',
        [$teacherId],
        'SELECT'
    );
} catch (\Throwable $e) {
    error_log('Teacher QR list: ' . $e->getMessage());
}

$pageTitle = 'My QR Sessions';
$pageHeading = 'My QR sessions';
$pageSubtitle = count($sessions) . ' session(s)';

require __DIR__ . '/../../components/teacher/shell-start.php';
?>

<div class="mb-4 flex flex-wrap justify-between items-center gap-3">
    <p class="text-sm text-slate-600">Sessions you created for your class.</p>
    <a href="/pages/teacher/create_qr.php"
       class="inline-flex items-center gap-2 bg-sky-600 hover:bg-sky-700 text-white font-semibold px-4 py-2 rounded-lg text-sm">
        <span class="material-symbols-outlined text-lg">add</span>
        Create session
    </a>
</div>

<div class="admin-card overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm text-left">
            <thead class="bg-slate-50 border-b border-slate-200">
                <tr>
                    <th class="px-4 py-3 font-semibold">Code</th>
                    <th class="px-4 py-3 font-semibold">Session</th>
                    <th class="px-4 py-3 font-semibold">Created</th>
                    <th class="px-4 py-3 font-semibold">Expires</th>
                    <th class="px-4 py-3 font-semibold">Active</th>
                    <th class="px-4 py-3 font-semibold">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                <?php if (empty($sessions)): ?>
                    <tr>
                        <td colspan="6" class="px-4 py-8 text-slate-500">
                            No QR sessions yet.
                            <a href="/pages/teacher/create_qr.php" class="text-sky-700 font-semibold hover:underline">Create one</a>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($sessions as $s): ?>
                        <tr class="hover:bg-slate-50">
                            <td class="px-4 py-3 font-mono font-bold text-sky-800"><?= htmlspecialchars($s['code']) ?></td>
                            <td class="px-4 py-3"><?= htmlspecialchars($s['session_name'] ?? '') ?></td>
                            <td class="px-4 py-3 text-slate-600"><?= htmlspecialchars($s['created_date'] ?? '') ?></td>
                            <td class="px-4 py-3 text-slate-600"><?= htmlspecialchars($s['expires_at'] ?? '') ?></td>
                            <td class="px-4 py-3">
                                <?= !empty($s['is_active']) ? '<span class="text-emerald-700 font-semibold">Yes</span>' : '<span class="text-slate-400">No</span>' ?>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex flex-wrap gap-1">
                                    <?php if (!empty($s['is_active'])): ?>
                                        <form method="post" class="inline">
                                            <input type="hidden" name="id" value="<?= (int) $s['id'] ?>">
                                            <input type="hidden" name="action" value="deactivate">
                                            <button type="submit" class="px-2 py-1 text-xs font-semibold bg-amber-500 text-white rounded hover:bg-amber-600">Deactivate</button>
                                        </form>
                                    <?php else: ?>
                                        <form method="post" class="inline">
                                            <input type="hidden" name="id" value="<?= (int) $s['id'] ?>">
                                            <input type="hidden" name="action" value="activate">
                                            <button type="submit" class="px-2 py-1 text-xs font-semibold bg-sky-600 text-white rounded hover:bg-sky-700">Activate</button>
                                        </form>
                                    <?php endif; ?>
                                    <form method="post" class="inline">
                                        <input type="hidden" name="id" value="<?= (int) $s['id'] ?>">
                                        <input type="hidden" name="action" value="delete">
                                        <button type="submit" class="px-2 py-1 text-xs font-semibold bg-red-600 text-white rounded hover:bg-red-700">Delete</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require __DIR__ . '/../../components/teacher/shell-end.php'; ?>
