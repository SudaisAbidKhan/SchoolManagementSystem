<?php
// ============================================================
//  includes/auth_check.php
//  Include at the TOP of every protected page
// ============================================================

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['admin_id'])) {
    header("Location: " . BASE_URL . "auth/login.php");
    exit();
}