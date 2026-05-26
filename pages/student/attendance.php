<?php
// pages/student/attendance.php
// Student Attendance History Page

require_once __DIR__ . '/../../config/bootstrap.php';
require_once __DIR__ . '/../../utils/Utility.php';

session_start();

// Check if student is logged in
if (!isset($_SESSION['student_id'])) {
    header('Location: /pages/student/login.php');
    exit;
}

$studentId = $_SESSION['student_id'];

// Fetch student data
try {
    $student = Utility::safeQuery(
        'SELECT id, name FROM students WHERE id = ? LIMIT 1',
        [$studentId],
        'SELECT',
        true
    );
} catch (\Throwable $e) {
    error_log('Attendance page student fetch error: ' . $e->getMessage());
    $student = ['name' => 'Student'];
}

// Fetch attendance records
try {
    $attendanceRecords = Utility::safeQuery(
        'SELECT 
            id,
            attendance_date,
            status,
            created_at
        FROM attendance 
        WHERE student_id = ?
        ORDER BY attendance_date DESC
        LIMIT 30',
        [$studentId],
        'SELECT'
    );
} catch (\Throwable $e) {
    error_log('Attendance records fetch error: ' . $e->getMessage());
    $attendanceRecords = [];
}
?>
<!DOCTYPE html>
<html class="light" lang="en">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>Attendance History | EduAttend</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #f7f9fb; }
        .material-symbols-outlined { font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24; }
    </style>
</head>
<body class="pb-24 min-h-screen">
    <!-- TopAppBar -->
    <header class="bg-white border-b border-gray-200 sticky top-0 z-40 flex items-center justify-between px-6 w-full h-16">
        <div class="flex items-center gap-4">
            <a href="/pages/student/dashboard.php" class="active:scale-95 transition-transform duration-150 p-2">
                <span class="material-symbols-outlined text-green-700">arrow_back</span>
            </a>
            <h1 class="text-xl font-bold text-gray-900">Attendance History</h1>
        </div>
    </header>

    <main class="max-w-2xl mx-auto px-4 py-6">
        <!-- Attendance Records -->
        <?php if (!empty($attendanceRecords)): ?>
        <div class="space-y-3">
            <?php foreach ($attendanceRecords as $record): ?>
            <div class="bg-white border border-gray-200 rounded-lg p-4 flex items-center justify-between hover:shadow-sm transition-shadow">
                <div class="flex items-center gap-4">
                    <div class="w-10 h-10 rounded-full flex items-center justify-center <?php echo $record['status'] === 'present' ? 'bg-green-100' : ($record['status'] === 'late' ? 'bg-yellow-100' : 'bg-red-100'); ?>">
                        <span class="material-symbols-outlined <?php echo $record['status'] === 'present' ? 'text-green-700' : ($record['status'] === 'late' ? 'text-yellow-700' : 'text-red-700'); ?>">
                            <?php echo $record['status'] === 'present' ? 'check_circle' : ($record['status'] === 'late' ? 'schedule' : 'cancel'); ?>
                        </span>
                    </div>
                    <div>
                        <p class="font-bold text-gray-900"><?php echo ucfirst($record['status']); ?></p>
                        <p class="text-sm text-gray-500"><?php echo date('F d, Y', strtotime($record['attendance_date'])); ?></p>
                    </div>
                </div>
                <div class="text-right">
                    <span class="inline-block px-3 py-1 rounded-full text-xs font-bold <?php echo $record['status'] === 'present' ? 'bg-green-100 text-green-700' : ($record['status'] === 'late' ? 'bg-yellow-100 text-yellow-700' : 'bg-red-100 text-red-700'); ?>">
                        <?php echo ucfirst($record['status']); ?>
                    </span>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <div class="bg-white border border-gray-200 rounded-lg p-8 text-center">
            <span class="material-symbols-outlined text-6xl text-gray-300 block mb-4 text-center mx-auto" style="width: 100%; display: flex; justify-content: center;">calendar_today</span>
            <p class="text-gray-500 font-medium">No attendance records yet</p>
        </div>
        <?php endif; ?>
    </main>

    <!-- BottomNavBar -->
    <nav class="fixed bottom-0 left-0 w-full flex justify-around items-center px-4 py-2 bg-white border-t border-gray-200 shadow-lg z-50">
        <a class="flex flex-col items-center justify-center text-gray-500 px-4 py-1.5 hover:bg-gray-50 transition-all active:scale-95 duration-200" href="/pages/student/dashboard.php">
            <span class="material-symbols-outlined">dashboard</span>
            <span class="text-[10px] font-bold mt-1">Dashboard</span>
        </a>
        <a class="flex flex-col items-center justify-center bg-green-700 text-white rounded-xl px-4 py-1.5 active:scale-95 transition-all duration-200" href="/pages/student/attendance.php">
            <span class="material-symbols-outlined" style="font-variation-settings: 'FILL' 1;">calendar_today</span>
            <span class="text-[10px] font-bold mt-1">Attendance</span>
        </a>
        <a class="flex flex-col items-center justify-center text-gray-500 px-4 py-1.5 hover:bg-gray-50 transition-all active:scale-95 duration-200" href="/pages/student/qr-attendance.php">
            <span class="material-symbols-outlined">qr_code_2</span>
            <span class="text-[10px] font-bold mt-1">QR Scan</span>
        </a>
        <a class="flex flex-col items-center justify-center text-gray-500 px-4 py-1.5 hover:bg-gray-50 transition-all active:scale-95 duration-200" href="/pages/student/report.php">
            <span class="material-symbols-outlined">description</span>
            <span class="text-[10px] font-bold mt-1">Report</span>
        </a>
        <a class="flex flex-col items-center justify-center text-gray-500 px-4 py-1.5 hover:bg-gray-50 transition-all active:scale-95 duration-200" href="/pages/student/analytics.php">
            <span class="material-symbols-outlined">analytics</span>
            <span class="text-[10px] font-bold mt-1">Analytics</span>
        </a>
        <a class="flex flex-col items-center justify-center text-gray-500 px-4 py-1.5 hover:bg-gray-50 transition-all active:scale-95 duration-200" href="/pages/student/profile.php">
            <span class="material-symbols-outlined">person</span>
            <span class="text-[10px] font-bold mt-1">Profile</span>
        </a>
    </nav>
</body>
</html>
