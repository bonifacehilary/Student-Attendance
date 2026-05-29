<?php
// pages/student/settings.php
// Student Settings Page

require_once __DIR__ . '/../../config/bootstrap.php';

use StudentAttendance\Utils\StudentAuth;

StudentAuth::require();
$studentId = StudentAuth::studentId();
$student = StudentAuth::getStudent($studentId);

$pageTitle = 'Settings';
$pageHeading = 'Account Settings';
$activeNav = 'settings';

// Handle settings update
$settingMessage = '';
$settingError = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'update_email') {
        $newEmail = trim($_POST['email'] ?? '');
        if ($newEmail && $newEmail !== $student['email']) {
            try {
                // Check if email already exists
                $existing = \StudentAttendance\Utils\Utility::safeQuery(
                    'SELECT id FROM students WHERE email = ? AND id != ? LIMIT 1',
                    [$newEmail, $studentId],
                    'SELECT',
                    true
                );
                
                if (!$existing) {
                    \StudentAttendance\Utils\Utility::update('students', $studentId, ['email' => $newEmail]);
                    $settingMessage = 'Email updated successfully!';
                    $student['email'] = $newEmail;
                } else {
                    $settingError = 'This email is already in use.';
                }
            } catch (\Throwable $e) {
                $settingError = 'Failed to update email. Please try again.';
            }
        }
    }
}

require __DIR__ . '/../../components/student/layout-start.php';
?>

<div class="space-y-6">
    <!-- Account Information -->
    <div class="bg-white rounded-lg border border-slate-200 p-6 shadow-sm">
        <h2 class="text-xl font-bold text-slate-900 mb-6">Account Information</h2>
        
        <?php if ($settingMessage): ?>
            <div class="mb-4 p-3 bg-emerald-50 border border-emerald-200 rounded-lg text-emerald-700 text-sm">
                ✓ <?= htmlspecialchars($settingMessage) ?>
            </div>
        <?php endif; ?>
        
        <?php if ($settingError): ?>
            <div class="mb-4 p-3 bg-red-50 border border-red-200 rounded-lg text-red-700 text-sm">
                ✗ <?= htmlspecialchars($settingError) ?>
            </div>
        <?php endif; ?>
        
        <form method="post" class="space-y-4">
            <input type="hidden" name="action" value="update_email">
            
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-2">Full Name</label>
                <input type="text" value="<?= htmlspecialchars($student['name'] ?? '') ?>" disabled class="w-full px-3 py-2 border border-slate-300 rounded-lg bg-slate-50 text-slate-600">
                <p class="text-xs text-slate-500 mt-1">Contact your administrator to change your name</p>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-2">Admission Number</label>
                <input type="text" value="<?= htmlspecialchars($student['admission_number'] ?? '') ?>" disabled class="w-full px-3 py-2 border border-slate-300 rounded-lg bg-slate-50 text-slate-600">
            </div>
            
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-2">Email Address</label>
                <input type="email" name="email" value="<?= htmlspecialchars($student['email'] ?? '') ?>" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:border-emerald-600">
            </div>
            
            <button type="submit" class="px-4 py-2 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 transition-colors font-medium">
                Update Email
            </button>
        </form>
    </div>

    <!-- Notification Settings -->
    <div class="bg-white rounded-lg border border-slate-200 p-6 shadow-sm">
        <h2 class="text-xl font-bold text-slate-900 mb-6">Notifications</h2>
        
        <div class="space-y-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="font-medium text-slate-900">Attendance Alerts</p>
                    <p class="text-sm text-slate-600">Get notified about your attendance status</p>
                </div>
                <input type="checkbox" checked class="w-5 h-5 text-emerald-600 rounded">
            </div>
            
            <div class="border-t border-slate-200 pt-4">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="font-medium text-slate-900">Class Schedules</p>
                        <p class="text-sm text-slate-600">Get reminders about upcoming classes</p>
                    </div>
                    <input type="checkbox" checked class="w-5 h-5 text-emerald-600 rounded">
                </div>
            </div>
            
            <div class="border-t border-slate-200 pt-4">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="font-medium text-slate-900">Announcements</p>
                        <p class="text-sm text-slate-600">Receive school announcements</p>
                    </div>
                    <input type="checkbox" checked class="w-5 h-5 text-emerald-600 rounded">
                </div>
            </div>
        </div>
    </div>

    <!-- Privacy Settings -->
    <div class="bg-white rounded-lg border border-slate-200 p-6 shadow-sm">
        <h2 class="text-xl font-bold text-slate-900 mb-6">Privacy & Security</h2>
        
        <div class="space-y-4">
            <a href="/pages/student/profile.php#change-password" class="block p-4 rounded-lg border border-slate-200 hover:border-slate-300 hover:bg-slate-50 transition-colors">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <span class="material-symbols-outlined text-slate-600">lock</span>
                        <div>
                            <p class="font-medium text-slate-900">Change Password</p>
                            <p class="text-sm text-slate-600">Update your account password</p>
                        </div>
                    </div>
                    <span class="material-symbols-outlined text-slate-400">arrow_forward</span>
                </div>
            </a>
            
            <a href="/pages/privacy.php" class="block p-4 rounded-lg border border-slate-200 hover:border-slate-300 hover:bg-slate-50 transition-colors">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <span class="material-symbols-outlined text-slate-600">privacy_tip</span>
                        <div>
                            <p class="font-medium text-slate-900">Privacy Policy</p>
                            <p class="text-sm text-slate-600">View our privacy policy</p>
                        </div>
                    </div>
                    <span class="material-symbols-outlined text-slate-400">arrow_forward</span>
                </div>
            </a>
        </div>
    </div>

    <!-- Danger Zone -->
    <div class="bg-white rounded-lg border border-red-200 p-6 shadow-sm">
        <h2 class="text-xl font-bold text-red-700 mb-6">Danger Zone</h2>
        
        <div class="p-4 rounded-lg bg-red-50 border border-red-200">
            <p class="font-medium text-red-900 mb-3">Delete Account</p>
            <p class="text-sm text-red-700 mb-4">Deleting your account is permanent and cannot be undone. All your data will be lost.</p>
            <button type="button" class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors font-medium" disabled>
                Delete Account (Contact Admin)
            </button>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../../components/student/layout-end.php'; ?>
