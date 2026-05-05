<?php
// ============================================================
//  modules/classes/add.php
//  Add a new class
// ============================================================

session_start();
require_once '../../config/db.php';

$errors = [];
$input  = ['name' => '', 'section' => '', 'fee' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // ── Sanitize inputs ──────────────────────────────────────
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

    // ── Check duplicate ──────────────────────────────────────
    if (empty($errors)) {
        $section_check = empty($input['section']) ? 'IS NULL' : "= '" . mysqli_real_escape_string($conn, $input['section']) . "'";
        $dup = mysqli_fetch_assoc(mysqli_query($conn, "
            SELECT id FROM classes
            WHERE name = '" . mysqli_real_escape_string($conn, $input['name']) . "'
              AND section {$section_check}
            LIMIT 1
        "));
        if ($dup) {
            $errors[] = "A class with this name and section already exists.";
        }
    }

    // ── Insert ───────────────────────────────────────────────
    if (empty($errors)) {
        $section = !empty($input['section']) ? $input['section'] : null;
        $stmt = mysqli_prepare($conn,
            "INSERT INTO classes (name, section, fee) VALUES (?, ?, ?)"
        );
        mysqli_stmt_bind_param($stmt, "ssd", $input['name'], $section, $input['fee']);

        if (mysqli_stmt_execute($stmt)) {
            $_SESSION['success'] = "Class '{$input['name']}'" .
                ($section ? " - {$section}" : '') . " added successfully.";
            header("Location: index.php");
            exit();
        } else {
            $errors[] = "Database error. Please try again.";
        }
        mysqli_stmt_close($stmt);
    }
}

$page_title = "Add Class";
require_once '../../includes/header.php';
require_once '../../includes/sidebar.php';
?>

<!-- ── PAGE HEADER ────────────────────────────────────────── -->
<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h4 class="fw-bold mb-0">Add Class</h4>
        <small class="text-muted">Create a new class or section</small>
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
        <i class="fas fa-chalkboard me-2 text-primary"></i>Class Details
    </div>
    <div class="card-body">
        <form action="add.php" method="POST">

            <!-- Class Name -->
            <div class="mb-3">
                <label class="form-label fw-semibold">
                    Class Name <span class="text-danger">*</span>
                </label>
                <input
                    type="text"
                    name="name"
                    class="form-control"
                    placeholder="e.g. Class 1, Grade 5, KG"
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
                    placeholder="e.g. A, B, Blue"
                    maxlength="10"
                    value="<?= htmlspecialchars($input['section']) ?>"
                >
                <div class="form-text">Leave blank if the class has no sections.</div>
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
                        placeholder="e.g. 2500"
                        min="0"
                        step="0.01"
                        value="<?= htmlspecialchars($input['fee']) ?>"
                        required
                    >
                </div>
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary px-4">
                    <i class="fas fa-save me-2"></i>Save Class
                </button>
                <a href="index.php" class="btn btn-outline-secondary">Cancel</a>
            </div>

        </form>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>