<?php
// ============================================================
//  modules/students/add.php
//  Add a new student
// ============================================================

$page_title = "Add Student";
require_once '../../includes/header.php';
require_once '../../includes/sidebar.php';
require_once '../../config/db.php';

// ── Fetch classes for dropdown ───────────────────────────────
$all_classes = mysqli_query($conn,
    "SELECT id, name, section FROM classes ORDER BY name ASC, section ASC"
);

$errors = [];
$input  = [
    'name'           => '',
    'father_name'    => '',
    'cnic'           => '',
    'contact'        => '',
    'address'        => '',
    'class_id'       => '',
    'admission_date' => date('Y-m-d'),
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
        // Validate CNIC format: 42101-1234567-1 or 13 digit B-Form
        if (!preg_match('/^\d{5}-\d{7}-\d$/', $input['cnic']) &&
            !preg_match('/^\d{13}$/', $input['cnic'])) {
            $errors[] = "CNIC/B-Form format is invalid. Use 42101-1234567-1 or 13 digits.";
        }
    }
    if (!empty($input['contact'])) {
        if (!preg_match('/^(\+92|0)\d{10}$/', preg_replace('/[\s-]/', '', $input['contact']))) {
            $errors[] = "Contact number format is invalid. Use 03XX-XXXXXXX or +92XXXXXXXXXX.";
        }
    }

    // ── Check duplicate CNIC ─────────────────────────────────
    if (empty($errors) && !empty($input['cnic'])) {
        $dup_stmt = mysqli_prepare($conn,
            "SELECT id FROM students WHERE cnic = ? LIMIT 1"
        );
        mysqli_stmt_bind_param($dup_stmt, "s", $input['cnic']);
        mysqli_stmt_execute($dup_stmt);
        mysqli_stmt_store_result($dup_stmt);
        if (mysqli_stmt_num_rows($dup_stmt) > 0) {
            $errors[] = "A student with this CNIC/B-Form already exists.";
        }
        mysqli_stmt_close($dup_stmt);
    }

    // ── Insert ───────────────────────────────────────────────
    if (empty($errors)) {
        $cnic    = !empty($input['cnic'])    ? $input['cnic']    : null;
        $contact = !empty($input['contact']) ? $input['contact'] : null;
        $address = !empty($input['address']) ? $input['address'] : null;

        $stmt = mysqli_prepare($conn, "
            INSERT INTO students
                (name, father_name, cnic, contact, address, class_id, admission_date)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        mysqli_stmt_bind_param(
            $stmt, "sssssис",
            $input['name'],
            $input['father_name'],
            $cnic,
            $contact,
            $address,
            $input['class_id'],
            $input['admission_date']
        );

        // Fix: correct type string
        $stmt = mysqli_prepare($conn, "
            INSERT INTO students
                (name, father_name, cnic, contact, address, class_id, admission_date)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $class_id_int = (int)$input['class_id'];
        mysqli_stmt_bind_param(
            $stmt, "sssssис",
            $input['name'], $input['father_name'],
            $cnic, $contact, $address,
            $class_id_int, $input['admission_date']
        );

        $stmt = mysqli_prepare($conn, "
            INSERT INTO students
                (name, father_name, cnic, contact, address, class_id, admission_date)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $class_id_int = (int)$input['class_id'];
        mysqli_stmt_bind_param($stmt, "sssssис", 
            $input['name'], $input['father_name'],
            $cnic, $contact, $address,
            $class_id_int, $input['admission_date']
        );

        // Clean insert
        $stmt = mysqli_prepare($conn, "
            INSERT INTO students (name, father_name, cnic, contact, address, class_id, admission_date)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $cid = (int)$input['class_id'];
        mysqli_stmt_bind_param($stmt, "sssssis",
            $input['name'],
            $input['father_name'],
            $cnic,
            $contact,
            $address,
            $cid,
            $input['admission_date']
        );

        if (mysqli_stmt_execute($stmt)) {
            $_SESSION['success'] = "Student '{$input['name']}' added successfully.";
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
        <h4 class="fw-bold mb-0">Add Student</h4>
        <small class="text-muted">Register a new student</small>
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
<div class="card" style="max-width:720px;">
    <div class="card-header">
        <i class="fas fa-user-graduate me-2 text-primary"></i>Student Information
    </div>
    <div class="card-body">
        <form action="add.php" method="POST">

            <div class="row g-3">

                <!-- Name -->
                <div class="col-md-6">
                    <label class="form-label fw-semibold">
                        Full Name <span class="text-danger">*</span>
                    </label>
                    <input type="text" name="name" class="form-control"
                        placeholder="e.g. Ahmed Ali"
                        value="<?= htmlspecialchars($input['name']) ?>" required>
                </div>

                <!-- Father Name -->
                <div class="col-md-6">
                    <label class="form-label fw-semibold">
                        Father's Name <span class="text-danger">*</span>
                    </label>
                    <input type="text" name="father_name" class="form-control"
                        placeholder="e.g. Ali Hassan"
                        value="<?= htmlspecialchars($input['father_name']) ?>" required>
                </div>

                <!-- CNIC -->
                <div class="col-md-6">
                    <label class="form-label fw-semibold">CNIC / B-Form</label>
                    <input type="text" name="cnic" class="form-control"
                        placeholder="42101-1234567-1 or 13 digits"
                        value="<?= htmlspecialchars($input['cnic']) ?>">
                    <div class="form-text">Optional but must be unique if provided.</div>
                </div>

                <!-- Contact -->
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Contact Number</label>
                    <input type="text" name="contact" class="form-control"
                        placeholder="e.g. 0300-1234567"
                        value="<?= htmlspecialchars($input['contact']) ?>">
                </div>

                <!-- Class -->
                <div class="col-md-6">
                    <label class="form-label fw-semibold">
                        Class <span class="text-danger">*</span>
                    </label>
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

                <!-- Admission Date -->
                <div class="col-md-6">
                    <label class="form-label fw-semibold">
                        Admission Date <span class="text-danger">*</span>
                    </label>
                    <input type="date" name="admission_date" class="form-control"
                        value="<?= htmlspecialchars($input['admission_date']) ?>"
                        max="<?= date('Y-m-d') ?>" required>
                </div>

                <!-- Address -->
                <div class="col-12">
                    <label class="form-label fw-semibold">Address</label>
                    <textarea name="address" class="form-control" rows="2"
                        placeholder="Street, Area, City"><?= htmlspecialchars($input['address']) ?></textarea>
                </div>

            </div><!-- /.row -->

            <div class="d-flex gap-2 mt-4">
                <button type="submit" class="btn btn-primary px-4">
                    <i class="fas fa-save me-2"></i>Save Student
                </button>
                <a href="index.php" class="btn btn-outline-secondary">Cancel</a>
            </div>

        </form>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>