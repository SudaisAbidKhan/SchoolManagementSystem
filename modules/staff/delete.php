<?php
// ============================================================
//  modules/staff/delete.php
//  Delete a staff member
//  Timetable slots are removed automatically via ON DELETE CASCADE
// ============================================================

require_once '../../includes/header.php';
require_once '../../config/db.php';

// ── Validate ID ──────────────────────────────────────────────
$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    $_SESSION['error'] = "Invalid staff ID.";
    header("Location: index.php");
    exit();
}

// ── Fetch staff member ───────────────────────────────────────
$stmt = mysqli_prepare($conn, "SELECT name, role FROM staff WHERE id = ? LIMIT 1");
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$staff = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);

if (!$staff) {
    $_SESSION['error'] = "Staff member not found.";
    header("Location: index.php");
    exit();
}

// ── Count assigned timetable slots ───────────────────────────
$tt_stmt = mysqli_prepare($conn,
    "SELECT COUNT(*) AS total FROM timetable WHERE teacher_id = ?"
);
mysqli_stmt_bind_param($tt_stmt, "i", $id);
mysqli_stmt_execute($tt_stmt);
$tt_count = mysqli_fetch_assoc(mysqli_stmt_get_result($tt_stmt))['total'];
mysqli_stmt_close($tt_stmt);

// ── Delete ───────────────────────────────────────────────────
$del = mysqli_prepare($conn, "DELETE FROM staff WHERE id = ?");
mysqli_stmt_bind_param($del, "i", $id);

if (mysqli_stmt_execute($del)) {
    $msg = "Staff member '{$staff['name']}' deleted successfully.";
    if ($tt_count > 0) {
        $msg .= " {$tt_count} timetable slot(s) were also removed.";
    }
    $_SESSION['success'] = $msg;
} else {
    $_SESSION['error'] = "Failed to delete staff member. Please try again.";
}

mysqli_stmt_close($del);
header("Location: index.php");
exit();