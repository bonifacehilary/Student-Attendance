<?php
// pages/student/qr-attendance.php
// QR Code Attendance Scanner - Mark attendance via QR scan

require_once __DIR__ . '/../../config/bootstrap.php';
require_once __DIR__ . '/../../utils/Utility.php';

session_start();

// Check if student is logged in
if (!isset($_SESSION['student_id'])) {
    header('Location: /pages/student/login.php');
    exit;
}

$studentId = $_SESSION['student_id'];
$scanMessage = null;
$scanStatus = null;

// Handle QR scan (via POST from JavaScript)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['qr_code'])) {
    $qrCode = trim($_POST['qr_code']);
    
    try {
        // Validate QR code format (should be numeric or alphanumeric)
        if (empty($qrCode)) {
            $scanStatus = 'error';
            $scanMessage = 'Invalid QR code. Please try again.';
        } else {
            // Check if QR code is valid (exists, active, not expired)
            $qrSession = Utility::safeQuery(
                'SELECT id, class_id, created_date, expires_at, is_active FROM attendance_qr_sessions WHERE code = ? AND is_active = 1 AND expires_at >= NOW() LIMIT 1',
                [$qrCode],
                'SELECT',
                true
            );
            
            if ($qrSession) {
                // Check if already marked for this session
                $existing = Utility::safeQuery(
                    'SELECT id FROM attendance WHERE student_id = ? AND qr_session_id = ? LIMIT 1',
                    [$studentId, $qrSession['id']],
                    'SELECT',
                    true
                );
                
                if ($existing) {
                    $scanStatus = 'warning';
                    $scanMessage = 'You have already marked attendance for this session.';
                } else {
                    // Mark attendance
                    Utility::safeQuery(
                        'INSERT INTO attendance (student_id, qr_session_id, status, marked_time, marked_date) VALUES (?, ?, ?, NOW(), CURDATE())',
                        [$studentId, $qrSession['id'], 'present'],
                        'INSERT'
                    );
                    $scanStatus = 'success';
                    $scanMessage = 'Attendance marked successfully!';
                }
            } else {
                $scanStatus = 'error';
                $scanMessage = 'Invalid or expired QR code. Please scan a valid code.';
            }
        }
    } catch (\Throwable $e) {
        error_log('QR scan error: ' . $e->getMessage());
        $scanStatus = 'error';
        $scanMessage = 'An error occurred while processing your scan. Please try again.';
    }
}

// Fetch student data
try {
    $student = Utility::safeQuery(
        'SELECT id, name, admission_number FROM students WHERE id = ? LIMIT 1',
        [$studentId],
        'SELECT',
        true
    );
} catch (\Throwable $e) {
    error_log('QR student fetch error: ' . $e->getMessage());
    $student = ['name' => 'Student', 'admission_number' => 'STU' . $studentId];
}
?>
<!DOCTYPE html>
<html class="light" lang="en">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>QR Attendance | EduAttend</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jsQR/1.4.0/jsQR.js"></script>
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #f7f9fb; }
        .material-symbols-outlined { font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24; }
        canvas { max-width: 100%; border-radius: 12px; }
        .video-container { position: relative; background: #000; border-radius: 12px; overflow: hidden; }
        video { width: 100%; height: auto; display: block; }
    </style>
</head>
<body class="pb-24 min-h-screen">
    <!-- TopAppBar -->
    <header class="bg-white border-b border-gray-200 sticky top-0 z-40 flex items-center justify-between px-6 w-full h-16">
        <div class="flex items-center gap-4">
            <a href="/pages/student/dashboard.php" class="active:scale-95 transition-transform duration-150 p-2">
                <span class="material-symbols-outlined text-green-700">arrow_back</span>
            </a>
            <h1 class="text-xl font-bold text-gray-900">QR Attendance</h1>
        </div>
    </header>

    <main class="max-w-2xl mx-auto px-4 py-8">
        <!-- Status Message -->
        <?php if ($scanMessage): ?>
            <div class="mb-6 p-4 rounded-lg border-l-4 <?php 
                echo $scanStatus === 'success' ? 'bg-green-50 border-green-500 text-green-800' : 
                    ($scanStatus === 'warning' ? 'bg-yellow-50 border-yellow-500 text-yellow-800' : 
                    'bg-red-50 border-red-500 text-red-800'); 
            ?>">
                <p class="font-medium"><?php echo htmlspecialchars($scanMessage); ?></p>
            </div>
        <?php endif; ?>

        <!-- QR Scanner Card -->
        <div class="bg-white border border-gray-200 rounded-xl p-6 shadow-sm mb-6">
            <h2 class="text-lg font-bold text-gray-900 mb-4 flex items-center gap-2">
                <span class="material-symbols-outlined text-green-700">qr_code_2</span>
                Scan QR Code
            </h2>
            
            <div class="space-y-4">
                <!-- Camera Feed -->
                <div class="video-container">
                    <video id="qr-video" playsinline></video>
                </div>

                <!-- Canvas for QR Detection (hidden) -->
                <canvas id="qr-canvas" style="display: none;"></canvas>

                <!-- Fallback Input for Manual Entry -->
                <div class="mt-4">
                    <label class="text-xs uppercase font-bold text-gray-500 block mb-2">
                        Manual Entry (if scan fails)
                    </label>
                    <form method="POST" class="flex gap-2">
                        <input type="text" name="qr_code" id="qr-input" class="flex-1 border border-gray-200 rounded-lg px-3 py-2 text-sm" placeholder="Enter or paste QR code here..." />
                        <button type="submit" class="px-4 py-2 bg-green-700 text-white rounded-lg font-bold transition-all hover:brightness-110 active:scale-95">
                            Submit
                        </button>
                    </form>
                </div>

                <!-- Instructions -->
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-3 text-sm text-blue-800">
                    <p class="font-medium mb-1">📱 How to use:</p>
                    <ol class="list-decimal list-inside space-y-1 text-xs">
                        <li>Allow camera access when prompted</li>
                        <li>Point your device camera at the QR code</li>
                        <li>Wait for automatic detection and submission</li>
                        <li>Alternatively, manually enter the QR code above</li>
                    </ol>
                </div>
            </div>
        </div>

        <!-- Info Card -->
        <div class="bg-gradient-to-r from-green-50 to-blue-50 border border-green-200 rounded-xl p-6">
            <div class="flex gap-4">
                <span class="material-symbols-outlined text-green-700 text-2xl">info</span>
                <div>
                    <p class="font-bold text-gray-900 mb-1">About QR Attendance</p>
                    <p class="text-sm text-gray-700">Your attendance will be automatically marked when you successfully scan a valid QR code. Make sure you're within the designated time frame set by your institution.</p>
                </div>
            </div>
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
            <span class="text-[10px] font-bold mt-1">History</span>
        </a>
        <a class="flex flex-col items-center justify-center text-green-700 bg-green-50 rounded-xl px-4 py-1.5 active:scale-95 transition-all duration-200" href="/pages/student/qr-attendance.php">
            <span class="material-symbols-outlined">qr_code_2</span>
            <span class="text-[10px] font-bold mt-1">QR</span>
        </a>
        <a class="flex flex-col items-center justify-center text-gray-500 px-4 py-1.5 hover:bg-gray-50 transition-all active:scale-95 duration-200" href="/pages/student/report.php">
            <span class="material-symbols-outlined">description</span>
            <span class="text-[10px] font-bold mt-1">Report</span>
        </a>
        <a class="flex flex-col items-center justify-center text-gray-500 px-4 py-1.5 hover:bg-gray-50 transition-all active:scale-95 duration-200" href="/pages/student/profile.php">
            <span class="material-symbols-outlined">person</span>
            <span class="text-[10px] font-bold mt-1">Profile</span>
        </a>
    </nav>

    <script>
        const videoElement = document.getElementById('qr-video');
        const canvasElement = document.getElementById('qr-canvas');
        const qrInput = document.getElementById('qr-input');

        // Request camera access
        async function startCamera() {
            try {
                const stream = await navigator.mediaDevices.getUserMedia({
                    video: { facingMode: 'environment' }
                });
                videoElement.srcObject = stream;
                videoElement.play();
                scanQRCode();
            } catch (err) {
                console.log('Camera access denied or not available:', err);
                qrInput.focus();
            }
        }

        // Scan for QR codes
        function scanQRCode() {
            const canvas = canvasElement.getContext('2d');
            canvasElement.width = videoElement.videoWidth;
            canvasElement.height = videoElement.videoHeight;
            canvas.drawImage(videoElement, 0, 0);

            const imageData = canvas.getImageData(0, 0, canvasElement.width, canvasElement.height);
            const code = jsQR(imageData.data, imageData.width, imageData.height);

            if (code) {
                qrInput.value = code.data;
                // Auto-submit
                document.querySelector('form').submit();
            } else {
                requestAnimationFrame(scanQRCode);
            }
        }

        // Start camera on page load
        window.addEventListener('DOMContentLoaded', startCamera);

        // Stop camera on page unload
        window.addEventListener('beforeunload', () => {
            if (videoElement.srcObject) {
                videoElement.srcObject.getTracks().forEach(track => track.stop());
            }
        });
    </script>
</body>
</html>
