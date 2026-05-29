<?php
// pages/student/courses.php
// Student Courses/Subjects View

require_once __DIR__ . '/../../config/bootstrap.php';

use StudentAttendance\Utils\StudentAuth;
use StudentAttendance\Utils\Timetable;

StudentAuth::require();
$studentId = StudentAuth::studentId();
$student = StudentAuth::getStudent($studentId);
$studentClass = trim((string) ($student['student_class'] ?? ''));
Timetable::ensureSchema();
$courses = $studentClass !== '' ? Timetable::courses($studentClass) : [];

if (isset($_GET['download'])) {
    $materials = [
        'chapter-1' => [
            'filename' => 'chapter-1-introduction.txt',
            'body' => "EduAttend course material\n\nChapter 1: Introduction\n\nAsk your teacher for the full PDF handout in class.",
        ],
    ];
    $key = (string) $_GET['download'];
    if (isset($materials[$key])) {
        header('Content-Type: text/plain; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $materials[$key]['filename'] . '"');
        echo $materials[$key]['body'];
        exit;
    }
}

$pageTitle = 'Courses';
$pageHeading = 'My Courses & Subjects';
$activeNav = 'courses';

require __DIR__ . '/../../components/student/layout-start.php';
?>

<div class="space-y-6">
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        <?php if (empty($courses)): ?>
            <div class="md:col-span-2 lg:col-span-3 bg-white rounded-lg border border-slate-200 p-6 shadow-sm">
                <p class="text-sm text-slate-600">
                    No courses have been assigned to <?= htmlspecialchars($studentClass !== '' ? $studentClass : 'your class') ?> yet.
                </p>
            </div>
        <?php else: ?>
            <?php foreach ($courses as $course): ?>
                <div class="bg-white rounded-lg border border-slate-200 p-6 shadow-sm hover:shadow-md transition-shadow">
                    <div class="flex items-start justify-between mb-3">
                        <div class="flex items-center gap-3">
                            <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-emerald-100">
                                <span class="material-symbols-outlined text-emerald-700">school</span>
                            </div>
                            <h3 class="font-bold text-slate-900"><?= htmlspecialchars($course['course_name']) ?></h3>
                        </div>
                        <span class="text-xs font-semibold text-emerald-700 bg-emerald-50 px-2 py-1 rounded">
                            <?= htmlspecialchars($course['course_code'] ?: 'Course') ?>
                        </span>
                    </div>
                    <p class="text-sm text-slate-600 mb-3">Class: <?= htmlspecialchars($course['class_name']) ?></p>
                    <p class="text-xs text-slate-500 mb-3">Lecturer: <?= htmlspecialchars($course['teacher_name'] ?? 'Unassigned') ?></p>
                    <a href="/pages/student/timetable.php" class="text-sm font-semibold text-emerald-700 hover:underline">View timetable</a>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- Course Details Card -->
    <div class="bg-white rounded-lg border border-slate-200 p-6 shadow-sm">
        <h3 class="font-bold text-slate-900 mb-4">Course Materials</h3>
        <div class="space-y-3">
            <a href="/pages/student/courses.php?download=chapter-1" class="flex items-center justify-between p-3 bg-slate-50 rounded-lg border border-slate-200 hover:border-slate-300">
                <div class="flex items-center gap-3">
                    <span class="material-symbols-outlined text-slate-600">description</span>
                    <div>
                        <p class="text-sm font-medium text-slate-900">Chapter 1: Introduction</p>
                        <p class="text-xs text-slate-500">PDF • 2.4 MB</p>
                    </div>
                </div>
                <span class="text-emerald-700 text-sm font-semibold">Download</span>
            </a>
            <a href="/pages/student/help.php" class="flex items-center justify-between p-3 bg-slate-50 rounded-lg border border-slate-200 hover:border-slate-300">
                <div class="flex items-center gap-3">
                    <span class="material-symbols-outlined text-slate-600">video_library</span>
                    <div>
                        <p class="text-sm font-medium text-slate-900">Lesson Video: Basics</p>
                        <p class="text-xs text-slate-500">MP4 • 45 min</p>
                    </div>
                </div>
                <span class="text-emerald-700 text-sm font-semibold">Watch</span>
            </a>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../../components/student/layout-end.php'; ?>
