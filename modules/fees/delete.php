<?php
// ============================================================
//  modules/fees/delete.php
//  Delete a fee record
// ============================================================

require_once '../../includes/header.php';
require_once '../../config/db.php';

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    $_SESSION['error'] = "Invalid fee record ID.";
    header("Location: index.php");
    exit();
}

// ── Fetch record for confirmation message ────────────────────
$stmt = mysqli_prepare($conn, "
    SELECT f.month, f.amount, f.status, s.name AS student_name
    FROM fees f
    JOIN students s ON f.student_id = s.id
    WHERE f.id = ? LIMIT 1
");
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$fee = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);

if (!$fee) {
    $_SESSION['error'] = "Fee record not found.";
    header("Location: index.php");
    exit();
}

// ── Delete ───────────────────────────────────────────────────
$del = mysqli_prepare($conn, "DELETE FROM fees WHERE id = ?");
mysqli_stmt_bind_param($del, "i", $id);

if (mysqli_stmt_execute($del)) {
    $_SESSION['success'] =
        "Fee record deleted: <strong>{$fee['student_name']}</strong> — " .
        "{$fee['month']} — PKR " . number_format($fee['amount'], 0) . ".";
} else {
    $_SESSION['error'] = "Failed to delete fee record. Please try again.";
}

mysqli_stmt_close($del);
header("Location: index.php");
exit();