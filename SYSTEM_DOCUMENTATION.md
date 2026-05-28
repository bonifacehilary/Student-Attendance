# EduAttend - Student Attendance System

## Overview
EduAttend is a modern, professional student attendance management system built with PHP, MySQL, and Tailwind CSS. It provides a complete solution for tracking student attendance with an intuitive Material Design 3 interface.

## Features

### ✅ Core Features

#### 1. **Student Authentication**
- Secure login using admission number or email
- bcrypt password hashing for maximum security
- Session-based authentication
- Logout functionality
- Password recovery system

#### 2. **Student Dashboard** 
- Welcome message with personalized greeting
- **Attendance Statistics**:
  - Overall attendance percentage
  - Total present days
  - Total absent days
  - Total late arrivals
  - Attendance target (90%)
- **4-Week Attendance Trends** visualization
- Recent notifications display
- Today's status highlight card
- Responsive Material Design 3 layout

#### 3. **Attendance History**
- View all attendance records in a responsive table
- Columns: Date, Status, Marked Time
- **Status Indicators**:
  - ✅ Present (Green)
  - ❌ Absent (Red)
  - ⏱️ Late (Orange)
- Search and filter functionality
- Filter by month/date range
- Mobile-optimized display

#### 4. **Analytics Page**
- **Interactive Charts** (powered by Chart.js):
  - Doughnut chart: Attendance breakdown (Present/Late/Absent)
  - Bar chart: Last 7 days attendance
  - Line chart: Monthly attendance trends (6 months)
- Key statistics cards:
  - Overall attendance percentage
  - Average punctuality
  - Recent late arrivals list
- Real-time data calculations

#### 5. **Attendance Reports**
- Generate comprehensive attendance reports
- **Date Range Filtering**:
  - Select custom start and end dates
  - Pre-defined ranges (This Month, Last 30 Days, etc.)
- **Export Options**:
  - PDF: Styled report layout
  - CSV: Tabular data for spreadsheet analysis
- **Statistics Summary**:
  - Present/Absent/Late counts
  - Attendance percentage
  - Weekly breakdown
- Printable report layout

#### 6. **QR Code Attendance**
- **Modern QR Scanner**:
  - Real-time QR code detection via camera
  - Automatic marking of attendance on scan
  - Manual fallback input for QR codes
- **User Instructions**:
  - Step-by-step guide for scanning
  - Error handling and feedback
  - Session management
- Prevents duplicate attendance marking
- Mobile-friendly camera interface

#### 7. **Student Profile**
- **View Profile Information**:
  - Profile photo
  - Full name
  - Admission number
  - Class/Course
  - Phone number
  - Email address
- **Edit Profile**:
  - Update name, email, phone, class
  - Change profile picture
  - Real-time validation
- **Password Management**:
  - Secure password change
  - Current password verification
  - Password strength validation
- **Photo Upload**:
  - Supported formats: JPG, JPEG, PNG, WebP
  - Automatic file validation
  - Unique file naming system

#### 8. **Notifications System**
- **Real-time Notifications**:
  - Attendance marked alerts
  - Absence warnings
  - Low attendance alerts
  - System announcements
- **Filtering Options**:
  - View All notifications
  - Filter by Alerts (warnings)
  - Filter by Announcements
- **Actions**:
  - Mark as read
  - Dismiss notifications
  - Appeal (with admin review)
- **Notification Display**:
  - Icon and color-coded types
  - Time formatting (5min ago, Yesterday, etc.)
  - Unread count badge on navigation

#### 9. **Splash Screen**
- Beautiful loading screen during authentication
- Animated spinner with status updates
- Automatic redirect to dashboard
- Session validation before redirect
- Professional Material Design styling

### 📱 User Interface Features

#### Responsive Design
- **Desktop**: Fixed 280px sidebar + main content area
- **Tablet**: Adaptive layout with mobile bottom navigation
- **Mobile**: Bottom navigation bar (320px+)
- Touch-friendly button sizes
- Smooth animations and transitions

#### Navigation
- Desktop sidebar navigation (logged-out hidden)
- Mobile bottom navigation bar
- Active state indicators
- Quick access to all features
- Consistent navigation across all pages

#### Material Design 3
- Professional color scheme:
  - Primary: #006c49 (Green)
  - Secondary: #565e74 (Blue-Gray)
  - Error: #ba1a1a (Red)
- Clean typography with Inter font
- Rounded corners and subtle shadows
- Proper spacing and alignment
- Icon support via Material Symbols

---

## Page Structure

### Student Pages
```
/pages/student/
├── login.php              # Student login screen
├── splash.php             # Loading screen
├── dashboard.php          # Main dashboard
├── attendance.php         # Attendance history
├── qr-attendance.php      # QR scanner
├── analytics.php          # Charts and analytics
├── report.php             # Report generation
├── profile.php            # Student profile & settings
├── notifications.php      # Notifications center
├── forgot-password.php    # Password recovery
├── logout.php             # Logout handler
```

### Admin Pages
```
/pages/admin/
├── admin-login.php        # Admin authentication
├── mark_attendance.php    # Mark attendance interface
```

---

## Database Schema

### Core Tables

#### `students`
- `id` (PK)
- `name` (varchar)
- `email` (unique)
- `admission_number` (unique)
- `password_hash` (varchar)
- `student_class` (varchar)
- `phone` (varchar)
- `profile_photo` (varchar) - path to photo file
- `created_at` (datetime)
- `updated_at` (datetime)

#### `attendance`
- `id` (PK)
- `student_id` (FK → students)
- `status` (enum: present, absent, late)
- `attendance_date` (date)
- `marked_time` (datetime)
- `qr_session_id` (FK → attendance_qr_sessions, nullable)
- `created_at` (datetime)

#### `notifications`
- `id` (PK)
- `student_id` (FK → students)
- `type` (enum: attendance_alert, warning, announcement, confirmation, info)
- `title` (varchar)
- `message` (text)
- `is_read` (boolean)
- `can_appeal` (boolean)
- `appeal_status` (enum: null, pending, approved, rejected)
- `action_url` (varchar, nullable)
- `created_at` (datetime)
- `updated_at` (datetime)

#### `attendance_qr_sessions`
- `id` (PK)
- `code` (varchar, unique)
- `class_id` (int, nullable)
- `session_name` (varchar)
- `created_by` (int - admin/teacher ID)
- `created_date` (datetime)
- `expires_at` (datetime)
- `is_active` (boolean)

#### `password_resets`
- `id` (PK)
- `student_id` (FK → students)
- `token` (unique)
- `expires_at` (datetime)
- `created_at` (datetime)

---

## Security Features

### Backend Security
✅ **Prepared Statements** - All database queries use parameterized statements  
✅ **SQL Injection Protection** - Via Utility::safeQuery()  
✅ **Password Hashing** - bcrypt algorithm (PASSWORD_BCRYPT)  
✅ **Session Authentication** - Secure session-based auth  
✅ **Input Validation** - All user inputs validated before processing  
✅ **File Upload Validation** - MIME type and extension checks  
✅ **CSRF Protection Ready** - Can be added with token system  

### Frontend Security
✅ **HTML Escaping** - htmlspecialchars() on all output  
✅ **Secure Headers** - Set via PHP configuration  
✅ **No Sensitive Data** - Passwords never logged or displayed  
✅ **Session Validation** - Check on every protected page  

---

## Installation & Setup

### Prerequisites
- PHP 7.4 or higher
- MySQL 5.7 or higher
- Composer (for dependencies)
- Web server (Apache/Nginx)

### Installation Steps

1. **Clone/Extract Project**
   ```bash
   cd Student-Attendance/attendance
   ```

2. **Install Dependencies**
   ```bash
   composer install
   ```

3. **Configure Database**
   - Copy `.env.example` to `.env`
   - Set database credentials:
     ```
     DB_HOST=localhost
     DB_NAME=student_attendance
     DB_USER=root
     DB_PASSWORD=
     ```

4. **Run Migrations**
   ```bash
   vendor/bin/doctrine migrations:migrate
   ```

5. **Create Admin User** (if needed)
   ```bash
   php bin/console admin:create
   ```

6. **Start Development Server**
   ```bash
   php -S localhost:8000
   ```

7. **Access Application**
   - Student Login: http://localhost:8000/pages/student/login.php
   - Admin Login: http://localhost:8000/pages/admin/admin-login.php

---

## Usage Guide

### For Students

#### 1. Login
- Use admission number or email
- Password (case-sensitive)
- Remember me option available

#### 2. Dashboard
- View attendance overview
- Check today's status
- See recent notifications
- Access 4-week trends

#### 3. Mark Attendance via QR
- Go to QR Attendance page
- Allow camera access
- Point camera at QR code
- Automatic detection and marking
- Or manually enter QR code

#### 4. View Attendance History
- See all historical records
- Filter by date range
- Search by status
- Mobile-friendly view

#### 5. Generate Reports
- Select date range
- Choose export format (PDF/CSV)
- Download or print
- Share with educators

#### 6. View Analytics
- Interactive charts
- Attendance trends
- Punctuality metrics
- Late arrival history

#### 7. Manage Profile
- Update personal information
- Upload profile picture
- Change password
- Update contact details

#### 8. Check Notifications
- View all notifications
- Filter by type
- Mark as read
- Appeal if needed

---

## API Endpoints (for future integration)

### Authentication
```
POST /api/auth/login
POST /api/auth/logout
POST /api/auth/forgot-password
```

### Attendance
```
GET /api/attendance/history
GET /api/attendance/stats
POST /api/attendance/mark-qr
POST /api/attendance/report
```

### Profile
```
GET /api/profile
PUT /api/profile/update
POST /api/profile/password
POST /api/profile/photo
```

### Notifications
```
GET /api/notifications
PUT /api/notifications/read
DELETE /api/notifications/dismiss
```

---

## File Structure

```
attendance/
├── assets/
│   ├── bootstrap/          # Bootstrap CSS/JS
│   ├── DataTables/         # DataTables library
│   ├── jquery/             # jQuery library
│   ├── sweetalert2/        # Alert library
│   ├── tailwindcss/        # Tailwind CSS
│   └── profile_photos/     # Student profile photos
├── bin/
│   ├── console             # Doctrine console
│   └── doctrine            # Migrations tool
├── components/
│   └── ui/
│       ├── navbar.php      # Navigation bar
│       ├── footer.php      # Footer
│       └── feedback-modal.php
├── config/
│   └── bootstrap.php       # Database config
├── controller/
│   └── UserController.php  # User logic
├── js/
│   ├── app.js              # Main app JS
│   ├── api-client.js       # API client
│   └── admin.js            # Admin scripts
├── pages/
│   ├── student/            # Student pages
│   └── admin/              # Admin pages
├── src/
│   └── Migrations/         # Database migrations
├── utils/
│   ├── Utility.php         # Query helper
│   ├── Database.php        # DB connection
│   ├── Router.php          # Routing
│   └── ActivityLogger.php  # Logging
└── README.md               # Documentation
```

---

## Customization Guide

### Add New Notification Type
1. Update `notifications` table schema
2. Add case to `getNotificationDisplay()` in notifications.php
3. Create new notification trigger logic

### Customize Colors
- Edit Material Design 3 theme in Tailwind config
- Primary color: `#006c49`
- Secondary color: `#565e74`
- Update in each page's `<script>` block

### Add More Charts
1. Create canvas element in HTML
2. Initialize new Chart.js instance
3. Add PHP data generation logic
4. Configure chart options

### Extend Profile Fields
1. Add column via migration
2. Update profile.php form
3. Add edit logic to POST handler
4. Update display section

---

## Troubleshooting

### Students can't login
- Check database connection
- Verify password hash (bcrypt)
- Check session settings in php.ini
- Clear browser cookies

### QR Scanner not working
- Ensure browser allows camera access
- Check HTTPS requirement (camera needs secure context)
- Test jsQR library loading
- Verify camera permissions

### Charts not displaying
- Check Chart.js library loaded
- Verify canvas element IDs match
- Check data formatting (JSON)
- Open browser console for errors

### Database migrations fail
- Check Doctrine installation
- Verify database permissions
- Ensure tables don't exist already
- Check MySQL version compatibility

### Attendance not being marked
- Verify student_id in session
- Check attendance table permissions
- Ensure correct date format
- Check foreign key constraints

---

## Performance Optimization

### Implemented
✅ Prepared statements (no query concatenation)  
✅ Indexed columns (student_id, dates, status)  
✅ Lazy loading for large datasets  
✅ Frontend caching (CSS/JS files)  
✅ Responsive images  

### Recommendations
- Add database query caching (Redis)
- Implement pagination for large result sets
- Enable gzip compression on server
- Minimize CSS/JS files
- Use CDN for static assets

---

## Security Hardening Checklist

- [ ] Set strong database password
- [ ] Configure HTTPS/SSL certificate
- [ ] Set secure session cookie flags
- [ ] Enable CSRF token validation
- [ ] Implement rate limiting on login
- [ ] Add two-factor authentication
- [ ] Regular security audits
- [ ] Keep dependencies updated
- [ ] Implement activity logging
- [ ] Set proper file permissions (644/755)

---

## Support & Maintenance

### Regular Maintenance
- Monitor database size
- Check error logs
- Review user activity
- Update dependencies
- Backup database regularly

### Contact & Support
For issues or improvements, please contact the development team.

---

## License
This project is provided as-is for educational institution use.

---

## Version History

### v1.0 (Current)
- ✅ Student authentication
- ✅ Attendance tracking
- ✅ QR code marking
- ✅ Analytics and reports
- ✅ Profile management
- ✅ Notifications system
- ✅ Responsive design
- ✅ Material Design 3 UI

### Future Enhancements
- [ ] Mobile app (React Native/Flutter)
- [ ] Parent notifications
- [ ] Class-wise attendance
- [ ] Biometric integration
- [ ] Advanced reporting
- [ ] API for third-party integration
- [ ] AI-powered insights
- [ ] SMS/Email alerts

---

**Last Updated**: May 26, 2026  
**System**: EduAttend v1.0  
**Status**: Production Ready ✅
