<?php
// pages/admin/logout.php
// Admin Logout Handler

session_start();

// Destroy session and clear cookies
session_destroy();

// Clear the admin session cookie
if (isset($_COOKIE['PHPSESSID'])) {
    setcookie('PHPSESSID', '', time() - 3600, '/');
}

// Redirect to admin login
header('Location: /pages/admin/login.php');
exit;
?>
