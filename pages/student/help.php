<?php
// pages/student/help.php
// Student Help & Support Page

require_once __DIR__ . '/../../config/bootstrap.php';

use StudentAttendance\Utils\StudentAuth;

StudentAuth::require();
$studentId = StudentAuth::studentId();

$pageTitle = 'Help & Support';
$pageHeading = 'Help & Support Center';
$activeNav = 'help';

require __DIR__ . '/../../components/student/layout-start.php';
?>

<div class="space-y-6">
    <!-- FAQ Section -->
    <div class="bg-white rounded-lg border border-slate-200 p-6 shadow-sm">
        <h2 class="text-xl font-bold text-slate-900 mb-6">Frequently Asked Questions</h2>
        
        <div class="space-y-3">
            <!-- FAQ Item 1 -->
            <details class="group border border-slate-200 rounded-lg">
                <summary class="flex items-center justify-between px-6 py-4 cursor-pointer hover:bg-slate-50 font-medium text-slate-900">
                    <span>How do I mark attendance using QR code?</span>
                    <span class="material-symbols-outlined text-slate-600 group-open:rotate-180 transition-transform">expand_more</span>
                </summary>
                <div class="px-6 pb-4 pt-2 border-t border-slate-200 text-sm text-slate-600">
                    <p>Go to the "My Attendance" or "Scan QR" section and point your device camera at the QR code displayed by your teacher. The system will automatically record your attendance.</p>
                </div>
            </details>

            <!-- FAQ Item 2 -->
            <details class="group border border-slate-200 rounded-lg">
                <summary class="flex items-center justify-between px-6 py-4 cursor-pointer hover:bg-slate-50 font-medium text-slate-900">
                    <span>How can I download my attendance report?</span>
                    <span class="material-symbols-outlined text-slate-600 group-open:rotate-180 transition-transform">expand_more</span>
                </summary>
                <div class="px-6 pb-4 pt-2 border-t border-slate-200 text-sm text-slate-600">
                    <p>Visit the "Download Report" section in the sidebar. You can select a date range and download your attendance report in PDF or CSV format.</p>
                </div>
            </details>

            <!-- FAQ Item 3 -->
            <details class="group border border-slate-200 rounded-lg">
                <summary class="flex items-center justify-between px-6 py-4 cursor-pointer hover:bg-slate-50 font-medium text-slate-900">
                    <span>What if I'm marked absent by mistake?</span>
                    <span class="material-symbols-outlined text-slate-600 group-open:rotate-180 transition-transform">expand_more</span>
                </summary>
                <div class="px-6 pb-4 pt-2 border-t border-slate-200 text-sm text-slate-600">
                    <p>You can request an appeal for attendance corrections. Visit your attendance history and click on the incorrect entry to file an appeal with your teacher.</p>
                </div>
            </details>

            <!-- FAQ Item 4 -->
            <details class="group border border-slate-200 rounded-lg">
                <summary class="flex items-center justify-between px-6 py-4 cursor-pointer hover:bg-slate-50 font-medium text-slate-900">
                    <span>How do I update my profile information?</span>
                    <span class="material-symbols-outlined text-slate-600 group-open:rotate-180 transition-transform">expand_more</span>
                </summary>
                <div class="px-6 pb-4 pt-2 border-t border-slate-200 text-sm text-slate-600">
                    <p>Go to "Settings" to update your email address. For other profile changes, please contact your school administrator.</p>
                </div>
            </details>
        </div>
    </div>

    <!-- Getting Started Guide -->
    <div class="bg-white rounded-lg border border-slate-200 p-6 shadow-sm">
        <h2 class="text-xl font-bold text-slate-900 mb-6">Getting Started</h2>
        
        <div class="space-y-4">
            <div class="flex gap-4 p-4 bg-slate-50 rounded-lg border border-slate-200">
                <div class="flex h-8 w-8 items-center justify-center rounded-full bg-emerald-600 text-white font-bold flex-shrink-0">1</div>
                <div>
                    <p class="font-medium text-slate-900">View Your Dashboard</p>
                    <p class="text-sm text-slate-600 mt-1">Start by viewing your dashboard to see your attendance summary and quick actions.</p>
                </div>
            </div>
            
            <div class="flex gap-4 p-4 bg-slate-50 rounded-lg border border-slate-200">
                <div class="flex h-8 w-8 items-center justify-center rounded-full bg-emerald-600 text-white font-bold flex-shrink-0">2</div>
                <div>
                    <p class="font-medium text-slate-900">Mark Attendance</p>
                    <p class="text-sm text-slate-600 mt-1">Use the QR code scanner to mark your attendance when your teacher displays a QR code.</p>
                </div>
            </div>
            
            <div class="flex gap-4 p-4 bg-slate-50 rounded-lg border border-slate-200">
                <div class="flex h-8 w-8 items-center justify-center rounded-full bg-emerald-600 text-white font-bold flex-shrink-0">3</div>
                <div>
                    <p class="font-medium text-slate-900">Check Your Records</p>
                    <p class="text-sm text-slate-600 mt-1">Review your attendance history and check your attendance rate on the History page.</p>
                </div>
            </div>
            
            <div class="flex gap-4 p-4 bg-slate-50 rounded-lg border border-slate-200">
                <div class="flex h-8 w-8 items-center justify-center rounded-full bg-emerald-600 text-white font-bold flex-shrink-0">4</div>
                <div>
                    <p class="font-medium text-slate-900">Download Reports</p>
                    <p class="text-sm text-slate-600 mt-1">Generate and download your attendance reports whenever you need them.</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Contact Support -->
    <div class="bg-white rounded-lg border border-slate-200 p-6 shadow-sm">
        <h2 class="text-xl font-bold text-slate-900 mb-6">Contact Support</h2>
        
        <div class="space-y-4">
            <div class="p-4 rounded-lg border border-slate-200 hover:border-emerald-600 cursor-pointer hover:bg-emerald-50 transition-colors">
                <div class="flex items-center gap-3">
                    <span class="material-symbols-outlined text-emerald-700">mail</span>
                    <div>
                        <p class="font-medium text-slate-900">Email Support</p>
                        <p class="text-sm text-slate-600">support@school.edu</p>
                    </div>
                </div>
            </div>
            
            <div class="p-4 rounded-lg border border-slate-200 hover:border-emerald-600 cursor-pointer hover:bg-emerald-50 transition-colors">
                <div class="flex items-center gap-3">
                    <span class="material-symbols-outlined text-emerald-700">phone</span>
                    <div>
                        <p class="font-medium text-slate-900">Phone Support</p>
                        <p class="text-sm text-slate-600">+1 (555) 123-4567</p>
                    </div>
                </div>
            </div>
            
            <div class="p-4 rounded-lg border border-slate-200 hover:border-emerald-600 cursor-pointer hover:bg-emerald-50 transition-colors">
                <a href="/pages/contact.php" class="flex items-center gap-3">
                    <span class="material-symbols-outlined text-emerald-700">contact_support</span>
                    <div>
                        <p class="font-medium text-slate-900">Contact Form</p>
                        <p class="text-sm text-slate-600">Send us a message</p>
                    </div>
                </a>
            </div>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../../components/student/layout-end.php'; ?>
