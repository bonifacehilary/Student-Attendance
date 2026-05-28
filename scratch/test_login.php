<?php
ob_start();
include __DIR__ . '/../pages/student/login.php';
$output = ob_get_clean();
if (str_contains($output, 'Fatal error')) {
    echo $output;
    exit(1);
}
echo strlen($output) > 100 ? "login page ok\n" : "unexpected output\n";
