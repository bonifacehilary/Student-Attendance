<?php
// pages/student/profile.php
// Student Profile Page

require_once __DIR__ . '/../../config/bootstrap.php';

use StudentAttendance\Utils\StudentAuth;

StudentAuth::require();
$studentId = StudentAuth::studentId();

// Handle profile update
$profileMsg = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $fields = [
        'name' => trim($_POST['name'] ?? ''),
        'email' => trim($_POST['email'] ?? ''),
        'student_class' => trim($_POST['student_class'] ?? ''),
        'phone' => trim($_POST['phone'] ?? ''),
    ];
    // Handle photo upload
    if (isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] === UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($_FILES['profile_photo']['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'webp'];
        if (in_array($ext, $allowed)) {
            $destDir = \StudentAttendance\Utils\Assets::profilePhotoDir() . DIRECTORY_SEPARATOR;
            if (!is_dir($destDir)) mkdir($destDir, 0777, true);
            $filename = 'stu_' . $studentId . '_' . time() . '.' . $ext;
            $destPath = $destDir . $filename;
            if (move_uploaded_file($_FILES['profile_photo']['tmp_name'], $destPath)) {
                $fields['profile_photo'] = \StudentAttendance\Utils\Assets::profilePhotoUrl($filename);
            }
        }
    }
    try {
        Utility::update('students', $studentId, $fields);
        StudentAuth::setFlash('Profile updated successfully!', 'success');
    } catch (\Throwable $e) {
        StudentAuth::setFlash('Error updating profile: ' . $e->getMessage(), 'error');
    }
}

// Handle password change
$pwMsg = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $current = $_POST['current_password'] ?? '';
    $new = $_POST['new_password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';
    try {
        $row = Utility::safeQuery('SELECT password_hash FROM students WHERE id = ? LIMIT 1', [$studentId], 'SELECT', true);
        if (!$row || !password_verify($current, $row['password_hash'])) {
            $pwMsg = 'Current password is incorrect.';
        } elseif (strlen($new) < 8) {
            $pwMsg = 'New password must be at least 8 characters.';
        } elseif ($new !== $confirm) {
            $pwMsg = 'Passwords do not match.';
        } else {
            $hash = password_hash($new, PASSWORD_BCRYPT);
            Utility::safeQuery('UPDATE students SET password_hash = ? WHERE id = ?', [$hash, $studentId], 'UPDATE');
            $pwMsg = 'Password updated successfully!';
        }
    } catch (\Throwable $e) {
        $pwMsg = 'Error updating password: ' . $e->getMessage();
    }
}

// Fetch student data
$student = StudentAuth::getStudent($studentId);

$pageTitle = 'Profile';
$pageHeading = 'My Profile';
$activeNav = 'profile';
$showBack = true;

require __DIR__ . '/../../components/student/layout-start.php';
?>

<div class="bg-white border border-slate-200 rounded-xl p-6 shadow-sm mb-6">
    <h3 class="text-lg font-bold text-slate-900 mb-4">Profile Information</h3>
    <form method="POST" enctype="multipart/form-data" class="space-y-4">
        <input type="hidden" name="update_profile" value="1" />
        
        <?php if ($profileMsg): ?>
            <div class="p-3 rounded bg-emerald-50 text-emerald-800 border border-emerald-200 text-center"><?php echo htmlspecialchars($profileMsg); ?></div>
        <?php endif; ?>
        
        <div class="flex flex-col items-center mb-6">
            <label for="profile_photo" class="relative group cursor-pointer">
                <img alt="Profile" class="w-20 h-20 rounded-full border-4 border-emerald-700 mb-2 object-cover" src="<?php echo $student['profile_photo'] ? htmlspecialchars($student['profile_photo']) : 'https://lh3.googleusercontent.com/aida-public/AB6AXuDCuY7MQ5x2T79-rbXXtxabauDVlUeDo1LUhKJsNO8LKGLRJt523VnEih4qdyArQFulhz24pVYe7BvAgt3BYCYf5DYjxP4p2onMksH21MF6v6B9jqs0UaCpQcbow3nUzd-o5ZHZdZGOSUT6uPqF9GF8BkGvf2MyxTxudTiDMI981Wh6bm16_yZK2gO_CGjY0_4zb2Q9M_vRM1KDe6UisVW2ajDTJilj6qKfvXiwoW6nckbbiJUcsM7kqE-dLtCr-BDRIWRRzdwRVfHR'; ?>"/>
                <span class="absolute bottom-0 right-0 bg-emerald-700 text-white p-1.5 rounded-full shadow-lg flex items-center justify-center">
                    <span class="material-symbols-outlined text-sm">edit</span>
                </span>
                <input type="file" name="profile_photo" id="profile_photo" accept="image/*" class="hidden" />
            </label>
            <h2 class="text-xl font-bold text-slate-900 mt-2"><?php echo htmlspecialchars($student['name']); ?></h2>
            <p class="text-xs text-slate-500">ID: <?php echo htmlspecialchars($student['admission_number']); ?></p>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="text-xs font-semibold text-slate-600 mb-1 block">Name</label>
                <input type="text" name="name" value="<?php echo htmlspecialchars($student['name']); ?>" class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm" required />
            </div>
            <div>
                <label class="text-xs font-semibold text-slate-600 mb-1 block">Email</label>
                <input type="email" name="email" value="<?php echo htmlspecialchars($student['email']); ?>" class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm" required />
            </div>
            <div>
                <label class="text-xs font-semibold text-slate-600 mb-1 block">Class</label>
                <input type="text" name="student_class" value="<?php echo htmlspecialchars($student['student_class'] ?? ''); ?>" class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm" />
            </div>
            <div>
                <label class="text-xs font-semibold text-slate-600 mb-1 block">Phone</label>
                <input type="tel" name="phone" value="<?php echo htmlspecialchars($student['phone'] ?? ''); ?>" class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm" />
            </div>
        </div>

        <button type="submit" class="w-full bg-emerald-600 hover:bg-emerald-700 text-white font-bold py-2 px-4 rounded-lg text-sm flex items-center justify-center gap-2">
            <span class="material-symbols-outlined text-sm">save</span> Save Changes
        </button>
    </form>
</div>

<div class="bg-white border border-slate-200 rounded-xl overflow-hidden shadow-sm">
    <div class="px-6 py-3 border-b border-slate-200 bg-slate-100">
        <h3 class="text-sm font-bold text-slate-900">Change Password</h3>
    </div>
    <form method="POST" class="p-6 space-y-4">
        <input type="hidden" name="change_password" value="1" />
        <?php if ($pwMsg): ?>
            <div class="p-3 rounded bg-blue-50 text-blue-800 border border-blue-200 text-center text-sm"><?php echo htmlspecialchars($pwMsg); ?></div>
        <?php endif; ?>
        <div class="space-y-3">
            <div>
                <label class="text-xs font-semibold text-slate-600 mb-1 block">Current Password</label>
                <input type="password" name="current_password" class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm" required />
            </div>
            <div>
                <label class="text-xs font-semibold text-slate-600 mb-1 block">New Password</label>
                <input type="password" name="new_password" class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm" required />
            </div>
            <div>
                <label class="text-xs font-semibold text-slate-600 mb-1 block">Confirm Password</label>
                <input type="password" name="confirm_password" class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm" required />
            </div>
        </div>
        <button type="submit" class="w-full border border-emerald-700 text-emerald-700 hover:bg-emerald-50 font-bold py-2 px-4 rounded-lg text-sm flex items-center justify-center gap-2">
            <span class="material-symbols-outlined text-sm">update</span> Update Password
        </button>
    </form>
</div>

<div class="mt-6">
    <a href="/pages/student/logout.php" class="block w-full bg-red-600 text-white py-2 px-4 rounded-lg font-bold text-center hover:bg-red-700 text-sm">
        Logout
    </a>
</div><?php require __DIR__ . '/../../components/student/layout-end.php'; ?>
