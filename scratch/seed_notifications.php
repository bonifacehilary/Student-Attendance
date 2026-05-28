<?php
// scratch/seed_notifications.php
// Generate sample notifications for all students

require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../utils/Utility.php';

echo "=== EduAttend Notification Seeding Script ===\n\n";

try {
    // Get all students
    $students = Utility::safeQuery(
        'SELECT id, name FROM students ORDER BY id',
        [],
        'SELECT'
    );
    
    if (empty($students)) {
        echo "❌ No students found. Run seed_students.php first.\n";
        exit(1);
    }
    
    $notificationCount = 0;
    
    foreach ($students as $student) {
        $studentId = $student['id'];
        $studentName = $student['name'];
        
        // Attendance alert - random chance
        if (rand(1, 3) === 1) {
            Utility::safeQuery(
                'INSERT INTO notifications (student_id, type, title, message, icon_type, color_type, is_read, can_appeal, created_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)',
                [
                    $studentId,
                    'attendance_alert',
                    'Attendance Alert',
                    'You were marked absent for Modern Literature today (May 26). If this is a mistake, you can appeal.',
                    'warning',
                    'red',
                    0,
                    1,
                    date('Y-m-d H:i:s', strtotime('-2 hours'))
                ],
                'INSERT'
            );
            $notificationCount++;
            echo "✅ Added attendance alert for $studentName\n";
        }
        
        // Academic warning - for attendance below 90%
        if (rand(1, 4) === 1) {
            Utility::safeQuery(
                'INSERT INTO notifications (student_id, type, title, message, icon_type, color_type, is_read, created_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
                [
                    $studentId,
                    'warning',
                    'Academic Warning',
                    'Your attendance in Calculus I has dropped below 90%. Consistent participation is required to maintain your scholarship status.',
                    'priority_high',
                    'orange',
                    0,
                    date('Y-m-d H:i:s', strtotime('-4 hours'))
                ],
                'INSERT'
            );
            $notificationCount++;
            echo "✅ Added academic warning for $studentName\n";
        }
        
        // System announcement (all students get same)
        $existingAnnouncement = Utility::safeQuery(
            'SELECT id FROM notifications WHERE student_id = ? AND type = "announcement" LIMIT 1',
            [$studentId],
            'SELECT',
            true
        );
        
        if (!$existingAnnouncement) {
            Utility::safeQuery(
                'INSERT INTO notifications (student_id, type, title, message, icon_type, color_type, is_read, created_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
                [
                    $studentId,
                    'announcement',
                    'System Announcement',
                    'Campus will be closed this Friday for Teacher Appreciation Day. All morning lectures are cancelled.',
                    'info',
                    'blue',
                    1,
                    date('Y-m-d H:i:s', strtotime('-1 day'))
                ],
                'INSERT'
            );
            $notificationCount++;
            echo "✅ Added system announcement for $studentName\n";
        }
        
        // Attendance confirmation - random
        if (rand(1, 2) === 1) {
            Utility::safeQuery(
                'INSERT INTO notifications (student_id, type, title, message, icon_type, color_type, is_read, created_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
                [
                    $studentId,
                    'confirmation',
                    'Attendance Recorded',
                    'Your attendance for Physics has been recorded as Present. Great job staying consistent!',
                    'check_circle',
                    'green',
                    1,
                    date('Y-m-d H:i:s', strtotime('-1 day'))
                ],
                'INSERT'
            );
            $notificationCount++;
            echo "✅ Added attendance confirmation for $studentName\n";
        }
    }
    
    echo "\n✅ Success! Created $notificationCount notifications.\n";
    echo "\nTest Login:\n";
    echo "  Email: alex@school.edu\n";
    echo "  Password: password123\n";
    echo "  Then go to: /pages/student/notifications.php\n";
    
} catch (\Throwable $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
?>
