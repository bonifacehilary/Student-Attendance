<?php

namespace StudentAttendance\Utils;

/**
 * Admin session guard and attendance helpers (SQLite + MySQL compatible).
 */
class AdminAuth
{
    public static function check(): bool
    {
        return !empty($_SESSION['admin_logged_in']);
    }

    public static function require(): void
    {
        if (!self::check()) {
            header('Location: /pages/admin/login.php');
            exit;
        }
        UserManagement::ensureSchema();
    }

    public static function setFlash(string $message): void
    {
        $_SESSION['admin_flash'] = $message;
    }

    public static function pullFlash(): ?string
    {
        $message = $_SESSION['admin_flash'] ?? null;
        unset($_SESSION['admin_flash']);
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
        $query = trim($query);
        if ($query === '') {
            return $rows;
        }
        $needle = strtolower($query);
        return array_values(array_filter($rows, static function (array $row) use ($fields, $needle): bool {
            $haystack = '';
            foreach ($fields as $field) {
                $haystack .= ' ' . strtolower((string) ($row[$field] ?? ''));
            }
            return str_contains($haystack, $needle);
        }));
    }

    public static function upsertAttendance(int $studentId, string $date, string $status): void
    {
        if (!in_array($status, ['present', 'late', 'absent'], true)) {
            throw new \InvalidArgumentException('Invalid attendance status');
        }

        $existing = Utility::safeQuery(
            'SELECT id FROM attendance WHERE student_id = ? AND attendance_date = ? LIMIT 1',
            [$studentId, $date],
            'SELECT',
            true
        );

        if ($existing) {
            Utility::safeQuery(
                'UPDATE attendance SET status = ? WHERE id = ?',
                [$status, $existing['id']],
                'UPDATE'
            );
            return;
        }

        Utility::safeQuery(
            'INSERT INTO attendance (student_id, attendance_date, status) VALUES (?, ?, ?)',
            [$studentId, $date, $status],
            'INSERT'
        );
    }

    /** @return array{marked:int,total:int,present:int,late:int,absent:int} */
    public static function getStatsForDate(string $date): array
    {
        $defaults = ['marked' => 0, 'total' => 0, 'present' => 0, 'late' => 0, 'absent' => 0];

        try {
            $row = Utility::safeQuery(
                'SELECT 
                    (SELECT COUNT(DISTINCT a.student_id) FROM attendance a INNER JOIN students s ON s.id = a.student_id WHERE a.attendance_date = ? AND COALESCE(s.is_active, 1) = 1) AS marked,
                    (SELECT COUNT(*) FROM students WHERE COALESCE(is_active, 1) = 1) AS total,
                    (SELECT COUNT(*) FROM attendance a INNER JOIN students s ON s.id = a.student_id WHERE a.attendance_date = ? AND a.status = ? AND COALESCE(s.is_active, 1) = 1) AS present,
                    (SELECT COUNT(*) FROM attendance a INNER JOIN students s ON s.id = a.student_id WHERE a.attendance_date = ? AND a.status = ? AND COALESCE(s.is_active, 1) = 1) AS late,
                    (SELECT COUNT(*) FROM attendance a INNER JOIN students s ON s.id = a.student_id WHERE a.attendance_date = ? AND a.status = ? AND COALESCE(s.is_active, 1) = 1) AS absent',
                [$date, $date, 'present', $date, 'late', $date, 'absent'],
                'SELECT',
                true
            );
            return $row ?: $defaults;
        } catch (\Throwable $e) {
            error_log('AdminAuth::getStatsForDate: ' . $e->getMessage());
            return $defaults;
        }
    }
}
