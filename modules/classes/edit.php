<?php
// ============================================================
//  modules/classes/edit.php
//  Edit an existing class
// ============================================================

$page_title = "Edit Class";
require_once '../../includes/header.php';
require_once '../../includes/sidebar.php';
require_once '../../config/db.php';

// ── Validate ID ──────────────────────────────────────────────
$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    $_SESSION['error'] = "Invalid class ID.";
    header("Location: index.php");
    exit();
}

// ── Fetch existing record ────────────────────────────────────
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

$errors = [];
$input  = [
    'name'    => $class['name'],
    'section' => $class['section'] ?? '',
    'fee'     => $class['fee'],
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // ── Sanitize ─────────────────────────────────────────────
    $input['name']    = trim($_POST['name']    ?? '');
    $input['section'] = trim($_POST['section'] ?? '');
    $input['fee']     = trim($_POST['fee']     ?? '');

    // ── Validate ─────────────────────────────────────────────
    if (empty($input['name'])) {
        $errors[] = "Class name is required.";
    }
    if ($input['fee'] === '' || !is_numeric($input['fee']) || (float)$input['fee'] < 0) {
        $errors[] = "A valid monthly fee is required.";
    }

    // ── Check duplicate (exclude self) ───────────────────────
    if (empty($errors)) {
        $section_check = empty($input['section']) ? 'IS NULL' : "= '" . mysqli_real_escape_string($conn, $input['section']) . "'";
        $dup = mysqli_fetch_assoc(mysqli_query($conn, "
            SELECT id FROM classes
            WHERE name = '" . mysqli_real_escape_string($conn, $input['name']) . "'
              AND section {$section_check}
              AND id != {$id}
            LIMIT 1
        "));
        if ($dup) {
            $errors[] = "Another class with this name and section already exists.";
        }
    }

    // ── Update ───────────────────────────────────────────────
    if (empty($errors)) {
        $section = !empty($input['section']) ? $input['section'] : null;
        $stmt = mysqli_prepare($conn,
            "UPDATE classes SET name = ?, section = ?, fee = ? WHERE id = ?"
        );
        mysqli_stmt_bind_param($stmt, "ssdi", $input['name'], $section, $input['fee'], $id);

        if (mysqli_stmt_execute($stmt)) {
            $_SESSION['success'] = "Class updated successfully.";
            header("Location: index.php");
            exit();
        } else {
            $errors[] = "Database error. Please try again.";
        }
        mysqli_stmt_close($stmt);
    }
}
?>

<!-- ── PAGE HEADER ────────────────────────────────────────── -->
<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h4 class="fw-bold mb-0">Edit Class</h4>
        <small class="text-muted">Update class details</small>
    </div>
    <a href="index.php" class="btn btn-outline-secondary">
        <i class="fas fa-arrow-left me-2"></i>Back
    </a>
</div>

<!-- ── ERRORS ────────────────────────────────────────────── -->
<?php if (!empty($errors)): ?>
    <div class="alert alert-danger">
        <i class="fas fa-exclamation-circle me-2"></i>
        <ul class="mb-0 mt-1">
            <?php foreach ($errors as $e): ?>
                <li><?= htmlspecialchars($e) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<!-- ── FORM ───────────────────────────────────────────────── -->
<div class="card" style="max-width:560px;">
    <div class="card-header">
        <i class="fas fa-edit me-2 text-primary"></i>Edit Class Details
    </div>
    <div class="card-body">
        <form action="edit.php?id=<?= $id ?>" method="POST">

            <!-- Class Name -->
            <div class="mb-3">
                <label class="form-label fw-semibold">
                    Class Name <span class="text-danger">*</span>
                </label>
                <input
                    type="text"
                    name="name"
                    class="form-control"
                    placeholder="e.g. Class 1, Grade 5"
                    value="<?= htmlspecialchars($input['name']) ?>"
                    required
                >
            </div>

            <!-- Section -->
            <div class="mb-3">
                <label class="form-label fw-semibold">Section <span class="text-muted">(Optional)</span></label>
                <input
                    type="text"
                    name="section"
                    class="form-control"
                    placeholder="e.g. A, B"
                    maxlength="10"
                    value="<?= htmlspecialchars($input['section']) ?>"
                >
                <div class="form-text">Leave blank if no section applies.</div>
            </div>

            <!-- Monthly Fee -->
            <div class="mb-4">
                <label class="form-label fw-semibold">
                    Monthly Fee (PKR) <span class="text-danger">*</span>
                </label>
                <div class="input-group">
                    <span class="input-group-text">PKR</span>
                    <input
                        type="number"
                        name="fee"
                        class="form-control"
                        min="0"
                        step="0.01"
                        value="<?= htmlspecialchars($input['fee']) ?>"
                        required
                    >
                </div>
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary px-4">
                    <i class="fas fa-save me-2"></i>Update Class
                </button>
                <a href="index.php" class="btn btn-outline-secondary">Cancel</a>
            </div>

        </form>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>