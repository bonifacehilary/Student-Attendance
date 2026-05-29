<?php
require_once __DIR__ . '/../../config/bootstrap.php';

use StudentAttendance\Utils\TeacherAuth;
use StudentAttendance\Utils\Timetable;

$assignedClass = TeacherAuth::requireClass();
$teacherId = TeacherAuth::teacherId();
Timetable::ensureSchema();

$teacherError = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $entryId = (int) ($_POST['entry_id'] ?? 0);
        if ($entryId <= 0) {
            throw new \InvalidArgumentException('Invalid timetable entry.');
        }
        if (($_POST['start_time'] ?? '') >= ($_POST['end_time'] ?? '')) {
            throw new \InvalidArgumentException('End time must be after start time.');
        }
        Timetable::updateEntry($entryId, $_POST, $teacherId);
        TeacherAuth::redirect('/pages/teacher/timetable.php', 'Class timetable updated.');
    } catch (\Throwable $e) {
        $teacherError = $e->getMessage();
    }
}

$entries = Timetable::entries(null, $teacherId);
$pageTitle = 'My Timetable';
$pageHeading = 'My timetable';
$pageSubtitle = 'Edit lectures assigned to you';

require __DIR__ . '/../../components/teacher/shell-start.php';
?>

<div class="admin-card overflow-hidden">
    <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between">
        <div>
            <h2 class="font-bold text-slate-900">Assigned classes</h2>
            <p class="text-sm text-slate-500">Class scope: <?= htmlspecialchars($assignedClass) ?></p>
        </div>
        <a href="/pages/teacher/students.php" class="text-sm font-medium text-sky-700 hover:underline">View students</a>
    </div>

    <?php if (empty($entries)): ?>
        <p class="px-6 py-8 text-slate-500 text-sm">No lectures have been assigned to your account yet.</p>
    <?php else: ?>
        <div class="divide-y divide-slate-100">
            <?php foreach ($entries as $entry): ?>
                <form method="post" class="p-6 grid lg:grid-cols-12 gap-3 items-end hover:bg-slate-50">
                    <input type="hidden" name="entry_id" value="<?= (int) $entry['id'] ?>">
                    <div class="lg:col-span-2">
                        <label class="block text-xs font-semibold text-slate-600 mb-1">Class</label>
                        <p class="text-sm font-semibold text-slate-900"><?= htmlspecialchars($entry['class_name']) ?></p>
                        <p class="text-xs text-slate-500"><?= htmlspecialchars($entry['course_name']) ?></p>
                    </div>
                    <div class="lg:col-span-3">
                        <label class="block text-xs font-semibold text-slate-600 mb-1">Lecture title</label>
                        <input name="lecture_title" value="<?= htmlspecialchars($entry['lecture_title'] ?? '') ?>"
                               class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm">
                    </div>
                    <div class="lg:col-span-2">
                        <label class="block text-xs font-semibold text-slate-600 mb-1">Day</label>
                        <select name="day_of_week" class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm">
                            <?php foreach (Timetable::DAYS as $day): ?>
                                <option value="<?= htmlspecialchars($day) ?>" <?= $entry['day_of_week'] === $day ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($day) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="lg:col-span-1">
                        <label class="block text-xs font-semibold text-slate-600 mb-1">Start</label>
                        <input name="start_time" type="time" required value="<?= htmlspecialchars($entry['start_time']) ?>"
                               class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm">
                    </div>
                    <div class="lg:col-span-1">
                        <label class="block text-xs font-semibold text-slate-600 mb-1">End</label>
                        <input name="end_time" type="time" required value="<?= htmlspecialchars($entry['end_time']) ?>"
                               class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm">
                    </div>
                    <div class="lg:col-span-2">
                        <label class="block text-xs font-semibold text-slate-600 mb-1">Room</label>
                        <input name="room" value="<?= htmlspecialchars($entry['room'] ?? '') ?>"
                               class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm">
                    </div>
                    <div class="lg:col-span-1">
                        <button type="submit" class="w-full bg-sky-600 hover:bg-sky-700 text-white font-semibold px-3 py-2 rounded-lg text-sm">
                            Save
                        </button>
                    </div>
                </form>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/../../components/teacher/shell-end.php'; ?>
