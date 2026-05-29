<?php

namespace StudentAttendance\Utils;

class Timetable
{
    public const DAYS = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];

    public static function ensureSchema(): void
    {
        $db = $GLOBALS['db'] ?? null;
        if ($db === null) {
            throw new \RuntimeException('Database connection not initialized.');
        }

        if (($_ENV['DB_DRIVER'] ?? '') === 'pdo_sqlite') {
            $db->executeStatement(
                'CREATE TABLE IF NOT EXISTS courses (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    course_name VARCHAR(255) NOT NULL,
                    course_code VARCHAR(50) DEFAULT NULL,
                    class_name VARCHAR(100) NOT NULL,
                    teacher_id INTEGER DEFAULT NULL,
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
                )'
            );
            $db->executeStatement(
                'CREATE TABLE IF NOT EXISTS timetable_entries (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    course_id INTEGER NOT NULL,
                    class_name VARCHAR(100) NOT NULL,
                    teacher_id INTEGER DEFAULT NULL,
                    lecture_title VARCHAR(255) DEFAULT NULL,
                    room VARCHAR(100) DEFAULT NULL,
                    day_of_week VARCHAR(20) NOT NULL,
                    start_time VARCHAR(5) NOT NULL,
                    end_time VARCHAR(5) NOT NULL,
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
                )'
            );
            return;
        }

        $db->executeStatement(
            'CREATE TABLE IF NOT EXISTS courses (
                id INT AUTO_INCREMENT PRIMARY KEY,
                course_name VARCHAR(255) NOT NULL,
                course_code VARCHAR(50) DEFAULT NULL,
                class_name VARCHAR(100) NOT NULL,
                teacher_id INT DEFAULT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )'
        );
        $db->executeStatement(
            'CREATE TABLE IF NOT EXISTS timetable_entries (
                id INT AUTO_INCREMENT PRIMARY KEY,
                course_id INT NOT NULL,
                class_name VARCHAR(100) NOT NULL,
                teacher_id INT DEFAULT NULL,
                lecture_title VARCHAR(255) DEFAULT NULL,
                room VARCHAR(100) DEFAULT NULL,
                day_of_week VARCHAR(20) NOT NULL,
                start_time VARCHAR(5) NOT NULL,
                end_time VARCHAR(5) NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )'
        );
    }

    /** @return array<int, array<string, mixed>> */
    public static function courses(?string $className = null, ?int $teacherId = null): array
    {
        self::ensureSchema();
        $where = [];
        $params = [];
        if ($className !== null && trim($className) !== '') {
            $where[] = 'c.class_name = ?';
            $params[] = trim($className);
        }
        if ($teacherId !== null && $teacherId > 0) {
            $where[] = 'c.teacher_id = ?';
            $params[] = $teacherId;
        }
        $sql = 'SELECT c.id, c.course_name, c.course_code, c.class_name, c.teacher_id, t.name AS teacher_name
                FROM courses c
                LEFT JOIN teachers t ON t.id = c.teacher_id';
        if ($where) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }
        $sql .= ' ORDER BY c.class_name ASC, c.course_name ASC';

        return Utility::safeQuery($sql, $params, 'SELECT');
    }

    /** @return array<int, array<string, mixed>> */
    public static function entries(?string $className = null, ?int $teacherId = null): array
    {
        self::ensureSchema();
        $where = [];
        $params = [];
        if ($className !== null && trim($className) !== '') {
            $where[] = 'e.class_name = ?';
            $params[] = trim($className);
        }
        if ($teacherId !== null && $teacherId > 0) {
            $where[] = 'e.teacher_id = ?';
            $params[] = $teacherId;
        }
        $sql = 'SELECT e.id, e.course_id, e.class_name, e.teacher_id, e.lecture_title, e.room,
                       e.day_of_week, e.start_time, e.end_time,
                       c.course_name, c.course_code, t.name AS teacher_name
                FROM timetable_entries e
                INNER JOIN courses c ON c.id = e.course_id
                LEFT JOIN teachers t ON t.id = e.teacher_id';
        if ($where) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }
        $sql .= " ORDER BY
                    CASE e.day_of_week
                        WHEN 'Monday' THEN 1 WHEN 'Tuesday' THEN 2 WHEN 'Wednesday' THEN 3
                        WHEN 'Thursday' THEN 4 WHEN 'Friday' THEN 5 ELSE 6
                    END,
                    e.start_time ASC";

        return Utility::safeQuery($sql, $params, 'SELECT');
    }

    /** @param array<string, mixed> $data */
    public static function createCourse(array $data): void
    {
        self::ensureSchema();
        Utility::safeQuery(
            'INSERT INTO courses (course_name, course_code, class_name, teacher_id) VALUES (?, ?, ?, ?)',
            [
                trim((string) $data['course_name']),
                trim((string) ($data['course_code'] ?? '')) ?: null,
                trim((string) $data['class_name']),
                (int) ($data['teacher_id'] ?? 0) ?: null,
            ],
            'INSERT'
        );
    }

    /** @param array<string, mixed> $data */
    public static function createEntry(array $data): void
    {
        self::ensureSchema();
        Utility::safeQuery(
            'INSERT INTO timetable_entries (course_id, class_name, teacher_id, lecture_title, room, day_of_week, start_time, end_time)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
            [
                (int) $data['course_id'],
                trim((string) $data['class_name']),
                (int) ($data['teacher_id'] ?? 0) ?: null,
                trim((string) ($data['lecture_title'] ?? '')) ?: null,
                trim((string) ($data['room'] ?? '')) ?: null,
                (string) $data['day_of_week'],
                (string) $data['start_time'],
                (string) $data['end_time'],
            ],
            'INSERT'
        );
    }

    /** @param array<string, mixed> $data */
    public static function updateEntry(int $entryId, array $data, ?int $teacherId = null): bool
    {
        self::ensureSchema();
        $where = 'id = ?';
        $params = [
            trim((string) ($data['lecture_title'] ?? '')) ?: null,
            trim((string) ($data['room'] ?? '')) ?: null,
            (string) $data['day_of_week'],
            (string) $data['start_time'],
            (string) $data['end_time'],
            $entryId,
        ];
        if ($teacherId !== null) {
            $where .= ' AND teacher_id = ?';
            $params[] = $teacherId;
        }

        Utility::safeQuery(
            "UPDATE timetable_entries
             SET lecture_title = ?, room = ?, day_of_week = ?, start_time = ?, end_time = ?
             WHERE {$where}",
            $params,
            'UPDATE'
        );
        return true;
    }

    /** @return array<string, array<int, array<string, mixed>>> */
    public static function groupByDay(array $entries): array
    {
        $grouped = [];
        foreach (self::DAYS as $day) {
            $grouped[$day] = [];
        }
        foreach ($entries as $entry) {
            $day = $entry['day_of_week'] ?? '';
            if (!isset($grouped[$day])) {
                $grouped[$day] = [];
            }
            $grouped[$day][] = $entry;
        }
        return $grouped;
    }
}
