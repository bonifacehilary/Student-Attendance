<?php
// pages/admin/create_qr.php
// Admin: Create QR Attendance Session

require_once __DIR__ . '/../../config/bootstrap.php';
require_once __DIR__ . '/../../utils/Utility.php';

session_start();

// Simple admin auth guard - adjust as needed
if (!isset($_SESSION['admin_id'])) {
    // If no admin session, show a warning but allow form for development convenience
    $allow = true;
} else {
    $allow = true;
}

$errors = [];
$success = null;
$generatedCode = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $sessionName = trim($_POST['session_name'] ?? '');
    $classId = intval($_POST['class_id'] ?? 0) ?: null;
    $duration = intval($_POST['duration_minutes'] ?? 60);

    if ($sessionName === '') {
        $errors[] = 'Session name is required.';
    }
    if ($duration <= 0) $duration = 60;

    if (empty($errors)) {
        try {
            // Generate a short unique code
            $generatedCode = strtoupper(bin2hex(random_bytes(4)));
            $createdBy = $_SESSION['admin_id'] ?? 0;
            $createdDate = date('Y-m-d H:i:s');
            $expiresAt = date('Y-m-d H:i:s', strtotime("+{$duration} minutes"));

            Utility::safeQuery(
                'INSERT INTO attendance_qr_sessions (code, class_id, session_name, created_by, created_date, expires_at, is_active) VALUES (?, ?, ?, ?, ?, ?, ?)',
                [$generatedCode, $classId, $sessionName, $createdBy, $createdDate, $expiresAt, 1],
                'INSERT'
            );

            $success = 'QR session created successfully.';
        } catch (\Throwable $e) {
            error_log('Create QR error: ' . $e->getMessage());
            $errors[] = 'Failed to create QR session.';
        }
    }
}

?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Create QR Session - Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-gray-50">
    <div class="max-w-3xl mx-auto p-6">
        <h1 class="text-2xl font-bold mb-4">Create QR Attendance Session</h1>

        <?php if ($errors): ?>
            <div class="mb-4 bg-red-50 border border-red-200 text-red-800 p-3 rounded">
                <?php foreach ($errors as $err) echo '<div>' . htmlspecialchars($err) . '</div>'; ?>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="mb-4 bg-green-50 border border-green-200 text-green-800 p-3 rounded">
                <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>

        <form method="post" class="space-y-4 bg-white p-6 rounded shadow">
            <div>
                <label class="block text-sm font-medium text-gray-700">Session Name</label>
                <input name="session_name" class="mt-1 block w-full border border-gray-200 rounded p-2" placeholder="e.g. Morning Chemistry" value="<?php echo htmlspecialchars($_POST['session_name'] ?? ''); ?>">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Class ID (optional)</label>
                <input name="class_id" class="mt-1 block w-full border border-gray-200 rounded p-2" placeholder="Class ID" value="<?php echo htmlspecialchars($_POST['class_id'] ?? ''); ?>">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Duration (minutes)</label>
                <input name="duration_minutes" type="number" min="1" class="mt-1 block w-32 border border-gray-200 rounded p-2" value="<?php echo htmlspecialchars($_POST['duration_minutes'] ?? 60); ?>">
            </div>
            <div class="flex items-center justify-between">
                <button class="bg-green-700 text-white px-4 py-2 rounded font-semibold">Create QR Session</button>
                <a href="/pages/admin/qrs.php" class="text-sm text-gray-600 hover:underline">Manage QR Sessions</a>
            </div>
        </form>

        <?php if ($generatedCode): ?>
            <div class="mt-6 bg-white p-4 rounded shadow">
                <h3 class="font-semibold mb-2">Generated Code</h3>
                <div class="flex items-center gap-4">
                    <div class="text-xl font-mono p-3 border rounded bg-gray-50"><?php echo htmlspecialchars($generatedCode); ?></div>
                    <div>
                        <img alt="QR Code" src="https://chart.googleapis.com/chart?cht=qr&chs=200x200&chl=<?php echo urlencode($generatedCode); ?>" />
                    </div>
                </div>
                <p class="mt-3 text-sm text-gray-600">Expires at: <?php echo htmlspecialchars($expiresAt ?? ''); ?></p>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
