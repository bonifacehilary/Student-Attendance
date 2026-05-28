<?php
// scratch/seed_students.php
// Seed test student data and sample attendance records

require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../utils/Utility.php';

// Test student data
$testStudents = [
    [
        'name' => 'Alex Rivers',
        'email' => 'alex@school.edu',
        'admission_number' => 'STU2024001',
        'password' => 'password123'
    ],
    [
        'name' => 'Jordan Smith',
        'email' => 'jordan@school.edu',
        'admission_number' => 'STU2024002',
        'password' => 'password123'
    ],
    [
        'name' => 'Sam Johnson',
        'email' => 'sam@school.edu',
        'admission_number' => 'STU2024003',
        'password' => 'password123'
    ],
    [
        'name' => 'Taylor Chen',
        'email' => 'taylor@school.edu',
        'admission_number' => 'STU2024004',
        'password' => 'password123'
    ],
    [
        'name' => 'Morgan Lee',
        'email' => 'morgan@school.edu',
        'admission_number' => 'STU2024005',
        'password' => 'password123'
    ]
];

try {
    echo "=== SEEDING TEST STUDENTS ===\n\n";
    
    // Insert students
    foreach ($testStudents as $student) {
        $hashedPassword = password_hash($student['password'], PASSWORD_BCRYPT);
        
        try {
            Utility::safeQuery(
                'INSERT INTO students (name, email, admission_number, password_hash) 
                 VALUES (?, ?, ?, ?)',
                [$student['name'], $student['email'], $student['admission_number'], $hashedPassword],
                'INSERT'
            );
            echo "✓ Created student: {$student['name']} ({$student['email']})\n";
        } catch (\Throwable $e) {
            echo "✗ Failed to create {$student['name']}: " . $e->getMessage() . "\n";
        }
    }
    
    echo "\n=== SEEDING ATTENDANCE RECORDS ===\n\n";
    
    // Get all students
    $students = Utility::safeQuery(
        'SELECT id, name FROM students',
        [],
        'SELECT'
    );
    
    // Generate attendance records for past 30 days
    foreach ($students as $student) {
        $presentCount = 0;
        $lateCount = 0;
        $absentCount = 0;
        
        for ($i = 30; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-$i days"));
            $dayOfWeek = date('N', strtotime($date)); // 1=Monday, 7=Sunday
            
            // Skip weekends
            if ($dayOfWeek >= 6) {
                continue;
            }
            
            // Randomly assign status
            $rand = rand(1, 100);
            if ($rand > 90) {
                $status = 'absent';
                $absentCount++;
            } elseif ($rand > 85) {
                $status = 'late';
                $lateCount++;
            } else {
                $status = 'present';
                $presentCount++;
            }
            
            try {
                Utility::safeQuery(
                    'INSERT INTO attendance (student_id, attendance_date, status)
                     VALUES (?, ?, ?)
                     ON DUPLICATE KEY UPDATE status = ?',
                    [$student['id'], $date, $status, $status],
                    'INSERT'
                );
            } catch (\Throwable $e) {
                // Skip duplicate dates
                if (strpos($e->getMessage(), 'UNIQUE') === false) {
                    echo "Error for {$student['name']} on $date: " . $e->getMessage() . "\n";
                }
            }
        }
        
        $total = $presentCount + $lateCount + $absentCount;
        $attendanceRate = $total > 0 ? round(($presentCount / $total) * 100, 1) : 0;
        
        echo "✓ {$student['name']}: {$presentCount} present, {$lateCount} late, {$absentCount} absent (Attendance: {$attendanceRate}%)\n";
    }
    
    echo "\n=== SEEDING COMPLETE ===\n\n";
    echo "Test Login Credentials:\n";
    foreach ($testStudents as $student) {
        echo "- Email: {$student['email']} | Password: {$student['password']}\n";
    }
    echo "- Or use Admission Number instead of email\n";
    
} catch (\Throwable $e) {
    echo "Error during seeding: " . $e->getMessage() . "\n";
    exit(1);
}
?>
