<?php
// ============================================================
//  modules/timetable/delete.php
//  Delete a timetable slot
// ============================================================

require_once '../../includes/header.php';
require_once '../../config/db.php';

// ── Validate ID ──────────────────────────────────────────────
$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    $_SESSION['error'] = "Invalid timetable slot ID.";
    header("Location: index.php");
    exit();
}

// ── Fetch slot details for the success message ───────────────
$stmt = mysqli_prepare($conn, "
    SELECT t.subject, t.day, t.time_slot,
           c.name AS class_name, c.section,
           s.name AS teacher_name
    FROM timetable t
    JOIN classes c ON t.class_id   = c.id
    JOIN staff   s ON t.teacher_id = s.id
    WHERE t.id = ? LIMIT 1
");
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$slot = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);

if (!$slot) {
    $_SESSION['error'] = "Timetable slot not found.";
    header("Location: index.php");
    exit();
}

// ── Delete ───────────────────────────────────────────────────
$del = mysqli_prepare($conn, "DELETE FROM timetable WHERE id = ?");
mysqli_stmt_bind_param($del, "i", $id);

if (mysqli_stmt_execute($del)) {
    $class = $slot['class_name'] . ($slot['section'] ? ' - ' . $slot['section'] : '');
    $_SESSION['success'] =
        "Slot deleted: <strong>{$slot['subject']}</strong> &mdash; " .
        "{$class} &mdash; {$slot['day']} at {$slot['time_slot']}.";
} else {
    $_SESSION['error'] = "Failed to delete slot. Please try again.";
}

mysqli_stmt_close($del);
header("Location: index.php");
exit();