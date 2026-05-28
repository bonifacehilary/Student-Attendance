<?php

namespace StudentAttendance\Utils;

/**
 * Teacher session guard — class-scoped attendance and QR management.
 */
class TeacherAuth
{
    public static function check(): bool
    {
        return !empty($_SESSION['teacher_logged_in']);
    }

    public static function require(): void
    {
        if (!self::check()) {
            header('Location: /pages/teacher/login.php');
            exit;
        }
    }

    public static function teacherId(): int
    {
        return (int) ($_SESSION['teacher_id'] ?? 0);
    }

    public static function assignedClass(): string
    {
        return trim((string) ($_SESSION['teacher_assigned_class'] ?? ''));
    }

    public static function requireClass(): string
    {
        self::require();

        $class = self::assignedClass();
        if ($class !== '') {
            return $class;
        }

        $current = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?: '';
        if ($current !== '/pages/teacher/dashboard.php') {
            self::redirect(
                '/pages/teacher/dashboard.php',
                'No class is assigned to your account. Contact administration.'
            );
        }

        return '';
    }

    public static function setFlash(string $message): void
    {
        $_SESSION['teacher_flash'] = $message;
    }

    public static function pullFlash(): ?string
    {
        $message = $_SESSION['teacher_flash'] ?? null;
        unset($_SESSION['teacher_flash']);
        return $message;
    }

    public static function redirect(string $path, ?string $flash = null): void
    {
        if ($flash !== null) {
            self::setFlash($flash);
        }
        header('Location: ' . $path);
        exit;
    }

    /** @param array<int, array<string, mixed>> $rows */
    public static function filterRows(array $rows, string $query, array $fields): array
    {
        return AdminAuth::filterRows($rows, $query, $fields);
    }

    public static function upsertAttendanceForClass(int $studentId, string $date, string $status, string $class): void
    {
        $student = Utility::safeQuery(
            'SELECT id FROM students WHERE id = ? AND student_class = ? LIMIT 1',
            [$studentId, $class],
            'SELECT',
            true
        );
        if (!$student) {
            throw new \InvalidArgumentException('Student is not in your assigned class.');
        }
        AdminAuth::upsertAttendance($studentId, $date, $status);
    }

    public static function ownsQrSession(int $sessionId): bool
    {
        $row = Utility::safeQuery(
            'SELECT id FROM attendance_qr_sessions WHERE id = ? AND created_by = ? LIMIT 1',
            [$sessionId, self::teacherId()],
            'SELECT',
            true
        );
        return (bool) $row;
    }

    /** @return array{marked:int,total:int,present:int,late:int,absent:int} */
    public static function getStatsForDate(string $date, string $class): array
    {
        $defaults = ['marked' => 0, 'total' => 0, 'present' => 0, 'late' => 0, 'absent' => 0];

        try {
            $row = Utility::safeQuery(
                'SELECT 
                    (SELECT COUNT(DISTINCT a.student_id) FROM attendance a
                     INNER JOIN students s ON s.id = a.student_id
                     WHERE a.attendance_date = ? AND s.student_class = ?) AS marked,
                    (SELECT COUNT(*) FROM students WHERE student_class = ?) AS total,
                    (SELECT COUNT(*) FROM attendance a
                     INNER JOIN students s ON s.id = a.student_id
                     WHERE a.attendance_date = ? AND a.status = ? AND s.student_class = ?) AS present,
                    (SELECT COUNT(*) FROM attendance a
                     INNER JOIN students s ON s.id = a.student_id
                     WHERE a.attendance_date = ? AND a.status = ? AND s.student_class = ?) AS late,
                    (SELECT COUNT(*) FROM attendance a
                     INNER JOIN students s ON s.id = a.student_id
                     WHERE a.attendance_date = ? AND a.status = ? AND s.student_class = ?) AS absent',
                [
                    $date, $class,
                    $class,
                    $date, 'present', $class,
                    $date, 'late', $class,
                    $date, 'absent', $class,
                ],
                'SELECT',
                true
            );
            return $row ?: $defaults;
        } catch (\Throwable $e) {
            error_log('TeacherAuth::getStatsForDate: ' . $e->getMessage());
            return $defaults;
        }
    }

    /**
     * Create teachers table (if missing) and upsert the default demo teacher account.
     */
    public static function ensureDefaultTeacher(): bool
    {
        $conn = $GLOBALS['db'] ?? null;
        if ($conn === null) {
            return false;
        }

        $isSqlite = ($_ENV['DB_DRIVER'] ?? '') === 'pdo_sqlite';

        try {
            if ($isSqlite) {
                $conn->executeStatement(
                    'CREATE TABLE IF NOT EXISTS teachers (
                        id INTEGER PRIMARY KEY AUTOINCREMENT,
                        name VARCHAR(255) NOT NULL,
                        email VARCHAR(255) NOT NULL UNIQUE,
                        password_hash VARCHAR(255) NOT NULL,
                        assigned_class VARCHAR(100) NOT NULL,
                        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
                    )'
                );
            } else {
                $conn->executeStatement(
                    'CREATE TABLE IF NOT EXISTS teachers (
                        id INT AUTO_INCREMENT PRIMARY KEY,
                        name VARCHAR(255) NOT NULL,
                        email VARCHAR(255) NOT NULL UNIQUE,
                        password_hash VARCHAR(255) NOT NULL,
                        assigned_class VARCHAR(100) NOT NULL,
                        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                    )'
                );
            }
        } catch (\Throwable $e) {
            error_log('TeacherAuth::ensureDefaultTeacher table: ' . $e->getMessage());
            return false;
        }

        $email = 'teacher@school.edu';
        $hash = password_hash('teacher123', PASSWORD_BCRYPT);

        try {
            $existing = Utility::safeQuery(
                'SELECT id FROM teachers WHERE LOWER(email) = LOWER(?) LIMIT 1',
                [$email],
                'SELECT',
                true
            );

            if ($existing) {
                Utility::safeQuery(
                    'UPDATE teachers SET name = ?, password_hash = ?, assigned_class = ? WHERE id = ?',
                    ['Ms. Parker', $hash, 'Grade 10A', $existing['id']],
                    'UPDATE'
                );
            } else {
                Utility::safeQuery(
                    'INSERT INTO teachers (name, email, password_hash, assigned_class) VALUES (?, ?, ?, ?)',
                    ['Ms. Parker', $email, $hash, 'Grade 10A'],
                    'INSERT'
                );
            }

            return true;
        } catch (\Throwable $e) {
            error_log('TeacherAuth::ensureDefaultTeacher seed: ' . $e->getMessage());
            return false;
        }
    }

    public static function attemptLogin(string $email, string $password, ?string &$error = null): bool
    {
        $email = strtolower(trim($email));
        $password = trim($password);

        if ($email === '' || $password === '') {
            $error = 'Please enter both email and password.';
            return false;
        }

        $teacher = null;

        try {
            $teacher = Utility::safeQuery(
                'SELECT id, name, email, password_hash, assigned_class FROM teachers WHERE LOWER(email) = ? LIMIT 1',
                [$email],
                'SELECT',
                true
            );
        } catch (\Throwable $e) {
            error_log('TeacherAuth::attemptLogin: ' . $e->getMessage());
            $msg = $e->getMessage();
            if (stripos($msg, 'no such table') !== false || stripos($msg, "doesn't exist") !== false) {
                if (self::ensureDefaultTeacher()) {
                    return self::attemptLogin($email, $password, $error);
                }
                $error = 'Teacher database not ready. Run: php scratch/setup_local.php';
            } else {
                $error = 'Could not connect to the database. Check .env and run setup_local.php.';
            }
            return false;
        }

        if (
            (!$teacher || !password_verify($password, (string) ($teacher['password_hash'] ?? '')))
            && $email === 'teacher@school.edu'
            && self::isLocalRequest()
        ) {
            self::ensureDefaultTeacher();
            try {
                $teacher = Utility::safeQuery(
                    'SELECT id, name, email, password_hash, assigned_class FROM teachers WHERE LOWER(email) = ? LIMIT 1',
                    [$email],
                    'SELECT',
                    true
                );
            } catch (\Throwable $e) {
                error_log('TeacherAuth::attemptLogin retry: ' . $e->getMessage());
            }
        }

        if (!$teacher) {
            $error = 'No teacher account found for this email.';
            return false;
        }

        if (!password_verify($password, (string) $teacher['password_hash'])) {
            $error = 'Incorrect password.';
            return false;
        }

        $_SESSION['teacher_id'] = (int) $teacher['id'];
        $_SESSION['teacher_name'] = $teacher['name'];
        $_SESSION['teacher_email'] = $teacher['email'];
        $_SESSION['teacher_assigned_class'] = $teacher['assigned_class'];
        $_SESSION['teacher_logged_in'] = true;

        return true;
    }

    private static function isLocalRequest(): bool
    {
        $host = strtolower((string) ($_SERVER['HTTP_HOST'] ?? ''));
        $addr = (string) ($_SERVER['REMOTE_ADDR'] ?? '');

        return str_contains($host, 'localhost')
            || str_contains($host, '127.0.0.1')
            || in_array($addr, ['127.0.0.1', '::1'], true);
    }
}
