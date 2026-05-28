<?php
require_once __DIR__ . '/../../config/bootstrap.php';

unset(
    $_SESSION['teacher_logged_in'],
    $_SESSION['teacher_id'],
    $_SESSION['teacher_name'],
    $_SESSION['teacher_email'],
    $_SESSION['teacher_assigned_class'],
    $_SESSION['teacher_flash'],
    $_SESSION['teacher_last_qr_code'],
    $_SESSION['teacher_last_qr_expires']
);

header('Location: /pages/teacher/login.php');
exit;
