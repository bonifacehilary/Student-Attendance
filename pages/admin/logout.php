<?php
// Admin logout — clears admin session only (keeps student session if any)

require_once __DIR__ . '/../../config/bootstrap.php';

unset(
    $_SESSION['admin_logged_in'],
    $_SESSION['admin_id'],
    $_SESSION['admin_email'],
    $_SESSION['admin_flash'],
    $_SESSION['admin_last_qr_code'],
    $_SESSION['admin_last_qr_expires']
);

header('Location: /pages/admin/login.php');
exit;
