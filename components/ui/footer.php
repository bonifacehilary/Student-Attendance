<?php
use StudentAttendance\Utils\Assets;
?>
<footer class="mt-auto border-t border-gray-200 bg-white py-8">
    <div class="max-w-6xl mx-auto px-4 text-center text-sm text-gray-600">
        <p class="font-semibold text-gray-900"><?= htmlspecialchars(Assets::APP_NAME) ?></p>
        <p class="mt-1"><?= htmlspecialchars(Assets::APP_TAGLINE) ?></p>
        <div class="mt-4 flex flex-wrap justify-center gap-4">
            <a class="hover:text-emerald-700" href="/pages/student/login.php">Student Login</a>
            <a class="hover:text-emerald-700" href="/pages/admin/login.php">Admin Login</a>
            <a class="hover:text-emerald-700" href="/pages/contact.php">Contact</a>
            <a class="hover:text-emerald-700" href="/pages/privacy.php">Privacy</a>
            <a class="hover:text-emerald-700" href="/pages/user-guide.php">User Guide</a>
        </div>
        <p class="mt-4 text-gray-500">© <?= date('Y') ?> <?= htmlspecialchars(Assets::APP_NAME) ?>. All rights reserved.</p>
    </div>
</footer>
