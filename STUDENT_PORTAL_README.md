# Student Portal - Login, Dashboard & Analytics

## Overview
Complete student attendance portal with login authentication, interactive dashboard, attendance history, analytics charts, and user profile management.

## Files Created

### 1. **pages/student/login.php** - Student Login Screen
- Modern, responsive login interface
- Features:
  - Email or Admission Number login
  - Password visibility toggle
  - "Remember me" for 30 days (cookie-based)
  - Forgot password link
  - Error handling and validation
  - Material Design icons
  - Mobile responsive layout
  - Tailwind CSS styling

#### Login Flow:
```
POST -> Validates credentials -> Hash verification -> Session creation -> Redirect to Dashboard
```

### 2. **pages/student/dashboard.php** - Main Dashboard (Home)
- Comprehensive attendance overview
- Features:
  - Welcome message with student name
  - Attendance percentage gauge (circular progress)
  - Quick stats cards:
    - Days Present
    - Days Absent
    - Days Late
    - Minimum Target (90%)
  - Today's status highlight card
  - 4-week attendance trends chart
  - Recent notifications list
  - Responsive bento grid layout
  - Desktop sidebar navigation
  - Mobile bottom navigation bar

#### Dashboard Features:
```
- Animated card fade-in on load
- Dynamic date display
- Real-time attendance data from database
- Mobile-friendly touch interactions
- Responsive breakpoints (mobile/tablet/desktop)
- Quick access to other sections
```

### 3. **pages/student/analytics.php** - Attendance Analytics Screen
- Comprehensive attendance statistics and charts
- Features:
  - Key stats overview (Overall Present %, Avg. Punctuality)
  - **Doughnut Chart** - Attendance breakdown (Present/Late/Absent)
  - **Bar Chart** - Last 7 days attendance
  - **Line Chart** - Monthly attendance trends (last 6 months)
  - Late arrivals statistics with detailed list
  - Chart.js for interactive charts
  - Responsive grid layout
  - Mobile-friendly design

#### Analytics Data:
```
- Overall attendance percentage
- Average punctuality score
- Daily breakdown (Present/Late/Absent counts)
- Weekly attendance bars
- Monthly trend line chart
- Recent late incidents with timestamps
```

### 4. **pages/student/attendance.php** - Attendance History
- Detailed attendance records
- Features:
  - List of all attendance records
  - Status badges (Present/Late/Absent)
  - Date and time information
  - Color-coded status (green/yellow/red)
  - Scrollable history
  - Mobile responsive
  - Quick navigation

### 5. **pages/student/profile.php** - User Profile
- Student profile information
- Features:
  - Profile photo
  - Student name and ID
  - Email address
  - Admission number
  - Settings menu (Change Password, Notifications, Help)
  - Logout button
  - Clean card-based layout

### 6. **pages/student/logout.php** - Logout Handler
- Destroys session
- Clears cookies
- Redirects to login page

### 7. **Supporting Pages**
- `pages/student/forgot-password.php` - Placeholder for password recovery
- `pages/contact.php` - Contact administration
- `pages/privacy.php` - Privacy policy
- `pages/user-guide.php` - User guide
- `pages/status.php` - System status

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
    class_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    UNIQUE KEY unique_student_date (student_id, attendance_date)
);
```

### Migration
- **Version20260526000000.php** - Creates both tables

## Navigation Structure

### Dashboard (Home)
```
Dashboard (Active)
├── Attendance History
├── Analytics
├── Alerts
└── Profile (+ Logout)
```

### Mobile Bottom Navigation
```
[Home] [Attendance] [Analytics] [Alerts] [Profile]
```

### Desktop Sidebar Navigation
```
EduAttend
├── Student Profile Section
├── Dashboard (Active)
├── History
├── Analytics
├── Alerts
├── Profile
└── Logout
```

## Setup Instructions

1. **Run Migration**
   ```bash
   cd attendance
   php bin/doctrine migrations:execute StudentAttendance\\Migrations\\Version20260526000000 --up
   ```

2. **Add Test Student Data**
   ```sql
   INSERT INTO students (name, email, admission_number, password_hash) 
   VALUES (
       'Alex Rivers', 
       'alex@school.edu', 
       'STU2024001', 
       '$2y$10$...'  -- bcrypt hash of password
   );
   ```

3. **Add Sample Attendance Records**
   ```sql
   INSERT INTO attendance (student_id, attendance_date, status)
   VALUES 
       (1, '2026-05-20', 'present'),
       (1, '2026-05-19', 'present'),
       (1, '2026-05-18', 'late'),
       (1, '2026-05-17', 'present');
   ```

## Authentication Flow

### Login:
```
1. User enters Email/Admission Number + Password
2. Query database for student record
3. Verify password using password_verify()
4. Create session: $_SESSION['student_id'], $_SESSION['student_name']
5. If "Remember me" checked: Set 30-day cookie
6. Redirect to dashboard.php
```

### Dashboard Access:
```
1. Check if $_SESSION['student_id'] exists
2. If not: Redirect to login.php
3. Fetch student data from students table
4. Fetch attendance statistics
5. Fetch today's status
6. Render dashboard with real data
```

### Analytics Access:
```
1. Check authentication
2. Fetch overall statistics
3. Calculate week data (last 7 days)
4. Calculate month data (last 6 months)
5. Generate Chart.js data objects
6. Render charts with live data
```

### Logout:
```
1. Destroy session
2. Clear cookies
3. Redirect to login.php
```

## Features

### Login Page
- ✅ Email/Admission Number input field
- ✅ Password input with show/hide toggle
- ✅ Remember me checkbox (30 days)
- ✅ Forgot password link
- ✅ Error messages (invalid credentials)
- ✅ Material Design icons
- ✅ Responsive design (mobile/desktop)
- ✅ Tailwind CSS styling
- ✅ Form validation

### Dashboard
- ✅ Student name & ID display
- ✅ Attendance percentage (circular gauge)
- ✅ Present/Absent/Late days counter
- ✅ Today's status card
- ✅ 4-week trend chart
- ✅ Recent notifications
- ✅ Desktop sidebar navigation
- ✅ Mobile bottom navigation
- ✅ Responsive grid layout
- ✅ Dynamic date display
- ✅ Animation effects

### Analytics
- ✅ Key stats overview cards
- ✅ Doughnut chart (attendance breakdown)
- ✅ Bar chart (weekly data)
- ✅ Line chart (monthly trends)
- ✅ Color-coded statistics
- ✅ Late arrivals list
- ✅ Responsive layout
- ✅ Chart.js integration
- ✅ Mobile friendly

### Attendance History
- ✅ List of attendance records
- ✅ Status badges
- ✅ Date information
- ✅ Color-coded status
- ✅ Scrollable history
- ✅ Mobile responsive

### Profile
- ✅ Student information display
- ✅ Profile photo
- ✅ Settings menu
- ✅ Logout button
- ✅ Account details

## Styling

### Design System
- **Colors**: Material Design 3 color scheme
- **Typography**: Inter font family
- **Icons**: Material Symbols (24px)
- **Spacing**: 8px base unit
- **Breakpoints**: 
  - Mobile: < 768px
  - Tablet/Desktop: ≥ 768px

### Tailwind Configuration
All custom colors and spacing are configured in `tailwind.config` within the HTML.

## Dependencies

### External Libraries
- **Chart.js** - Charts and graphs
- **Tailwind CSS** - Styling
- **Material Symbols** - Icons
- **Inter Font** - Typography

### PHP Libraries
- **Utility Class** - Database queries
- **Session/Cookies** - Authentication
- **Password Verify** - Bcrypt verification

## Security Considerations

- ✅ Password hashing with bcrypt
- ✅ Session-based authentication
- ✅ SQL injection prevention (prepared statements via Utility)
- ✅ XSS prevention (htmlspecialchars on output)
- ✅ Secure cookies (httponly, samesite)
- ✅ CSRF protection (implement in forms)
- ✅ Authentication checks on all pages

## API Integration Points

### From Database:
- `students` table for login verification
- `attendance` table for statistics and daily status
- Activity logging (optional)

### From Session:
- `$_SESSION['student_id']` - Current student ID
- `$_SESSION['student_name']` - Current student name

## Responsive Breakpoints

### Mobile (< 768px)
- Top app bar with logo and notifications
- Single column layout
- Bottom navigation bar (5 items)
- Full-width cards
- Chart.js responsive containers

### Desktop (≥ 768px)
- Fixed left sidebar (280px)
- Multi-column grid layout
- Bento grid for cards
- Hidden bottom navigation
- Responsive charts

## Performance

- Minimal external API calls
- Database queries optimized with LIMIT
- CSS classes bundled via Tailwind
- No unnecessary DOM manipulation
- Lazy-loaded images
- Chart.js efficient rendering
- Responsive design optimization

## Troubleshooting

### Login Issues
- Check if `config/bootstrap.php` is included
- Verify database connection in `config/bootstrap.php`
- Ensure `students` table exists and has test data
- Check password hashing: Use `password_hash($password, PASSWORD_BCRYPT)`

### Dashboard Not Loading
- Verify session is created after successful login
- Check if `attendance` table exists
- Review browser console for errors
- Check server error logs

### Charts Not Displaying
- Verify Chart.js CDN is loaded
- Check browser console for Chart.js errors
- Ensure canvas elements exist
- Verify data format (arrays, numbers)

### Styling Issues
- Clear browser cache
- Verify Tailwind CSS CDN is loaded
- Check for CSS class conflicts
- Ensure custom theme config is applied

## Future Enhancements

- [ ] Class schedule integration
- [ ] Attendance request/appeal system
- [ ] Email notifications
- [ ] Export attendance reports (PDF)
- [ ] Parent/Guardian portal
- [ ] Teacher feedback integration
- [ ] Mobile app
- [ ] QR code check-in
- [ ] Biometric attendance
- [ ] Advanced analytics
- [ ] Dark mode support
- [ ] Multi-language support

## File Structure

```
attendance/
├── pages/
│   ├── student/
│   │   ├── login.php              # Login screen
│   │   ├── dashboard.php          # Home/Dashboard
│   │   ├── analytics.php          # Analytics & Charts
│   │   ├── attendance.php         # Attendance history
│   │   ├── profile.php            # User profile
│   │   ├── logout.php             # Logout handler
│   │   ├── forgot-password.php    # Password recovery
│   │   └── dashboard.php          # Main dashboard
│   ├── contact.php                # Contact page
│   ├── privacy.php                # Privacy policy
│   ├── user-guide.php             # User guide
│   └── status.php                 # System status
├── src/
│   └── Migrations/
│       └── Version20260526000000.php  # Database schema
├── config/
│   └── bootstrap.php              # Database connection
└── utils/
    ├── Database.php               # Database utilities
    ├── Utility.php                # Safe queries
    └── Router.php                 # Routing logic
```

## Contact & Support
For issues, contact: admin@school.edu
