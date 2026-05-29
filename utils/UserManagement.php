<?php

namespace StudentAttendance\Utils;

class UserManagement
{
    public const ROLES = ['admin', 'teacher', 'student'];

    public static function ensureSchema(): void
    {
        $db = $GLOBALS['db'] ?? null;
        if ($db === null) {
            throw new \RuntimeException('Database connection not initialized.');
        }

        $isSqlite = ($_ENV['DB_DRIVER'] ?? '') === 'pdo_sqlite';

        self::createBaseTables($isSqlite);

        self::addColumnIfMissing('students', 'role', 'VARCHAR(50) DEFAULT "student"');
        self::addColumnIfMissing('students', 'is_active', $isSqlite ? 'INTEGER DEFAULT 1' : 'TINYINT(1) DEFAULT 1');
        self::addColumnIfMissing('students', 'department_id', 'INTEGER DEFAULT NULL');
        self::addColumnIfMissing('students', 'class_id', 'INTEGER DEFAULT NULL');
        self::addColumnIfMissing('teachers', 'role', 'VARCHAR(50) DEFAULT "teacher"');
        self::addColumnIfMissing('teachers', 'is_active', $isSqlite ? 'INTEGER DEFAULT 1' : 'TINYINT(1) DEFAULT 1');
        self::addColumnIfMissing('teachers', 'department_id', 'INTEGER DEFAULT NULL');
        self::addColumnIfMissing('teachers', 'class_id', 'INTEGER DEFAULT NULL');

        self::ensureDefaultAdmin();
    }

    private static function createBaseTables(bool $isSqlite): void
    {
        $db = $GLOBALS['db'];
        if ($isSqlite) {
            $db->executeStatement(
                'CREATE TABLE IF NOT EXISTS admin_users (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    name VARCHAR(255) NOT NULL,
                    email VARCHAR(255) NOT NULL UNIQUE,
                    password_hash VARCHAR(255) NOT NULL,
                    role VARCHAR(50) DEFAULT "admin",
                    is_active INTEGER DEFAULT 1,
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
                )'
            );
            $db->executeStatement(
                'CREATE TABLE IF NOT EXISTS teachers (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    name VARCHAR(255) NOT NULL,
                    email VARCHAR(255) NOT NULL UNIQUE,
                    password_hash VARCHAR(255) NOT NULL,
                    assigned_class VARCHAR(100) DEFAULT NULL,
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
                )'
            );
            $db->executeStatement(
                'CREATE TABLE IF NOT EXISTS departments (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    name VARCHAR(255) NOT NULL UNIQUE,
                    code VARCHAR(50) DEFAULT NULL,
                    description TEXT DEFAULT NULL,
                    is_active INTEGER DEFAULT 1,
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
                )'
            );
            $db->executeStatement(
                'CREATE TABLE IF NOT EXISTS classes (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    name VARCHAR(255) NOT NULL,
                    department_id INTEGER DEFAULT NULL,
                    description TEXT DEFAULT NULL,
                    is_active INTEGER DEFAULT 1,
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
                )'
            );
            return;
        }

        $db->executeStatement(
            'CREATE TABLE IF NOT EXISTS admin_users (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(255) NOT NULL,
                email VARCHAR(255) NOT NULL UNIQUE,
                password_hash VARCHAR(255) NOT NULL,
                role VARCHAR(50) DEFAULT "admin",
                is_active TINYINT(1) DEFAULT 1,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )'
        );
        $db->executeStatement(
            'CREATE TABLE IF NOT EXISTS teachers (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(255) NOT NULL,
                email VARCHAR(255) NOT NULL UNIQUE,
                password_hash VARCHAR(255) NOT NULL,
                assigned_class VARCHAR(100) DEFAULT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )'
        );
        $db->executeStatement(
            'CREATE TABLE IF NOT EXISTS departments (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(255) NOT NULL UNIQUE,
                code VARCHAR(50) DEFAULT NULL,
                description TEXT DEFAULT NULL,
                is_active TINYINT(1) DEFAULT 1,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )'
        );
        $db->executeStatement(
            'CREATE TABLE IF NOT EXISTS classes (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(255) NOT NULL,
                department_id INT DEFAULT NULL,
                description TEXT DEFAULT NULL,
                is_active TINYINT(1) DEFAULT 1,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )'
        );
    }

    private static function addColumnIfMissing(string $table, string $column, string $definition): void
    {
        $db = $GLOBALS['db'];
        try {
            if (($_ENV['DB_DRIVER'] ?? '') === 'pdo_sqlite') {
                $cols = $db->fetchAllAssociative("PRAGMA table_info({$table})");
                foreach ($cols as $col) {
                    if (($col['name'] ?? '') === $column) {
                        return;
                    }
                }
                $db->executeStatement("ALTER TABLE {$table} ADD COLUMN {$column} {$definition}");
                return;
            }

            $row = Utility::safeQuery("SHOW COLUMNS FROM {$table} LIKE ?", [$column], 'SELECT', true);
            if (!$row) {
                $db->executeStatement("ALTER TABLE {$table} ADD COLUMN {$column} {$definition}");
            }
        } catch (\Throwable $e) {
            error_log("UserManagement::addColumnIfMissing {$table}.{$column}: " . $e->getMessage());
        }
    }

    public static function ensureDefaultAdmin(): void
    {
        $existing = Utility::safeQuery(
            'SELECT id FROM admin_users WHERE LOWER(email) = LOWER(?) LIMIT 1',
            ['admin@school.edu'],
            'SELECT',
            true
        );
        if ($existing) {
            return;
        }

        Utility::safeQuery(
            'INSERT INTO admin_users (name, email, password_hash, role, is_active) VALUES (?, ?, ?, ?, 1)',
            ['School Administrator', 'admin@school.edu', password_hash('admin123', PASSWORD_BCRYPT), 'admin'],
            'INSERT'
        );
    }

    public static function attemptAdminLogin(string $email, string $password, ?string &$error = null): bool
    {
        self::ensureSchema();
        $email = strtolower(trim($email));
        if ($email === '' || $password === '') {
            $error = 'Please enter both email and password';
            return false;
        }

        $admin = Utility::safeQuery(
            'SELECT id, name, email, password_hash, role, is_active FROM admin_users WHERE LOWER(email) = ? LIMIT 1',
            [$email],
            'SELECT',
            true
        );

        if (!$admin) {
            $error = 'Invalid email or password';
            return false;
        }
        if (empty($admin['is_active'])) {
            $error = 'This admin account is deactivated.';
            return false;
        }
        if (!password_verify($password, (string) $admin['password_hash'])) {
            $error = 'Invalid email or password';
            return false;
        }

        $_SESSION['admin_id'] = (int) $admin['id'];
        $_SESSION['admin_email'] = $admin['email'];
        $_SESSION['admin_name'] = $admin['name'];
        $_SESSION['admin_role'] = $admin['role'] ?: 'admin';
        $_SESSION['admin_logged_in'] = true;
        return true;
    }

    /** @return array<int, array<string, mixed>> */
    public static function allUsers(): array
    {
        self::ensureSchema();
        $admins = Utility::safeQuery(
            'SELECT id, name, email, role, is_active, created_at, NULL AS assigned_class, NULL AS admission_number, "admin" AS user_type
             FROM admin_users',
            [],
            'SELECT'
        );
        $teachers = Utility::safeQuery(
            'SELECT t.id, t.name, t.email, COALESCE(t.role, "teacher") AS role, COALESCE(t.is_active, 1) AS is_active, t.created_at,
                    COALESCE(c.name, t.assigned_class) AS assigned_class, NULL AS admission_number, "teacher" AS user_type,
                    t.department_id, t.class_id,
                    d.name AS department_name
             FROM teachers t
             LEFT JOIN classes c ON c.id = t.class_id
             LEFT JOIN departments d ON d.id = t.department_id',
            [],
            'SELECT'
        );
        $students = Utility::safeQuery(
            'SELECT s.id, s.name, s.email, COALESCE(s.role, "student") AS role, COALESCE(s.is_active, 1) AS is_active, s.created_at,
                    COALESCE(c.name, s.student_class) AS assigned_class, s.admission_number, "student" AS user_type,
                    s.department_id, s.class_id,
                    d.name AS department_name
             FROM students s
             LEFT JOIN classes c ON c.id = s.class_id
             LEFT JOIN departments d ON d.id = s.department_id',
            [],
            'SELECT'
        );

        $users = array_merge($admins, $teachers, $students);
        usort($users, static fn(array $a, array $b): int => strcmp($a['name'] ?? '', $b['name'] ?? ''));
        return $users;
    }

    /** @param array<string, mixed> $data */
    public static function createUser(array $data): void
    {
        self::ensureSchema();
        $type = $data['user_type'] ?? '';
        $name = trim((string) ($data['name'] ?? ''));
        $email = strtolower(trim((string) ($data['email'] ?? '')));
        $password = (string) ($data['password'] ?? '');
        if (!in_array($type, self::ROLES, true) || $name === '' || $email === '' || strlen($password) < 6) {
            throw new \InvalidArgumentException('Role, name, email, and a 6+ character password are required.');
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException('Please enter a valid email address.');
        }
        self::assertUniqueLogin($email, $type);

        $hash = password_hash($password, PASSWORD_BCRYPT);

        if ($type === 'admin') {
            Utility::safeQuery(
                'INSERT INTO admin_users (name, email, password_hash, role, is_active) VALUES (?, ?, ?, ?, 1)',
                [$name, $email, $hash, 'admin'],
                'INSERT'
            );
            return;
        }

        if ($type === 'teacher') {
            $class = trim((string) ($data['assigned_class'] ?? ''));
            $departmentId = self::nullableInt($data['department_id'] ?? null);
            $classId = self::nullableInt($data['class_id'] ?? null);
            if ($class === '' && $classId !== null) {
                $class = self::className($classId) ?? '';
            }
            Utility::safeQuery(
                'INSERT INTO teachers (name, email, password_hash, assigned_class, role, is_active, department_id, class_id) VALUES (?, ?, ?, ?, ?, 1, ?, ?)',
                [$name, $email, $hash, $class, 'teacher', $departmentId, $classId],
                'INSERT'
            );
            return;
        }

        $admission = trim((string) ($data['admission_number'] ?? ''));
        $class = trim((string) ($data['assigned_class'] ?? ''));
        $departmentId = self::nullableInt($data['department_id'] ?? null);
        $classId = self::nullableInt($data['class_id'] ?? null);
        if ($class === '' && $classId !== null) {
            $class = self::className($classId) ?? '';
        }
        if ($admission === '') {
            throw new \InvalidArgumentException('Admission number is required for students.');
        }
        self::assertUniqueAdmission($admission);
        Utility::safeQuery(
            'INSERT INTO students (name, email, admission_number, password_hash, student_class, role, is_active, department_id, class_id) VALUES (?, ?, ?, ?, ?, ?, 1, ?, ?)',
            [$name, $email, $admission, $hash, $class, 'student', $departmentId, $classId],
            'INSERT'
        );
    }

    public static function setActive(string $type, int $id, bool $active): void
    {
        self::ensureSchema();
        [$table] = self::tableForType($type);
        Utility::safeQuery("UPDATE {$table} SET is_active = ? WHERE id = ?", [$active ? 1 : 0, $id], 'UPDATE');
    }

    public static function resetPassword(string $type, int $id, string $password): void
    {
        self::ensureSchema();
        if (strlen($password) < 6) {
            throw new \InvalidArgumentException('Password must be at least 6 characters.');
        }
        [$table] = self::tableForType($type);
        Utility::safeQuery(
            "UPDATE {$table} SET password_hash = ? WHERE id = ?",
            [password_hash($password, PASSWORD_BCRYPT), $id],
            'UPDATE'
        );
    }

    public static function updateUser(string $type, int $id, array $data): void
    {
        self::ensureSchema();
        $name = trim((string) ($data['name'] ?? ''));
        $email = strtolower(trim((string) ($data['email'] ?? '')));
        if ($name === '' || $email === '') {
            throw new \InvalidArgumentException('Name and email are required.');
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException('Please enter a valid email address.');
        }
        self::assertUniqueLogin($email, $type, $id);

        if ($type === 'student') {
            $classId = self::nullableInt($data['class_id'] ?? null);
            $className = trim((string) ($data['assigned_class'] ?? ''));
            if ($className === '' && $classId !== null) {
                $className = self::className($classId) ?? '';
            }
            $admission = trim((string) ($data['admission_number'] ?? ''));
            if ($admission === '') {
                throw new \InvalidArgumentException('Admission number is required for students.');
            }
            self::assertUniqueAdmission($admission, $id);
            Utility::safeQuery(
                'UPDATE students SET name = ?, email = ?, admission_number = ?, student_class = ?, role = ?, department_id = ?, class_id = ? WHERE id = ?',
                [$name, $email, $admission, $className, 'student', self::nullableInt($data['department_id'] ?? null), $classId, $id],
                'UPDATE'
            );
            return;
        }
        if ($type === 'teacher') {
            $classId = self::nullableInt($data['class_id'] ?? null);
            $className = trim((string) ($data['assigned_class'] ?? ''));
            if ($className === '' && $classId !== null) {
                $className = self::className($classId) ?? '';
            }
            Utility::safeQuery(
                'UPDATE teachers SET name = ?, email = ?, assigned_class = ?, role = ?, department_id = ?, class_id = ? WHERE id = ?',
                [$name, $email, $className, 'teacher', self::nullableInt($data['department_id'] ?? null), $classId, $id],
                'UPDATE'
            );
            return;
        }
        Utility::safeQuery(
            'UPDATE admin_users SET name = ?, email = ?, role = ? WHERE id = ?',
            [$name, $email, 'admin', $id],
            'UPDATE'
        );
    }

    /** @return array{0:string} */
    private static function tableForType(string $type): array
    {
        return match ($type) {
            'admin' => ['admin_users'],
            'teacher' => ['teachers'],
            'student' => ['students'],
            default => throw new \InvalidArgumentException('Invalid user type.'),
        };
    }

    /** @return array<int, array<string, mixed>> */
    public static function departments(): array
    {
        self::ensureSchema();
        return Utility::safeQuery('SELECT * FROM departments ORDER BY name ASC', [], 'SELECT');
    }

    /** @return array<int, array<string, mixed>> */
    public static function classes(): array
    {
        self::ensureSchema();
        return Utility::safeQuery(
            'SELECT c.*, d.name AS department_name
             FROM classes c
             LEFT JOIN departments d ON d.id = c.department_id
             ORDER BY d.name ASC, c.name ASC',
            [],
            'SELECT'
        );
    }

    /** @param array<string, mixed> $data */
    public static function saveDepartment(array $data): void
    {
        self::ensureSchema();
        $name = trim((string) ($data['name'] ?? ''));
        if ($name === '') {
            throw new \InvalidArgumentException('Department name is required.');
        }
        Utility::safeQuery(
            'INSERT INTO departments (name, code, description, is_active) VALUES (?, ?, ?, 1)',
            [$name, trim((string) ($data['code'] ?? '')), trim((string) ($data['description'] ?? ''))],
            'INSERT'
        );
    }

    /** @param array<string, mixed> $data */
    public static function saveClass(array $data): void
    {
        self::ensureSchema();
        $name = trim((string) ($data['name'] ?? ''));
        if ($name === '') {
            throw new \InvalidArgumentException('Class name is required.');
        }
        Utility::safeQuery(
            'INSERT INTO classes (name, department_id, description, is_active) VALUES (?, ?, ?, 1)',
            [$name, self::nullableInt($data['department_id'] ?? null), trim((string) ($data['description'] ?? ''))],
            'INSERT'
        );
    }

    public static function setDepartmentActive(int $id, bool $active): void
    {
        self::ensureSchema();
        Utility::safeQuery('UPDATE departments SET is_active = ? WHERE id = ?', [$active ? 1 : 0, $id], 'UPDATE');
    }

    public static function setClassActive(int $id, bool $active): void
    {
        self::ensureSchema();
        Utility::safeQuery('UPDATE classes SET is_active = ? WHERE id = ?', [$active ? 1 : 0, $id], 'UPDATE');
    }

    private static function nullableInt($value): ?int
    {
        $id = (int) $value;
        return $id > 0 ? $id : null;
    }

    private static function className(int $classId): ?string
    {
        $row = Utility::safeQuery('SELECT name FROM classes WHERE id = ? LIMIT 1', [$classId], 'SELECT', true);
        return $row ? (string) $row['name'] : null;
    }

    private static function assertUniqueLogin(string $email, string $type, ?int $ignoreId = null): void
    {
        $tables = [
            'admin' => 'admin_users',
            'teacher' => 'teachers',
            'student' => 'students',
        ];

        foreach ($tables as $role => $table) {
            $params = [$email];
            $where = 'LOWER(email) = LOWER(?)';
            if ($role === $type && $ignoreId !== null) {
                $where .= ' AND id <> ?';
                $params[] = $ignoreId;
            }

            $row = Utility::safeQuery("SELECT id FROM {$table} WHERE {$where} LIMIT 1", $params, 'SELECT', true);
            if ($row) {
                throw new \InvalidArgumentException('That email is already used by another account.');
            }
        }
    }

    private static function assertUniqueAdmission(string $admission, ?int $ignoreId = null): void
    {
        $params = [$admission];
        $where = 'admission_number = ?';
        if ($ignoreId !== null) {
            $where .= ' AND id <> ?';
            $params[] = $ignoreId;
        }

        $row = Utility::safeQuery("SELECT id FROM students WHERE {$where} LIMIT 1", $params, 'SELECT', true);
        if ($row) {
            throw new \InvalidArgumentException('That admission number is already used by another student.');
        }
    }
}
