<?php
// pages/student/report.php
// Attendance Report with DataTable Export

require_once __DIR__ . '/../../config/bootstrap.php';

use StudentAttendance\Utils\StudentAuth;

StudentAuth::require();
$studentId = StudentAuth::studentId();

// Get date range from request
$startDate = $_GET['start_date'] ?? date('Y-m-01'); // First day of current month
$endDate = $_GET['end_date'] ?? date('Y-m-d'); // Today

// Validate dates
if (strtotime($startDate) > strtotime($endDate)) {
    $temp = $startDate;
    $startDate = $endDate;
    $endDate = $temp;
}

// Fetch student data
try {
    $student = \StudentAttendance\Utils\Utility::safeQuery(
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
    $attendanceRecords = \StudentAttendance\Utils\Utility::safeQuery(
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

foreach ($attendanceRecords as $record) {
    if ($record['status'] === 'present') {
        $presentCount++;
    } elseif ($record['status'] === 'late') {
        $lateCount++;
    } elseif ($record['status'] === 'absent') {
        $absentCount++;
    }
}

$attendanceRate = $totalClasses > 0 ? round(($presentCount / $totalClasses) * 100, 0) : 0;

// Format dates for display
$startDateDisplay = date('M d, Y', strtotime($startDate));
$endDateDisplay = date('M d, Y', strtotime($endDate));

// Prepare table data
$tableData = [];
foreach ($attendanceRecords as $record) {
    $dateObj = new DateTime($record['attendance_date']);
    $statusColor = 'present' === $record['status'] ? 'green' : ('late' === $record['status'] ? 'yellow' : 'red');
    $statusText = ucfirst($record['status']);
    
    $tableData[] = [
        'date' => $dateObj->format('M d, Y'),
        'day' => $dateObj->format('l'),
        'time' => date('h:i A', strtotime($record['created_at'])),
        'status' => $statusText,
        'statusColor' => $statusColor
    ];
}

$pageTitle = 'Attendance Report';
$pageHeading = 'Attendance Report';
$activeNav = 'report';

require __DIR__ . '/../../components/student/layout-start.php';
?>

<div class="space-y-6">
    <!-- Summary Statistics -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="bg-white rounded-lg border border-slate-200 p-6 text-center">
            <p class="text-xs font-medium text-slate-600 mb-1 uppercase tracking-wide">Total Classes</p>
            <p class="text-3xl font-bold text-emerald-700"><?php echo $totalClasses; ?></p>
        </div>
        <div class="bg-white rounded-lg border border-slate-200 p-6 text-center">
            <p class="text-xs font-medium text-slate-600 mb-1 uppercase tracking-wide">Attendance Rate</p>
            <p class="text-3xl font-bold text-emerald-700"><?php echo $attendanceRate; ?>%</p>
        </div>
        <div class="bg-white rounded-lg border border-slate-200 p-6 text-center">
            <p class="text-xs font-medium text-slate-600 mb-1 uppercase tracking-wide">Present</p>
            <p class="text-3xl font-bold text-green-600"><?php echo $presentCount; ?></p>
        </div>
        <div class="bg-white rounded-lg border border-slate-200 p-6 text-center">
            <p class="text-xs font-medium text-slate-600 mb-1 uppercase tracking-wide">Absent</p>
            <p class="text-3xl font-bold text-red-600"><?php echo $absentCount; ?></p>
        </div>
    </div>

    <!-- Date Filter and Export Buttons -->
    <div class="bg-white rounded-lg border border-slate-200 p-6 shadow-sm">
        <form method="GET" class="grid grid-cols-1 md:grid-cols-12 gap-4 items-end">
            <div class="md:col-span-4">
                <label class="block text-sm font-medium text-slate-700 mb-2">Start Date</label>
                <input type="date" name="start_date" value="<?php echo htmlspecialchars($startDate); ?>" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:border-emerald-600">
            </div>
            <div class="md:col-span-4">
                <label class="block text-sm font-medium text-slate-700 mb-2">End Date</label>
                <input type="date" name="end_date" value="<?php echo htmlspecialchars($endDate); ?>" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:border-emerald-600">
            </div>
            <div class="md:col-span-4">
                <button type="submit" class="w-full px-4 py-2 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 font-medium transition-colors">
                    Generate Report
                </button>
            </div>
        </form>
    </div>

    <!-- DataTable with Export Buttons -->
    <div class="bg-white rounded-lg border border-slate-200 shadow-sm overflow-hidden">
        <div class="p-6 border-b border-slate-200 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <div>
                <h3 class="text-lg font-bold text-slate-900">Attendance Records</h3>
                <p class="text-sm text-slate-600 mt-1">Displaying records from <?php echo $startDateDisplay; ?> to <?php echo $endDateDisplay; ?></p>
            </div>
            <div class="flex gap-2 flex-wrap">
                <button id="btnExcelExport" type="button" class="flex items-center gap-2 px-3 py-2 bg-green-100 text-green-700 rounded-lg hover:bg-green-200 transition-colors text-sm font-medium">
                    <span class="material-symbols-outlined text-base">download</span>
                    Excel
                </button>
                <button id="btnPdfExport" type="button" class="flex items-center gap-2 px-3 py-2 bg-red-100 text-red-700 rounded-lg hover:bg-red-200 transition-colors text-sm font-medium">
                    <span class="material-symbols-outlined text-base">picture_as_pdf</span>
                    PDF
                </button>
                <button id="btnPrintTable" type="button" class="flex items-center gap-2 px-3 py-2 bg-blue-100 text-blue-700 rounded-lg hover:bg-blue-200 transition-colors text-sm font-medium">
                    <span class="material-symbols-outlined text-base">print</span>
                    Print
                </button>
            </div>
        </div>

        <!-- DataTable Container -->
        <div class="overflow-x-auto">
            <table id="attendanceTable" class="w-full border-collapse">
                <thead>
                    <tr class="bg-slate-50">
                        <th class="px-6 py-3 text-left text-xs font-bold text-slate-700 uppercase tracking-wide border-b border-slate-200">Date</th>
                        <th class="px-6 py-3 text-left text-xs font-bold text-slate-700 uppercase tracking-wide border-b border-slate-200">Day</th>
                        <th class="px-6 py-3 text-left text-xs font-bold text-slate-700 uppercase tracking-wide border-b border-slate-200">Time</th>
                        <th class="px-6 py-3 text-left text-xs font-bold text-slate-700 uppercase tracking-wide border-b border-slate-200">Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($tableData)): ?>
                        <?php foreach ($tableData as $row): ?>
                        <tr class="border-b border-slate-200 hover:bg-slate-50 transition-colors">
                            <td class="px-6 py-4 text-sm text-slate-900"><?php echo htmlspecialchars($row['date']); ?></td>
                            <td class="px-6 py-4 text-sm text-slate-600"><?php echo htmlspecialchars($row['day']); ?></td>
                            <td class="px-6 py-4 text-sm text-slate-600"><?php echo htmlspecialchars($row['time']); ?></td>
                            <td class="px-6 py-4">
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold
                                    <?php
                                        if ($row['statusColor'] === 'green') {
                                            echo 'bg-green-100 text-green-700';
                                        } elseif ($row['statusColor'] === 'yellow') {
                                            echo 'bg-yellow-100 text-yellow-700';
                                        } else {
                                            echo 'bg-red-100 text-red-700';
                                        }
                                    ?>">
                                    <?php echo htmlspecialchars($row['status']); ?>
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4" class="px-6 py-12 text-center text-slate-500">
                                <span class="material-symbols-outlined text-4xl block mb-3 opacity-30">event_note</span>
                                <p class="font-medium">No attendance records found for the selected date range</p>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Scripts for Export Functionality -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
<script>
    // Prepare table data for export
    const reportData = <?php echo json_encode($tableData); ?>;
    const studentName = "<?php echo htmlspecialchars($student['name'] ?? 'Student'); ?>";
    const admissionNumber = "<?php echo htmlspecialchars($student['admission_number'] ?? 'N/A'); ?>";
    const startDate = "<?php echo htmlspecialchars($startDateDisplay); ?>";
    const endDate = "<?php echo htmlspecialchars($endDateDisplay); ?>";
    const generatedDate = "<?php echo date('M d, Y h:i A'); ?>";
    const totalClasses = <?php echo $totalClasses; ?>;
    const attendanceRate = <?php echo $attendanceRate; ?>;
    const presentCount = <?php echo $presentCount; ?>;
    const lateCount = <?php echo $lateCount; ?>;
    const absentCount = <?php echo $absentCount; ?>;

    function downloadTextFile(filename, content, mimeType = 'text/plain') {
        const blob = new Blob([content], { type: mimeType });
        const url = URL.createObjectURL(blob);
        const link = document.createElement('a');
        link.href = url;
        link.download = filename;
        document.body.appendChild(link);
        link.click();
        link.remove();
        URL.revokeObjectURL(url);
    }

    function csvEscape(value) {
        return `"${String(value ?? '').replace(/"/g, '""')}"`;
    }

    // Excel Export
    document.getElementById('btnExcelExport').addEventListener('click', function() {
        if (typeof XLSX === 'undefined') {
            const rows = [
                ['Date', 'Day', 'Time', 'Status'],
                ...reportData.map(row => [row.date, row.day, row.time, row.status])
            ];
            const csv = rows.map(row => row.map(csvEscape).join(',')).join('\n');
            downloadTextFile(`attendance_report_${new Date().toISOString().split('T')[0]}.csv`, csv, 'text/csv');
            return;
        }

        // Create a new workbook
        const wb = XLSX.utils.book_new();

        // Add summary sheet
        const summaryData = [
            ['ATTENDANCE REPORT'],
            [],
            ['Student Information'],
            ['Name:', studentName],
            ['Admission Number:', admissionNumber],
            [],
            ['Report Details'],
            ['Period:', `${startDate} to ${endDate}`],
            ['Generated:', generatedDate],
            [],
            ['Statistics'],
            ['Total Classes:', totalClasses],
            ['Attendance Rate:', `${attendanceRate}%`],
            ['Present:', presentCount],
            ['Late:', lateCount],
            ['Absent:', absentCount]
        ];

        const ws1 = XLSX.utils.aoa_to_sheet(summaryData);
        XLSX.utils.book_append_sheet(wb, ws1, 'Summary');

        // Add detailed records sheet
        const headers = ['Date', 'Day', 'Time', 'Status'];
        const rows = reportData.map(row => [row.date, row.day, row.time, row.status]);
        const ws2 = XLSX.utils.aoa_to_sheet([headers, ...rows]);
        XLSX.utils.book_append_sheet(wb, ws2, 'Records');

        // Download Excel file
        XLSX.writeFile(wb, `attendance_report_${new Date().toISOString().split('T')[0]}.xlsx`);
    });

    // PDF Export
    document.getElementById('btnPdfExport').addEventListener('click', function() {
        const element = document.getElementById('attendanceTable');
        if (typeof html2pdf === 'undefined') {
            document.getElementById('btnPrintTable').click();
            return;
        }

        const opt = {
            margin: 10,
            filename: `attendance_report_${new Date().toISOString().split('T')[0]}.pdf`,
            image: { type: 'jpeg', quality: 0.98 },
            html2canvas: { scale: 2 },
            jsPDF: { orientation: 'portrait', unit: 'mm', format: 'a4' }
        };

        // Create PDF with header information
        const pdfContent = `
            <div style="font-family: Arial, sans-serif; margin: 20px;">
                <h1>Attendance Report</h1>
                <div style="margin: 20px 0; padding: 10px; background: #f0f0f0; border-radius: 5px;">
                    <p><strong>Student:</strong> ${studentName}</p>
                    <p><strong>Admission Number:</strong> ${admissionNumber}</p>
                    <p><strong>Period:</strong> ${startDate} to ${endDate}</p>
                    <p><strong>Generated:</strong> ${generatedDate}</p>
                </div>
                <h3>Statistics</h3>
                <table style="width: 100%; border-collapse: collapse; margin: 20px 0;">
                    <tr>
                        <td style="padding: 8px; border: 1px solid #ddd;"><strong>Total Classes</strong></td>
                        <td style="padding: 8px; border: 1px solid #ddd;">${totalClasses}</td>
                        <td style="padding: 8px; border: 1px solid #ddd;"><strong>Attendance Rate</strong></td>
                        <td style="padding: 8px; border: 1px solid #ddd;">${attendanceRate}%</td>
                    </tr>
                    <tr>
                        <td style="padding: 8px; border: 1px solid #ddd;"><strong>Present</strong></td>
                        <td style="padding: 8px; border: 1px solid #ddd;">${presentCount}</td>
                        <td style="padding: 8px; border: 1px solid #ddd;"><strong>Absent</strong></td>
                        <td style="padding: 8px; border: 1px solid #ddd;">${absentCount}</td>
                    </tr>
                </table>
                <h3>Detailed Records</h3>
            </div>
            ${element.innerHTML}
        `;

        html2pdf().set(opt).from(pdfContent).save();
    });

    // Print Table
    document.getElementById('btnPrintTable').addEventListener('click', function() {
        const printWindow = window.open('', '', 'height=600,width=800');
        const table = document.getElementById('attendanceTable');

        printWindow.document.write(`
            <html>
            <head>
                <title>Attendance Report</title>
                <style>
                    body { font-family: Arial, sans-serif; margin: 20px; }
                    h1 { color: #333; }
                    .header { margin: 20px 0; padding: 15px; background: #f0f0f0; border-radius: 5px; }
                    .header p { margin: 5px 0; }
                    table { width: 100%; border-collapse: collapse; margin: 20px 0; }
                    th, td { padding: 10px; border: 1px solid #ddd; text-align: left; }
                    th { background-color: #f5f5f5; font-weight: bold; }
                    .green { color: #10b981; }
                    .red { color: #dc2626; }
                    .yellow { color: #f59e0b; }
                </style>
            </head>
            <body>
                <h1>Attendance Report</h1>
                <div class="header">
                    <p><strong>Student:</strong> ${studentName}</p>
                    <p><strong>Admission Number:</strong> ${admissionNumber}</p>
                    <p><strong>Period:</strong> ${startDate} to ${endDate}</p>
                    <p><strong>Generated:</strong> ${generatedDate}</p>
                </div>
                <h3>Statistics</h3>
                <p>Total Classes: <strong>${totalClasses}</strong> | Attendance Rate: <strong>${attendanceRate}%</strong> | Present: <strong>${presentCount}</strong> | Absent: <strong>${absentCount}</strong></p>
                <h3>Detailed Records</h3>
                ${table.outerHTML}
            </body>
            </html>
        `);

        printWindow.document.close();
        printWindow.focus();
        printWindow.print();
        printWindow.close();
    });
</script>

<?php require __DIR__ . '/../../components/student/layout-end.php'; ?>
