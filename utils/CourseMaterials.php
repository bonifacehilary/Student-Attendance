<?php

namespace StudentAttendance\Utils;

class CourseMaterials
{
    public const MAX_BYTES = 10485760;
    public const ALLOWED_EXTENSIONS = ['pdf', 'doc', 'docx', 'ppt', 'pptx', 'txt'];

    public static function ensureSchema(): void
    {
        $db = $GLOBALS['db'] ?? null;
        if ($db === null) {
            throw new \RuntimeException('Database connection not initialized.');
        }

        if (($_ENV['DB_DRIVER'] ?? '') === 'pdo_sqlite') {
            $db->executeStatement(
                'CREATE TABLE IF NOT EXISTS course_materials (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    course_id INTEGER NOT NULL,
                    teacher_id INTEGER NOT NULL,
                    class_name VARCHAR(100) NOT NULL,
                    title VARCHAR(255) NOT NULL,
                    original_filename VARCHAR(255) NOT NULL,
                    stored_filename VARCHAR(255) NOT NULL,
                    mime_type VARCHAR(120) DEFAULT NULL,
                    file_size INTEGER DEFAULT 0,
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
                )'
            );
            return;
        }

        $db->executeStatement(
            'CREATE TABLE IF NOT EXISTS course_materials (
                id INT AUTO_INCREMENT PRIMARY KEY,
                course_id INT NOT NULL,
                teacher_id INT NOT NULL,
                class_name VARCHAR(100) NOT NULL,
                title VARCHAR(255) NOT NULL,
                original_filename VARCHAR(255) NOT NULL,
                stored_filename VARCHAR(255) NOT NULL,
                mime_type VARCHAR(120) DEFAULT NULL,
                file_size INT DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )'
        );
    }

    public static function uploadDir(): string
    {
        $dir = dirname(__DIR__) . '/assets/course_materials';
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        return $dir;
    }

    public static function publish(array $data, array $file, int $teacherId, string $assignedClass): int
    {
        self::ensureSchema();

        $courseId = (int) ($data['course_id'] ?? 0);
        $title = trim((string) ($data['title'] ?? ''));

        if ($courseId <= 0) {
            throw new \InvalidArgumentException('Select a course before publishing notes.');
        }
        if ($title === '') {
            throw new \InvalidArgumentException('Enter a title for the notes.');
        }
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            throw new \InvalidArgumentException('Choose a notes file to upload.');
        }
        if (($file['size'] ?? 0) <= 0 || (int) $file['size'] > self::MAX_BYTES) {
            throw new \InvalidArgumentException('Notes file must be 10 MB or smaller.');
        }

        $course = self::teacherCourse($courseId, $teacherId, $assignedClass);
        if (!$course) {
            throw new \InvalidArgumentException('Selected course is not assigned to your class.');
        }

        $original = basename((string) ($file['name'] ?? 'notes'));
        $extension = strtolower(pathinfo($original, PATHINFO_EXTENSION));
        if (!in_array($extension, self::ALLOWED_EXTENSIONS, true)) {
            throw new \InvalidArgumentException('Upload PDF, Word, PowerPoint, or text notes only.');
        }

        $stored = 'notes-' . $teacherId . '-' . time() . '-' . bin2hex(random_bytes(4)) . '.' . $extension;
        $target = self::uploadDir() . '/' . $stored;

        if (!move_uploaded_file((string) $file['tmp_name'], $target)) {
            throw new \RuntimeException('Could not save the uploaded notes. Check upload permissions.');
        }

        return (int) Utility::safeQuery(
            'INSERT INTO course_materials (course_id, teacher_id, class_name, title, original_filename, stored_filename, mime_type, file_size)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
            [
                $courseId,
                $teacherId,
                $assignedClass,
                $title,
                $original,
                $stored,
                (string) ($file['type'] ?? 'application/octet-stream'),
                (int) $file['size'],
            ],
            'INSERT'
        );
    }

    /** @return array<int, array<string, mixed>> */
    public static function forStudentClass(string $className): array
    {
        self::ensureSchema();
        if (trim($className) === '') {
            return [];
        }

        return Utility::safeQuery(
            'SELECT m.*, c.course_name, c.course_code, t.name AS teacher_name
             FROM course_materials m
             INNER JOIN courses c ON c.id = m.course_id
             LEFT JOIN teachers t ON t.id = m.teacher_id
             WHERE m.class_name = ?
             ORDER BY m.created_at DESC',
            [trim($className)],
            'SELECT'
        );
    }

    /** @return array<int, array<string, mixed>> */
    public static function forTeacher(int $teacherId, string $assignedClass): array
    {
        self::ensureSchema();

        return Utility::safeQuery(
            'SELECT m.*, c.course_name, c.course_code
             FROM course_materials m
             INNER JOIN courses c ON c.id = m.course_id
             WHERE m.teacher_id = ? AND m.class_name = ?
             ORDER BY m.created_at DESC',
            [$teacherId, $assignedClass],
            'SELECT'
        );
    }

    public static function findForStudent(int $materialId, string $className): ?array
    {
        self::ensureSchema();
        $material = Utility::safeQuery(
            'SELECT m.*, c.course_name
             FROM course_materials m
             INNER JOIN courses c ON c.id = m.course_id
             WHERE m.id = ? AND m.class_name = ?
             LIMIT 1',
            [$materialId, $className],
            'SELECT',
            true
        );

        return $material ?: null;
    }

    public static function deleteForTeacher(int $materialId, int $teacherId, string $assignedClass): bool
    {
        self::ensureSchema();
        $material = Utility::safeQuery(
            'SELECT stored_filename FROM course_materials WHERE id = ? AND teacher_id = ? AND class_name = ? LIMIT 1',
            [$materialId, $teacherId, $assignedClass],
            'SELECT',
            true
        );
        if (!$material) {
            return false;
        }

        Utility::safeQuery(
            'DELETE FROM course_materials WHERE id = ? AND teacher_id = ? AND class_name = ?',
            [$materialId, $teacherId, $assignedClass],
            'DELETE'
        );

        $path = self::uploadDir() . '/' . basename((string) $material['stored_filename']);
        if (is_file($path)) {
            unlink($path);
        }

        return true;
    }

    public static function teacherCourse(int $courseId, int $teacherId, string $assignedClass): ?array
    {
        $course = Utility::safeQuery(
            'SELECT id, course_name, course_code, class_name, teacher_id
             FROM courses
             WHERE id = ? AND class_name = ? AND (teacher_id = ? OR teacher_id IS NULL)
             LIMIT 1',
            [$courseId, $assignedClass, $teacherId],
            'SELECT',
            true
        );

        return $course ?: null;
    }

    /** @return array<int, array<string, mixed>> */
    public static function teacherCourses(int $teacherId, string $assignedClass): array
    {
        self::ensureSchema();

        return Utility::safeQuery(
            'SELECT id, course_name, course_code, class_name, teacher_id
             FROM courses
             WHERE class_name = ? AND (teacher_id = ? OR teacher_id IS NULL)
             ORDER BY course_name ASC',
            [$assignedClass, $teacherId],
            'SELECT'
        );
    }

    public static function formatBytes(int $bytes): string
    {
        if ($bytes >= 1048576) {
            return round($bytes / 1048576, 1) . ' MB';
        }
        if ($bytes >= 1024) {
            return round($bytes / 1024, 1) . ' KB';
        }

        return $bytes . ' B';
    }
}
