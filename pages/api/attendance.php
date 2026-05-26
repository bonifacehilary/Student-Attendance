<?php
// pages/api/attendance.php
// API Endpoint for Attendance Operations

require_once __DIR__ . '/../../config/bootstrap.php';
require_once __DIR__ . '/../../utils/Utility.php';

header('Content-Type: application/json');

session_start();

// Check if admin is logged in
$isAdmin = isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;

if (!$isAdmin) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

// Handle different request methods
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

try {
    switch ($method) {
        case 'GET':
            if ($action === 'today') {
                // Get today's attendance
                $today = date('Y-m-d');
                $records = Utility::safeQuery(
                    'SELECT a.student_id, a.status, s.name, s.admission_number
                     FROM attendance a
                     JOIN students s ON a.student_id = s.id
                     WHERE a.attendance_date = ?
                     ORDER BY s.name ASC',
                    [$today],
                    'SELECT'
                );
                echo json_encode(['data' => $records]);
            } elseif ($action === 'stats') {
                // Get attendance statistics
                $today = date('Y-m-d');
                $stats = Utility::safeQuery(
                    'SELECT 
                        (SELECT COUNT(DISTINCT student_id) FROM attendance WHERE attendance_date = ?) as marked,
                        (SELECT COUNT(*) FROM students) as total,
                        (SELECT COUNT(*) FROM attendance WHERE attendance_date = ? AND status = "present") as present,
                        (SELECT COUNT(*) FROM attendance WHERE attendance_date = ? AND status = "late") as late,
                        (SELECT COUNT(*) FROM attendance WHERE attendance_date = ? AND status = "absent") as absent',
                    [$today, $today, $today, $today],
                    'SELECT',
                    true
                );
                echo json_encode(['data' => $stats]);
            }
            break;

        case 'POST':
            if ($action === 'mark') {
                $input = json_decode(file_get_contents('php://input'), true);
                
                if (!isset($input['student_id']) || !isset($input['status'])) {
                    http_response_code(400);
                    echo json_encode(['error' => 'Missing required fields']);
                    exit;
                }
                
                $studentId = intval($input['student_id']);
                $status = $input['status'];
                $today = date('Y-m-d');
                
                if (!in_array($status, ['present', 'late', 'absent'])) {
                    http_response_code(400);
                    echo json_encode(['error' => 'Invalid status']);
                    exit;
                }
                
                // Update or insert attendance
                Utility::safeQuery(
                    'INSERT INTO attendance (student_id, attendance_date, status)
                     VALUES (?, ?, ?)
                     ON DUPLICATE KEY UPDATE status = ?',
                    [$studentId, $today, $status, $status],
                    'INSERT'
                );
                
                echo json_encode(['success' => true, 'message' => 'Attendance marked']);
            }
            break;

        case 'PUT':
            if ($action === 'batch') {
                $input = json_decode(file_get_contents('php://input'), true);
                
                if (!isset($input['records']) || !is_array($input['records'])) {
                    http_response_code(400);
                    echo json_encode(['error' => 'Invalid records format']);
                    exit;
                }
                
                $today = date('Y-m-d');
                $successCount = 0;
                
                foreach ($input['records'] as $record) {
                    if (isset($record['student_id']) && isset($record['status'])) {
                        try {
                            Utility::safeQuery(
                                'INSERT INTO attendance (student_id, attendance_date, status)
                                 VALUES (?, ?, ?)
                                 ON DUPLICATE KEY UPDATE status = ?',
                                [$record['student_id'], $today, $record['status'], $record['status']],
                                'INSERT'
                            );
                            $successCount++;
                        } catch (\Throwable $e) {
                            // Continue with next record
                        }
                    }
                }
                
                echo json_encode(['success' => true, 'updated' => $successCount]);
            }
            break;

        default:
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
    }
} catch (\Throwable $e) {
    error_log('API error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
}
?>
