<?php
// ============================================================
//  index.php
//  Entry point — redirects to dashboard or login
// ============================================================

session_start();

if (isset($_SESSION['admin_id'])) {
    header("Location: dashboard.php");
} else {
    header("Location: auth/login.php");
}
exit();