# EduAttend - Student Attendance Management System

## Overview
Complete attendance management system with student portal (login, dashboard, analytics) and admin interface (attendance marking). Real-time statistics, interactive charts, and comprehensive attendance tracking.

## Quick Start

### 1. Setup Database
```bash
# Run migrations (both required)
php bin/doctrine migrations:execute StudentAttendance\\Migrations\\Version20260526000000 --up
php bin/doctrine migrations:execute StudentAttendance\\Migrations\\Version20260527000000 --up

# Seed test data
php scratch/seed_students.php
```

### 2. Student Portal
- **Login**: `/pages/student/login.php`
  - Test Email: `alex@school.edu`
  - Test Password: `password123`
  - Forgot Password: Click "Forgot password?" link
- **Dashboard**: Personal attendance overview with stats and charts
- **Analytics**: Attendance trends and detailed statistics
- **Report**: Generate custom reports with PDF/CSV export
- **History**: 30-day attendance record listing
- **Profile**: Student information and settings

### 3. Admin Portal
- **Login**: `/pages/admin/login.php`
  - Test Email: `admin@school.edu`
  - Test Password: `admin123`
- **Dashboard**: Mark attendance for all students daily
- **API**: RESTful endpoints for attendance operations

### 4. Password Recovery
- Click "Forgot password?" on login page
- Enter email or admission number
- Receive reset code
- Enter new password
- Auto-redirect to login

## System Architecture

### Components
```
EduAttend
├── Student Portal
│   ├── Login/Authentication
│   ├── Dashboard (Home)
│   ├── Analytics & Charts
│   ├── Report Generation & Export
│   ├── Attendance History
│   ├── User Profile
│   └── Settings
├── Admin Portal
│   ├── Login/Authentication
│   ├── Attendance Dashboard
│   ├── Quick Marking
│   ├── Statistics
│   └── API Endpoints
└── Database
    ├── Students Table
    ├── Attendance Table
    └── Migration Scripts
```

### Tech Stack
- **Backend**: PHP 7.4+
- **Database**: MySQL 5.7+ or MariaDB
- **Frontend**: HTML5, Tailwind CSS, Material Design 3
- **Charts**: Chart.js
- **Icons**: Material Symbols Outlined
- **Authentication**: PHP Sessions + Bcrypt

## Documentation

### Student Portal
See [STUDENT_PORTAL_README.md](STUDENT_PORTAL_README.md) for detailed documentation on:
- Student login and authentication
- Dashboard features and statistics
- Analytics with Chart.js visualizations
- Attendance history
- Profile management
- Responsive design

### Admin Portal
See [ADMIN_README.md](ADMIN_README.md) for detailed documentation on:
- Admin authentication
- Attendance marking interface
- Real-time statistics
- RESTful API endpoints
- Batch operations
- Security features

### Report Generation
See [REPORT_GENERATION_README.md](REPORT_GENERATION_README.md) for detailed documentation on:
- Custom date range report generation
- Statistics and analytics
- PDF and CSV export functionality
- Weekly trend visualization
- Absence tracking
- Mobile-responsive design

## File Structure

```
attendance/
├── README.md                          # Main documentation
├── STUDENT_PORTAL_README.md          # Student docs
├── ADMIN_README.md                   # Admin docs
├── FORGOT_PASSWORD_README.md         # Password recovery docs
├── REPORT_GENERATION_README.md       # Report generation docs
├── pages/
│   ├── student/
│   │   ├── login.php                 # Student login
│   │   ├── dashboard.php             # Home/Dashboard
│   │   ├── analytics.php             # Charts & Analytics
│   │   ├── report.php                # Report generation & export
│   │   ├── attendance.php            # History
│   │   ├── profile.php               # Profile
│   │   ├── logout.php                # Logout
│   │   └── forgot-password.php       # Password recovery
│   ├── admin/
│   │   ├── login.php                 # Admin login
│   │   ├── dashboard.php             # Attendance marking
│   │   └── logout.php                # Logout
│   ├── api/
│   │   └── attendance.php            # API endpoints
│   ├── contact.php                   # Contact page
│   ├── privacy.php                   # Privacy policy
│   ├── user-guide.php                # User guide
│   └── status.php                    # System status
├── src/
│   └── Migrations/
│       ├── Version20260526000000.php # Students & Attendance tables
│       └── Version20260527000000.php # Password resets table
├── config/
│   └── bootstrap.php                 # Database connection
├── utils/
│   ├── Utility.php                   # Database utilities
│   ├── Database.php                  # Connection management
│   └── Router.php                    # Routing
├── scratch/
│   ├── seed_students.php             # Database seeding
│   ├── create_db.php                 # DB creation
│   └── reset_db.php                  # DB reset
├── assets/
│   ├── bootstrap/                    # Bootstrap CSS
│   ├── DataTables/                   # DataTables library
│   ├── jquery/                       # jQuery
│   ├── sweetalert2/                  # SweetAlert2
│   └── tailwindcss/                  # Tailwind CSS
└── components/
    └── ui/
        ├── navbar.php                # Navigation component
        ├── footer.php                # Footer component
        └── feedback-modal.php        # Modal component
```

## Database Schema

### Students Table
```sql
CREATE TABLE students (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    admission_number VARCHAR(100) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

### Attendance Table
```sql
CREATE TABLE attendance (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    attendance_date DATE NOT NULL,
    status ENUM('present', 'absent', 'late') DEFAULT 'present',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    UNIQUE KEY unique_student_date (student_id, attendance_date)
);
```

### Password Resets Table
```sql
CREATE TABLE password_resets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL UNIQUE,
    token_hash VARCHAR(64) NOT NULL,
    expires_at DATETIME NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    used_at DATETIME,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE
);
```

## Features

### Student Portal
✅ Email/Admission number login
✅ Attendance percentage gauge
✅ Real-time statistics (Present/Absent/Late)
✅ Interactive charts (Doughnut/Bar/Line)
✅ 30-day attendance history
✅ Today's status highlight
✅ Weekly trends
✅ Monthly analysis
✅ Profile information
✅ Settings menu
✅ 30-day remember me cookie
✅ Mobile responsive design
✅ Material Design 3 styling

### Admin Portal
✅ Staff authentication
✅ One-click attendance marking
✅ Real-time progress tracking
✅ Statistics overview
✅ Bulk operations via API
✅ Color-coded statuses
✅ Session management
✅ Logout functionality
✅ RESTful API endpoints
✅ Mobile responsive design

## API Reference

### Attendance API
Base URL: `/pages/api/attendance.php`

#### GET - Retrieve Data
- `?action=today` - Today's attendance records
- `?action=stats` - Today's statistics

#### POST - Single Record
- `?action=mark` - Mark individual attendance
  ```json
  {
    "student_id": 1,
    "status": "present"
  }
  ```

#### PUT - Batch Update
- `?action=batch` - Update multiple records
  ```json
  {
    "records": [
      {"student_id": 1, "status": "present"},
      {"student_id": 2, "status": "late"}
    ]
  }
  ```

## Security Features

✅ Password hashing with bcrypt
✅ Secure token generation (32-byte random)
✅ Token expiration (1 hour)
✅ Session-based authentication
✅ SQL injection prevention
✅ XSS prevention
✅ CSRF token support
✅ Secure cookie handling
✅ Role-based access control
✅ Input validation
✅ Error handling
✅ Email masking in UI

## Default Test Credentials

### Student Accounts
```
Email: alex@school.edu | Password: password123 | Admission: STU2024001
Email: jordan@school.edu | Password: password123 | Admission: STU2024002
Email: sam@school.edu | Password: password123 | Admission: STU2024003
Email: taylor@school.edu | Password: password123 | Admission: STU2024004
Email: morgan@school.edu | Password: password123 | Admission: STU2024005
```

### Admin Account
```
Email: admin@school.edu
Password: admin123
```

## User Flows

### Student Flow
```
1. Navigate to /pages/student/login.php
2. Enter email/admission number and password
3. Click "Sign In"
4. Dashboard loads with attendance overview
5. Click "Analytics" for detailed charts
6. Click "Attendance" for history
7. Click "Profile" for settings
8. Click "Logout" to exit
```

### Admin Flow
```
1. Navigate to /pages/admin/login.php
2. Enter admin credentials
3. Dashboard displays all students
4. For each student, click Present/Late/Absent
5. Progress counter updates
6. Repeat for all students
7. Click logout to finish
```

## Troubleshooting

### Common Issues

**Login Failed**
- Verify credentials in seed_students.php
- Check database connection in config/bootstrap.php
- Clear browser cookies

**Dashboard Not Loading**
- Check if attendance table exists
- Verify student_id in session
- Check browser console for errors

**Charts Not Displaying**
- Clear browser cache
- Verify Chart.js CDN is loaded
- Check if data exists in database

**API Errors**
- Check if admin is logged in
- Verify Content-Type header is application/json
- Check request format

## Future Enhancements

- [ ] Mobile app (React Native/Flutter)
- [ ] Email notifications
- [ ] SMS alerts
- [ ] QR code attendance
- [ ] Biometric integration
- [ ] Parent portal
- [ ] Leave management
- [ ] Class schedule
- [ ] PDF reports
- [ ] Dark mode
- [ ] Multi-language support

## Support

### Documentation
- [Student Portal Guide](STUDENT_PORTAL_README.md)
- [Admin Portal Guide](ADMIN_README.md)

### Contact
- **Email**: support@eduattend.edu
- **Bug Reports**: issues@eduattend.edu

---

**Version**: 1.0.0
**Status**: Production Ready
**Last Updated**: May 2026
