<?php
require_once __DIR__ . '/../../config/bootstrap.php';

use StudentAttendance\Utils\CourseMaterials;
use StudentAttendance\Utils\TeacherAuth;
use StudentAttendance\Utils\Timetable;

$assignedClass = TeacherAuth::requireClass();
$teacherId = TeacherAuth::teacherId();
$teacherError = '';

Timetable::ensureSchema();
CourseMaterials::ensureSchema();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    try {
        if ($action === 'publish') {
            CourseMaterials::publish($_POST, $_FILES['notes_file'] ?? [], $teacherId, $assignedClass);
            TeacherAuth::redirect('/pages/teacher/materials.php', 'Course notes published.');
        } elseif ($action === 'delete') {
            $deleted = CourseMaterials::deleteForTeacher((int) ($_POST['material_id'] ?? 0), $teacherId, $assignedClass);
            TeacherAuth::redirect(
                '/pages/teacher/materials.php',
                $deleted ? 'Course notes removed.' : 'Course notes could not be found.'
            );
        }
    } catch (\Throwable $e) {
        $teacherError = $e->getMessage();
    }
}

$courses = CourseMaterials::teacherCourses($teacherId, $assignedClass);
$materials = CourseMaterials::forTeacher($teacherId, $assignedClass);

$pageTitle = 'Course Materials';
$pageHeading = 'Course materials';
$pageSubtitle = 'Publish notes for ' . $assignedClass;

require __DIR__ . '/../../components/teacher/shell-start.php';
?>

<div class="grid lg:grid-cols-[0.8fr_1.2fr] gap-6">
    <form method="post" enctype="multipart/form-data" class="admin-card p-6 space-y-4">
        <input type="hidden" name="action" value="publish">
        <div>
            <h2 class="font-bold text-slate-900">Publish notes</h2>
            <p class="text-sm text-slate-500 mt-1">Students in your class can download these from their Courses page.</p>
        </div>

        <div>
            <label class="block text-xs font-semibold text-slate-600 mb-1">Course</label>
            <select name="course_id" required class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm">
                <option value="">Select course</option>
                <?php foreach ($courses as $course): ?>
                    <option value="<?= (int) $course['id'] ?>">
                        <?= htmlspecialchars($course['course_name']) ?>
                        <?php if (!empty($course['course_code'])): ?>
                            (<?= htmlspecialchars($course['course_code']) ?>)
                        <?php endif; ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div>
            <label class="block text-xs font-semibold text-slate-600 mb-1">Notes title</label>
            <input name="title" required maxlength="255" placeholder="e.g. Week 3 lecture notes"
                   class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm">
        </div>

        <div>
            <label class="block text-xs font-semibold text-slate-600 mb-1">Notes file</label>
            <input type="file" name="notes_file" required accept=".pdf,.doc,.docx,.ppt,.pptx,.txt"
                   class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm bg-white">
            <p class="text-xs text-slate-500 mt-1">PDF, Word, PowerPoint, or text. Max 10 MB.</p>
        </div>

        <button type="submit"
                class="w-full bg-sky-600 hover:bg-sky-700 text-white font-semibold px-4 py-2 rounded-lg text-sm disabled:opacity-60"
                <?= empty($courses) ? 'disabled' : '' ?>>
            Publish notes
        </button>

        <?php if (empty($courses)): ?>
            <p class="text-sm text-amber-700 bg-amber-50 border border-amber-200 rounded-lg px-3 py-2">
                No courses are assigned to your class yet.
            </p>
        <?php endif; ?>
    </form>

    <div class="admin-card overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-200">
            <h2 class="font-bold text-slate-900">Published notes</h2>
            <p class="text-sm text-slate-500 mt-1"><?= count($materials) ?> material(s)</p>
        </div>

        <?php if (empty($materials)): ?>
            <p class="px-6 py-8 text-slate-500 text-sm">No course notes have been published yet.</p>
        <?php else: ?>
            <div class="divide-y divide-slate-100">
                <?php foreach ($materials as $material): ?>
                    <div class="p-6 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                        <div class="min-w-0">
                            <p class="font-semibold text-slate-900 truncate"><?= htmlspecialchars($material['title']) ?></p>
                            <p class="text-sm text-slate-500 mt-1">
                                <?= htmlspecialchars($material['course_name']) ?>
                                <span class="mx-1">/</span><?= htmlspecialchars($material['original_filename']) ?>
                                <span class="mx-1">/</span><?= htmlspecialchars(CourseMaterials::formatBytes((int) $material['file_size'])) ?>
                            </p>
                        </div>
                        <form method="post">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="material_id" value="<?= (int) $material['id'] ?>">
                            <button type="submit" class="text-sm font-semibold text-red-600 hover:text-red-700">
                                Remove
                            </button>
                        </form>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require __DIR__ . '/../../components/teacher/shell-end.php'; ?>
