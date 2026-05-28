# Attendance Report Generation Feature

**Status**: ✅ Production Ready  
**Last Updated**: May 26, 2026  
**File Location**: `pages/student/report.php`

## Overview

The **Attendance Report** feature allows students to generate detailed attendance summaries for any custom date range, export reports as PDF or CSV, and track their attendance trends with visual analytics.

### Key Features

✅ **Date Range Selection**
- Custom start and end date picker
- Automatic date validation (start ≤ end)
- Pre-filled with current month dates
- Works with any historical or future dates

✅ **Report Statistics**
- Total classes attended in period
- Attendance rate percentage (0-100%)
- Count of present days
- Count of absent days
- Count of late days

✅ **Visual Analytics**
- 7-day weekly trend bar chart
- Daily attendance percentage visualization
- Color-coded bars (green for present, gray for absent)
- Responsive chart resizing

✅ **Absence Tracking**
- Recent absences list (up to 5 entries)
- Display with date, time, and status
- Absence classification (Excused/Unexcused)
- Color-coded badges

✅ **Export Functionality**
- **PDF Export**: Downloads formatted report with all statistics
- **CSV Export**: Tab-separated data for Excel/Google Sheets
- **Print Support**: Browser print dialog integration
- Timestamped filenames (e.g., `attendance_report_2026-05-26.pdf`)

✅ **Responsive Design**
- Mobile-optimized layout
- Bottom navigation with 5 main sections
- Collapsible date picker on small screens
- Flexible grid system (2 cols mobile, 4 cols desktop)
- Touch-friendly buttons and controls

✅ **Material Design 3**
- Green primary color (#006c49 / #10b981)
- Clean typography hierarchy
- Icon system (Material Symbols Outlined)
- Consistent spacing and elevation

## File Structure

```
pages/student/
├── report.php          # Main report page with all functionality
├── dashboard.php       # Updated with report navigation link
├── attendance.php      # Updated with report navigation link
├── analytics.php       # Updated with report navigation link
└── profile.php         # Updated with report navigation link
```

## Navigation Integration

### Bottom Navigation Bar
The report page is accessible via the main bottom navigation bar on all student pages:

```
[Dashboard] [Attendance] [Report] [Analytics] [Profile]
                              ↑ Currently on Report
```

### Access Points
1. **From Dashboard**: Click "Report" in bottom navigation
2. **From Attendance**: Click "Report" in bottom navigation
3. **From Analytics**: Click "Report" in bottom navigation
4. **Direct URL**: `http://localhost/pages/student/report.php`
5. **With Parameters**: `/pages/student/report.php?start_date=2026-05-01&end_date=2026-05-31`

## Database Integration

### Query Pattern

```php
// Fetch attendance in date range
$attendanceRecords = Utility::safeQuery(
    'SELECT id, attendance_date, status, created_at
     FROM attendance 
     WHERE student_id = ? AND attendance_date BETWEEN ? AND ?
     ORDER BY attendance_date DESC',
    [$studentId, $startDate, $endDate],
    'SELECT'
);
```

### Tables Used
- `students` - Student profile information
- `attendance` - Attendance records with date and status
  - Columns: `id`, `student_id`, `attendance_date`, `status`, `created_at`
  - Statuses: `present`, `absent`, `late`

### Performance
- Direct indexed query on `student_id` and `attendance_date`
- Single database call per report generation
- Results ordered by date (DESC) for recency

## Data Calculations

### Statistics

```php
$totalClasses = count($attendanceRecords);
$presentCount = count(array_filter($records, fn($r) => $r['status'] === 'present'));
$lateCount = count(array_filter($records, fn($r) => $r['status'] === 'late'));
$absentCount = count(array_filter($records, fn($r) => $r['status'] === 'absent'));
$attendanceRate = ($totalClasses > 0) ? round(($presentCount / $totalClasses) * 100) : 0;
```

### Weekly Breakdown

The page generates a 7-day trend analysis:
- Loops through last 7 days
- Checks if attendance exists for each day
- Maps status to percentage (Present=100%, Late=50%, Absent=0%)
- Displays as bar chart with hover effects

## API Parameters

### Query String Parameters

| Parameter | Type | Default | Example |
|-----------|------|---------|---------|
| `start_date` | Date (YYYY-MM-DD) | First of current month | `2026-05-01` |
| `end_date` | Date (YYYY-MM-DD) | Today | `2026-05-31` |
| `view` | String (`normal`\|`pdf`\|`csv`) | `normal` | `pdf` |

### Parameter Handling

```php
$startDate = $_GET['start_date'] ?? date('Y-m-01');  // First day of month
$endDate = $_GET['end_date'] ?? date('Y-m-d');       // Today
$viewMode = $_GET['view'] ?? 'normal';               // Default view

// Validation: Ensure start ≤ end
if (strtotime($startDate) > strtotime($endDate)) {
    [$startDate, $endDate] = [$endDate, $startDate]; // Swap
}
```

## Export Functionality

### PDF Export

**Endpoint**: `/pages/student/report.php?start_date=...&end_date=...&view=pdf`

**Current Implementation**: Plain text PDF with basic content
- Headers: Student name, admission number
- Date range displayed
- Statistics summary (Total, Present, Late, Absent, Rate)
- Generation timestamp

**Future Enhancement**: Use TCPDF/FPDF for formatted PDF with:
- Logo and header
- Styled statistics cards
- Embedded charts
- Professional letterhead

**Usage Example**:
```html
<a href="?start_date=2026-05-01&end_date=2026-05-31&view=pdf">
    Download PDF
</a>
```

### CSV Export

**Endpoint**: `/pages/student/report.php?start_date=...&end_date=...&view=csv`

**Format**: Comma-separated values
- Headers: Date, Status, Time
- One record per line
- Importable to Excel, Google Sheets, or databases

**Example Output**:
```csv
Date,Status,Time
2026-05-26,present,09:15:30
2026-05-25,late,09:45:20
2026-05-24,absent,00:00:00
```

**Usage Example**:
```html
<a href="?start_date=2026-05-01&end_date=2026-05-31&view=csv">
    Export CSV
</a>
```

## UI Components

### Hero Section
- Large green background (`bg-green-700`)
- Headline and description text
- Abstract background decoration (gradient blur)
- Responsive padding (8px mobile, 12px desktop)

### Date Filter Section
- Glass-morphism card with semi-transparent white background
- Two date input fields with calendar icons
- Generate button with loading animation
- Responsive grid (1 col mobile, 3 cols desktop)

### Statistics Cards
4-card layout showing:
1. **Total Classes** - Dark background
2. **Attendance Rate** - Green background (highlighted)
3. **Present** - Dark background
4. **Absent** - Red background

Cards include hover effects (`hover:shadow-md`) and responsive grid.

### Chart Visualization
- Bar chart with 7 bars (one per day)
- Heights represent attendance percentage (0-100%)
- Color: Green (#006c49) for present, Gray for absent
- Day labels below each bar
- Hover effects with opacity and scale

### Absence List
- Display up to 5 recent absences
- Each absence shows:
  - Red circular icon with "event_busy" symbol
  - Class name
  - Date and time
  - Status badge (Excused/Unexcused)
- Hover state with red border

### Pro Tip Card
- Green background with white text
- Academic image with gradient overlay
- Motivational message about attendance
- Group image with hover zoom effect

## Authentication & Security

### Session Check
```php
if (!isset($_SESSION['student_id'])) {
    header('Location: /pages/student/login.php');
    exit;
}
```
- Redirects unauthenticated users to login
- Required before generating any report
- Prevents unauthorized access to other students' data

### SQL Injection Prevention
```php
Utility::safeQuery($query, [$studentId, $startDate, $endDate], 'SELECT')
```
- All queries use parameterized statements
- Values passed as separate array
- No string concatenation in queries

### XSS Prevention
```php
echo htmlspecialchars($startDate); // Date input value
echo htmlspecialchars($student['name']); // Student name display
```
- All user data HTML-escaped before output
- Prevents script injection in names/dates

### Data Isolation
- Each student only sees their own attendance data
- `WHERE student_id = ?` ensures this isolation
- Cannot bypass to view other students' records

## Testing Procedures

### Manual Testing

#### Test Case 1: Basic Report Generation
1. Login as student (alex@school.edu / password123)
2. Click "Report" in navigation
3. Verify default date range (current month)
4. Click "Generate Report"
5. Verify statistics display correctly
6. ✅ Expected: Stats show correct totals, chart renders

#### Test Case 2: Custom Date Range
1. On report page, select start date: 2026-05-01
2. Select end date: 2026-05-15
3. Click "Generate Report"
4. ✅ Expected: Only records in range are counted
5. ✅ Expected: Chart shows only relevant days

#### Test Case 3: Date Validation
1. Select start date: 2026-05-31
2. Select end date: 2026-05-01 (earlier date)
3. Click "Generate Report"
4. ✅ Expected: Dates are auto-swapped to be correct
5. ✅ Expected: Report generates with swapped dates

#### Test Case 4: PDF Export
1. Generate report with any date range
2. Click "Download PDF" button
3. ✅ Expected: PDF downloads with filename `attendance_report_YYYY-MM-DD.pdf`
4. ✅ Expected: PDF contains student name, dates, statistics

#### Test Case 5: CSV Export
1. Generate report with any date range
2. Click "Export CSV" button
3. ✅ Expected: CSV downloads with filename `attendance_report_YYYY-MM-DD.csv`
4. ✅ Expected: CSV opens in Excel with proper formatting

#### Test Case 6: Print Function
1. Generate report
2. Click Print button (printer icon)
3. ✅ Expected: Browser print dialog opens
4. ✅ Expected: Report layout optimized for printing

#### Test Case 7: Mobile Responsiveness
1. Open report on mobile device (< 768px width)
2. Verify date inputs stack vertically
3. Verify stats cards show 2x2 grid
4. Verify navigation is accessible
5. ✅ Expected: All elements readable and interactive
6. ✅ Expected: No horizontal scrolling

#### Test Case 8: Empty Period
1. Select date range with no attendance (future dates)
2. Generate report
3. ✅ Expected: All stats show 0
4. ✅ Expected: Chart shows empty bars
5. ✅ Expected: Absence list shows "no absences recorded"

#### Test Case 9: Single Day Report
1. Select same date for start and end
2. Generate report
3. ✅ Expected: Report shows only 1 class
4. ✅ Expected: Stats reflect single day data

#### Test Case 10: No Authentication
1. Clear browser cookies/session
2. Navigate directly to `/pages/student/report.php`
3. ✅ Expected: Redirect to login page
4. ✅ Expected: No data is exposed

### Data Verification

**Test Student**: alex@school.edu (STU2024001)

```sql
-- Verify test data
SELECT COUNT(*) as total_records FROM attendance WHERE student_id = 1;
-- Expected: 30 records

SELECT COUNT(*) as present_count FROM attendance 
WHERE student_id = 1 AND status = 'present';
-- Expected: ~27 (90% of 30)

SELECT COUNT(*) as absent_count FROM attendance 
WHERE student_id = 1 AND status = 'absent';
-- Expected: ~2 (5% of 30)

SELECT COUNT(*) as late_count FROM attendance 
WHERE student_id = 1 AND status = 'late';
-- Expected: ~1 (5% of 30)
```

## Troubleshooting

### Issue: Report shows 0 for all statistics
**Cause**: No attendance records in database  
**Solution**: 
1. Run seeding script: `php scratch/seed_students.php`
2. Verify records exist: `SELECT COUNT(*) FROM attendance`

### Issue: Chart not displaying
**Cause**: CSS bar sizing calculation error  
**Solution**:
1. Check browser console for JavaScript errors
2. Verify `max(20, ...)` CSS is applied to bars
3. Check that chart container has height

### Issue: Dates won't change
**Cause**: Date input browser compatibility  
**Solution**:
1. Verify browser supports HTML5 date input (Chrome, Firefox, Safari)
2. Try typing date manually (YYYY-MM-DD format)
3. Use fallback date format if needed

### Issue: PDF/CSV not downloading
**Cause**: Headers already sent or file not generating  
**Solution**:
1. Check for whitespace before `<?php` in file
2. Verify no previous `header()` calls
3. Check server error logs: `error_log()`
4. Verify write permissions in temp directory

### Issue: Mobile navigation cut off
**Cause**: Viewport meta tag missing or wrong  
**Solution**:
1. Verify: `<meta name="viewport" content="width=device-width, initial-scale=1.0">`
2. Clear browser cache (Ctrl+Shift+Delete)
3. Test in mobile device emulator (DevTools F12)

## Future Enhancements

### Priority 1 (High)
- ✨ **PDF with TCPDF**: Professional formatted PDFs with logo and charts
- 📧 **Email Export**: Send reports directly to student email
- 📊 **Advanced Charts**: Chart.js integration for better visualizations
- 🔔 **Scheduled Reports**: Automatic weekly/monthly report generation

### Priority 2 (Medium)
- 📈 **Trend Analysis**: Month-over-month comparison charts
- ⚠️ **Risk Alerts**: Alert student if attendance drops below 90%
- 📝 **Comments**: Faculty can add notes to absences
- 🏆 **Benchmarking**: Compare attendance with class average

### Priority 3 (Lower)
- 🌙 **Dark Mode**: Support dark theme for report
- 🌍 **Multi-language**: Support multiple languages
- 📱 **Mobile App**: Native mobile app export
- 🔐 **Scheduled Access**: Time-based report availability

## Code Examples

### Generate Report Programmatically

```php
<?php
// Get a student's attendance report for May 2026
$studentId = 1;
$startDate = '2026-05-01';
$endDate = '2026-05-31';

$records = Utility::safeQuery(
    'SELECT attendance_date, status 
     FROM attendance 
     WHERE student_id = ? AND attendance_date BETWEEN ? AND ?
     ORDER BY attendance_date ASC',
    [$studentId, $startDate, $endDate],
    'SELECT'
);

$present = count(array_filter($records, fn($r) => $r['status'] === 'present'));
$rate = round(($present / count($records)) * 100);

echo "Present: $present | Rate: $rate%\n";
?>
```

### Add Report Link to Custom Page

```html
<!-- Link to report with specific dates -->
<a href="/pages/student/report.php?start_date=2026-05-01&end_date=2026-05-31">
    View May Report
</a>

<!-- Download as PDF -->
<a href="/pages/student/report.php?view=pdf&start_date=2026-05-01&end_date=2026-05-31">
    Download PDF Report
</a>

<!-- Export as CSV -->
<a href="/pages/student/report.php?view=csv&start_date=2026-05-01&end_date=2026-05-31">
    Export to Excel
</a>
```

### Calculate Attendance Programmatically

```php
<?php
function getAttendanceRate($studentId, $startDate, $endDate) {
    $records = Utility::safeQuery(
        'SELECT COUNT(*) as total,
                SUM(CASE WHEN status = "present" THEN 1 ELSE 0 END) as present
         FROM attendance
         WHERE student_id = ? AND attendance_date BETWEEN ? AND ?',
        [$studentId, $startDate, $endDate],
        'SELECT',
        true
    );
    
    return $records['total'] > 0 
        ? round(($records['present'] / $records['total']) * 100)
        : 0;
}

$rate = getAttendanceRate(1, '2026-05-01', '2026-05-31');
echo "Attendance Rate: $rate%";
?>
```

## Performance Considerations

### Database Optimization
- ✅ Indexed queries on `student_id` and `attendance_date`
- ✅ Single database call per report generation
- ✅ Result set limited by date range
- ⚠️ Large date ranges may impact performance

### Frontend Optimization
- ✅ CDN-hosted Tailwind CSS and Material Symbols
- ✅ Lightweight custom bar chart (no Chart.js dependency)
- ✅ Lazy-loading for large charts
- ✅ Minimal JavaScript for interactions

### Recommendations
- Limit date range to 6 months for performance
- Cache generated reports for frequent access
- Use pagination for very large result sets (future)

## API Reference

### Get Report Data

**Endpoint**: `/pages/student/report.php` (GET/POST)

**Parameters**:
```
GET /pages/student/report.php?start_date=2026-05-01&end_date=2026-05-31
```

**Response**: HTML-rendered report with statistics and charts

**Status Codes**:
- 200: Report generated successfully
- 302: Redirect to login (not authenticated)
- 500: Database error

---

**Documentation Version**: 1.0  
**Last Reviewed**: May 26, 2026  
**Maintainer**: EduAttend Development Team
