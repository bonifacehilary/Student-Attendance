<?php
// pages/admin/qrs.php
// Admin: List and manage QR sessions

require_once __DIR__ . '/../../config/bootstrap.php';
require_once __DIR__ . '/../../utils/Utility.php';

session_start();

if (!isset($_SESSION['admin_id'])) {
    // Allow access for development; in production require admin session
}

// Handle actions: deactivate/delete
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $id = intval($_POST['id'] ?? 0);

    try {
        if ($action === 'deactivate' && $id) {
            Utility::safeQuery('UPDATE attendance_qr_sessions SET is_active = 0 WHERE id = ?', [$id], 'UPDATE');
        }
        if ($action === 'activate' && $id) {
            Utility::safeQuery('UPDATE attendance_qr_sessions SET is_active = 1 WHERE id = ?', [$id], 'UPDATE');
        }
        if ($action === 'delete' && $id) {
            Utility::safeQuery('DELETE FROM attendance_qr_sessions WHERE id = ?', [$id], 'DELETE');
        }
    } catch (\Throwable $e) {
        error_log('QR sessions action error: ' . $e->getMessage());
    }
}

// Fetch sessions
try {
    $sessions = Utility::safeQuery('SELECT id, code, class_id, session_name, created_by, created_date, expires_at, is_active FROM attendance_qr_sessions ORDER BY created_date DESC', [], 'SELECT');
} catch (\Throwable $e) {
    error_log('Fetch QR sessions error: ' . $e->getMessage());
    $sessions = [];
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Manage QR Sessions - Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-gray-50">
    <div class="max-w-5xl mx-auto p-6">
        <div class="flex items-center justify-between mb-6">
            <h1 class="text-2xl font-bold">QR Sessions</h1>
            <a href="/pages/admin/create_qr.php" class="bg-green-700 text-white px-3 py-2 rounded">Create New</a>
        </div>

        <div class="bg-white rounded shadow overflow-hidden">
            <table class="w-full text-left">
                <thead class="bg-gray-100">
                    <tr>
                        <th class="px-4 py-3">Code</th>
                        <th class="px-4 py-3">Session</th>
                        <th class="px-4 py-3">Class ID</th>
                        <th class="px-4 py-3">Created</th>
                        <th class="px-4 py-3">Expires</th>
                        <th class="px-4 py-3">Active</th>
                        <th class="px-4 py-3">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($sessions)): ?>
                        <tr><td class="px-4 py-4" colspan="7">No sessions found.</td></tr>
                    <?php else: foreach ($sessions as $s): ?>
                        <tr class="border-t">
                            <td class="px-4 py-3 font-mono font-semibold"><?php echo htmlspecialchars($s['code']); ?></td>
                            <td class="px-4 py-3"><?php echo htmlspecialchars($s['session_name']); ?></td>
                            <td class="px-4 py-3"><?php echo htmlspecialchars($s['class_id']); ?></td>
                            <td class="px-4 py-3"><?php echo htmlspecialchars($s['created_date']); ?></td>
                            <td class="px-4 py-3"><?php echo htmlspecialchars($s['expires_at']); ?></td>
                            <td class="px-4 py-3"><?php echo $s['is_active'] ? 'Yes' : 'No'; ?></td>
                            <td class="px-4 py-3">
                                <form method="post" style="display:inline-block;">
                                    <input type="hidden" name="id" value="<?php echo $s['id']; ?>">
                                    <?php if ($s['is_active']): ?>
                                        <input type="hidden" name="action" value="deactivate">
                                        <button class="px-2 py-1 bg-yellow-500 text-white rounded">Deactivate</button>
                                    <?php else: ?>
                                        <input type="hidden" name="action" value="activate">
                                        <button class="px-2 py-1 bg-green-600 text-white rounded">Activate</button>
                                    <?php endif; ?>
                                </form>
                                <form method="post" style="display:inline-block; margin-left:6px;" onsubmit="return confirm('Delete this session?');">
                                    <input type="hidden" name="id" value="<?php echo $s['id']; ?>">
                                    <input type="hidden" name="action" value="delete">
                                    <button class="px-2 py-1 bg-red-600 text-white rounded">Delete</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>
