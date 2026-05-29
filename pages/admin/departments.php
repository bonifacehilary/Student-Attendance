<?php
// Admin - departments and classes

require_once __DIR__ . '/../../config/bootstrap.php';

use StudentAttendance\Utils\AdminAuth;
use StudentAttendance\Utils\UserManagement;

AdminAuth::require();
UserManagement::ensureSchema();

$adminError = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    try {
        if ($action === 'create_department') {
            UserManagement::saveDepartment($_POST);
            AdminAuth::redirect('/pages/admin/departments.php', 'Department added successfully.');
        }
        if ($action === 'create_class') {
            UserManagement::saveClass($_POST);
            AdminAuth::redirect('/pages/admin/departments.php', 'Class added successfully.');
        }
        if ($action === 'set_department_active') {
            UserManagement::setDepartmentActive((int) ($_POST['id'] ?? 0), ($_POST['active'] ?? '0') === '1');
            AdminAuth::redirect('/pages/admin/departments.php', 'Department status updated.');
        }
        if ($action === 'set_class_active') {
            UserManagement::setClassActive((int) ($_POST['id'] ?? 0), ($_POST['active'] ?? '0') === '1');
            AdminAuth::redirect('/pages/admin/departments.php', 'Class status updated.');
        }
    } catch (\Throwable $e) {
        $adminError = $e->getMessage();
    }
}

$departments = UserManagement::departments();
$classes = UserManagement::classes();

$pageTitle = 'Departments & Classes';
$pageHeading = 'Departments & classes';
$pageSubtitle = 'Create departments, add classes, and keep inactive options out of new assignments';

require __DIR__ . '/../../components/admin/shell-start.php';
?>

<div class="grid lg:grid-cols-2 gap-6 mb-6">
    <section class="admin-card p-5">
        <h2 class="text-base font-bold text-slate-900 mb-4">Add department</h2>
        <form method="post" class="space-y-4">
            <input type="hidden" name="action" value="create_department">
            <div>
                <label class="block text-xs font-semibold text-slate-600 mb-1">Name</label>
                <input name="name" class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm" required>
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-600 mb-1">Code</label>
                <input name="code" class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm" placeholder="SCI, ENG, BUS">
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-600 mb-1">Description</label>
                <textarea name="description" rows="3" class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm"></textarea>
            </div>
            <button type="submit" class="bg-emerald-600 hover:bg-emerald-700 text-white font-semibold px-4 py-2 rounded-lg text-sm">
                Add department
            </button>
        </form>
    </section>

    <section class="admin-card p-5">
        <h2 class="text-base font-bold text-slate-900 mb-4">Add class</h2>
        <form method="post" class="space-y-4">
            <input type="hidden" name="action" value="create_class">
            <div>
                <label class="block text-xs font-semibold text-slate-600 mb-1">Class name</label>
                <input name="name" class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm" required placeholder="Grade 10A, CS101, Year 2">
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-600 mb-1">Department</label>
                <select name="department_id" class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm">
                    <option value="">No department</option>
                    <?php foreach ($departments as $department): ?>
                        <?php if (!empty($department['is_active'])): ?>
                            <option value="<?= (int) $department['id'] ?>"><?= htmlspecialchars($department['name']) ?></option>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-600 mb-1">Description</label>
                <textarea name="description" rows="3" class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm"></textarea>
            </div>
            <button type="submit" class="bg-emerald-600 hover:bg-emerald-700 text-white font-semibold px-4 py-2 rounded-lg text-sm">
                Add class
            </button>
        </form>
    </section>
</div>

<div class="grid lg:grid-cols-2 gap-6">
    <section class="admin-card overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-200">
            <h2 class="font-bold text-slate-900">Departments</h2>
        </div>
        <table class="w-full text-sm">
            <thead class="bg-slate-50 border-b border-slate-200">
                <tr>
                    <th class="text-left px-5 py-3 font-semibold">Name</th>
                    <th class="text-left px-5 py-3 font-semibold">Code</th>
                    <th class="text-left px-5 py-3 font-semibold">Status</th>
                    <th class="text-left px-5 py-3 font-semibold">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                <?php if (empty($departments)): ?>
                    <tr><td colspan="4" class="px-5 py-8 text-slate-500">No departments yet.</td></tr>
                <?php endif; ?>
                <?php foreach ($departments as $department): ?>
                    <tr class="hover:bg-slate-50">
                        <td class="px-5 py-3 font-semibold"><?= htmlspecialchars($department['name']) ?></td>
                        <td class="px-5 py-3 text-slate-600"><?= htmlspecialchars($department['code'] ?? '') ?></td>
                        <td class="px-5 py-3">
                            <span class="inline-flex px-2 py-0.5 rounded text-xs font-bold <?= !empty($department['is_active']) ? 'bg-emerald-100 text-emerald-800' : 'bg-red-100 text-red-800' ?>">
                                <?= !empty($department['is_active']) ? 'Active' : 'Inactive' ?>
                            </span>
                        </td>
                        <td class="px-5 py-3">
                            <form method="post">
                                <input type="hidden" name="action" value="set_department_active">
                                <input type="hidden" name="id" value="<?= (int) $department['id'] ?>">
                                <input type="hidden" name="active" value="<?= !empty($department['is_active']) ? '0' : '1' ?>">
                                <button type="submit" class="px-3 py-1 rounded <?= !empty($department['is_active']) ? 'bg-red-100 text-red-800' : 'bg-emerald-100 text-emerald-800' ?> font-semibold text-xs">
                                    <?= !empty($department['is_active']) ? 'Deactivate' : 'Activate' ?>
                                </button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </section>

    <section class="admin-card overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-200">
            <h2 class="font-bold text-slate-900">Classes</h2>
        </div>
        <table class="w-full text-sm">
            <thead class="bg-slate-50 border-b border-slate-200">
                <tr>
                    <th class="text-left px-5 py-3 font-semibold">Class</th>
                    <th class="text-left px-5 py-3 font-semibold">Department</th>
                    <th class="text-left px-5 py-3 font-semibold">Status</th>
                    <th class="text-left px-5 py-3 font-semibold">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                <?php if (empty($classes)): ?>
                    <tr><td colspan="4" class="px-5 py-8 text-slate-500">No classes yet.</td></tr>
                <?php endif; ?>
                <?php foreach ($classes as $class): ?>
                    <tr class="hover:bg-slate-50">
                        <td class="px-5 py-3 font-semibold"><?= htmlspecialchars($class['name']) ?></td>
                        <td class="px-5 py-3 text-slate-600"><?= htmlspecialchars($class['department_name'] ?? 'Unassigned') ?></td>
                        <td class="px-5 py-3">
                            <span class="inline-flex px-2 py-0.5 rounded text-xs font-bold <?= !empty($class['is_active']) ? 'bg-emerald-100 text-emerald-800' : 'bg-red-100 text-red-800' ?>">
                                <?= !empty($class['is_active']) ? 'Active' : 'Inactive' ?>
                            </span>
                        </td>
                        <td class="px-5 py-3">
                            <form method="post">
                                <input type="hidden" name="action" value="set_class_active">
                                <input type="hidden" name="id" value="<?= (int) $class['id'] ?>">
                                <input type="hidden" name="active" value="<?= !empty($class['is_active']) ? '0' : '1' ?>">
                                <button type="submit" class="px-3 py-1 rounded <?= !empty($class['is_active']) ? 'bg-red-100 text-red-800' : 'bg-emerald-100 text-emerald-800' ?> font-semibold text-xs">
                                    <?= !empty($class['is_active']) ? 'Deactivate' : 'Activate' ?>
                                </button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </section>
</div>

<?php require __DIR__ . '/../../components/admin/shell-end.php'; ?>
