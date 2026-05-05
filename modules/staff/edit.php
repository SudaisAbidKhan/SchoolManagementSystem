<?php
// ============================================================
//  modules/staff/edit.php
//  Edit an existing staff member
// ============================================================

$page_title = "Edit Staff";
require_once '../../includes/header.php';
require_once '../../includes/sidebar.php';
require_once '../../config/db.php';

$roles = ['Teacher', 'Admin', 'Accountant', 'Peon', 'Other'];

// ── Validate ID ──────────────────────────────────────────────
$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    $_SESSION['error'] = "Invalid staff ID.";
    header("Location: index.php");
    exit();
}

// ── Fetch existing record ────────────────────────────────────
$stmt = mysqli_prepare($conn, "SELECT * FROM staff WHERE id = ? LIMIT 1");
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$staff = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);

if (!$staff) {
    $_SESSION['error'] = "Staff member not found.";
    header("Location: index.php");
    exit();
}

$errors = [];
$input  = [
    'name'    => $staff['name'],
    'role'    => $staff['role'],
    'salary'  => $staff['salary'],
    'contact' => $staff['contact'] ?? '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // ── Sanitize ─────────────────────────────────────────────
    foreach ($input as $key => $_) {
        $input[$key] = trim($_POST[$key] ?? '');
    }

    // ── Validate ─────────────────────────────────────────────
    if (empty($input['name'])) {
        $errors[] = "Full name is required.";
    }
    if (empty($input['role']) || !in_array($input['role'], $roles)) {
        $errors[] = "Please select a valid role.";
    }
    if ($input['salary'] === '' || !is_numeric($input['salary']) || (float)$input['salary'] < 0) {
        $errors[] = "A valid salary amount is required.";
    }
    if (!empty($input['contact'])) {
        if (!preg_match('/^(\+92|0)\d{10}$/', preg_replace('/[\s-]/', '', $input['contact']))) {
            $errors[] = "Contact number format is invalid.";
        }
    }

    // ── Update ───────────────────────────────────────────────
    if (empty($errors)) {
        $contact = !empty($input['contact']) ? $input['contact'] : null;
        $salary  = (float)$input['salary'];

        $stmt = mysqli_prepare($conn,
            "UPDATE staff SET name = ?, role = ?, salary = ?, contact = ? WHERE id = ?"
        );
        mysqli_stmt_bind_param($stmt, "ssdsi",
            $input['name'],
            $input['role'],
            $salary,
            $contact,
            $id
        );

        if (mysqli_stmt_execute($stmt)) {
            $_SESSION['success'] = "Staff member '{$input['name']}' updated successfully.";
            header("Location: index.php");
            exit();
        } else {
            $errors[] = "Database error: " . mysqli_stmt_error($stmt);
        }
        mysqli_stmt_close($stmt);
    }
}
?>

<!-- ── PAGE HEADER ────────────────────────────────────────── -->
<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h4 class="fw-bold mb-0">Edit Staff Member</h4>
        <small class="text-muted">Update staff details</small>
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
<div class="card" style="max-width: 600px;">
    <div class="card-header">
        <i class="fas fa-edit me-2 text-primary"></i>
        Edit: <?= htmlspecialchars($staff['name']) ?>
    </div>
    <div class="card-body">
        <form action="edit.php?id=<?= $id ?>" method="POST">
            <div class="row g-3">

                <!-- Full Name -->
                <div class="col-12">
                    <label class="form-label fw-semibold">
                        Full Name <span class="text-danger">*</span>
                    </label>
                    <input
                        type="text"
                        name="name"
                        class="form-control"
                        value="<?= htmlspecialchars($input['name']) ?>"
                        required
                    >
                </div>

                <!-- Role -->
                <div class="col-md-6">
                    <label class="form-label fw-semibold">
                        Role <span class="text-danger">*</span>
                    </label>
                    <select name="role" class="form-select" required>
                        <?php foreach ($roles as $r): ?>
                            <option value="<?= $r ?>"
                                <?= $input['role'] === $r ? 'selected' : '' ?>>
                                <?= $r ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Salary -->
                <div class="col-md-6">
                    <label class="form-label fw-semibold">
                        Monthly Salary (PKR) <span class="text-danger">*</span>
                    </label>
                    <div class="input-group">
                        <span class="input-group-text">PKR</span>
                        <input
                            type="number"
                            name="salary"
                            class="form-control"
                            min="0"
                            step="0.01"
                            value="<?= htmlspecialchars($input['salary']) ?>"
                            required
                        >
                    </div>
                </div>

                <!-- Contact -->
                <div class="col-12">
                    <label class="form-label fw-semibold">Contact Number</label>
                    <div class="input-group">
                        <span class="input-group-text">
                            <i class="fas fa-phone"></i>
                        </span>
                        <input
                            type="text"
                            name="contact"
                            class="form-control"
                            placeholder="e.g. 0300-1234567"
                            value="<?= htmlspecialchars($input['contact']) ?>"
                        >
                    </div>
                    <div class="form-text">Format: 03XX-XXXXXXX or +92XXXXXXXXXX</div>
                </div>

            </div><!-- /.row -->

            <!-- Current salary info box -->
            <div class="alert alert-light border mt-3 mb-0">
                <small class="text-muted">
                    <i class="fas fa-info-circle me-1"></i>
                    Current salary on record:
                    <strong class="text-success">
                        PKR <?= number_format($staff['salary'], 0) ?>
                    </strong>
                </small>
            </div>

            <div class="d-flex gap-2 mt-4">
                <button type="submit" class="btn btn-primary px-4">
                    <i class="fas fa-save me-2"></i>Update Staff
                </button>
                <a href="index.php" class="btn btn-outline-secondary">Cancel</a>
            </div>

        </form>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>