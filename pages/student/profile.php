<?php
// pages/student/profile.php
// Student Profile Page

require_once __DIR__ . '/../../config/bootstrap.php';
require_once __DIR__ . '/../../utils/Utility.php';

session_start();

// Check if student is logged in
if (!isset($_SESSION['student_id'])) {
    header('Location: /pages/student/login.php');
    exit;
}

$studentId = $_SESSION['student_id'];

// Fetch student data
try {
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
                $destDir = __DIR__ . '/../../assets/profile_photos/';
                if (!is_dir($destDir)) mkdir($destDir, 0777, true);
                $filename = 'stu_' . $studentId . '_' . time() . '.' . $ext;
                $destPath = $destDir . $filename;
                if (move_uploaded_file($_FILES['profile_photo']['tmp_name'], $destPath)) {
                    $fields['profile_photo'] = '/assets/profile_photos/' . $filename;
                }
            }
        }
        try {
            Utility::update('students', $studentId, $fields);
            $profileMsg = 'Profile updated successfully!';
        } catch (\Throwable $e) {
            $profileMsg = 'Error updating profile: ' . $e->getMessage();
        }
    }

    // Handle password change
    $pwMsg = null;
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
        $current = $_POST['current_password'] ?? '';
        $new = $_POST['new_password'] ?? '';
        $confirm = $_POST['confirm_password'] ?? '';
        try {
            $row = Utility::safeQuery('SELECT password FROM students WHERE id = ? LIMIT 1', [$studentId], 'SELECT', true);
            if (!$row || !password_verify($current, $row['password'])) {
                $pwMsg = 'Current password is incorrect.';
            } elseif (strlen($new) < 8) {
                $pwMsg = 'New password must be at least 8 characters.';
            } elseif ($new !== $confirm) {
                $pwMsg = 'Passwords do not match.';
            } else {
                $hash = password_hash($new, PASSWORD_BCRYPT);
                Utility::safeQuery('UPDATE students SET password = ? WHERE id = ?', [$hash, $studentId], 'UPDATE');
                $pwMsg = 'Password updated successfully!';
            }
        } catch (\Throwable $e) {
            $pwMsg = 'Error updating password: ' . $e->getMessage();
        }
    }

    // Fetch student data (with all fields)
    try {
        $student = Utility::safeQuery(
            'SELECT id, name, email, admission_number, student_class, phone, profile_photo FROM students WHERE id = ? LIMIT 1',
            [$studentId],
            'SELECT',
            true
        );
        if (!$student) {
            session_destroy();
            header('Location: /pages/student/login.php');
            exit;
        }
    } catch (\Throwable $e) {
        error_log('Profile student fetch error: ' . $e->getMessage());
        $student = ['name' => 'Student', 'email' => 'student@school.edu', 'admission_number' => 'STU' . $studentId, 'student_class' => '', 'phone' => '', 'profile_photo' => ''];
    }
?>
<!DOCTYPE html>
<html class="light" lang="en">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>Student Profile | EduAttend</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #f7f9fb; }
        .material-symbols-outlined { font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24; }
    </style>
</head>
<body class="pb-24 min-h-screen">
    <!-- TopAppBar -->
    <header class="bg-white border-b border-gray-200 sticky top-0 z-40 flex items-center justify-between px-6 w-full h-16">
        <div class="flex items-center gap-4">
            <a href="/pages/student/dashboard.php" class="active:scale-95 transition-transform duration-150 p-2">
                <span class="material-symbols-outlined text-green-700">arrow_back</span>
            </a>
            <h1 class="text-xl font-bold text-gray-900">Profile</h1>
        </div>
    </header>

    <main class="max-w-2xl mx-auto px-4 py-8">
        <!-- Profile Card -->
        <div class="bg-white border border-gray-200 rounded-xl p-8 shadow-sm mb-6">
            <?php if ($profileMsg): ?>
                <div class="mb-4 p-3 rounded bg-green-50 text-green-800 border border-green-200 text-center"><?php echo htmlspecialchars($profileMsg); ?></div>
            <?php endif; ?>
            <form method="POST" enctype="multipart/form-data" class="space-y-6">
                <input type="hidden" name="update_profile" value="1" />
                <div class="flex flex-col items-center mb-8">
                    <label for="profile_photo" class="relative group cursor-pointer">
                        <img alt="Profile" class="w-24 h-24 rounded-full border-4 border-green-700 mb-2 object-cover" src="<?php echo $student['profile_photo'] ? htmlspecialchars($student['profile_photo']) : 'https://lh3.googleusercontent.com/aida-public/AB6AXuDCuY7MQ5x2T79-rbXXtxabauDVlUeDo1LUhKJsNO8LKGLRJt523VnEih4qdyArQFulhz24pVYe7BvAgt3BYCYf5DYjxP4p2onMksH21MF6v6B9jqs0UaCpQcbow3nUzd-o5ZHZdZGOSUT6uPqF9GF8BkGvf2MyxTxudTiDMI981Wh6bm16_yZK2gO_CGjY0_4zb2Q9M_vRM1KDe6UisVW2ajDTJilj6qKfvXiwoW6nckbbiJUcsM7kqE-dLtCr-BDRIWRRzdwRVfHR'; ?>"/>
                        <span class="absolute bottom-2 right-2 bg-green-700 text-white p-2 rounded-full shadow-lg transition-transform hover:scale-110 flex items-center justify-center">
                            <span class="material-symbols-outlined text-sm">edit</span>
                        </span>
                        <input type="file" name="profile_photo" id="profile_photo" accept="image/*" class="hidden" />
                    </label>
                    <h2 class="text-2xl font-bold text-gray-900 mt-2">
                        <input type="text" name="name" value="<?php echo htmlspecialchars($student['name']); ?>" class="text-center font-bold w-full bg-transparent border-none focus:ring-0" required />
                    </h2>
                    <p class="text-sm text-gray-500">Admission Number: <?php echo htmlspecialchars($student['admission_number']); ?></p>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="text-xs uppercase font-bold text-gray-500">Email</label>
                        <input type="email" name="email" value="<?php echo htmlspecialchars($student['email']); ?>" class="w-full border border-gray-200 rounded-lg px-3 py-2 mt-1" required />
                    </div>
                    <div>
                        <label class="text-xs uppercase font-bold text-gray-500">Phone</label>
                        <input type="tel" name="phone" value="<?php echo htmlspecialchars($student['phone'] ?? ''); ?>" class="w-full border border-gray-200 rounded-lg px-3 py-2 mt-1" />
                    </div>
                    <div>
                        <label class="text-xs uppercase font-bold text-gray-500">Class</label>
                        <input type="text" name="student_class" value="<?php echo htmlspecialchars($student['student_class'] ?? ''); ?>" class="w-full border border-gray-200 rounded-lg px-3 py-2 mt-1" />
                    </div>
                </div>
                <div class="pt-4">
                    <button type="submit" class="w-full md:w-auto px-8 py-3 bg-green-700 text-white rounded-lg font-bold transition-all hover:brightness-110 active:scale-95 flex items-center justify-center gap-2">
                        <span class="material-symbols-outlined text-sm">save</span> Save Changes
                    </button>
                </div>
            </form>
        </div>

        <!-- Password Change Section -->
        <div class="bg-white border border-gray-200 rounded-xl overflow-hidden shadow-sm mt-6">
            <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                <h3 class="text-sm font-bold text-gray-900">Change Password</h3>
            </div>
            <form method="POST" class="p-6 space-y-4">
                <input type="hidden" name="change_password" value="1" />
                <?php if ($pwMsg): ?>
                    <div class="mb-2 p-2 rounded bg-blue-50 text-blue-800 border border-blue-200 text-center"><?php echo htmlspecialchars($pwMsg); ?></div>
                <?php endif; ?>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="text-xs uppercase font-bold text-gray-500">Current Password</label>
                        <input type="password" name="current_password" class="w-full border border-gray-200 rounded-lg px-3 py-2 mt-1" required />
                    </div>
                    <div>
                        <label class="text-xs uppercase font-bold text-gray-500">New Password</label>
                        <input type="password" name="new_password" class="w-full border border-gray-200 rounded-lg px-3 py-2 mt-1" required />
                    </div>
                    <div>
                        <label class="text-xs uppercase font-bold text-gray-500">Confirm New Password</label>
                        <input type="password" name="confirm_password" class="w-full border border-gray-200 rounded-lg px-3 py-2 mt-1" required />
                    </div>
                </div>
                <div class="pt-2">
                    <button type="submit" class="w-full md:w-auto px-8 py-3 border border-green-700 text-green-700 hover:bg-green-50 rounded-lg font-bold transition-all active:scale-95 flex items-center justify-center gap-2">
                        <span class="material-symbols-outlined text-sm">update</span> Update Password
                    </button>
                </div>
            </form>
        </div>

        <!-- Logout Button -->
        <div class="mt-6">
            <a href="/pages/student/logout.php" class="block w-full bg-red-600 text-white py-3 rounded-xl font-bold text-center hover:bg-red-700 transition-colors">
                Logout
            </a>
        </div>
    </main>

    <!-- BottomNavBar -->
    <nav class="fixed bottom-0 left-0 w-full flex justify-around items-center px-4 py-2 bg-white border-t border-gray-200 shadow-lg z-50">
        <a class="flex flex-col items-center justify-center text-gray-500 px-4 py-1.5 hover:bg-gray-50 transition-all active:scale-95 duration-200" href="/pages/student/dashboard.php">
            <span class="material-symbols-outlined">dashboard</span>
            <span class="text-[10px] font-bold mt-1">Dashboard</span>
        </a>
        <a class="flex flex-col items-center justify-center text-gray-500 px-4 py-1.5 hover:bg-gray-50 transition-all active:scale-95 duration-200" href="/pages/student/attendance.php">
            <span class="material-symbols-outlined">calendar_today</span>
            <span class="text-[10px] font-bold mt-1">Attendance</span>
        </a>
        <a class="flex flex-col items-center justify-center text-gray-500 px-4 py-1.5 hover:bg-gray-50 transition-all active:scale-95 duration-200" href="/pages/student/qr-attendance.php">
            <span class="material-symbols-outlined">qr_code_2</span>
            <span class="text-[10px] font-bold mt-1">QR Scan</span>
        </a>
        <a class="flex flex-col items-center justify-center text-gray-500 px-4 py-1.5 hover:bg-gray-50 transition-all active:scale-95 duration-200" href="/pages/student/report.php">
            <span class="material-symbols-outlined">description</span>
            <span class="text-[10px] font-bold mt-1">Report</span>
        </a>
        <a class="flex flex-col items-center justify-center bg-green-700 text-white rounded-xl px-4 py-1.5 active:scale-95 transition-all duration-200" href="/pages/student/profile.php">
            <span class="material-symbols-outlined" style="font-variation-settings: 'FILL' 1;">person</span>
            <span class="text-[10px] font-bold mt-1">Profile</span>
        </a>
    </nav>
</body>
</html>
