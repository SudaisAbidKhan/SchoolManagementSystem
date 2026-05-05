<?php
// ============================================================
//  modules/students/edit.php
//  Edit an existing student record
// ============================================================

$page_title = "Edit Student";
require_once '../../includes/header.php';
require_once '../../includes/sidebar.php';
require_once '../../config/db.php';

// ── Validate ID ──────────────────────────────────────────────
$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    $_SESSION['error'] = "Invalid student ID.";
    header("Location: index.php");
    exit();
}

// ── Fetch student ────────────────────────────────────────────
$stmt = mysqli_prepare($conn, "SELECT * FROM students WHERE id = ? LIMIT 1");
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$student = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);

if (!$student) {
    $_SESSION['error'] = "Student not found.";
    header("Location: index.php");
    exit();
}

// ── Fetch classes ────────────────────────────────────────────
$all_classes = mysqli_query($conn,
    "SELECT id, name, section FROM classes ORDER BY name ASC, section ASC"
);

$errors = [];
$input  = [
    'name'           => $student['name'],
    'father_name'    => $student['father_name'],
    'cnic'           => $student['cnic']           ?? '',
    'contact'        => $student['contact']         ?? '',
    'address'        => $student['address']         ?? '',
    'class_id'       => $student['class_id'],
    'admission_date' => $student['admission_date'],
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // ── Sanitize ─────────────────────────────────────────────
    foreach ($input as $key => $_) {
        $input[$key] = trim($_POST[$key] ?? '');
    }

    // ── Validate ─────────────────────────────────────────────
    if (empty($input['name'])) {
        $errors[] = "Student name is required.";
    }
    if (empty($input['father_name'])) {
        $errors[] = "Father's name is required.";
    }
    if (empty($input['class_id']) || (int)$input['class_id'] <= 0) {
        $errors[] = "Please select a class.";
    }
    if (empty($input['admission_date'])) {
        $errors[] = "Admission date is required.";
    }
    if (!empty($input['cnic'])) {
        if (!preg_match('/^\d{5}-\d{7}-\d$/', $input['cnic']) &&
            !preg_match('/^\d{13}$/', $input['cnic'])) {
            $errors[] = "CNIC/B-Form format is invalid.";
        }
    }
    if (!empty($input['contact'])) {
        if (!preg_match('/^(\+92|0)\d{10}$/', preg_replace('/[\s-]/', '', $input['contact']))) {
            $errors[] = "Contact number format is invalid.";
        }
    }

    // ── Duplicate CNIC check (exclude self) ──────────────────
    if (empty($errors) && !empty($input['cnic'])) {
        $dup_stmt = mysqli_prepare($conn,
            "SELECT id FROM students WHERE cnic = ? AND id != ? LIMIT 1"
        );
        mysqli_stmt_bind_param($dup_stmt, "si", $input['cnic'], $id);
        mysqli_stmt_execute($dup_stmt);
        mysqli_stmt_store_result($dup_stmt);
        if (mysqli_stmt_num_rows($dup_stmt) > 0) {
            $errors[] = "Another student with this CNIC/B-Form already exists.";
        }
        mysqli_stmt_close($dup_stmt);
    }

    // ── Update ───────────────────────────────────────────────
    if (empty($errors)) {
        $cnic     = !empty($input['cnic'])    ? $input['cnic']    : null;
        $contact  = !empty($input['contact']) ? $input['contact'] : null;
        $address  = !empty($input['address']) ? $input['address'] : null;
        $cid      = (int)$input['class_id'];

        $stmt = mysqli_prepare($conn, "
            UPDATE students
            SET name=?, father_name=?, cnic=?, contact=?, address=?, class_id=?, admission_date=?
            WHERE id=?
        ");
        mysqli_stmt_bind_param($stmt, "sssssiси",
            $input['name'], $input['father_name'],
            $cnic, $contact, $address,
            $cid, $input['admission_date'], $id
        );

        $stmt = mysqli_prepare($conn, "
            UPDATE students
            SET name=?, father_name=?, cnic=?, contact=?, address=?, class_id=?, admission_date=?
            WHERE id=?
        ");
        mysqli_stmt_bind_param($stmt, "sssssisi",
            $input['name'], $input['father_name'],
            $cnic, $contact, $address,
            $cid, $input['admission_date'], $id
        );

        if (mysqli_stmt_execute($stmt)) {
            $_SESSION['success'] = "Student '{$input['name']}' updated successfully.";
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
        <h4 class="fw-bold mb-0">Edit Student</h4>
        <small class="text-muted">Update student record</small>
    </div>
    <div class="d-flex gap-2">
        <a href="view.php?id=<?= $id ?>" class="btn btn-outline-info">
            <i class="fas fa-eye me-2"></i>View Profile
        </a>
        <a href="index.php" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left me-2"></i>Back
        </a>
    </div>
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
<div class="card" style="max-width:720px;">
    <div class="card-header">
        <i class="fas fa-edit me-2 text-primary"></i>Edit Student Information
    </div>
    <div class="card-body">
        <form action="edit.php?id=<?= $id ?>" method="POST">
            <div class="row g-3">

                <div class="col-md-6">
                    <label class="form-label fw-semibold">Full Name <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control"
                        value="<?= htmlspecialchars($input['name']) ?>" required>
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-semibold">Father's Name <span class="text-danger">*</span></label>
                    <input type="text" name="father_name" class="form-control"
                        value="<?= htmlspecialchars($input['father_name']) ?>" required>
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-semibold">CNIC / B-Form</label>
                    <input type="text" name="cnic" class="form-control"
                        placeholder="42101-1234567-1"
                        value="<?= htmlspecialchars($input['cnic']) ?>">
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-semibold">Contact Number</label>
                    <input type="text" name="contact" class="form-control"
                        placeholder="0300-1234567"
                        value="<?= htmlspecialchars($input['contact']) ?>">
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-semibold">Class <span class="text-danger">*</span></label>
                    <select name="class_id" class="form-select" required>
                        <option value="">-- Select Class --</option>
                        <?php while ($cls = mysqli_fetch_assoc($all_classes)): ?>
                        <option value="<?= $cls['id'] ?>"
                            <?= (int)$input['class_id'] === (int)$cls['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($cls['name']) ?>
                            <?= $cls['section'] ? '- ' . htmlspecialchars($cls['section']) : '' ?>
                        </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-semibold">Admission Date <span class="text-danger">*</span></label>
                    <input type="date" name="admission_date" class="form-control"
                        value="<?= htmlspecialchars($input['admission_date']) ?>"
                        max="<?= date('Y-m-d') ?>" required>
                </div>

                <div class="col-12">
                    <label class="form-label fw-semibold">Address</label>
                    <textarea name="address" class="form-control" rows="2"
                        placeholder="Street, Area, City"><?= htmlspecialchars($input['address']) ?></textarea>
                </div>

            </div>

            <div class="d-flex gap-2 mt-4">
                <button type="submit" class="btn btn-primary px-4">
                    <i class="fas fa-save me-2"></i>Update Student
                </button>
                <a href="index.php" class="btn btn-outline-secondary">Cancel</a>
            </div>

        </form>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>