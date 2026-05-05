<?php
// ============================================================
//  modules/expenses/delete.php
//  Delete an expense record
// ============================================================

require_once '../../includes/header.php';
require_once '../../config/db.php';

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    $_SESSION['error'] = "Invalid expense ID.";
    header("Location: index.php");
    exit();
}

// ── Fetch record for message ─────────────────────────────────
$stmt = mysqli_prepare($conn,
    "SELECT title, amount, date FROM expenses WHERE id = ? LIMIT 1"
);
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$expense = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);

if (!$expense) {
    $_SESSION['error'] = "Expense record not found.";
    header("Location: index.php");
    exit();
}

// ── Delete ───────────────────────────────────────────────────
$del = mysqli_prepare($conn, "DELETE FROM expenses WHERE id = ?");
mysqli_stmt_bind_param($del, "i", $id);

if (mysqli_stmt_execute($del)) {
    $_SESSION['success'] =
        "Expense '<strong>" . htmlspecialchars($expense['title']) .
        "</strong>' — PKR " . number_format($expense['amount'], 0) .
        " (" . date('d M Y', strtotime($expense['date'])) . ") deleted.";
} else {
    $_SESSION['error'] = "Failed to delete expense. Please try again.";
}

mysqli_stmt_close($del);
header("Location: index.php");
exit();