<?php
// Admin - users, roles, passwords, and account status

require_once __DIR__ . '/../../config/bootstrap.php';

use StudentAttendance\Utils\AdminAuth;
use StudentAttendance\Utils\UserManagement;

AdminAuth::require();
UserManagement::ensureSchema();

$adminError = '';
$searchQ = trim($_GET['q'] ?? '');
$editType = $_GET['type'] ?? '';
$editId = (int) ($_GET['id'] ?? 0);
$formValues = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $type = $_POST['user_type'] ?? '';
    $id = (int) ($_POST['id'] ?? 0);

    try {
        if ($action === 'create_user') {
            UserManagement::createUser($_POST);
            AdminAuth::redirect('/pages/admin/users.php', 'User created successfully.');
        }
        if ($action === 'update_user' && $id > 0) {
            UserManagement::updateUser($type, $id, $_POST);
            AdminAuth::redirect('/pages/admin/users.php', 'User updated successfully.');
        }
        if ($action === 'set_active' && $id > 0) {
            UserManagement::setActive($type, $id, ($_POST['active'] ?? '0') === '1');
            AdminAuth::redirect('/pages/admin/users.php', 'Account status updated.');
        }
        if ($action === 'reset_password' && $id > 0) {
            UserManagement::resetPassword($type, $id, (string) ($_POST['password'] ?? ''));
            AdminAuth::redirect('/pages/admin/users.php', 'Password reset successfully.');
        }
    } catch (\Throwable $e) {
        $adminError = $e->getMessage();
        $formValues = $_POST;
    }
}

$allUsers = UserManagement::allUsers();
$departments = array_values(array_filter(
    UserManagement::departments(),
    static fn(array $department): bool => !empty($department['is_active'])
));
$classes = array_values(array_filter(
    UserManagement::classes(),
    static fn(array $class): bool => !empty($class['is_active'])
));

$editUser = null;
foreach ($allUsers as $candidate) {
    if ($candidate['user_type'] === $editType && (int) $candidate['id'] === $editId) {
        $editUser = $candidate;
        break;
    }
}
$users = AdminAuth::filterRows($allUsers, $searchQ, ['name', 'email', 'role', 'user_type', 'assigned_class', 'department_name', 'admission_number']);
$formSource = $formValues ?: ($editUser ?: []);
$selectedType = $editUser['user_type'] ?? ($formSource['user_type'] ?? 'student');

$pageTitle = 'Users & Roles';
$pageHeading = 'Users & roles';
$pageSubtitle = 'Create accounts, assign roles, reset passwords, and manage access';

require __DIR__ . '/../../components/admin/shell-start.php';
?>

<div class="grid xl:grid-cols-[minmax(20rem,24rem)_1fr] gap-6">
    <section class="admin-card p-5">
        <h2 class="text-base font-bold text-slate-900 mb-4"><?= $editUser ? 'Edit user' : 'Add user' ?></h2>
        <form method="post" class="space-y-4">
            <input type="hidden" name="action" value="<?= $editUser ? 'update_user' : 'create_user' ?>">
            <?php if ($editUser): ?>
                <input type="hidden" name="id" value="<?= (int) $editUser['id'] ?>">
                <input type="hidden" name="user_type" value="<?= htmlspecialchars($editUser['user_type']) ?>">
            <?php endif; ?>

            <div>
                <label class="block text-xs font-semibold text-slate-600 mb-1">Role</label>
                <?php if ($editUser): ?>
                    <p class="rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm font-semibold capitalize">
                        <?= htmlspecialchars($editUser['user_type']) ?>
                    </p>
                <?php else: ?>
                    <select id="userType" name="user_type" class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm" required>
                        <option value="student" <?= $selectedType === 'student' ? 'selected' : '' ?>>Student</option>
                        <option value="teacher" <?= $selectedType === 'teacher' ? 'selected' : '' ?>>Teacher / Lecturer</option>
                        <option value="admin" <?= $selectedType === 'admin' ? 'selected' : '' ?>>Admin</option>
                    </select>
                <?php endif; ?>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-600 mb-1">Name</label>
                <input name="name" value="<?= htmlspecialchars($formSource['name'] ?? '') ?>" class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm" required>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-600 mb-1">Email</label>
                <input type="email" name="email" value="<?= htmlspecialchars($formSource['email'] ?? '') ?>" class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm" required>
            </div>

            <div data-user-field="student">
                <label class="block text-xs font-semibold text-slate-600 mb-1">Admission number</label>
                <input name="admission_number" value="<?= htmlspecialchars($formSource['admission_number'] ?? '') ?>" class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm" placeholder="Students only">
            </div>

            <div data-user-field="student teacher">
                <label class="block text-xs font-semibold text-slate-600 mb-1">Department</label>
                <select name="department_id" class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm">
                    <option value="">Unassigned</option>
                    <?php foreach ($departments as $department): ?>
                        <option value="<?= (int) $department['id'] ?>" <?= (int) ($formSource['department_id'] ?? 0) === (int) $department['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($department['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div data-user-field="student teacher">
                <label class="block text-xs font-semibold text-slate-600 mb-1">Class</label>
                <select name="class_id" class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm">
                    <option value="">Unassigned</option>
                    <?php foreach ($classes as $class): ?>
                        <option value="<?= (int) $class['id'] ?>" <?= (int) ($formSource['class_id'] ?? 0) === (int) $class['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($class['name'] . (!empty($class['department_name']) ? ' - ' . $class['department_name'] : '')) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <input name="assigned_class" value="<?= htmlspecialchars($formSource['assigned_class'] ?? '') ?>" class="mt-2 w-full border border-slate-200 rounded-lg px-3 py-2 text-sm" placeholder="Or type class name">
            </div>

            <?php if (!$editUser): ?>
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1">Password</label>
                    <input type="password" name="password" class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm" minlength="6" required>
                </div>
            <?php endif; ?>

            <div class="flex flex-wrap gap-2">
                <button type="submit" class="bg-emerald-600 hover:bg-emerald-700 text-white font-semibold px-4 py-2 rounded-lg text-sm">
                    <?= $editUser ? 'Save changes' : 'Create user' ?>
                </button>
                <?php if ($editUser): ?>
                    <a href="/pages/admin/users.php" class="px-4 py-2 rounded-lg text-sm font-semibold border border-slate-200 text-slate-700">Cancel</a>
                <?php endif; ?>
            </div>
        </form>
    </section>

    <section>
        <form method="get" class="admin-card p-4 mb-4 flex flex-wrap items-end gap-4">
            <div class="flex-1 min-w-[12rem]">
                <label class="block text-xs font-semibold text-slate-600 mb-1">Search</label>
                <input type="search" name="q" value="<?= htmlspecialchars($searchQ) ?>" placeholder="Name, role, email, class..." class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm">
            </div>
            <button type="submit" class="bg-slate-800 hover:bg-slate-900 text-white font-semibold px-4 py-2 rounded-lg text-sm">Search</button>
            <?php if ($searchQ !== ''): ?>
                <a href="/pages/admin/users.php" class="text-sm text-slate-600 hover:underline py-2">Clear</a>
            <?php endif; ?>
        </form>

        <div class="admin-card overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-slate-50 border-b border-slate-200">
                        <tr>
                            <th class="text-left px-5 py-3 font-semibold">User</th>
                            <th class="text-left px-5 py-3 font-semibold">Role</th>
                            <th class="text-left px-5 py-3 font-semibold">Department / class</th>
                            <th class="text-left px-5 py-3 font-semibold">Status</th>
                            <th class="text-left px-5 py-3 font-semibold">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <?php if (empty($users)): ?>
                            <tr><td colspan="5" class="px-5 py-8 text-slate-500">No users found.</td></tr>
                        <?php endif; ?>
                        <?php foreach ($users as $user): ?>
                            <tr class="hover:bg-slate-50 align-top">
                                <td class="px-5 py-3">
                                    <p class="font-semibold text-slate-900"><?= htmlspecialchars($user['name']) ?></p>
                                    <p class="text-xs text-slate-500"><?= htmlspecialchars($user['email']) ?></p>
                                    <?php if (!empty($user['admission_number'])): ?>
                                        <p class="text-xs font-mono text-slate-500"><?= htmlspecialchars($user['admission_number']) ?></p>
                                    <?php endif; ?>
                                </td>
                                <td class="px-5 py-3 capitalize"><?= htmlspecialchars($user['user_type']) ?></td>
                                <td class="px-5 py-3 text-slate-600">
                                    <?= htmlspecialchars($user['department_name'] ?? 'Unassigned') ?><br>
                                    <span class="text-xs"><?= htmlspecialchars($user['assigned_class'] ?? 'No class') ?></span>
                                </td>
                                <td class="px-5 py-3">
                                    <span class="inline-flex px-2 py-0.5 rounded text-xs font-bold <?= !empty($user['is_active']) ? 'bg-emerald-100 text-emerald-800' : 'bg-red-100 text-red-800' ?>">
                                        <?= !empty($user['is_active']) ? 'Active' : 'Inactive' ?>
                                    </span>
                                </td>
                                <td class="px-5 py-3">
                                    <div class="flex flex-wrap gap-2">
                                        <a href="/pages/admin/users.php?type=<?= urlencode($user['user_type']) ?>&amp;id=<?= (int) $user['id'] ?>" class="px-3 py-1 rounded bg-slate-100 text-slate-800 font-semibold text-xs">Edit</a>
                                        <form method="post">
                                            <input type="hidden" name="action" value="set_active">
                                            <input type="hidden" name="user_type" value="<?= htmlspecialchars($user['user_type']) ?>">
                                            <input type="hidden" name="id" value="<?= (int) $user['id'] ?>">
                                            <input type="hidden" name="active" value="<?= !empty($user['is_active']) ? '0' : '1' ?>">
                                            <button class="px-3 py-1 rounded <?= !empty($user['is_active']) ? 'bg-red-100 text-red-800' : 'bg-emerald-100 text-emerald-800' ?> font-semibold text-xs" type="submit">
                                                <?= !empty($user['is_active']) ? 'Deactivate' : 'Activate' ?>
                                            </button>
                                        </form>
                                    </div>
                                    <form method="post" class="mt-2 flex gap-2">
                                        <input type="hidden" name="action" value="reset_password">
                                        <input type="hidden" name="user_type" value="<?= htmlspecialchars($user['user_type']) ?>">
                                        <input type="hidden" name="id" value="<?= (int) $user['id'] ?>">
                                        <input type="password" name="password" minlength="6" placeholder="New password" class="w-36 border border-slate-200 rounded px-2 py-1 text-xs" required>
                                        <button type="submit" class="px-3 py-1 rounded bg-blue-100 text-blue-800 font-semibold text-xs">Reset</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </section>
</div>

<script>
    (function () {
        const role = document.getElementById('userType');
        const fields = document.querySelectorAll('[data-user-field]');

        function syncUserFields() {
            const selected = role ? role.value : <?= json_encode($selectedType) ?>;
            fields.forEach(function (field) {
                const visible = field.dataset.userField.split(' ').includes(selected);
                field.classList.toggle('hidden', !visible);
                field.querySelectorAll('input, select, textarea').forEach(function (input) {
                    input.disabled = !visible;
                });
            });
        }

        role?.addEventListener('change', syncUserFields);
        syncUserFields();
    })();
</script>

<?php require __DIR__ . '/../../components/admin/shell-end.php'; ?>
