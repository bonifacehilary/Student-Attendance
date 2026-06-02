<?php
// pages/student/courses.php
// Student Courses/Subjects View

require_once __DIR__ . '/../../config/bootstrap.php';

use StudentAttendance\Utils\StudentAuth;
use StudentAttendance\Utils\Timetable;
use StudentAttendance\Utils\CourseMaterials;

StudentAuth::require();
$studentId = StudentAuth::studentId();
$student = StudentAuth::getStudent($studentId);
$studentClass = trim((string) ($student['student_class'] ?? ''));
Timetable::ensureSchema();
$courses = $studentClass !== '' ? Timetable::courses($studentClass) : [];
CourseMaterials::ensureSchema();

if (isset($_GET['download'])) {
    $materialId = (int) $_GET['download'];
    $material = CourseMaterials::findForStudent($materialId, $studentClass);
    if ($material) {
        $path = CourseMaterials::uploadDir() . '/' . basename((string) $material['stored_filename']);
        if (is_file($path)) {
            $downloadName = preg_replace('/[^A-Za-z0-9._ -]/', '_', basename((string) $material['original_filename'])) ?: 'course-notes';
            header('Content-Type: ' . ($material['mime_type'] ?: 'application/octet-stream'));
            header('Content-Disposition: attachment; filename="' . $downloadName . '"');
            header('Content-Length: ' . filesize($path));
            readfile($path);
            exit;
        }
    }
    http_response_code(404);
    exit('Material not found.');
}

$materials = CourseMaterials::forStudentClass($studentClass);

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

    <section class="bg-white rounded-lg border border-slate-200 p-6 shadow-sm">
        <div class="flex items-center justify-between gap-3 mb-4">
            <div>
                <h3 class="font-bold text-slate-900">Course Materials</h3>
                <p class="text-sm text-slate-500 mt-1">Download notes published by your teachers.</p>
            </div>
            <span class="material-symbols-outlined text-emerald-700">folder_open</span>
        </div>

        <?php if (empty($materials)): ?>
            <p class="text-sm text-slate-600">No notes have been published for your class yet.</p>
        <?php else: ?>
            <div class="space-y-3">
                <?php foreach ($materials as $material): ?>
                    <a href="/pages/student/courses.php?download=<?= (int) $material['id'] ?>"
                       class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 p-3 bg-slate-50 rounded-lg border border-slate-200 hover:border-emerald-300 transition-colors">
                        <div class="flex items-center gap-3 min-w-0">
                            <span class="material-symbols-outlined text-slate-600">description</span>
                            <div class="min-w-0">
                                <p class="text-sm font-semibold text-slate-900 truncate"><?= htmlspecialchars($material['title']) ?></p>
                                <p class="text-xs text-slate-500">
                                    <?= htmlspecialchars($material['course_name']) ?>
                                    <?php if (!empty($material['teacher_name'])): ?>
                                        <span class="mx-1">/</span><?= htmlspecialchars($material['teacher_name']) ?>
                                    <?php endif; ?>
                                    <span class="mx-1">/</span><?= htmlspecialchars(CourseMaterials::formatBytes((int) $material['file_size'])) ?>
                                </p>
                            </div>
                        </div>
                        <span class="inline-flex items-center gap-1 text-emerald-700 text-sm font-semibold">
                            <span class="material-symbols-outlined text-base">download</span>
                            Download
                        </span>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>
</div>

<?php require __DIR__ . '/../../components/student/layout-end.php'; ?>
