<?php
// ============================================================
//  auth/process_login.php
//  Handles login form submission securely
// ============================================================

session_start();
require_once '../config/db.php';

// ── Only accept POST requests ────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: login.php");
    exit();
}

// ── CSRF Token Validation ────────────────────────────────────
if (
    empty($_POST['csrf_token']) ||
    empty($_SESSION['csrf_token']) ||
    !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])
) {
    $_SESSION['login_error'] = "Invalid request. Please try again.";
    header("Location: login.php");
    exit();
}

// ── Sanitize & validate inputs ──────────────────────────────
$username = trim($_POST['username'] ?? '');
$password = trim($_POST['password'] ?? '');

if (empty($username) || empty($password)) {
    $_SESSION['login_error'] = "Username and password are required.";
    header("Location: login.php");
    exit();
}

// ── Brute-force protection: max 5 attempts ──────────────────
if (!isset($_SESSION['login_attempts'])) {
    $_SESSION['login_attempts']    = 0;
    $_SESSION['login_last_attempt'] = time();
}

// Reset attempts after 15 minutes
if (time() - $_SESSION['login_last_attempt'] > 900) {
    $_SESSION['login_attempts'] = 0;
}

if ($_SESSION['login_attempts'] >= 5) {
    $wait = ceil((900 - (time() - $_SESSION['login_last_attempt'])) / 60);
    $_SESSION['login_error'] = "Too many failed attempts. Please wait {$wait} minute(s).";
    header("Location: login.php");
    exit();
}

// ── Fetch admin record from DB ──────────────────────────────
$stmt = mysqli_prepare($conn, "SELECT id, username, password FROM admin WHERE username = ? LIMIT 1");
mysqli_stmt_bind_param($stmt, "s", $username);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$admin  = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

// ── Verify password ─────────────────────────────────────────
if ($admin && password_verify($password, $admin['password'])) {

    // Successful login — regenerate session ID to prevent fixation
    session_regenerate_id(true);

    // Store admin info in session
    $_SESSION['admin_id']       = $admin['id'];
    $_SESSION['admin_username'] = $admin['username'];

    // Generate a fresh CSRF token for the session
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

    // Reset brute-force counters
    unset($_SESSION['login_attempts'], $_SESSION['login_last_attempt']);

    header("Location: ../dashboard.php");
    exit();

} else {

    // Failed login
    $_SESSION['login_attempts']++;
    $_SESSION['login_last_attempt'] = time();

    $remaining = 5 - $_SESSION['login_attempts'];
    $_SESSION['login_error'] = $remaining > 0
        ? "Invalid username or password. {$remaining} attempt(s) remaining."
        : "Too many failed attempts. Please wait 15 minutes.";

    header("Location: login.php");
    exit();
}