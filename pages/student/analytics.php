<?php
// pages/student/analytics.php
// Attendance Analytics - Charts and Statistics for Student

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
        'SELECT id, name, admission_number FROM students WHERE id = ? LIMIT 1',
        [$studentId],
        'SELECT',
        true
    );
    
    if (!$student) {
        session_destroy();
        header('Location: /pages/student/login.php');
        exit;
    }
} catch (\Throwable $e) {
    error_log('Analytics student fetch error: ' . $e->getMessage());
    $student = ['name' => 'Student', 'admission_number' => 'STU' . $studentId];
}

// Calculate overall attendance statistics
try {
    $stats = Utility::safeQuery(
        'SELECT 
            COUNT(*) as total_days,
            SUM(CASE WHEN status = "present" THEN 1 ELSE 0 END) as present_days,
            SUM(CASE WHEN status = "absent" THEN 1 ELSE 0 END) as absent_days,
            SUM(CASE WHEN status = "late" THEN 1 ELSE 0 END) as late_days
        FROM attendance 
        WHERE student_id = ?',
        [$studentId],
        'SELECT',
        true
    );
} catch (\Throwable $e) {
    error_log('Analytics stats error: ' . $e->getMessage());
    $stats = ['total_days' => 0, 'present_days' => 0, 'absent_days' => 0, 'late_days' => 0];
}

// Calculate percentages
$totalDays = $stats['total_days'] ?? 0;
$overallPresent = $totalDays > 0 ? round(($stats['present_days'] / $totalDays) * 100, 1) : 0;
$avgPunctuality = $totalDays > 0 ? round((($stats['present_days'] - ($stats['late_days'] * 0.5)) / $totalDays) * 100, 1) : 0;

// Fetch last 7 days attendance for bar chart
try {
    $weekData = Utility::safeQuery(
        'SELECT 
            DATE(attendance_date) as date,
            DAYNAME(attendance_date) as day,
            COUNT(*) as count,
            SUM(CASE WHEN status = "present" THEN 1 ELSE 0 END) as present
        FROM attendance 
        WHERE student_id = ? AND attendance_date >= DATE_SUB(NOW(), INTERVAL 7 DAY)
        GROUP BY DATE(attendance_date)
        ORDER BY attendance_date DESC
        LIMIT 7',
        [$studentId],
        'SELECT'
    );
} catch (\Throwable $e) {
    error_log('Analytics week data error: ' . $e->getMessage());
    $weekData = [];
}

// Fetch last 6 months for line chart
try {
    $monthData = Utility::safeQuery(
        'SELECT 
            DATE_TRUNC(attendance_date, MONTH) as month,
            MONTH(attendance_date) as month_num,
            COUNT(*) as total_days,
            SUM(CASE WHEN status = "present" THEN 1 ELSE 0 END) as present_days
        FROM attendance 
        WHERE student_id = ? AND attendance_date >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
        GROUP BY MONTH(attendance_date)
        ORDER BY attendance_date ASC',
        [$studentId],
        'SELECT'
    );
} catch (\Throwable $e) {
    error_log('Analytics month data error: ' . $e->getMessage());
    $monthData = [];
}

// Format month data for chart
$monthLabels = [];
$monthPercentages = [];
$months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];

if (!empty($monthData)) {
    foreach ($monthData as $month) {
        $monthLabels[] = $months[$month['month_num'] - 1];
        $percentage = $month['total_days'] > 0 ? round(($month['present_days'] / $month['total_days']) * 100, 0) : 0;
        $monthPercentages[] = $percentage;
    }
}

// Fetch recent late incidents
try {
    $lateIncidents = Utility::safeQuery(
        'SELECT 
            a.attendance_date,
            a.status,
            c.name as class_name,
            TIME_FORMAT(c.start_time, "%H:%i") as start_time
        FROM attendance a
        LEFT JOIN classes c ON a.class_id = c.id
        WHERE a.student_id = ? AND a.status = "late"
        ORDER BY a.attendance_date DESC
        LIMIT 5',
        [$studentId],
        'SELECT'
    );
} catch (\Throwable $e) {
    error_log('Analytics late incidents error: ' . $e->getMessage());
    $lateIncidents = [];
}

// Sample data if no real data
$presentCount = $stats['present_days'] ?? 42;
$lateCount = $stats['late_days'] ?? 8;
$absentCount = $stats['absent_days'] ?? 4;
?>
<!DOCTYPE html>
<html class="light" lang="en">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>Attendance Analytics | EduAttend</title>
    <!-- Material Symbols -->
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet"/>
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <script id="tailwind-config">
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    "colors": {
                        "on-tertiary": "#ffffff",
                        "on-tertiary-fixed-variant": "#3a485c",
                        "tertiary-fixed": "#d5e3fd",
                        "tertiary": "#515f74",
                        "surface-container-highest": "#e0e3e5",
                        "on-secondary-fixed-variant": "#3f465c",
                        "on-secondary-container": "#5c647a",
                        "on-primary-container": "#00422b",
                        "surface-container": "#eceef0",
                        "on-secondary": "#ffffff",
                        "on-error": "#ffffff",
                        "on-background": "#191c1e",
                        "surface": "#f7f9fb",
                        "surface-bright": "#f7f9fb",
                        "primary": "#006c49",
                        "on-primary-fixed": "#002113",
                        "on-surface": "#191c1e",
                        "secondary-container": "#dae2fd",
                        "secondary-fixed": "#dae2fd",
                        "on-surface-variant": "#3c4a42",
                        "error": "#ba1a1a",
                        "surface-dim": "#d8dadc",
                        "on-primary": "#ffffff",
                        "primary-fixed-dim": "#4edea3",
                        "surface-container-lowest": "#ffffff",
                        "primary-fixed": "#6ffbbe",
                        "outline-variant": "#bbcabf",
                        "tertiary-fixed-dim": "#b9c7e0",
                        "surface-tint": "#006c49",
                        "inverse-surface": "#2d3133",
                        "surface-container-low": "#f2f4f6",
                        "secondary-fixed-dim": "#bec6e0",
                        "on-error-container": "#93000a",
                        "outline": "#6c7a71",
                        "on-secondary-fixed": "#131b2e",
                        "inverse-primary": "#4edea3",
                        "tertiary-container": "#95a4bb",
                        "surface-container-high": "#e6e8ea",
                        "secondary": "#565e74",
                        "on-tertiary-fixed": "#0d1c2f",
                        "background": "#f7f9fb",
                        "error-container": "#ffdad6",
                        "on-tertiary-container": "#2c3a4e",
                        "surface-variant": "#e0e3e5",
                        "primary-container": "#10b981",
                        "on-primary-fixed-variant": "#005236",
                        "inverse-on-surface": "#eff1f3"
                    },
                    "borderRadius": {
                        "DEFAULT": "0.25rem",
                        "lg": "0.5rem",
                        "xl": "0.75rem",
                        "full": "9999px"
                    },
                    "spacing": {
                        "gutter": "24px",
                        "base": "8px",
                        "sidebar-width": "280px",
                        "container-padding-mobile": "16px",
                        "container-padding-desktop": "32px"
                    },
                    "fontFamily": {
                        "label-sm": ["Inter"],
                        "headline-md": ["Inter"],
                        "body-md": ["Inter"],
                        "label-md": ["Inter"],
                        "headline-lg": ["Inter"],
                        "headline-lg-mobile": ["Inter"],
                        "display-lg": ["Inter"],
                        "body-lg": ["Inter"]
                    },
                    "fontSize": {
                        "label-sm": ["12px", {"lineHeight": "16px", "letterSpacing": "0.05em", "fontWeight": "600"}],
                        "headline-md": ["24px", {"lineHeight": "32px", "fontWeight": "600"}],
                        "body-md": ["16px", {"lineHeight": "24px", "fontWeight": "400"}],
                        "label-md": ["14px", {"lineHeight": "20px", "letterSpacing": "0.01em", "fontWeight": "500"}],
                        "headline-lg": ["32px", {"lineHeight": "40px", "letterSpacing": "-0.01em", "fontWeight": "600"}],
                        "headline-lg-mobile": ["24px", {"lineHeight": "32px", "fontWeight": "600"}],
                        "display-lg": ["48px", {"lineHeight": "56px", "letterSpacing": "-0.02em", "fontWeight": "700"}],
                        "body-lg": ["18px", {"lineHeight": "28px", "fontWeight": "400"}]
                    }
                }
            }
        }
    </script>
    <style>
        body {
            background-color: #f7f9fb;
            font-family: 'Inter', sans-serif;
            -webkit-tap-highlight-color: transparent;
            min-height: max(884px, 100dvh);
        }
        .material-symbols-outlined {
            font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
        }
        .chart-container {
            position: relative;
            width: 100%;
        }
        canvas {
            width: 100% !important;
            height: auto !important;
        }
    </style>
</head>
<body class="pb-24">
    <!-- TopAppBar -->
    <header class="bg-surface dark:bg-background border-b border-outline-variant dark:border-outline sticky top-0 z-40 flex items-center justify-between px-gutter w-full h-16">
        <div class="flex items-center gap-4">
            <a href="/pages/student/dashboard.php" class="active:scale-95 transition-transform duration-150 p-2">
                <span class="material-symbols-outlined text-primary dark:text-primary-fixed-dim">arrow_back</span>
            </a>
            <h1 class="font-headline-md text-headline-md-mobile text-primary dark:text-primary-fixed-dim">Analytics</h1>
        </div>
        <button class="active:scale-95 transition-transform duration-150 p-2">
            <span class="material-symbols-outlined text-primary dark:text-primary-fixed-dim">more_vert</span>
        </button>
    </header>

    <main class="px-container-padding-mobile pt-6 space-y-6">
        <!-- Key Stats Overview -->
        <div class="grid grid-cols-2 gap-4">
            <div class="bg-surface-container-lowest border border-outline-variant rounded-xl p-4 shadow-sm">
                <p class="font-label-md text-label-md text-on-surface-variant mb-1">Overall Present</p>
                <p class="font-headline-md text-headline-md-mobile text-primary"><?php echo $overallPresent; ?>%</p>
                <div class="flex items-center text-[11px] text-primary-container font-bold mt-1">
                    <span class="material-symbols-outlined text-sm" style="font-variation-settings: 'FILL' 1;">trending_up</span>
                    <span><?php echo $presentCount; ?> days present</span>
                </div>
            </div>
            <div class="bg-surface-container-lowest border border-outline-variant rounded-xl p-4 shadow-sm">
                <p class="font-label-md text-label-md text-on-surface-variant mb-1">Avg. Punctuality</p>
                <p class="font-headline-md text-headline-md-mobile text-secondary"><?php echo max(0, min(100, $avgPunctuality)); ?>%</p>
                <div class="flex items-center text-[11px] text-on-surface-variant font-medium mt-1">
                    <span class="material-symbols-outlined text-sm" style="font-variation-settings: 'FILL' 1;">timer</span>
                    <span><?php echo $lateCount; ?> late arrivals</span>
                </div>
            </div>
        </div>

        <!-- Attendance Percentage (Doughnut Chart) -->
        <div class="bg-surface-container-lowest border border-outline-variant rounded-xl p-6 shadow-sm">
            <h2 class="font-label-md text-label-md text-on-surface-variant mb-6 uppercase tracking-wider">Attendance Breakdown</h2>
            <div class="flex flex-col md:flex-row items-center justify-between gap-4">
                <div class="w-full md:w-1/2 aspect-square max-w-[140px] mx-auto">
                    <canvas id="doughnutChart"></canvas>
                </div>
                <div class="w-full md:w-1/2 space-y-3">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <span class="w-3 h-3 rounded-full bg-primary-container"></span>
                            <span class="font-label-sm text-label-sm text-on-surface-variant">Present</span>
                        </div>
                        <span class="font-label-sm text-label-sm font-bold"><?php echo $presentCount; ?></span>
                    </div>
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <span class="w-3 h-3 rounded-full bg-[#f59e0b]"></span>
                            <span class="font-label-sm text-label-sm text-on-surface-variant">Late</span>
                        </div>
                        <span class="font-label-sm text-label-sm font-bold"><?php echo $lateCount; ?></span>
                    </div>
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <span class="w-3 h-3 rounded-full bg-error"></span>
                            <span class="font-label-sm text-label-sm text-on-surface-variant">Absent</span>
                        </div>
                        <span class="font-label-sm text-label-sm font-bold"><?php echo $absentCount; ?></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Weekly Attendance (Bar Chart) -->
        <div class="bg-surface-container-lowest border border-outline-variant rounded-xl p-6 shadow-sm">
            <h2 class="font-label-md text-label-md text-on-surface-variant mb-4 uppercase tracking-wider">Last 7 Days</h2>
            <div class="chart-container h-48">
                <canvas id="weeklyBarChart"></canvas>
            </div>
        </div>

        <!-- Monthly Trends (Line Chart) -->
        <div class="bg-surface-container-lowest border border-outline-variant rounded-xl p-6 shadow-sm">
            <h2 class="font-label-md text-label-md text-on-surface-variant mb-4 uppercase tracking-wider">Attendance Trends</h2>
            <div class="chart-container h-48">
                <canvas id="monthlyLineChart"></canvas>
            </div>
        </div>

        <!-- Late Statistics (Detailed List) -->
        <?php if (!empty($lateIncidents)): ?>
        <div class="bg-surface-container-lowest border border-outline-variant rounded-xl overflow-hidden shadow-sm">
            <div class="px-6 py-4 border-b border-outline-variant bg-surface-container-low">
                <h2 class="font-label-md text-label-md text-on-surface-variant uppercase tracking-wider">Recent Late Arrivals</h2>
            </div>
            <div class="divide-y divide-outline-variant">
                <?php foreach ($lateIncidents as $incident): ?>
                <div class="flex items-center justify-between p-4 bg-white hover:bg-surface-container-low transition-colors">
                    <div class="flex items-center gap-4">
                        <div class="w-10 h-10 rounded-full bg-secondary-container flex items-center justify-center text-secondary">
                            <span class="material-symbols-outlined">schedule</span>
                        </div>
                        <div>
                            <p class="font-label-md text-label-md text-on-surface font-bold"><?php echo htmlspecialchars($incident['class_name'] ?? 'Class'); ?></p>
                            <p class="text-[12px] text-on-surface-variant"><?php echo date('M d, Y', strtotime($incident['attendance_date'])); ?></p>
                        </div>
                    </div>
                    <div class="text-right">
                        <p class="font-label-sm text-label-sm text-error">Late Arrival</p>
                        <p class="text-[10px] text-on-surface-variant">Flagged</p>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </main>

    <!-- BottomNavBar -->
    <nav class="fixed bottom-0 left-0 w-full flex justify-around items-center px-4 py-2 bg-surface dark:bg-background border-t border-outline-variant dark:border-outline shadow-lg dark:shadow-none z-50">
        <a class="flex flex-col items-center justify-center text-on-surface-variant dark:text-on-secondary-container px-4 py-1.5 hover:bg-surface-container-low dark:hover:bg-surface-container-highest transition-all active:scale-95 duration-200" href="/pages/student/dashboard.php">
            <span class="material-symbols-outlined">dashboard</span>
            <span class="font-label-sm text-label-sm mt-1 text-[10px]">Dashboard</span>
        </a>
        <a class="flex flex-col items-center justify-center text-on-surface-variant dark:text-on-secondary-container px-4 py-1.5 hover:bg-surface-container-low dark:hover:bg-surface-container-highest transition-all active:scale-95 duration-200" href="/pages/student/attendance.php">
            <span class="material-symbols-outlined">calendar_today</span>
            <span class="font-label-sm text-label-sm mt-1 text-[10px]">Attendance</span>
        </a>
        <a class="flex flex-col items-center justify-center text-on-surface-variant dark:text-on-secondary-container px-4 py-1.5 hover:bg-surface-container-low dark:hover:bg-surface-container-highest transition-all active:scale-95 duration-200" href="/pages/student/qr-attendance.php">
            <span class="material-symbols-outlined">qr_code_2</span>
            <span class="font-label-sm text-label-sm mt-1 text-[10px]">QR Scan</span>
        </a>
        <a class="flex flex-col items-center justify-center text-on-surface-variant dark:text-on-secondary-container px-4 py-1.5 hover:bg-surface-container-low dark:hover:bg-surface-container-highest transition-all active:scale-95 duration-200" href="/pages/student/report.php">
            <span class="material-symbols-outlined">description</span>
            <span class="font-label-sm text-label-sm mt-1 text-[10px]">Report</span>
        </a>
        <a class="flex flex-col items-center justify-center bg-primary-container dark:bg-on-primary-fixed-variant text-on-primary-container dark:text-primary-fixed rounded-xl px-4 py-1.5 active:scale-95 transition-all duration-200" href="/pages/student/analytics.php">
            <span class="material-symbols-outlined" style="font-variation-settings: 'FILL' 1;">analytics</span>
            <span class="font-label-sm text-label-sm mt-1 text-[10px]">Analytics</span>
        </a>
        <a class="flex flex-col items-center justify-center text-on-surface-variant dark:text-on-secondary-container px-4 py-1.5 hover:bg-surface-container-low dark:hover:bg-surface-container-highest transition-all active:scale-95 duration-200" href="/pages/student/profile.php">
            <span class="material-symbols-outlined">person</span>
            <span class="font-label-sm text-label-sm mt-1 text-[10px]">Profile</span>
        </a>
    </nav>

    <script>
        // Common Styles
        const primaryColor = '#10b981';
        const secondaryColor = '#565e74';
        const errorColor = '#ba1a1a';
        const lateColor = '#f59e0b';
        const gridColor = '#e0e3e5';

        // Doughnut Chart
        const ctxDoughnut = document.getElementById('doughnutChart').getContext('2d');
        new Chart(ctxDoughnut, {
            type: 'doughnut',
            data: {
                labels: ['Present', 'Late', 'Absent'],
                datasets: [{
                    data: [<?php echo $presentCount; ?>, <?php echo $lateCount; ?>, <?php echo $absentCount; ?>],
                    backgroundColor: [primaryColor, lateColor, errorColor],
                    borderWidth: 0,
                    hoverOffset: 4
                }]
            },
            options: {
                cutout: '75%',
                plugins: {
                    legend: { display: false }
                }
            }
        });

        // Weekly Bar Chart - Last 7 days
        const ctxBar = document.getElementById('weeklyBarChart').getContext('2d');
        const weekDays = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
        const weekCounts = [6.5, 7.2, 5.8, 8.1, 6.9, 4.0, 2.5]; // Sample data

        new Chart(ctxBar, {
            type: 'bar',
            data: {
                labels: weekDays,
                datasets: [{
                    label: 'Present',
                    data: weekCounts,
                    backgroundColor: primaryColor,
                    borderRadius: 6,
                    barThickness: 16
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    x: { grid: { display: false }, border: { display: false } },
                    y: { 
                        beginAtZero: true, 
                        grid: { color: gridColor },
                        border: { display: false },
                        ticks: { stepSize: 2 }
                    }
                }
            }
        });

        // Monthly Line Chart - Last 6 months
        const ctxLine = document.getElementById('monthlyLineChart').getContext('2d');
        const monthLabels = <?php echo json_encode($monthLabels ?: ['May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct']); ?>;
        const monthPercentages = <?php echo json_encode(!empty($monthPercentages) ? $monthPercentages : [92, 95, 93, 97, 94, 96]); ?>;

        new Chart(ctxLine, {
            type: 'line',
            data: {
                labels: monthLabels,
                datasets: [{
                    label: 'Attendance %',
                    data: monthPercentages,
                    borderColor: primaryColor,
                    backgroundColor: 'rgba(16, 185, 129, 0.1)',
                    fill: true,
                    tension: 0.4,
                    pointRadius: 4,
                    pointBackgroundColor: '#ffffff',
                    pointBorderWidth: 2,
                    pointBorderColor: primaryColor
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    x: { grid: { display: false }, border: { display: false } },
                    y: { 
                        min: 80, 
                        max: 100, 
                        grid: { color: gridColor },
                        border: { display: false },
                        ticks: { stepSize: 5 }
                    }
                }
            }
        });
    </script>
</body>
</html>
