# Admin Dashboard - Attendance Management

## Overview
Complete admin/staff interface for marking student attendance daily, viewing statistics, and managing records. Staff can quickly mark attendance status for all students and view real-time progress.

## Pages Created

### 1. **pages/admin/login.php** - Admin Login
- Staff authentication interface
- Features:
  - Email-based login
  - Password visibility toggle
  - Demo credentials display
  - Error handling
  - Clean Material Design interface
  - Responsive layout
  - Links to student login

#### Demo Credentials (for testing):
```
Email: admin@school.edu
Password: admin123
```

### 2. **pages/admin/dashboard.php** - Attendance Marking Dashboard
- Real-time attendance marking interface
- Features:
  - Statistics overview (Total/Marked/Present/Late/Absent)
  - Student list with quick-action buttons
  - One-click status marking (Present/Late/Absent)
  - Progress indicator (Marked / Total)
  - Real-time counter updates
  - Responsive table layout
  - Status color coding
  - Date display

#### Functionality:
```
1. View all students in a table
2. Click status buttons to mark attendance
3. Real-time progress update
4. Color-coded status indicators
5. Session-based authentication
```

### 3. **pages/admin/logout.php** - Admin Logout
- Session cleanup and redirect

### 4. **pages/api/attendance.php** - Attendance API
- RESTful API for attendance operations
- Endpoints:
  - `GET /pages/api/attendance.php?action=today` - Get today's records
  - `GET /pages/api/attendance.php?action=stats` - Get statistics
  - `POST /pages/api/attendance.php?action=mark` - Mark individual attendance
  - `PUT /pages/api/attendance.php?action=batch` - Batch update records

## Features

### Admin Dashboard
✅ Real-time attendance marking
✅ Statistics overview (5 cards)
✅ Student table with inline actions
✅ One-click status buttons
✅ Progress tracking
✅ Color-coded statuses (green/yellow/red)
✅ Responsive design (mobile/desktop)
✅ Session authentication
✅ Date display
✅ Logout functionality

### API Endpoints

#### GET /pages/api/attendance.php?action=today
**Response:**
```json
{
  "data": [
    {
      "student_id": 1,
      "status": "present",
      "name": "Alex Rivers",
      "admission_number": "STU2024001"
    }
  ]
}
```

#### GET /pages/api/attendance.php?action=stats
**Response:**
```json
{
  "data": {
    "marked": 45,
    "total": 50,
    "present": 42,
    "late": 2,
    "absent": 1
  }
}
```

#### POST /pages/api/attendance.php?action=mark
**Request:**
```json
{
  "student_id": 1,
  "status": "present"  // or "late", "absent"
}
```

**Response:**
```json
{
  "success": true,
  "message": "Attendance marked"
}
```

#### PUT /pages/api/attendance.php?action=batch
**Request:**
```json
{
  "records": [
    {"student_id": 1, "status": "present"},
    {"student_id": 2, "status": "late"},
    {"student_id": 3, "status": "absent"}
  ]
}
```

**Response:**
```json
{
  "success": true,
  "updated": 3
}
```

## Status Color Scheme

| Status | Color | Icon | Meaning |
|--------|-------|------|---------|
| Present | Green | ✓ | Student arrived on time |
| Late | Yellow | ⏱ | Student arrived late |
| Absent | Red | ✗ | Student not present |
| Unmarked | Gray | - | No status recorded |

## Database Tables Used

### Students Table
```sql
SELECT id, name, admission_number, email FROM students
```

### Attendance Table
```sql
SELECT student_id, attendance_date, status FROM attendance
WHERE attendance_date = CURDATE()
```

## Setup Instructions

### 1. Database Preparation
```bash
# Run migration to create tables
cd attendance
php bin/doctrine migrations:execute Mpemba\\Crud\\Migrations\\Version20260526000000 --up

# Seed test students
php scratch/seed_students.php
```

### 2. Access Admin Dashboard
1. Navigate to `/pages/admin/login.php`
2. Login with demo credentials:
   - Email: `admin@school.edu`
   - Password: `admin123`
3. Mark attendance for students
4. Click logout when done

### 3. Mark Attendance
1. Dashboard displays all students
2. For each student, click a status button:
   - `✓ Present` - Mark as present
   - `⏱ Late` - Mark as late
   - `✗ Absent` - Mark as absent
3. Button highlights and progress counter updates in real-time
4. Changes are saved to database immediately

## Features in Detail

### Statistics Overview
- **Total Students**: Count of all enrolled students
- **Marked Today**: Number of students with recorded attendance
- **Present**: Count of students marked present
- **Late**: Count of students marked late
- **Absent**: Count of students marked absent

### Student Table
- Admission Number
- Student Name
- Email Address
- Status Buttons (Present/Late/Absent)

### Progress Indicator
- Shows: `Marked today / Total students`
- Updates in real-time as attendance is marked
- Helps track completion status

## Security Features

✅ Session-based authentication
✅ Admin login required
✅ API endpoint authorization checks
✅ Input validation
✅ Error handling
✅ Prepared statements (SQL injection prevention)
✅ JSON response validation

## API Usage Examples

### JavaScript Example - Mark Single Attendance
```javascript
async function markAttendance(studentId, status) {
    const response = await fetch('/pages/api/attendance.php?action=mark', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            student_id: studentId,
            status: status
        })
    });
    const data = await response.json();
    console.log(data);
}
```

### JavaScript Example - Get Today's Stats
```javascript
async function getStats() {
    const response = await fetch('/pages/api/attendance.php?action=stats');
    const data = await response.json();
    console.log(`Marked: ${data.data.marked}/${data.data.total}`);
}
```

### JavaScript Example - Batch Update
```javascript
async function batchUpdate() {
    const response = await fetch('/pages/api/attendance.php?action=batch', {
        method: 'PUT',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            records: [
                { student_id: 1, status: 'present' },
                { student_id: 2, status: 'late' },
                { student_id: 3, status: 'absent' }
            ]
        })
    });
    const data = await response.json();
    console.log(`Updated: ${data.updated} records`);
}
```

## Workflow

### Daily Attendance Marking
```
1. Staff logs into admin dashboard
2. Dashboard loads with all students
3. For each student:
   - Click appropriate status button
   - Button highlights
   - Progress counter updates
   - Data saved to database
4. Once complete, staff logs out
5. Students can view their records in their dashboard
```

### End-of-Day Report
```
1. Navigate to admin dashboard
2. View statistics cards:
   - Total marked attendance
   - Present/Late/Absent breakdown
   - Progress percentage
3. Data persists for historical analysis
```

## Responsive Design

### Mobile (< 768px)
- Single column table
- Stacked status buttons
- Horizontal scroll for table
- Full-width cards
- Top app bar with logout

### Desktop (≥ 768px)
- Multi-column layout
- Inline status buttons
- Responsive table
- Grid statistics cards
- Side-by-side controls

## Performance Optimizations

✅ Single database query per action
✅ Real-time UI updates without page reload
✅ Efficient button state management
✅ Minimal JavaScript overhead
✅ CSS animations for smooth transitions
✅ Responsive images and layouts

## Troubleshooting

### Login Fails
- Verify credentials: admin@school.edu / admin123
- Check if session is enabled on server
- Clear browser cookies

### Dashboard Not Loading
- Check if students table has data (run seed script)
- Verify attendance table exists
- Check browser console for JavaScript errors

### Attendance Not Saving
- Verify database connection in config/bootstrap.php
- Check if attendance table exists
- Review server error logs

### Button Clicks Not Working
- Check browser JavaScript console
- Verify fetch API support
- Check Content-Type headers

## Future Enhancements

- [ ] Admin role-based access control
- [ ] Bulk import attendance from CSV
- [ ] Attendance reports by date range
- [ ] Email notifications for attendance alerts
- [ ] Attendance by class/section
- [ ] Absentee tracking and alerts
- [ ] Historical attendance comparison
- [ ] Mobile admin app
- [ ] QR code scanning
- [ ] Automated attendance from biometric systems
- [ ] Leave management integration
- [ ] Parent notification system

## Production Considerations

### Authentication
- Replace demo credentials with proper admin table
- Implement password hashing
- Add role-based access control (Teacher/Admin)
- Implement proper session management

### Security
- Add CSRF token validation
- Implement rate limiting on API
- Add request signing
- Use HTTPS only
- Implement audit logging

### Performance
- Add database indexes on attendance_date
- Implement caching for student list
- Add pagination for large classes
- Optimize queries

### Reliability
- Add transaction support for batch updates
- Implement error recovery
- Add data backup
- Monitor database growth

## File Structure

```
attendance/
├── pages/
│   ├── admin/
│   │   ├── login.php           # Admin login
│   │   ├── dashboard.php       # Attendance marking
│   │   └── logout.php          # Logout handler
│   ├── api/
│   │   └── attendance.php      # API endpoints
│   └── student/
│       └── ...
├── scratch/
│   └── seed_students.php       # Database seeding
├── config/
│   └── bootstrap.php           # DB connection
└── utils/
    └── Utility.php             # Helper functions
```

## Support & Contact

For admin issues: admin@school.edu
For technical support: tech-support@school.edu
