<?php
// ============================================================
//  includes/sidebar.php
//  Left sidebar navigation
//  Requires: $current_page (set in header.php)
// ============================================================

// Helper: returns 'active' class if current page matches
function isActive(string $page): string {
    global $current_page;
    return $current_page === $page ? 'active' : '';
}

// Helper: returns 'active' if current page is inside a module folder
function isModuleActive(string $module): string {
    $path = $_SERVER['PHP_SELF'];
    return str_contains($path, "/modules/{$module}/") ? 'active' : '';
}
?>

<!-- ── SIDEBAR ───────────────────────────────────────────── -->
<nav id="sidebar">
    <div class="sidebar-header">
        <i class="fas fa-graduation-cap fs-4"></i>
        <span class="sidebar-label ms-2 fw-bold">School System</span>
    </div>

    <ul class="sidebar-nav list-unstyled mb-0">

        <!-- Dashboard -->
        <li>
            <a href="<?= BASE_URL ?>dashboard.php" class="sidebar-link <?= isActive('dashboard.php') ?>">
                <i class="fas fa-tachometer-alt"></i>
                <span class="sidebar-label">Dashboard</span>
            </a>
        </li>

        <li class="sidebar-section-title">
            <span class="sidebar-label">Academic</span>
        </li>

        <!-- Students -->
        <li>
            <a href="<?= BASE_URL ?>modules/students/index.php" class="sidebar-link <?= isModuleActive('students') ?>">
                <i class="fas fa-user-graduate"></i>
                <span class="sidebar-label">Students</span>
            </a>
        </li>

        <!-- Classes -->
        <li>
            <a href="<?= BASE_URL ?>modules/classes/index.php" class="sidebar-link <?= isModuleActive('classes') ?>">
                <i class="fas fa-chalkboard"></i>
                <span class="sidebar-label">Classes</span>
            </a>
        </li>

        <!-- Timetable -->
        <li>
            <a href="<?= BASE_URL ?>modules/timetable/index.php" class="sidebar-link <?= isModuleActive('timetable') ?>">
                <i class="fas fa-calendar-week"></i>
                <span class="sidebar-label">Timetable</span>
            </a>
        </li>

        <li class="sidebar-section-title">
            <span class="sidebar-label">Finance</span>
        </li>

        <!-- Fees -->
        <li>
            <a href="<?= BASE_URL ?>modules/fees/index.php" class="sidebar-link <?= isModuleActive('fees') ?>">
                <i class="fas fa-money-bill-wave"></i>
                <span class="sidebar-label">Fees</span>
            </a>
        </li>

        <!-- Expenses -->
        <li>
            <a href="<?= BASE_URL ?>modules/expenses/index.php" class="sidebar-link <?= isModuleActive('expenses') ?>">
                <i class="fas fa-receipt"></i>
                <span class="sidebar-label">Expenses</span>
            </a>
        </li>

        <li class="sidebar-section-title">
            <span class="sidebar-label">HR</span>
        </li>

        <!-- Staff -->
        <li>
            <a href="<?= BASE_URL ?>modules/staff/index.php" class="sidebar-link <?= isModuleActive('staff') ?>">
                <i class="fas fa-users"></i>
                <span class="sidebar-label">Staff</span>
            </a>
        </li>

    </ul>
</nav>
<!-- ── END SIDEBAR ───────────────────────────────────────── -->

<!-- ── PAGE CONTENT WRAPPER ──────────────────────────────── -->
<div id="pageContent">
    <div class="content-inner">