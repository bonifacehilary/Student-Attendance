<?php

namespace StudentAttendance\Utils;

/**
 * Student session guard and attendance helpers (SQLite + MySQL).
 */
class StudentAuth
{
    public static function check(): bool
    {
        return !empty($_SESSION['student_id']);
    }

    public static function require(): void
    {
        if (!self::check()) {
            header('Location: /pages/student/login.php');
            exit;
        }
        if (class_exists(UserManagement::class)) {
            UserManagement::ensureSchema();
        }
    }

    public static function studentId(): int
    {
        return (int) ($_SESSION['student_id'] ?? 0);
    }

    public static function setFlash(string $message, string $type = 'success'): void
    {
        $_SESSION['student_flash'] = $message;
        $_SESSION['student_flash_type'] = $type;
    }

    /** @return array{message: ?string, type: string} */
    public static function pullFlash(): array
    {
        $message = $_SESSION['student_flash'] ?? null;
        $type = $_SESSION['student_flash_type'] ?? 'success';
        unset($_SESSION['student_flash'], $_SESSION['student_flash_type']);
        return ['message' => $message, 'type' => $type];
    }

    public static function redirect(string $path, ?string $flash = null, string $flashType = 'success'): void
    {
        if ($flash !== null) {
            self::setFlash($flash, $flashType);
        }
        header('Location: ' . $path);
        exit;
    }

    public static function isSqlite(): bool
    {
        return ($_ENV['DB_DRIVER'] ?? '') === 'pdo_sqlite';
    }

    public static function nowExpr(): string
    {
        return self::isSqlite() ? "datetime('now')" : 'NOW()';
    }

    public static function todayExpr(): string
    {
        return self::isSqlite() ? "date('now')" : 'CURDATE()';
    }

    /** @return array<string, mixed>|null */
    public static function getStudent(int $studentId): ?array
    {
        try {
            if (class_exists(UserManagement::class)) {
                UserManagement::ensureSchema();
            }
            $row = Utility::safeQuery(
                'SELECT s.id, s.name, s.email, s.admission_number,
                        COALESCE(c.name, s.student_class) AS student_class,
                        s.phone, s.profile_photo, COALESCE(s.is_active, 1) AS is_active,
                        s.department_id, s.class_id, d.name AS department_name
                 FROM students s
                 LEFT JOIN classes c ON c.id = s.class_id
                 LEFT JOIN departments d ON d.id = s.department_id
                 WHERE s.id = ? LIMIT 1',
                [$studentId],
                'SELECT',
                true
            );
            if (!$row || empty($row['is_active'])) {
                return null;
            }
            return $row;
        } catch (\Throwable $e) {
            error_log('StudentAuth::getStudent: ' . $e->getMessage());
            return null;
        }
    }

    /** @return array{total_days:int,present_days:int,absent_days:int,late_days:int} */
    public static function getAttendanceStats(int $studentId): array
    {
        $defaults = ['total_days' => 0, 'present_days' => 0, 'absent_days' => 0, 'late_days' => 0];
        try {
            $row = Utility::safeQuery(
                'SELECT COUNT(*) AS total_days,
                        SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) AS present_days,
                        SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) AS absent_days,
                        SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) AS late_days
                 FROM attendance WHERE student_id = ?',
                ['present', 'absent', 'late', $studentId],
                'SELECT',
                true
            );
            return [
                'total_days' => (int) ($row['total_days'] ?? 0),
                'present_days' => (int) ($row['present_days'] ?? 0),
                'absent_days' => (int) ($row['absent_days'] ?? 0),
                'late_days' => (int) ($row['late_days'] ?? 0),
            ];
        } catch (\Throwable $e) {
            error_log('StudentAuth::getAttendanceStats: ' . $e->getMessage());
            return $defaults;
        }
    }

    /** @return array<string, mixed>|null */
    public static function getTodayStatus(int $studentId): ?array
    {
        $today = date('Y-m-d');
        try {
            return Utility::safeQuery(
                'SELECT status FROM attendance WHERE student_id = ? AND attendance_date = ? LIMIT 1',
                [$studentId, $today],
                'SELECT',
                true
            ) ?: null;
        } catch (\Throwable $e) {
            error_log('StudentAuth::getTodayStatus: ' . $e->getMessage());
            return null;
        }
    }

    /** @return array<int, array<string, mixed>> */
    public static function getRecentNotifications(int $studentId, int $limit = 5): array
    {
        try {
            return Utility::safeQuery(
                'SELECT id, title, message, type, is_read, created_at
                 FROM notifications WHERE student_id = ?
                 ORDER BY created_at DESC LIMIT ' . (int) $limit,
                [$studentId],
                'SELECT'
            );
        } catch (\Throwable $e) {
            return [];
        }
    }

    /** @return array<int, array<string, mixed>> */
    public static function getAttendanceHistory(int $studentId, int $limit = 30, ?string $month = null): array
    {
        try {
            if ($month && preg_match('/^\d{4}-\d{2}$/', $month)) {
                return Utility::safeQuery(
                    'SELECT id, attendance_date, status, created_at
                     FROM attendance
                     WHERE student_id = ? AND attendance_date LIKE ?
                     ORDER BY attendance_date DESC',
                    [$studentId, $month . '%'],
                    'SELECT'
                );
            }
            return Utility::safeQuery(
                'SELECT id, attendance_date, status, created_at
                 FROM attendance WHERE student_id = ?
                 ORDER BY attendance_date DESC LIMIT ' . (int) $limit,
                [$studentId],
                'SELECT'
            );
        } catch (\Throwable $e) {
            error_log('StudentAuth::getAttendanceHistory: ' . $e->getMessage());
            return [];
        }
    }

    /** @return array<string, mixed>|null */
    public static function getNextClass(int $studentId): ?array
    {
        $student = self::getStudent($studentId);
        $studentClass = trim((string) ($student['student_class'] ?? ''));
        if ($studentClass === '') {
            return null;
        }

        try {
            Timetable::ensureSchema();
            $entries = Timetable::entries($studentClass);
        } catch (\Throwable $e) {
            error_log('StudentAuth::getNextClass: ' . $e->getMessage());
            return null;
        }

        if (empty($entries)) {
            return null;
        }

        $now = new \DateTimeImmutable();
        $todayNumber = (int) $now->format('N');
        $best = null;
        $bestTime = null;
        $dayMap = array_flip(Timetable::DAYS);

        foreach ($entries as $entry) {
            $dayIndex = ($dayMap[$entry['day_of_week'] ?? ''] ?? 0) + 1;
            $daysAhead = ($dayIndex - $todayNumber + 7) % 7;
            $candidate = $now->modify("+{$daysAhead} days")->setTime(
                (int) substr((string) $entry['start_time'], 0, 2),
                (int) substr((string) $entry['start_time'], 3, 2)
            );
            if ($candidate < $now) {
                $candidate = $candidate->modify('+7 days');
            }
            if ($bestTime === null || $candidate < $bestTime) {
                $bestTime = $candidate;
                $best = $entry;
            }
        }

        if ($best !== null && $bestTime !== null) {
            $best['starts_at_label'] = $bestTime->format('D, M j') . ' at ' . $bestTime->format('g:i A');
        }

        return $best;
    }

    /** @return array<int, array<string, mixed>> */
    public static function getReceivableQrSessions(int $studentId): array
    {
        $now = self::nowExpr();
        $student = self::getStudent($studentId);
        $studentClass = trim((string) ($student['student_class'] ?? ''));

        try {
            $sessions = Utility::safeQuery(
                "SELECT q.id, q.code, q.session_name, q.created_date, q.expires_at, q.created_by,
                        t.name AS teacher_name, t.assigned_class
                 FROM attendance_qr_sessions q
                 LEFT JOIN teachers t ON t.id = q.created_by
                 WHERE q.is_active = 1 AND q.expires_at >= {$now}
                 ORDER BY q.created_date DESC",
                [],
                'SELECT'
            );
        } catch (\Throwable $e) {
            error_log('StudentAuth::getReceivableQrSessions: ' . $e->getMessage());
            return [];
        }

        $hasQrColumn = self::attendanceHasQrColumn();
        $receivableSessions = [];
        foreach ($sessions as $session) {
            $assignedClass = trim((string) ($session['assigned_class'] ?? ''));
            $hasTeacherOwner = !empty($session['teacher_name']) || $assignedClass !== '';
            $isTeacherForStudent = $hasTeacherOwner
                && $assignedClass !== ''
                && $studentClass !== ''
                && strcasecmp($assignedClass, $studentClass) === 0;

            if ($hasTeacherOwner && !$isTeacherForStudent) {
                continue;
            }

            $session['source'] = $isTeacherForStudent ? 'Teacher' : 'Admin';
            $session['source_name'] = $isTeacherForStudent
                ? ($session['teacher_name'] ?: 'Teacher')
                : 'Administration';
            $session['already_marked'] = false;

            if ($hasQrColumn) {
                try {
                    $marked = Utility::safeQuery(
                        'SELECT id FROM attendance WHERE student_id = ? AND qr_session_id = ? AND status = ? LIMIT 1',
                        [$studentId, (int) $session['id'], 'present'],
                        'SELECT',
                        true
                    );
                    $session['already_marked'] = (bool) $marked;
                } catch (\Throwable $e) {
                    $session['already_marked'] = false;
                }
            }

            $receivableSessions[] = $session;
        }

        return $receivableSessions;
    }

    /** @return array{success:bool,message:string,status:string} */
    public static function markQrAttendance(int $studentId, string $qrCode): array
    {
        $qrCode = strtoupper(trim($qrCode));
        if ($qrCode === '') {
            return ['success' => false, 'message' => 'Please enter a QR code.', 'status' => 'error'];
        }

        $now = self::nowExpr();
        $today = date('Y-m-d');

        try {
            $qrSession = Utility::safeQuery(
                "SELECT id, session_name, expires_at, is_active
                 FROM attendance_qr_sessions
                 WHERE code = ? AND is_active = 1 AND expires_at >= {$now} LIMIT 1",
                [$qrCode],
                'SELECT',
                true
            );
        } catch (\Throwable $e) {
            error_log('StudentAuth::markQrAttendance lookup: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Could not verify QR code. Run php scratch/setup_local.php', 'status' => 'error'];
        }

        if (!$qrSession) {
            return ['success' => false, 'message' => 'Invalid or expired QR code.', 'status' => 'error'];
        }

        $sessionId = (int) $qrSession['id'];

        try {
            $hasQrColumn = self::attendanceHasQrColumn();
            if ($hasQrColumn) {
                $existing = Utility::safeQuery(
                    'SELECT id FROM attendance WHERE student_id = ? AND qr_session_id = ? LIMIT 1',
                    [$studentId, $sessionId],
                    'SELECT',
                    true
                );
                if ($existing) {
                    return ['success' => false, 'message' => 'You already marked attendance for this session.', 'status' => 'warning'];
                }
            }

            $todayRow = Utility::safeQuery(
                'SELECT id FROM attendance WHERE student_id = ? AND attendance_date = ? LIMIT 1',
                [$studentId, $today],
                'SELECT',
                true
            );

            if ($todayRow) {
                if ($hasQrColumn) {
                    Utility::safeQuery(
                        'UPDATE attendance SET status = ?, qr_session_id = ? WHERE id = ?',
                        ['present', $sessionId, $todayRow['id']],
                        'UPDATE'
                    );
                } else {
                    Utility::safeQuery(
                        'UPDATE attendance SET status = ? WHERE id = ?',
                        ['present', $todayRow['id']],
                        'UPDATE'
                    );
                }
            } elseif ($hasQrColumn) {
                Utility::safeQuery(
                    'INSERT INTO attendance (student_id, attendance_date, status, qr_session_id) VALUES (?, ?, ?, ?)',
                    [$studentId, $today, 'present', $sessionId],
                    'INSERT'
                );
            } else {
                Utility::safeQuery(
                    'INSERT INTO attendance (student_id, attendance_date, status) VALUES (?, ?, ?)',
                    [$studentId, $today, 'present'],
                    'INSERT'
                );
            }

            $sessionName = $qrSession['session_name'] ?? $qrCode;
            return [
                'success' => true,
                'message' => 'Attendance marked present for session: ' . $sessionName,
                'status' => 'success',
            ];
        } catch (\Throwable $e) {
            error_log('StudentAuth::markQrAttendance save: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Could not save attendance: ' . $e->getMessage(), 'status' => 'error'];
        }
    }

    public static function attendanceHasQrColumn(): bool
    {
        static $cached = null;
        if ($cached !== null) {
            return $cached;
        }
        try {
            if (self::isSqlite()) {
                $row = Utility::safeQuery("PRAGMA table_info(attendance)", [], 'SELECT');
                foreach ($row as $col) {
                    if (($col['name'] ?? '') === 'qr_session_id') {
                        $cached = true;
                        return true;
                    }
                }
            } else {
                $row = Utility::safeQuery(
                    "SHOW COLUMNS FROM attendance LIKE 'qr_session_id'",
                    [],
                    'SELECT',
                    true
                );
                if ($row) {
                    $cached = true;
                    return true;
                }
            }
        } catch (\Throwable $e) {
            // ignore
        }
        $cached = false;
        return false;
    }

    /** @return array<int, array{date:string,day:string,present:int,total:int}> */
    public static function getLast7DaysChart(int $studentId): array
    {
        $days = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-{$i} days"));
            $days[$date] = ['date' => $date, 'day' => date('D', strtotime($date)), 'present' => 0, 'total' => 0];
        }

        try {
            if (self::isSqlite()) {
                $rows = Utility::safeQuery(
                    "SELECT attendance_date, status FROM attendance
                     WHERE student_id = ? AND attendance_date >= date('now', '-6 days')",
                    [$studentId],
                    'SELECT'
                );
            } else {
                $rows = Utility::safeQuery(
                    'SELECT attendance_date, status FROM attendance
                     WHERE student_id = ? AND attendance_date >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)',
                    [$studentId],
                    'SELECT'
                );
            }
            foreach ($rows as $row) {
                $d = $row['attendance_date'];
                if (!isset($days[$d])) {
                    continue;
                }
                $days[$d]['total']++;
                if ($row['status'] === 'present' || $row['status'] === 'late') {
                    $days[$d]['present']++;
                }
            }
        } catch (\Throwable $e) {
            error_log('StudentAuth::getLast7DaysChart: ' . $e->getMessage());
        }

        return array_values($days);
    }

    /** @return array<int, array{label:string,pct:int}> */
    public static function getMonthlyTrend(int $studentId, int $months = 6): array
    {
        $result = [];
        for ($i = $months - 1; $i >= 0; $i--) {
            $label = date('M', strtotime("-{$i} months"));
            $ym = date('Y-m', strtotime("-{$i} months"));
            $result[$ym] = ['label' => $label, 'pct' => 0, 'total' => 0, 'present' => 0];
        }

        try {
            $rows = Utility::safeQuery(
                'SELECT attendance_date, status FROM attendance WHERE student_id = ?',
                [$studentId],
                'SELECT'
            );
            foreach ($rows as $row) {
                $ym = substr((string) $row['attendance_date'], 0, 7);
                if (!isset($result[$ym])) {
                    continue;
                }
                $result[$ym]['total']++;
                if ($row['status'] === 'present' || $row['status'] === 'late') {
                    $result[$ym]['present']++;
                }
            }
            foreach ($result as $ym => $data) {
                $result[$ym]['pct'] = $data['total'] > 0
                    ? (int) round(($data['present'] / $data['total']) * 100)
                    : 0;
            }
        } catch (\Throwable $e) {
            error_log('StudentAuth::getMonthlyTrend: ' . $e->getMessage());
        }

        return array_values($result);
    }

    public static function profilePhotoUrl(?string $stored): string
    {
        if ($stored === null || $stored === '') {
            return '';
        }
        if (str_starts_with($stored, 'http') || str_starts_with($stored, '/assets/')) {
            return $stored;
        }
        return Assets::profilePhotoUrl($stored);
    }

    public static function timeAgo(string $timestamp): string
    {
        $time = strtotime($timestamp);
        if ($time === false) {
            return '';
        }
        $diff = time() - $time;
        if ($diff < 60) {
            return 'just now';
        }
        if ($diff < 3600) {
            return (int) floor($diff / 60) . 'm ago';
        }
        if ($diff < 86400) {
            return (int) floor($diff / 3600) . 'h ago';
        }
        if ($diff < 604800) {
            $days = (int) floor($diff / 86400);
            return $days === 1 ? 'Yesterday' : $days . ' days ago';
        }
        return date('M j', $time);
    }

    public static function notificationIcon(string $type): string
    {
        return match ($type) {
            'attendance_alert', 'warning' => 'warning',
            'confirmation' => 'check_circle',
            'announcement' => 'campaign',
            default => 'info',
        };
    }
}
