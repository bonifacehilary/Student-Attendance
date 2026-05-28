<?php
require_once __DIR__ . '/../../config/bootstrap.php';

use StudentAttendance\Utils\AdminAuth;

header('Location: ' . (AdminAuth::check() ? '/pages/admin/dashboard.php' : '/pages/admin/login.php'));
exit;
