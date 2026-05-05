<?php
// ============================================================
//  modules/students/delete.php
//  Delete a student and their fee records (CASCADE)
// ============================================================

require_once '../../includes/header.php';
require_once '../../config/db.php';

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    $_SESSION['error'] = "Invalid student ID.";
    header("Location: index.php");
    exit();
}

// ── Fetch student ────────────────────────────────────────────
$stmt = mysqli_prepare($conn, "SELECT name FROM students WHERE id = ? LIMIT 1");
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$student = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);

if (!$student) {
    $_SESSION['error'] = "Student not found.";
    header("Location: index.php");
    exit();
}

// ── Delete (fees removed automatically via ON DELETE CASCADE) 
$del = mysqli_prepare($conn, "DELETE FROM students WHERE id = ?");
mysqli_stmt_bind_param($del, "i", $id);

if (mysqli_stmt_execute($del)) {
    $_SESSION['success'] = "Student '{$student['name']}' and all related fee records deleted.";
} else {
    $_SESSION['error'] = "Failed to delete student. Please try again.";
}

mysqli_stmt_close($del);
header("Location: index.php");
exit();