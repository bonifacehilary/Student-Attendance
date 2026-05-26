<?php
// pages/admin/dashboard.php
// Admin Attendance Marking Dashboard

require_once __DIR__ . '/../../config/bootstrap.php';
require_once __DIR__ . '/../../utils/Utility.php';

session_start();

// Check if admin is logged in (placeholder - use proper admin auth)
// For now, we'll allow access with ?admin=true parameter
$isAdmin = isset($_GET['admin']) && $_GET['admin'] === 'true';

if (!$isAdmin) {
    header('Location: /pages/admin/login.php');
    exit;
}

// Get all students
try {
    $students = Utility::safeQuery(
        'SELECT id, name, admission_number, email FROM students ORDER BY name ASC',
        [],
        'SELECT'
    );
} catch (\Throwable $e) {
    error_log('Admin dashboard student fetch error: ' . $e->getMessage());
    $students = [];
}

// Get today's attendance status
$today = date('Y-m-d');
$attendanceToday = [];

try {
    $records = Utility::safeQuery(
        'SELECT student_id, status FROM attendance WHERE attendance_date = ?',
        [$today],
        'SELECT'
    );
    
    foreach ($records as $record) {
        $attendanceToday[$record['student_id']] = $record['status'];
    }
} catch (\Throwable $e) {
    error_log('Admin dashboard attendance fetch error: ' . $e->getMessage());
}

// Handle attendance updates (via AJAX POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (isset($input['student_id']) && isset($input['status'])) {
        $studentId = $input['student_id'];
        $status = $input['status'];
        
        if (in_array($status, ['present', 'late', 'absent'])) {
            try {
                Utility::safeQuery(
                    'INSERT INTO attendance (student_id, attendance_date, status)
                     VALUES (?, ?, ?)
                     ON DUPLICATE KEY UPDATE status = ?',
                    [$studentId, $today, $status, $status],
                    'INSERT'
                );
                
                http_response_code(200);
                echo json_encode(['success' => true, 'message' => 'Attendance updated']);
            } catch (\Throwable $e) {
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
            }
        }
    }
    exit;
}

// Get attendance statistics
try {
    $stats = Utility::safeQuery(
        'SELECT 
            (SELECT COUNT(DISTINCT student_id) FROM attendance WHERE attendance_date = ?) as marked,
            (SELECT COUNT(*) FROM students) as total,
            (SELECT COUNT(*) FROM attendance WHERE attendance_date = ? AND status = "present") as present,
            (SELECT COUNT(*) FROM attendance WHERE attendance_date = ? AND status = "late") as late,
            (SELECT COUNT(*) FROM attendance WHERE attendance_date = ? AND status = "absent") as absent',
        [$today, $today, $today, $today],
        'SELECT',
        true
    );
} catch (\Throwable $e) {
    error_log('Admin stats error: ' . $e->getMessage());
    $stats = ['marked' => 0, 'total' => 0, 'present' => 0, 'late' => 0, 'absent' => 0];
}
?>
<!DOCTYPE html>
<html class="light" lang="en">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>Admin Dashboard | EduAttend</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #f7f9fb; }
        .material-symbols-outlined { font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24; }
        .status-btn { transition: all 150ms ease-in-out; }
        .status-btn.active { transform: scale(1.05); }
    </style>
</head>
<body class="min-h-screen">
    <!-- TopAppBar -->
    <header class="bg-white border-b border-gray-200 sticky top-0 z-40 flex items-center justify-between px-6 w-full h-16">
        <div class="flex items-center gap-4">
            <span class="material-symbols-outlined text-green-700 text-2xl">admin_panel_settings</span>
            <h1 class="text-xl font-bold text-gray-900">Admin Dashboard</h1>
        </div>
        <div class="flex items-center gap-4">
            <p class="text-sm text-gray-600"><?php echo date('l, F d, Y'); ?></p>
            <a href="/pages/admin/logout.php" class="text-red-600 hover:bg-red-50 px-3 py-2 rounded transition">
                <span class="material-symbols-outlined">logout</span>
            </a>
        </div>
    </header>

    <main class="max-w-6xl mx-auto px-4 py-8">
        <!-- Statistics Cards -->
        <div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-8">
            <div class="bg-white rounded-lg p-4 border border-gray-200 shadow-sm">
                <p class="text-gray-600 text-sm font-medium">Total Students</p>
                <p class="text-3xl font-bold text-gray-900 mt-2"><?php echo $stats['total']; ?></p>
            </div>
            <div class="bg-white rounded-lg p-4 border border-gray-200 shadow-sm">
                <p class="text-gray-600 text-sm font-medium">Marked Today</p>
                <p class="text-3xl font-bold text-blue-600 mt-2"><?php echo $stats['marked']; ?></p>
            </div>
            <div class="bg-white rounded-lg p-4 border border-gray-200 shadow-sm">
                <p class="text-gray-600 text-sm font-medium">Present</p>
                <p class="text-3xl font-bold text-green-600 mt-2"><?php echo $stats['present']; ?></p>
            </div>
            <div class="bg-white rounded-lg p-4 border border-gray-200 shadow-sm">
                <p class="text-gray-600 text-sm font-medium">Late</p>
                <p class="text-3xl font-bold text-yellow-600 mt-2"><?php echo $stats['late']; ?></p>
            </div>
            <div class="bg-white rounded-lg p-4 border border-gray-200 shadow-sm">
                <p class="text-gray-600 text-sm font-medium">Absent</p>
                <p class="text-3xl font-bold text-red-600 mt-2"><?php echo $stats['absent']; ?></p>
            </div>
        </div>

        <!-- Students List -->
        <div class="bg-white rounded-lg border border-gray-200 shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
                <h2 class="text-lg font-bold text-gray-900">Mark Attendance</h2>
                <div class="text-sm text-gray-600">
                    Progress: <span id="progress"><?php echo $stats['marked']; ?></span> / <?php echo $stats['total']; ?>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50 border-b border-gray-200">
                        <tr>
                            <th class="text-left px-6 py-3 text-sm font-bold text-gray-900">Admission #</th>
                            <th class="text-left px-6 py-3 text-sm font-bold text-gray-900">Student Name</th>
                            <th class="text-left px-6 py-3 text-sm font-bold text-gray-900">Email</th>
                            <th class="text-center px-6 py-3 text-sm font-bold text-gray-900">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <?php foreach ($students as $student): ?>
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-6 py-3 text-sm text-gray-600"><?php echo $student['admission_number']; ?></td>
                            <td class="px-6 py-3 text-sm font-medium text-gray-900"><?php echo $student['name']; ?></td>
                            <td class="px-6 py-3 text-sm text-gray-600"><?php echo $student['email']; ?></td>
                            <td class="px-6 py-3 text-center">
                                <div class="flex justify-center gap-2 student-row" data-student-id="<?php echo $student['id']; ?>">
                                    <button type="button" 
                                            class="status-btn px-3 py-1 rounded text-xs font-bold transition-all <?php echo ($attendanceToday[$student['id']] ?? '') === 'present' ? 'bg-green-600 text-white' : 'bg-green-100 text-green-700 hover:bg-green-600 hover:text-white'; ?>" 
                                            data-status="present"
                                            onclick="markAttendance(<?php echo $student['id']; ?>, 'present', this)">
                                        ✓ Present
                                    </button>
                                    <button type="button" 
                                            class="status-btn px-3 py-1 rounded text-xs font-bold transition-all <?php echo ($attendanceToday[$student['id']] ?? '') === 'late' ? 'bg-yellow-600 text-white' : 'bg-yellow-100 text-yellow-700 hover:bg-yellow-600 hover:text-white'; ?>" 
                                            data-status="late"
                                            onclick="markAttendance(<?php echo $student['id']; ?>, 'late', this)">
                                        ⏱ Late
                                    </button>
                                    <button type="button" 
                                            class="status-btn px-3 py-1 rounded text-xs font-bold transition-all <?php echo ($attendanceToday[$student['id']] ?? '') === 'absent' ? 'bg-red-600 text-white' : 'bg-red-100 text-red-700 hover:bg-red-600 hover:text-white'; ?>" 
                                            data-status="absent"
                                            onclick="markAttendance(<?php echo $student['id']; ?>, 'absent', this)">
                                        ✗ Absent
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <script>
        function markAttendance(studentId, status, button) {
            // Update button styles
            const row = button.closest('.student-row');
            row.querySelectorAll('button').forEach(btn => {
                btn.classList.remove('active', 'bg-green-600', 'bg-yellow-600', 'bg-red-600', 'text-white');
                btn.classList.add('bg-green-100', 'bg-yellow-100', 'bg-red-100', 'text-green-700', 'text-yellow-700', 'text-red-700');
            });
            
            button.classList.add('active');
            button.classList.remove('bg-green-100', 'bg-yellow-100', 'bg-red-100', 'text-green-700', 'text-yellow-700', 'text-red-700');
            button.classList.add('text-white');
            
            if (status === 'present') {
                button.classList.add('bg-green-600');
            } else if (status === 'late') {
                button.classList.add('bg-yellow-600');
            } else {
                button.classList.add('bg-red-600');
            }

            // Send to server
            fetch(window.location.href, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    student_id: studentId,
                    status: status
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Update progress
                    const progressEl = document.getElementById('progress');
                    const current = parseInt(progressEl.textContent);
                    
                    // Check if this was an unmarked student (first time marking)
                    if (!button.parentElement.querySelector('button.active')) {
                        progressEl.textContent = current + 1;
                    }
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Failed to mark attendance');
            });
        }
    </script>
</body>
</html>
