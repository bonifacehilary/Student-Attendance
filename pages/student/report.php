<?php
// pages/student/report.php
// Attendance Report Generation & Export

require_once __DIR__ . '/../../config/bootstrap.php';
require_once __DIR__ . '/../../utils/Utility.php';

session_start();

// Check if student is logged in
if (!isset($_SESSION['student_id'])) {
    header('Location: /pages/student/login.php');
    exit;
}

$studentId = $_SESSION['student_id'];

// Get date range from request
$startDate = $_GET['start_date'] ?? date('Y-m-01'); // First day of current month
$endDate = $_GET['end_date'] ?? date('Y-m-d'); // Today
$viewMode = $_GET['view'] ?? 'normal'; // 'pdf', 'csv' for exports

// Validate dates
if (strtotime($startDate) > strtotime($endDate)) {
    $temp = $startDate;
    $startDate = $endDate;
    $endDate = $temp;
}

// Fetch student data
try {
    $student = Utility::safeQuery(
        'SELECT id, name, admission_number FROM students WHERE id = ? LIMIT 1',
        [$studentId],
        'SELECT',
        true
    );
} catch (\Throwable $e) {
    error_log('Report student fetch error: ' . $e->getMessage());
    $student = ['name' => 'Student', 'admission_number' => 'N/A'];
}

// Fetch attendance records in date range
try {
    $attendanceRecords = Utility::safeQuery(
        'SELECT 
            id,
            attendance_date,
            status,
            created_at
        FROM attendance 
        WHERE student_id = ? AND attendance_date BETWEEN ? AND ?
        ORDER BY attendance_date DESC',
        [$studentId, $startDate, $endDate],
        'SELECT'
    );
} catch (\Throwable $e) {
    error_log('Attendance records fetch error: ' . $e->getMessage());
    $attendanceRecords = [];
}

// Calculate statistics
$totalClasses = count($attendanceRecords);
$presentCount = 0;
$lateCount = 0;
$absentCount = 0;
$absences = [];

foreach ($attendanceRecords as $record) {
    if ($record['status'] === 'present') {
        $presentCount++;
    } elseif ($record['status'] === 'late') {
        $lateCount++;
    } elseif ($record['status'] === 'absent') {
        $absentCount++;
        $absences[] = $record;
    }
}

$attendanceRate = $totalClasses > 0 ? round(($presentCount / $totalClasses) * 100, 0) : 0;

// Weekly breakdown (last 7 days)
$weeklyData = [];
for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $dayName = date('D', strtotime($date));
    $count = 0;
    
    foreach ($attendanceRecords as $record) {
        if ($record['attendance_date'] === $date) {
            $count = ($record['status'] === 'present') ? 100 : (($record['status'] === 'late') ? 50 : 0);
            break;
        }
    }
    
    $weeklyData[] = [
        'date' => $date,
        'day' => $dayName,
        'percentage' => $count
    ];
}

// Handle exports
if ($viewMode === 'pdf') {
    // Simple PDF generation (could use TCPDF/FPDF in production)
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="attendance_report_' . date('Y-m-d') . '.pdf"');
    
    // Basic PDF content
    echo "Attendance Report\n";
    echo "Student: " . $student['name'] . "\n";
    echo "Admission Number: " . $student['admission_number'] . "\n";
    echo "Period: " . $startDate . " to " . $endDate . "\n";
    echo "Generated: " . date('Y-m-d H:i:s') . "\n\n";
    echo "Statistics:\n";
    echo "Total Classes: " . $totalClasses . "\n";
    echo "Present: " . $presentCount . "\n";
    echo "Late: " . $lateCount . "\n";
    echo "Absent: " . $absentCount . "\n";
    echo "Attendance Rate: " . $attendanceRate . "%\n";
    
    exit;
}

if ($viewMode === 'csv') {
    // CSV export
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="attendance_report_' . date('Y-m-d') . '.csv"');
    
    echo "Date,Status,Time\n";
    foreach ($attendanceRecords as $record) {
        echo date('Y-m-d', strtotime($record['attendance_date'])) . "," . 
             ucfirst($record['status']) . "," . 
             date('H:i:s', strtotime($record['created_at'])) . "\n";
    }
    
    exit;
}

// Format dates for display
$startDateDisplay = date('M d, Y', strtotime($startDate));
$endDateDisplay = date('M d, Y', strtotime($endDate));
?>
<!DOCTYPE html>
<html class="light" lang="en">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>Attendance Report | EduAttend</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <style>
        body { 
            font-family: 'Inter', sans-serif; 
            background-color: #f7f9fb;
        }
        .material-symbols-outlined { 
            font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24; 
        }
        .glass-card {
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(226, 232, 240, 1);
        }
        .bar-chart { display: flex; align-items: flex-end; gap: 12px; height: 160px; }
        .bar { width: 40px; border-radius: 4px 4px 0 0; transition: all 150ms; }
        .bar:hover { opacity: 0.8; transform: scaleY(1.05); }
    </style>
    <script id="tailwind-config">
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    colors: {
                        "on-error-container": "#93000a",
                        "on-background": "#191c1e",
                        "tertiary": "#515f74",
                        "surface-container-low": "#f2f4f6",
                        "on-secondary": "#ffffff",
                        "error": "#ba1a1a",
                        "tertiary-fixed-dim": "#b9c7e0",
                        "primary-fixed-dim": "#4edea3",
                        "secondary-fixed": "#dae2fd",
                        "surface-tint": "#006c49",
                        "on-tertiary": "#ffffff",
                        "on-secondary-fixed-variant": "#3f465c",
                        "secondary-fixed-dim": "#bec6e0",
                        "outline-variant": "#bbcabf",
                        "primary-fixed": "#6ffbbe",
                        "on-primary-container": "#00422b",
                        "on-tertiary-container": "#2c3a4e",
                        "secondary-container": "#dae2fd",
                        "outline": "#6c7a71",
                        "on-primary": "#ffffff",
                        "error-container": "#ffdad6",
                        "on-tertiary-fixed-variant": "#3a485c",
                        "on-surface-variant": "#3c4a42",
                        "on-primary-fixed-variant": "#005236",
                        "on-surface": "#191c1e",
                        "surface-container-high": "#e6e8ea",
                        "tertiary-fixed": "#d5e3fd",
                        "surface-container": "#eceef0",
                        "on-error": "#ffffff",
                        "surface-container-lowest": "#ffffff",
                        "inverse-on-surface": "#eff1f3",
                        "inverse-surface": "#2d3133",
                        "background": "#f7f9fb",
                        "secondary": "#565e74",
                        "on-primary-fixed": "#002113",
                        "primary": "#006c49",
                        "primary-container": "#10b981",
                        "on-secondary-fixed": "#131b2e",
                        "inverse-primary": "#4edea3",
                        "surface": "#f7f9fb",
                        "surface-container-highest": "#e0e3e5",
                        "on-secondary-container": "#5c647a",
                        "surface-variant": "#e0e3e5",
                        "tertiary-container": "#95a4bb",
                        "surface-bright": "#f7f9fb",
                        "surface-dim": "#d8dadc",
                        "on-tertiary-fixed": "#0d1c2f"
                    },
                    spacing: {
                        "sidebar-width": "280px",
                        "container-padding-mobile": "16px",
                        "gutter": "24px",
                        "container-padding-desktop": "32px",
                        "base": "8px"
                    }
                }
            }
        };
    </script>
</head>
<body class="pb-24 min-h-screen">
    <!-- TopAppBar -->
    <header class="w-full bg-white border-b border-gray-200 fixed top-0 z-50">
        <div class="flex items-center px-4 md:px-8 h-16">
            <a href="/pages/student/dashboard.php" class="active:scale-95 transition-transform p-2">
                <span class="material-symbols-outlined text-green-700">arrow_back</span>
            </a>
            <h1 class="text-xl font-bold text-gray-900 ml-4">Attendance Report</h1>
        </div>
    </header>

    <main class="pt-24 px-4 md:px-8 max-w-6xl mx-auto space-y-8 pb-12">
        <!-- Hero Section -->
        <section class="relative overflow-hidden rounded-xl bg-green-700 p-8 md:p-12 text-white">
            <div class="relative z-10 max-w-2xl">
                <h2 class="text-3xl md:text-4xl font-bold mb-4">Generate Report</h2>
                <p class="text-lg opacity-90">Select a date range to generate and export your detailed attendance summary for academic verification and performance tracking.</p>
            </div>
            <div class="absolute top-0 right-0 -translate-y-1/2 translate-x-1/4 w-96 h-96 bg-green-600 rounded-full blur-3xl opacity-20"></div>
        </section>

        <!-- Filter Section -->
        <form method="GET" class="glass-card p-6 rounded-xl shadow-sm">
            <div class="grid grid-cols-1 md:grid-cols-12 gap-6 items-end">
                <!-- Start Date -->
                <div class="md:col-span-4 space-y-2">
                    <label for="start_date" class="block text-sm font-medium text-gray-700">Start Date</label>
                    <div class="relative">
                        <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-gray-400">calendar_today</span>
                        <input 
                            type="date" 
                            id="start_date"
                            name="start_date"
                            value="<?php echo htmlspecialchars($startDate); ?>"
                            class="w-full pl-10 pr-4 py-3 bg-white border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-600 focus:border-transparent outline-none transition-all"
                        />
                    </div>
                </div>

                <!-- End Date -->
                <div class="md:col-span-4 space-y-2">
                    <label for="end_date" class="block text-sm font-medium text-gray-700">End Date</label>
                    <div class="relative">
                        <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-gray-400">calendar_today</span>
                        <input 
                            type="date" 
                            id="end_date"
                            name="end_date"
                            value="<?php echo htmlspecialchars($endDate); ?>"
                            class="w-full pl-10 pr-4 py-3 bg-white border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-600 focus:border-transparent outline-none transition-all"
                        />
                    </div>
                </div>

                <!-- Generate Button -->
                <div class="md:col-span-4">
                    <button 
                        type="submit"
                        class="w-full bg-green-700 hover:bg-green-800 active:scale-95 text-white py-3 px-6 rounded-lg font-medium flex items-center justify-center gap-2 transition-all"
                    >
                        <span class="material-symbols-outlined">analytics</span>
                        Generate Report
                    </button>
                </div>
            </div>
        </form>

        <!-- Report Preview Card -->
        <section class="bg-white border border-gray-200 rounded-xl overflow-hidden shadow-sm">
            <!-- Header -->
            <div class="p-6 border-b border-gray-200 bg-gray-50 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                <div>
                    <h3 class="text-2xl font-bold text-gray-900">Report Preview</h3>
                    <p class="text-sm text-gray-600 uppercase tracking-wider mt-1">
                        Displaying records from <?php echo $startDateDisplay; ?> to <?php echo $endDateDisplay; ?>
                    </p>
                </div>
                <div class="flex gap-2">
                    <button class="p-2 rounded-full hover:bg-white text-green-700 transition-colors" title="Print">
                        <span class="material-symbols-outlined">print</span>
                    </button>
                </div>
            </div>

            <!-- Summary Grid -->
            <div class="p-8">
                <div class="grid grid-cols-2 md:grid-cols-4 gap-6 mb-8">
                    <!-- Total Classes Card -->
                    <div class="bg-gray-50 rounded-lg p-6 text-center border border-gray-200 hover:shadow-md transition-all">
                        <p class="text-xs font-medium text-gray-600 mb-1 uppercase tracking-wide">Total Classes</p>
                        <p class="text-4xl font-bold text-green-700"><?php echo $totalClasses; ?></p>
                    </div>

                    <!-- Attendance Rate Card -->
                    <div class="bg-green-50 rounded-lg p-6 text-center border border-green-200 hover:shadow-md transition-all">
                        <p class="text-xs font-medium text-gray-600 mb-1 uppercase tracking-wide">Attendance Rate</p>
                        <p class="text-4xl font-bold text-green-700"><?php echo $attendanceRate; ?>%</p>
                    </div>

                    <!-- Present Card -->
                    <div class="bg-gray-50 rounded-lg p-6 text-center border border-gray-200 hover:shadow-md transition-all">
                        <p class="text-xs font-medium text-gray-600 mb-1 uppercase tracking-wide">Present</p>
                        <p class="text-4xl font-bold text-gray-900"><?php echo $presentCount; ?></p>
                    </div>

                    <!-- Absent Card -->
                    <div class="bg-red-50 rounded-lg p-6 text-center border border-red-200 hover:shadow-md transition-all">
                        <p class="text-xs font-medium text-red-600 mb-1 uppercase tracking-wide">Absent</p>
                        <p class="text-4xl font-bold text-red-600"><?php echo $absentCount; ?></p>
                    </div>
                </div>

                <!-- Chart Visualization -->
                <div class="p-8 bg-gray-50 rounded-xl border border-dashed border-gray-300 mb-8">
                    <div class="flex flex-col items-center">
                        <div class="bar-chart w-full mb-4">
                            <?php foreach ($weeklyData as $day): ?>
                                <div class="flex flex-col items-center gap-2 flex-1">
                                    <div class="w-full flex justify-center">
                                        <div 
                                            class="bar <?php echo $day['percentage'] > 0 ? 'bg-green-600' : 'bg-gray-300'; ?>" 
                                            style="height: <?php echo max(20, ($day['percentage'] / 100) * 100); ?>%;"
                                            title="<?php echo $day['day']; ?>"
                                        ></div>
                                    </div>
                                    <span class="text-xs font-medium text-gray-600"><?php echo $day['day']; ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <p class="text-sm text-gray-600 font-medium mt-4">Weekly Attendance Trend Analysis</p>
                    </div>
                </div>

                <!-- Statistics breakdown row -->
                <div class="grid grid-cols-3 gap-4 text-center py-4">
                    <div>
                        <p class="text-2xl font-bold text-green-600"><?php echo $presentCount; ?></p>
                        <p class="text-xs text-gray-600 mt-1">Present</p>
                    </div>
                    <div>
                        <p class="text-2xl font-bold text-yellow-600"><?php echo $lateCount; ?></p>
                        <p class="text-xs text-gray-600 mt-1">Late</p>
                    </div>
                    <div>
                        <p class="text-2xl font-bold text-red-600"><?php echo $absentCount; ?></p>
                        <p class="text-xs text-gray-600 mt-1">Absent</p>
                    </div>
                </div>
            </div>

            <!-- Export Actions -->
            <div class="px-8 py-6 border-t border-gray-200 bg-gray-50 flex flex-col sm:flex-row gap-4 items-center justify-end">
                <a 
                    href="?start_date=<?php echo urlencode($startDate); ?>&end_date=<?php echo urlencode($endDate); ?>&view=pdf"
                    class="flex items-center justify-center gap-2 px-6 py-3 border border-gray-300 text-gray-700 font-medium rounded-lg hover:bg-gray-100 active:scale-95 transition-all"
                >
                    <span class="material-symbols-outlined">description</span>
                    Download PDF
                </a>
                <a 
                    href="?start_date=<?php echo urlencode($startDate); ?>&end_date=<?php echo urlencode($endDate); ?>&view=csv"
                    class="flex items-center justify-center gap-2 px-6 py-3 border border-gray-300 text-gray-700 font-medium rounded-lg hover:bg-gray-100 active:scale-95 transition-all"
                >
                    <span class="material-symbols-outlined">table_view</span>
                    Export CSV
                </a>
            </div>
        </section>

        <!-- Detailed Absences Section -->
        <section class="grid grid-cols-1 md:grid-cols-3 gap-8 pb-12">
            <!-- Recent Absences Card -->
            <div class="md:col-span-2 glass-card rounded-xl p-6">
                <h4 class="text-2xl font-bold mb-6 text-gray-900">Recent Absences</h4>
                <?php if (!empty($absences)): ?>
                    <div class="space-y-4">
                        <?php foreach (array_slice($absences, 0, 5) as $absence): ?>
                        <div class="flex items-center p-4 bg-white rounded-lg border border-gray-200 hover:border-red-300 transition-colors">
                            <div class="w-10 h-10 rounded-full bg-red-100 flex items-center justify-center text-red-600 mr-4">
                                <span class="material-symbols-outlined">event_busy</span>
                            </div>
                            <div class="flex-1">
                                <p class="font-medium text-gray-900">Class Session</p>
                                <p class="text-sm text-gray-600"><?php echo date('M d, Y • h:i A', strtotime($absence['attendance_date'])); ?></p>
                            </div>
                            <span class="px-3 py-1 bg-red-100 text-red-700 rounded-full text-xs font-bold uppercase tracking-widest">Absent</span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="py-12 text-center text-gray-500">
                        <span class="material-symbols-outlined text-5xl block mb-3 opacity-20">event_available</span>
                        <p class="font-medium">No absences recorded in this period</p>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Pro Tip Card -->
            <div class="bg-green-700 text-white rounded-xl p-6 relative overflow-hidden group">
                <h4 class="text-2xl font-bold mb-2">Pro Tip</h4>
                <p class="text-base opacity-90 mb-6">Maintain an attendance rate above 90% to remain eligible for the Dean's List and specific scholarship renewals.</p>
                <div class="w-full aspect-video rounded-lg overflow-hidden relative">
                    <img 
                        class="object-cover w-full h-full group-hover:scale-110 transition-transform duration-700" 
                        src="https://lh3.googleusercontent.com/aida-public/AB6AXuDMG3DwWnQNhly8ycONcvNuWGLyPji6AkvU0-0MYQ4VqBcwprio23eMtOeTRlWH2s7ahoKPV5TqWFyf2vqRCVrt9bc_FcnsAelNxa_LOuXgg8tQGtNju8bHo-jDFILSD-KsRe8VI2uE56NQbnWcj6cXMQUfa9lzGwDfPiv5Jgc5dZkXZ0yOOcePC2IEEZBYum2kZq1RZACKGhnPTnHQTKdxCjl85dqpSOSGxhJ3fc98iguX-ZRzObfxhNq71OWNzeiPBtVqZYNy6lnQ"
                        alt="Academic lounge"
                    />
                    <div class="absolute inset-0 bg-gradient-to-t from-green-700/80 to-transparent"></div>
                </div>
            </div>
        </section>
    </main>

    <!-- Bottom Navigation -->
    <nav class="fixed bottom-0 left-0 w-full bg-white border-t border-gray-200 z-40">
        <div class="flex justify-around items-center h-16 px-4">
            <a class="flex flex-col items-center justify-center text-gray-600 hover:text-green-700 transition-colors flex-1 py-2" href="/pages/student/dashboard.php">
                <span class="material-symbols-outlined">home</span>
                <span class="text-xs font-medium mt-1">Home</span>
            </a>
            <a class="flex flex-col items-center justify-center text-gray-600 hover:text-green-700 transition-colors flex-1 py-2" href="/pages/student/attendance.php">
                <span class="material-symbols-outlined">history</span>
                <span class="text-xs font-medium mt-1">History</span>
            </a>
            <a class="flex flex-col items-center justify-center text-gray-600 hover:text-green-700 transition-colors flex-1 py-2" href="/pages/student/qr-attendance.php">
                <span class="material-symbols-outlined">qr_code_2</span>
                <span class="text-xs font-medium mt-1">QR</span>
            </a>
            <a class="flex flex-col items-center justify-center text-green-700 font-bold transition-colors flex-1 py-2" href="/pages/student/report.php">
                <span class="material-symbols-outlined" style="font-variation-settings: 'FILL' 1;">description</span>
                <span class="text-xs font-medium mt-1">Report</span>
            </a>
            <a class="flex flex-col items-center justify-center text-gray-600 hover:text-green-700 transition-colors flex-1 py-2" href="/pages/student/profile.php">
                <span class="material-symbols-outlined">person</span>
                <span class="text-xs font-medium mt-1">Profile</span>
            </a>
        </div>
    </nav>

    <script>
        // Smooth interactions
        document.querySelectorAll('button[type="submit"]').forEach(button => {
            button.addEventListener('click', function() {
                const originalHTML = this.innerHTML;
                this.innerHTML = '<span class="animate-spin material-symbols-outlined">progress_activity</span> Generating...';
                setTimeout(() => {
                    this.innerHTML = originalHTML;
                }, 500);
            });
        });

        // Print functionality
        document.querySelector('[title="Print"]')?.addEventListener('click', () => {
            window.print();
        });
    </script>
</body>
</html>
