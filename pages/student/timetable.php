<?php
// pages/student/timetable.php
// Student Timetable View

require_once __DIR__ . '/../../config/bootstrap.php';

use StudentAttendance\Utils\StudentAuth;
use StudentAttendance\Utils\Timetable;

StudentAuth::require();
$studentId = StudentAuth::studentId();
$student = StudentAuth::getStudent($studentId);
$studentClass = trim((string) ($student['student_class'] ?? ''));

Timetable::ensureSchema();
$entries = $studentClass !== '' ? Timetable::entries($studentClass) : [];
$grouped = Timetable::groupByDay($entries);

$pageTitle = 'Timetable';
$pageHeading = 'Class Timetable';
$activeNav = 'timetable';

require __DIR__ . '/../../components/student/layout-start.php';
?>

<div class="space-y-6">
    <div class="bg-white rounded-lg border border-slate-200 p-6 shadow-sm">
        <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
            <div>
                <h2 class="text-xl font-bold text-slate-900">Weekly Timetable</h2>
                <p class="text-sm text-slate-500 mt-1">
                    <?= $studentClass !== '' ? htmlspecialchars($studentClass) : 'No class assigned' ?>
                </p>
            </div>
            <a href="/pages/student/courses.php" class="text-sm font-semibold text-emerald-700 hover:underline">View courses</a>
        </div>

        <?php if ($studentClass === ''): ?>
            <div class="p-4 rounded-lg border border-amber-200 bg-amber-50 text-sm text-amber-900">
                Your profile does not have a class assigned yet. Update your profile or contact administration.
            </div>
        <?php elseif (empty($entries)): ?>
            <div class="p-4 rounded-lg border border-slate-200 bg-slate-50 text-sm text-slate-600">
                No timetable has been published for your class yet.
            </div>
        <?php else: ?>
            <div class="grid grid-cols-1 lg:grid-cols-5 gap-4">
                <?php foreach (Timetable::DAYS as $day): ?>
                    <div class="border border-slate-200 rounded-lg overflow-hidden bg-slate-50">
                        <div class="px-4 py-3 bg-white border-b border-slate-200">
                            <h3 class="font-bold text-slate-900"><?= htmlspecialchars($day) ?></h3>
                        </div>
                        <div class="p-3 space-y-3">
                            <?php if (empty($grouped[$day])): ?>
                                <p class="text-sm text-slate-500">No classes</p>
                            <?php else: ?>
                                <?php foreach ($grouped[$day] as $entry): ?>
                                    <div class="bg-white border border-emerald-100 rounded-lg p-3">
                                        <p class="text-xs font-semibold text-emerald-700">
                                            <?= htmlspecialchars($entry['start_time'] . ' - ' . $entry['end_time']) ?>
                                        </p>
                                        <p class="font-bold text-slate-900 mt-1"><?= htmlspecialchars($entry['course_name']) ?></p>
                                        <?php if (!empty($entry['lecture_title'])): ?>
                                            <p class="text-sm text-slate-600"><?= htmlspecialchars($entry['lecture_title']) ?></p>
                                        <?php endif; ?>
                                        <div class="mt-2 text-xs text-slate-500 space-y-1">
                                            <p>Lecturer: <?= htmlspecialchars($entry['teacher_name'] ?? 'Unassigned') ?></p>
                                            <p>Room: <?= htmlspecialchars($entry['room'] ?? 'TBA') ?></p>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <div class="bg-white rounded-lg border border-slate-200 p-6 shadow-sm">
        <h3 class="font-bold text-slate-900 mb-4">Assigned Lecturers</h3>
        <?php if (empty($entries)): ?>
            <p class="text-sm text-slate-500">Lecturer details will appear once courses are assigned.</p>
        <?php else: ?>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <?php foreach ($entries as $entry): ?>
                    <div class="p-4 bg-slate-50 rounded-lg border border-slate-200">
                        <p class="font-semibold text-slate-900"><?= htmlspecialchars($entry['course_name']) ?></p>
                        <p class="text-sm text-slate-600 mt-1"><?= htmlspecialchars($entry['teacher_name'] ?? 'Unassigned') ?></p>
                        <p class="text-xs text-slate-500 mt-2"><?= htmlspecialchars($entry['day_of_week'] . ', ' . $entry['start_time'] . ' - ' . $entry['end_time']) ?></p>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require __DIR__ . '/../../components/student/layout-end.php'; ?>
