<?php
// ============================================================
//  modules/timetable/add.php
//  Add a new timetable slot
// ============================================================

$page_title = "Add Timetable Slot";
require_once '../../includes/header.php';
require_once '../../includes/sidebar.php';
require_once '../../config/db.php';

$days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];

// ── Predefined time slots ────────────────────────────────────
$time_slots = [
    '07:30 AM - 08:30 AM',
    '08:30 AM - 09:30 AM',
    '09:30 AM - 10:30 AM',
    '10:30 AM - 11:30 AM',
    '11:30 AM - 12:30 PM',
    '12:30 PM - 01:30 PM',
    '01:30 PM - 02:30 PM',
    '02:30 PM - 03:30 PM',
];

// ── Fetch classes and teachers ───────────────────────────────
$all_classes  = mysqli_query($conn,
    "SELECT id, name, section FROM classes ORDER BY name ASC, section ASC"
);
$all_teachers = mysqli_query($conn,
    "SELECT id, name, role FROM staff ORDER BY name ASC"
);

$errors = [];
$input  = [
    'class_id'   => (int)($_GET['class_id'] ?? 0),
    'teacher_id' => '',
    'day'        => $_GET['day'] ?? '',
    'time_slot'  => '',
    'subject'    => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // ── Sanitize ─────────────────────────────────────────────
    $input['class_id']   = (int)($_POST['class_id']   ?? 0);
    $input['teacher_id'] = (int)($_POST['teacher_id'] ?? 0);
    $input['day']        = trim($_POST['day']        ?? '');
    $input['time_slot']  = trim($_POST['time_slot']  ?? '');
    $input['subject']    = trim($_POST['subject']    ?? '');

    // ── Validate ─────────────────────────────────────────────
    if ($input['class_id'] <= 0) {
        $errors[] = "Please select a class.";
    }
    if ($input['teacher_id'] <= 0) {
        $errors[] = "Please select a teacher.";
    }
    if (empty($input['day']) || !in_array($input['day'], $days)) {
        $errors[] = "Please select a valid day.";
    }
    if (empty($input['time_slot'])) {
        $errors[] = "Please select a time slot.";
    }
    if (empty($input['subject'])) {
        $errors[] = "Subject name is required.";
    }

    // ── Check: same class already has a slot at this day+time
    if (empty($errors)) {
        $dup_stmt = mysqli_prepare($conn, "
            SELECT id FROM timetable
            WHERE class_id = ? AND day = ? AND time_slot = ?
            LIMIT 1
        ");
        mysqli_stmt_bind_param($dup_stmt, "iss",
            $input['class_id'], $input['day'], $input['time_slot']
        );
        mysqli_stmt_execute($dup_stmt);
        mysqli_stmt_store_result($dup_stmt);
        if (mysqli_stmt_num_rows($dup_stmt) > 0) {
            $errors[] = "This class already has a slot on {$input['day']} at {$input['time_slot']}.";
        }
        mysqli_stmt_close($dup_stmt);
    }

    // ── Check: teacher already booked at this day+time ───────
    if (empty($errors)) {
        $teacher_dup = mysqli_prepare($conn, "
            SELECT t.id, c.name AS class_name, c.section
            FROM timetable t
            JOIN classes c ON t.class_id = c.id
            WHERE t.teacher_id = ? AND t.day = ? AND t.time_slot = ?
            LIMIT 1
        ");
        mysqli_stmt_bind_param($teacher_dup, "iss",
            $input['teacher_id'], $input['day'], $input['time_slot']
        );
        mysqli_stmt_execute($teacher_dup);
        $conflict = mysqli_fetch_assoc(mysqli_stmt_get_result($teacher_dup));
        mysqli_stmt_close($teacher_dup);

        if ($conflict) {
            $cls = $conflict['class_name'] .
                ($conflict['section'] ? ' - ' . $conflict['section'] : '');
            $errors[] = "This teacher is already assigned to {$cls} on {$input['day']} at {$input['time_slot']}.";
        }
    }

    // ── Insert ───────────────────────────────────────────────
    if (empty($errors)) {
        $subject = !empty($input['subject']) ? $input['subject'] : null;

        $stmt = mysqli_prepare($conn, "
            INSERT INTO timetable (class_id, teacher_id, day, time_slot, subject)
            VALUES (?, ?, ?, ?, ?)
        ");
        mysqli_stmt_bind_param($stmt, "iisss",
            $input['class_id'],
            $input['teacher_id'],
            $input['day'],
            $input['time_slot'],
            $subject
        );

        if (mysqli_stmt_execute($stmt)) {
            $_SESSION['success'] = "Timetable slot added successfully.";
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
        <h4 class="fw-bold mb-0">Add Timetable Slot</h4>
        <small class="text-muted">Schedule a new class period</small>
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
<div class="card" style="max-width: 650px;">
    <div class="card-header">
        <i class="fas fa-calendar-plus me-2 text-primary"></i>Slot Details
    </div>
    <div class="card-body">
        <form action="add.php" method="POST">
            <div class="row g-3">

                <!-- Subject -->
                <div class="col-12">
                    <label class="form-label fw-semibold">
                        Subject <span class="text-danger">*</span>
                    </label>
                    <input
                        type="text"
                        name="subject"
                        class="form-control"
                        placeholder="e.g. Mathematics, English, Science"
                        value="<?= htmlspecialchars($input['subject']) ?>"
                        required
                    >
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

                <!-- Teacher -->
                <div class="col-md-6">
                    <label class="form-label fw-semibold">
                        Teacher <span class="text-danger">*</span>
                    </label>
                    <select name="teacher_id" class="form-select" required>
                        <option value="">-- Select Teacher --</option>
                        <?php while ($t = mysqli_fetch_assoc($all_teachers)): ?>
                        <option value="<?= $t['id'] ?>"
                            <?= (int)$input['teacher_id'] === (int)$t['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($t['name']) ?>
                            (<?= htmlspecialchars($t['role']) ?>)
                        </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <!-- Day -->
                <div class="col-md-6">
                    <label class="form-label fw-semibold">
                        Day <span class="text-danger">*</span>
                    </label>
                    <select name="day" class="form-select" required>
                        <option value="">-- Select Day --</option>
                        <?php foreach ($days as $d): ?>
                        <option value="<?= $d ?>"
                            <?= $input['day'] === $d ? 'selected' : '' ?>>
                            <?= $d ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Time Slot -->
                <div class="col-md-6">
                    <label class="form-label fw-semibold">
                        Time Slot <span class="text-danger">*</span>
                    </label>
                    <select name="time_slot" class="form-select" required>
                        <option value="">-- Select Time --</option>
                        <?php foreach ($time_slots as $ts): ?>
                        <option value="<?= $ts ?>"
                            <?= $input['time_slot'] === $ts ? 'selected' : '' ?>>
                            <?= $ts ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>

            </div><!-- /.row -->

            <!-- Conflict info box -->
            <div class="alert alert-info border mt-3 mb-0">
                <i class="fas fa-info-circle me-2"></i>
                <small>
                    The system will automatically check for scheduling conflicts
                    — a teacher cannot be assigned to two classes at the same day and time.
                </small>
            </div>

            <div class="d-flex gap-2 mt-4">
                <button type="submit" class="btn btn-primary px-4">
                    <i class="fas fa-save me-2"></i>Save Slot
                </button>
                <a href="index.php" class="btn btn-outline-secondary">Cancel</a>
            </div>

        </form>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>