<?php
require_once __DIR__ . '/../../config/bootstrap.php';

use StudentAttendance\Utils\AdminAuth;
use StudentAttendance\Utils\Timetable;

AdminAuth::require();
Timetable::ensureSchema();

$adminError = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    try {
        if ($action === 'create_course') {
            $courseName = trim($_POST['course_name'] ?? '');
            $className = trim($_POST['class_name'] ?? '');
            if ($courseName === '' || $className === '') {
                throw new \InvalidArgumentException('Course name and class are required.');
            }
            Timetable::createCourse($_POST);
            AdminAuth::redirect('/pages/admin/timetable.php', 'Course assigned successfully.');
        }

        if ($action === 'create_entry') {
            $courseId = (int) ($_POST['course_id'] ?? 0);
            $className = trim($_POST['class_name'] ?? '');
            $day = $_POST['day_of_week'] ?? '';
            $start = $_POST['start_time'] ?? '';
            $end = $_POST['end_time'] ?? '';
            if ($courseId <= 0 || $className === '' || !in_array($day, Timetable::DAYS, true) || $start === '' || $end === '') {
                throw new \InvalidArgumentException('Course, class, day, start time, and end time are required.');
            }
            if ($start >= $end) {
                throw new \InvalidArgumentException('End time must be after start time.');
            }
            Timetable::createEntry($_POST);
            AdminAuth::redirect('/pages/admin/timetable.php', 'Lecture scheduled successfully.');
        }

        if ($action === 'delete_entry') {
            $id = (int) ($_POST['id'] ?? 0);
            if ($id > 0) {
                Utility::safeQuery('DELETE FROM timetable_entries WHERE id = ?', [$id], 'DELETE');
            }
            AdminAuth::redirect('/pages/admin/timetable.php', 'Lecture removed.');
        }
    } catch (\Throwable $e) {
        $adminError = $e->getMessage();
    }
}

$teachers = [];
$classes = [];
try {
    $teachers = Utility::safeQuery('SELECT id, name, assigned_class FROM teachers ORDER BY name ASC', [], 'SELECT');
} catch (\Throwable $e) {
    $teachers = [];
}
try {
    $classes = Utility::safeQuery(
        'SELECT DISTINCT student_class FROM students WHERE student_class IS NOT NULL AND student_class != ? ORDER BY student_class ASC',
        [''],
        'SELECT'
    );
} catch (\Throwable $e) {
    $classes = [];
}

$courses = Timetable::courses();
$entries = Timetable::entries();
$pageTitle = 'Timetable';
$pageHeading = 'Timetable management';
$pageSubtitle = 'Assign courses, lectures, rooms, and class times';

require __DIR__ . '/../../components/admin/shell-start.php';
?>

<div class="grid lg:grid-cols-2 gap-6 mb-6">
    <form method="post" class="admin-card p-6 space-y-4">
        <input type="hidden" name="action" value="create_course">
        <h2 class="font-bold text-slate-900">Assign Course</h2>
        <div>
            <label class="block text-sm font-semibold text-slate-700 mb-1">Course name</label>
            <input name="course_name" required class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm" placeholder="Mathematics">
        </div>
        <div>
            <label class="block text-sm font-semibold text-slate-700 mb-1">Course code</label>
            <input name="course_code" class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm" placeholder="MATH101">
        </div>
        <div>
            <label class="block text-sm font-semibold text-slate-700 mb-1">Class</label>
            <input name="class_name" list="class-list" required class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm" placeholder="Grade 10A">
        </div>
        <div>
            <label class="block text-sm font-semibold text-slate-700 mb-1">Lecturer</label>
            <select name="teacher_id" class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm">
                <option value="">Unassigned</option>
                <?php foreach ($teachers as $teacher): ?>
                    <option value="<?= (int) $teacher['id'] ?>"><?= htmlspecialchars($teacher['name'] . ' - ' . $teacher['assigned_class']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <button type="submit" class="bg-emerald-600 hover:bg-emerald-700 text-white font-semibold px-4 py-2 rounded-lg text-sm">
            Save course
        </button>
    </form>

    <form method="post" class="admin-card p-6 space-y-4">
        <input type="hidden" name="action" value="create_entry">
        <h2 class="font-bold text-slate-900">Schedule Lecture</h2>
        <div>
            <label class="block text-sm font-semibold text-slate-700 mb-1">Course</label>
            <select name="course_id" required class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm">
                <option value="">Select course</option>
                <?php foreach ($courses as $course): ?>
                    <option value="<?= (int) $course['id'] ?>">
                        <?= htmlspecialchars($course['class_name'] . ' - ' . $course['course_name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="grid sm:grid-cols-2 gap-3">
            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-1">Class</label>
                <input name="class_name" list="class-list" required class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm" placeholder="Grade 10A">
            </div>
            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-1">Lecturer</label>
                <select name="teacher_id" class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm">
                    <option value="">Unassigned</option>
                    <?php foreach ($teachers as $teacher): ?>
                        <option value="<?= (int) $teacher['id'] ?>"><?= htmlspecialchars($teacher['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div>
            <label class="block text-sm font-semibold text-slate-700 mb-1">Lecture title</label>
            <input name="lecture_title" class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm" placeholder="Algebra introduction">
        </div>
        <div class="grid sm:grid-cols-4 gap-3">
            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-1">Day</label>
                <select name="day_of_week" required class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm">
                    <?php foreach (Timetable::DAYS as $day): ?>
                        <option value="<?= htmlspecialchars($day) ?>"><?= htmlspecialchars($day) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-1">Start</label>
                <input name="start_time" type="time" required class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm">
            </div>
            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-1">End</label>
                <input name="end_time" type="time" required class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm">
            </div>
            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-1">Room</label>
                <input name="room" class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm" placeholder="Room 101">
            </div>
        </div>
        <button type="submit" class="bg-emerald-600 hover:bg-emerald-700 text-white font-semibold px-4 py-2 rounded-lg text-sm">
            Schedule lecture
        </button>
    </form>
</div>

<datalist id="class-list">
    <?php foreach ($classes as $class): ?>
        <option value="<?= htmlspecialchars($class['student_class']) ?>"></option>
    <?php endforeach; ?>
</datalist>

<div class="admin-card overflow-hidden">
    <div class="px-6 py-4 border-b border-slate-200">
        <h2 class="font-bold text-slate-900">Scheduled lectures</h2>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-left text-sm">
            <thead class="bg-slate-50 border-b border-slate-200">
                <tr>
                    <th class="px-4 py-3 font-semibold">Class</th>
                    <th class="px-4 py-3 font-semibold">Course</th>
                    <th class="px-4 py-3 font-semibold">Lecturer</th>
                    <th class="px-4 py-3 font-semibold">Day</th>
                    <th class="px-4 py-3 font-semibold">Time</th>
                    <th class="px-4 py-3 font-semibold">Room</th>
                    <th class="px-4 py-3 font-semibold">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                <?php if (empty($entries)): ?>
                    <tr><td colspan="7" class="px-4 py-8 text-slate-500">No timetable entries yet.</td></tr>
                <?php else: ?>
                    <?php foreach ($entries as $entry): ?>
                        <tr class="hover:bg-slate-50">
                            <td class="px-4 py-3"><?= htmlspecialchars($entry['class_name']) ?></td>
                            <td class="px-4 py-3 font-semibold"><?= htmlspecialchars($entry['course_name']) ?></td>
                            <td class="px-4 py-3"><?= htmlspecialchars($entry['teacher_name'] ?? 'Unassigned') ?></td>
                            <td class="px-4 py-3"><?= htmlspecialchars($entry['day_of_week']) ?></td>
                            <td class="px-4 py-3"><?= htmlspecialchars($entry['start_time'] . ' - ' . $entry['end_time']) ?></td>
                            <td class="px-4 py-3"><?= htmlspecialchars($entry['room'] ?? '') ?></td>
                            <td class="px-4 py-3">
                                <form method="post">
                                    <input type="hidden" name="action" value="delete_entry">
                                    <input type="hidden" name="id" value="<?= (int) $entry['id'] ?>">
                                    <button type="submit" class="px-2 py-1 text-xs font-semibold bg-red-600 text-white rounded hover:bg-red-700">Delete</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require __DIR__ . '/../../components/admin/shell-end.php'; ?>
