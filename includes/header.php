<?php
// ============================================================
//  includes/header.php
//  Global HTML <head> + top navbar
// ============================================================

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Base URL — adjust if hosted in a subdirectory
define('BASE_URL', '/SchoolManagementSystem/');

require_once __DIR__ . '/../includes/auth_check.php';

// Current page for active sidebar highlighting
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($page_title) ? htmlspecialchars($page_title) . ' | ' : '' ?>School Management System</title>

    <!-- Bootstrap 5 -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/style.css">
    <link rel="icon" type="image/x-icon" href="<?= BASE_URL ?>assets/images/School.ico">
</head>
<body>

<!-- ── TOP NAVBAR ─────────────────────────────────────────── -->
<nav class="navbar navbar-expand-lg navbar-dark bg-primary px-3 fixed-top">

    <!-- Brand / Toggle sidebar -->
    <div class="d-flex align-items-center gap-3">
        <button class="btn btn-outline-light btn-sm" id="sidebarToggle" title="Toggle Sidebar">
            <i class="fas fa-bars"></i>
        </button>
        <a class="navbar-brand fw-bold mb-0" href="<?= BASE_URL ?>dashboard.php">
            <i class="fas fa-school me-2"></i>SMS
        </a>
    </div>

    <!-- Right side -->
    <div class="ms-auto d-flex align-items-center gap-3">

        <!-- Current date -->
        <span class="text-white-50 d-none d-md-inline small">
            <i class="fas fa-calendar-alt me-1"></i>
            <?= date('l, d M Y') ?>
        </span>

        <!-- Admin dropdown -->
        <div class="dropdown">
            <button
                class="btn btn-outline-light btn-sm dropdown-toggle"
                type="button"
                data-bs-toggle="dropdown"
            >
                <i class="fas fa-user-circle me-1"></i>
                <?= htmlspecialchars($_SESSION['admin_username'] ?? 'Admin') ?>
            </button>
            <ul class="dropdown-menu dropdown-menu-end">
                <li>
                    <span class="dropdown-item-text text-muted small">
                        Logged in as <strong><?= htmlspecialchars($_SESSION['admin_username'] ?? 'Admin') ?></strong>
                    </span>
                </li>
                <li><hr class="dropdown-divider"></li>
                <li>
                    <a class="dropdown-item text-danger" href="<?= BASE_URL ?>auth/logout.php">
                        <i class="fas fa-sign-out-alt me-2"></i>Logout
                    </a>
                </li>
            </ul>
        </div>

    </div>
</nav>

<!-- ── WRAPPER (sidebar + content) ───────────────────────── -->
<div class="d-flex" id="mainWrapper">