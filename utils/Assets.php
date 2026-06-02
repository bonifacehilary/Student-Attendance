<?php

namespace StudentAttendance\Utils;

/**
 * Central asset URLs and HTML output for EduAttend (Student Attendance).
 */
class Assets
{
    public const APP_NAME = 'EduAttend';
    public const APP_TAGLINE = 'Student Attendance System';

    /** Web path prefix for static files (no trailing slash). */
    public const BASE = '/assets';

    public static function url(string $path): string
    {
        return self::BASE . '/' . ltrim($path, '/');
    }

    public static function profilePhotoDir(): string
    {
        $dir = dirname(__DIR__) . '/assets/profile_photos';
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        return $dir;
    }

    public static function profilePhotoUrl(string $filename): string
    {
        return self::url('profile_photos/' . ltrim($filename, '/'));
    }

    /**
     * @return array{css: string[], js_head: string[], js_foot: string[]}
     */
    public static function manifest(string $context = 'public'): array
    {
        $common = [
            'css' => [
                'https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap',
                'https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap',
                self::url('css/eduattend.css'),
            ],
            'js_head' => [
                'https://cdn.tailwindcss.com?plugins=forms,container-queries',
            ],
            'js_foot' => [
                self::url('jquery/jquery.min.js'),
                self::url('sweetalert2/sweetalert2.all.min.js'),
            ],
        ];

        if ($context === 'student') {
            $common['js_foot'][] = self::url('js/student-app.js');
        } elseif ($context === 'admin') {
            $common['css'][] = self::url('css/admin-panel.css');
            $common['js_foot'][] = self::url('js/admin-app.js');
            $common['js_foot'][] = self::url('js/api-client.js');
        } elseif ($context === 'teacher') {
            $common['css'][] = self::url('css/admin-panel.css');
            $common['css'][] = self::url('css/teacher-panel.css');
            $common['js_foot'][] = self::url('js/teacher-app.js');
        } elseif ($context === 'public') {
            $common['js_foot'][] = self::url('js/student-app.js');
        }

        return $common;
    }

    public static function renderHeadStart(string $title, string $context = 'public'): void
    {
        $fullTitle = str_contains($title, self::APP_NAME)
            ? $title
            : $title . ' | ' . self::APP_NAME;

        echo '<!DOCTYPE html>', "\n";
        echo '<html class="light" lang="en">', "\n";
        echo '<head>', "\n";
        echo '    <meta charset="utf-8"/>', "\n";
        echo '    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>', "\n";
        echo '    <meta name="application-name" content="', htmlspecialchars(self::APP_NAME), '"/>', "\n";
        echo '    <title>', htmlspecialchars($fullTitle), '</title>', "\n";

        foreach (self::manifest($context)['css'] as $href) {
            echo '    <link rel="stylesheet" href="', htmlspecialchars($href), '"/>', "\n";
        }
    }

    public static function renderHeadScripts(string $context = 'public', array $extra = []): void
    {
        $scripts = array_merge(self::manifest($context)['js_head'], $extra);
        foreach ($scripts as $src) {
            echo '    <script src="', htmlspecialchars($src), '"></script>', "\n";
        }
    }

    public static function renderFoot(string $context = 'public', array $extra = []): void
    {
        $scripts = array_merge(self::manifest($context)['js_foot'], $extra);
        foreach ($scripts as $src) {
            echo '    <script src="', htmlspecialchars($src), '"></script>', "\n";
        }
    }

    /** Student portal navigation links. */
    public static function studentNavItems(): array
    {
        return [
            ['label' => 'Dashboard', 'href' => '/pages/student/dashboard.php', 'icon' => 'dashboard'],
            ['label' => 'Attendance', 'href' => '/pages/student/attendance.php', 'icon' => 'event_available'],
            ['label' => 'QR Scan', 'href' => '/pages/student/qr-attendance.php', 'icon' => 'qr_code_scanner'],
            ['label' => 'Analytics', 'href' => '/pages/student/analytics.php', 'icon' => 'analytics'],
            ['label' => 'Reports', 'href' => '/pages/student/report.php', 'icon' => 'description'],
            ['label' => 'Profile', 'href' => '/pages/student/profile.php', 'icon' => 'person'],
        ];
    }

    /** Admin portal navigation links. */
    public static function adminNavItems(): array
    {
        return [
            ['label' => 'Dashboard', 'href' => '/pages/admin/dashboard.php', 'icon' => 'dashboard'],
            ['label' => 'Mark attendance', 'href' => '/pages/admin/attendance.php', 'icon' => 'fact_check'],
            ['label' => 'Users & roles', 'href' => '/pages/admin/users.php', 'icon' => 'manage_accounts'],
            ['label' => 'Departments', 'href' => '/pages/admin/departments.php', 'icon' => 'account_tree'],
            ['label' => 'Students', 'href' => '/pages/admin/students.php', 'icon' => 'groups'],
            ['label' => 'Reports', 'href' => '/pages/admin/reports.php', 'icon' => 'summarize'],
            ['label' => 'Timetable', 'href' => '/pages/admin/timetable.php', 'icon' => 'calendar_month'],
            ['label' => 'QR sessions', 'href' => '/pages/admin/qrs.php', 'icon' => 'qr_code_2'],
            ['label' => 'Create QR', 'href' => '/pages/admin/create_qr.php', 'icon' => 'add_circle'],
        ];
    }

    /** Teacher portal navigation (class-scoped). */
    public static function teacherNavItems(): array
    {
        return [
            ['label' => 'Dashboard', 'href' => '/pages/teacher/dashboard.php', 'icon' => 'dashboard'],
            ['label' => 'Mark attendance', 'href' => '/pages/teacher/attendance.php', 'icon' => 'fact_check'],
            ['label' => 'My class', 'href' => '/pages/teacher/students.php', 'icon' => 'groups'],
            ['label' => 'Reports', 'href' => '/pages/teacher/reports.php', 'icon' => 'summarize'],
            ['label' => 'My timetable', 'href' => '/pages/teacher/timetable.php', 'icon' => 'calendar_month'],
            ['label' => 'Course materials', 'href' => '/pages/teacher/materials.php', 'icon' => 'folder_open'],
            ['label' => 'QR sessions', 'href' => '/pages/teacher/qrs.php', 'icon' => 'qr_code_2'],
            ['label' => 'Create QR', 'href' => '/pages/teacher/create_qr.php', 'icon' => 'add_circle'],
        ];
    }
}
