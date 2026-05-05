<?php
// ============================================================
//  modules/classes/delete.php
//  Deletes a class — only if no students are enrolled
// ============================================================

require_once '../../includes/header.php';
require_once '../../config/db.php';

// ── Validate ID ──────────────────────────────────────────────
$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    $_SESSION['error'] = "Invalid class ID.";
    header("Location: index.php");
    exit();
}

// ── Fetch class ──────────────────────────────────────────────
$stmt = mysqli_prepare($conn, "SELECT * FROM classes WHERE id = ? LIMIT 1");
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$class = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);

if (!$class) {
    $_SESSION['error'] = "Class not found.";
    header("Location: index.php");
    exit();
}

// ── Block delete if students are enrolled ────────────────────
$count_stmt = mysqli_prepare($conn, "SELECT COUNT(*) AS total FROM students WHERE class_id = ?");
mysqli_stmt_bind_param($count_stmt, "i", $id);
mysqli_stmt_execute($count_stmt);
$count = mysqli_fetch_assoc(mysqli_stmt_get_result($count_stmt))['total'];
mysqli_stmt_close($count_stmt);

if ($count > 0) {
    $_SESSION['error'] = "Cannot delete '{$class['name']}' — it has {$count} enrolled student(s). Reassign them first.";
    header("Location: index.php");
    exit();
}

// ── Delete ───────────────────────────────────────────────────
$del_stmt = mysqli_prepare($conn, "DELETE FROM classes WHERE id = ?");
mysqli_stmt_bind_param($del_stmt, "i", $id);

if (mysqli_stmt_execute($del_stmt)) {
    $_SESSION['success'] = "Class '{$class['name']}'" .
        ($class['section'] ? " - {$class['section']}" : '') . " deleted successfully.";
} else {
    $_SESSION['error'] = "Failed to delete class. Please try again.";
}

mysqli_stmt_close($del_stmt);
header("Location: index.php");
exit();