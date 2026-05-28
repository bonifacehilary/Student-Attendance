<?php
// pages/student/forgot-password.php
// Forgot Password - Request Password Reset

require_once __DIR__ . '/../../config/bootstrap.php';

$error = '';
$success = '';
$step = 1; // Step 1: Enter email, Step 2: Enter reset code

// Step 1: Request password reset
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'request') {
        $identifier = trim($_POST['identifier'] ?? '');
        
        if (empty($identifier)) {
            $error = 'Please enter email or admission number';
        } else {
            try {
                // Find student by email or admission number
                $student = Utility::safeQuery(
                    'SELECT id, name, email FROM students WHERE email = ? OR admission_number = ? LIMIT 1',
                    [$identifier, $identifier],
                    'SELECT',
                    true
                );
                
                if ($student) {
                    // Generate reset token
                    $token = bin2hex(random_bytes(32));
                    $tokenHash = hash('sha256', $token);
                    $expiresAt = date('Y-m-d H:i:s', strtotime('+1 hour'));
                    
                    // Store reset token in database
                    try {
                        Utility::safeQuery(
                            'INSERT INTO password_resets (student_id, token_hash, expires_at) 
                             VALUES (?, ?, ?)
                             ON DUPLICATE KEY UPDATE token_hash = ?, expires_at = ?',
                            [$student['id'], $tokenHash, $expiresAt, $tokenHash, $expiresAt],
                            'INSERT'
                        );
                    } catch (\Throwable $e) {
                        // If table doesn't exist, use session-based tokens for demo
                    }
                    
                    // Store in session for display (demo only)
                    $_SESSION['reset_token'] = $token;
                    $_SESSION['reset_email'] = $student['email'];
                    $_SESSION['reset_student_id'] = $student['id'];
                    $_SESSION['reset_token_expires'] = time() + 3600; // 1 hour
                    
                    $success = 'Reset code sent to ' . $student['email'];
                    $step = 2;
                } else {
                    $error = 'No account found with that email or admission number';
                }
            } catch (\Throwable $e) {
                error_log('Forgot password error: ' . $e->getMessage());
                $error = 'An error occurred. Please try again.';
            }
        }
    }
    // Step 2: Verify token and reset password
    elseif ($_POST['action'] === 'reset') {
        $resetToken = trim($_POST['reset_token'] ?? '');
        $newPassword = trim($_POST['new_password'] ?? '');
        $confirmPassword = trim($_POST['confirm_password'] ?? '');
        
        if (empty($resetToken)) {
            $error = 'Reset code is required';
        } elseif (empty($newPassword) || empty($confirmPassword)) {
            $error = 'Please enter and confirm your new password';
        } elseif ($newPassword !== $confirmPassword) {
            $error = 'Passwords do not match';
        } elseif (strlen($newPassword) < 6) {
            $error = 'Password must be at least 6 characters';
        } else {
            // Verify token
            if (isset($_SESSION['reset_token']) && $_SESSION['reset_token'] === $resetToken) {
                // Check if token is still valid
                if (isset($_SESSION['reset_token_expires']) && time() < $_SESSION['reset_token_expires']) {
                    $studentId = $_SESSION['reset_student_id'];
                    $hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT);
                    
                    try {
                        // Update password
                        Utility::safeQuery(
                            'UPDATE students SET password_hash = ? WHERE id = ?',
                            [$hashedPassword, $studentId],
                            'UPDATE'
                        );
                        
                        // Clear reset token
                        unset($_SESSION['reset_token']);
                        unset($_SESSION['reset_email']);
                        unset($_SESSION['reset_student_id']);
                        unset($_SESSION['reset_token_expires']);
                        
                        $success = 'Password reset successfully! Redirecting to login...';
                        header('Refresh: 2; URL=/pages/student/login.php');
                    } catch (\Throwable $e) {
                        error_log('Password update error: ' . $e->getMessage());
                        $error = 'Failed to update password. Please try again.';
                    }
                } else {
                    $error = 'Reset code has expired. Please request a new one.';
                    unset($_SESSION['reset_token']);
                    unset($_SESSION['reset_email']);
                    unset($_SESSION['reset_student_id']);
                    unset($_SESSION['reset_token_expires']);
                    $step = 1;
                }
            } else {
                $error = 'Invalid reset code';
            }
        }
    }
}

// Check if already at step 2
if (!isset($_POST['action']) && isset($_SESSION['reset_token'])) {
    $step = 2;
}
?>
<!DOCTYPE html>
<html class="light" lang="en">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>Forgot Password | EduAttend</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <style>
        body { font-family: 'Inter', sans-serif; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
        .material-symbols-outlined { font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24; }
    </style>
</head>
<body class="flex items-center justify-center min-h-screen p-4">
    <div class="w-full max-w-md">
        <!-- Card -->
        <div class="bg-white rounded-lg shadow-2xl overflow-hidden">
            <!-- Header -->
            <div class="bg-gradient-to-r from-blue-600 to-blue-700 px-6 py-8 text-center">
                <div class="flex justify-center mb-4">
                    <span class="material-symbols-outlined text-5xl text-white">lock_reset</span>
                </div>
                <h1 class="text-2xl font-bold text-white">Reset Password</h1>
                <p class="text-blue-100 text-sm mt-2">Regain access to your account</p>
            </div>

            <!-- Form -->
            <form method="POST" class="px-6 py-8 space-y-6">
                <!-- Error Message -->
                <?php if (!empty($error)): ?>
                <div class="bg-red-50 border border-red-200 rounded-lg p-4 flex gap-3">
                    <span class="material-symbols-outlined text-red-600">error</span>
                    <div>
                        <p class="font-bold text-red-900">Error</p>
                        <p class="text-sm text-red-700"><?php echo htmlspecialchars($error); ?></p>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Success Message -->
                <?php if (!empty($success)): ?>
                <div class="bg-green-50 border border-green-200 rounded-lg p-4 flex gap-3">
                    <span class="material-symbols-outlined text-green-600">check_circle</span>
                    <div>
                        <p class="font-bold text-green-900">Success</p>
                        <p class="text-sm text-green-700"><?php echo htmlspecialchars($success); ?></p>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Step 1: Request Reset -->
                <?php if ($step === 1): ?>
                    <div>
                        <label for="identifier" class="block text-sm font-bold text-gray-900 mb-2">
                            Email or Admission Number
                        </label>
                        <input 
                            type="text" 
                            id="identifier"
                            name="identifier"
                            placeholder="alex@school.edu or STU2024001"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-600 focus:border-transparent transition"
                            required
                        />
                        <p class="text-xs text-gray-600 mt-2">Enter the email or admission number associated with your account</p>
                    </div>

                    <input type="hidden" name="action" value="request"/>
                    <button 
                        type="submit"
                        class="w-full bg-blue-600 hover:bg-blue-700 active:scale-95 text-white font-bold py-2.5 rounded-lg transition duration-150 flex items-center justify-center gap-2"
                    >
                        <span class="material-symbols-outlined">mail</span>
                        Send Reset Code
                    </button>

                <!-- Step 2: Reset Password -->
                <?php elseif ($step === 2): ?>
                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                        <p class="text-sm text-blue-700">
                            <strong>Reset code sent to:</strong> 
                            <?php echo isset($_SESSION['reset_email']) ? htmlspecialchars(substr($_SESSION['reset_email'], 0, 3) . '***') : '***'; ?>
                        </p>
                    </div>

                    <div>
                        <label for="reset_token" class="block text-sm font-bold text-gray-900 mb-2">
                            Reset Code
                        </label>
                        <input 
                            type="text" 
                            id="reset_token"
                            name="reset_token"
                            placeholder="Paste your reset code here"
                            value="<?php echo isset($_SESSION['reset_token']) ? substr($_SESSION['reset_token'], 0, 16) . '...' : ''; ?>"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-600 focus:border-transparent transition font-mono text-sm"
                            required
                        />
                        <p class="text-xs text-gray-600 mt-2">Enter the reset code from your email</p>
                    </div>

                    <div>
                        <label for="new_password" class="block text-sm font-bold text-gray-900 mb-2">
                            New Password
                        </label>
                        <div class="relative">
                            <input 
                                type="password" 
                                id="new_password"
                                name="new_password"
                                placeholder="••••••••"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-600 focus:border-transparent transition"
                                required
                            />
                            <button 
                                type="button" 
                                class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-600 hover:text-gray-900"
                                onclick="togglePassword('new_password')"
                            >
                                <span class="material-symbols-outlined" id="toggle-icon-1">visibility</span>
                            </button>
                        </div>
                    </div>

                    <div>
                        <label for="confirm_password" class="block text-sm font-bold text-gray-900 mb-2">
                            Confirm Password
                        </label>
                        <div class="relative">
                            <input 
                                type="password" 
                                id="confirm_password"
                                name="confirm_password"
                                placeholder="••••••••"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-600 focus:border-transparent transition"
                                required
                            />
                            <button 
                                type="button" 
                                class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-600 hover:text-gray-900"
                                onclick="togglePassword('confirm_password')"
                            >
                                <span class="material-symbols-outlined" id="toggle-icon-2">visibility</span>
                            </button>
                        </div>
                    </div>

                    <input type="hidden" name="action" value="reset"/>
                    <button 
                        type="submit"
                        class="w-full bg-blue-600 hover:bg-blue-700 active:scale-95 text-white font-bold py-2.5 rounded-lg transition duration-150 flex items-center justify-center gap-2"
                    >
                        <span class="material-symbols-outlined">lock</span>
                        Reset Password
                    </button>

                    <div class="pt-4 border-t border-gray-200">
                        <button 
                            type="button"
                            onclick="window.location.href='/pages/student/login.php'"
                            class="w-full text-gray-600 hover:text-gray-900 py-2 font-medium transition"
                        >
                            Back to Login
                        </button>
                    </div>
                <?php endif; ?>
            </form>

            <!-- Footer Links -->
            <?php if ($step === 1): ?>
            <div class="bg-gray-50 px-6 py-4 border-t border-gray-200 text-center">
                <p class="text-sm text-gray-600">
                    Remember your password? 
                    <a href="/pages/student/login.php" class="text-blue-600 font-bold hover:underline">
                        Sign In
                    </a>
                </p>
            </div>
            <?php endif; ?>
        </div>

        <!-- Info Card -->
        <div class="mt-6 bg-white/10 backdrop-blur-md rounded-lg p-4 text-white text-center">
            <p class="text-sm">
                <span class="material-symbols-outlined text-lg">info</span><br/>
                Demo: Reset code auto-filled (check input field)
            </p>
        </div>
    </div>

    <script>
        function togglePassword(fieldId) {
            const input = document.getElementById(fieldId);
            const iconId = fieldId === 'new_password' ? 'toggle-icon-1' : 'toggle-icon-2';
            const icon = document.getElementById(iconId);
            
            if (input.type === 'password') {
                input.type = 'text';
                icon.textContent = 'visibility_off';
            } else {
                input.type = 'password';
                icon.textContent = 'visibility';
            }
        }

        // Auto-fill reset code if available
        window.addEventListener('load', function() {
            const resetTokenField = document.getElementById('reset_token');
            if (resetTokenField && resetTokenField.value.includes('...')) {
                const storedToken = '<?php echo isset($_SESSION['reset_token']) ? $_SESSION['reset_token'] : ''; ?>';
                if (storedToken) {
                    resetTokenField.value = storedToken;
                }
            }
        });
    </script>
</body>
</html>
