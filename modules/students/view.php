<?php
// ============================================================
//  modules/students/view.php
//  View full student profile + fee history
// ============================================================

$page_title = "Student Profile";
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

// ── Fetch student with class info ────────────────────────────
$stmt = mysqli_prepare($conn, "
    SELECT s.*, c.name AS class_name, c.section AS class_section, c.fee AS class_fee
    FROM students s
    JOIN classes c ON s.class_id = c.id
    WHERE s.id = ? LIMIT 1
");
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$student = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);

if (!$student) {
    $_SESSION['error'] = "Student not found.";
    header("Location: index.php");
    exit();
}

// ── Fee summary ──────────────────────────────────────────────
$fee_summary = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT
        COUNT(*)                                AS total_months,
        SUM(CASE WHEN status='Paid'   THEN 1 ELSE 0 END) AS paid_count,
        SUM(CASE WHEN status='Unpaid' THEN 1 ELSE 0 END) AS unpaid_count,
        SUM(CASE WHEN status='Paid'   THEN amount ELSE 0 END) AS total_paid,
        SUM(CASE WHEN status='Unpaid' THEN amount ELSE 0 END) AS total_due
    FROM fees WHERE student_id = {$id}
"));

// ── Fee history ──────────────────────────────────────────────
$fee_history = mysqli_query($conn, "
    SELECT * FROM fees
    WHERE student_id = {$id}
    ORDER BY id DESC
");
?>

<!-- ── PAGE HEADER ────────────────────────────────────────── -->
<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h4 class="fw-bold mb-0">Student Profile</h4>
        <small class="text-muted">Full details and fee history</small>
    </div>
    <div class="d-flex gap-2">
        <a href="edit.php?id=<?= $id ?>" class="btn btn-outline-primary">
            <i class="fas fa-edit me-2"></i>Edit
        </a>
        <a href="index.php" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left me-2"></i>Back
        </a>
    </div>
</div>

<div class="row g-4">

    <!-- ── LEFT: Profile card ─────────────────────────────── -->
    <div class="col-md-4">
        <div class="card text-center">
            <div class="card-body py-4">
                <!-- Avatar -->
                <div class="mx-auto mb-3 d-flex align-items-center justify-content-center rounded-circle"
                     style="width:80px;height:80px;background:#0d6efd22;font-size:2rem;font-weight:700;color:#0d6efd;">
                    <?= strtoupper(substr($student['name'], 0, 1)) ?>
                </div>
                <h5 class="fw-bold mb-0"><?= htmlspecialchars($student['name']) ?></h5>
                <p class="text-muted small mb-3">
                    <?= htmlspecialchars($student['class_name']) ?>
                    <?= $student['class_section'] ? '- ' . htmlspecialchars($student['class_section']) : '' ?>
                </p>
                <span class="badge bg-success px-3 py-2">Active Student</span>
            </div>
            <ul class="list-group list-group-flush text-start">
                <li class="list-group-item">
                    <small class="text-muted d-block">Father's Name</small>
                    <span class="fw-semibold"><?= htmlspecialchars($student['father_name']) ?></span>
                </li>
                <li class="list-group-item">
                    <small class="text-muted d-block">CNIC / B-Form</small>
                    <span class="fw-semibold"><?= htmlspecialchars($student['cnic'] ?? '—') ?></span>
                </li>
                <li class="list-group-item">
                    <small class="text-muted d-block">Contact</small>
                    <span class="fw-semibold"><?= htmlspecialchars($student['contact'] ?? '—') ?></span>
                </li>
                <li class="list-group-item">
                    <small class="text-muted d-block">Address</small>
                    <span class="fw-semibold"><?= htmlspecialchars($student['address'] ?? '—') ?></span>
                </li>
                <li class="list-group-item">
                    <small class="text-muted d-block">Admission Date</small>
                    <span class="fw-semibold">
                        <?= date('d M Y', strtotime($student['admission_date'])) ?>
                    </span>
                </li>
                <li class="list-group-item">
                    <small class="text-muted d-block">Monthly Fee</small>
                    <span class="fw-semibold text-success">
                        PKR <?= number_format($student['class_fee'], 0) ?>
                    </span>
                </li>
            </ul>
        </div>
    </div>

    <!-- ── RIGHT: Fee summary + history ──────────────────── -->
    <div class="col-md-8">

        <!-- Fee Summary Cards -->
        <div class="row g-3 mb-4">
            <div class="col-6 col-md-3">
                <div class="card text-center border-0 shadow-sm">
                    <div class="card-body py-3">
                        <div class="fs-4 fw-bold text-primary"><?= $fee_summary['total_months'] ?></div>
                        <div class="text-muted small">Total Months</div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card text-center border-0 shadow-sm">
                    <div class="card-body py-3">
                        <div class="fs-4 fw-bold text-success"><?= $fee_summary['paid_count'] ?></div>
                        <div class="text-muted small">Paid</div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card text-center border-0 shadow-sm">
                    <div class="card-body py-3">
                        <div class="fs-4 fw-bold text-danger"><?= $fee_summary['unpaid_count'] ?></div>
                        <div class="text-muted small">Unpaid</div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card text-center border-0 shadow-sm">
                    <div class="card-body py-3">
                        <div class="fs-6 fw-bold text-danger">
                            PKR <?= number_format($fee_summary['total_due'], 0) ?>
                        </div>
                        <div class="text-muted small">Total Due</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Fee History Table -->
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="fas fa-history me-2 text-primary"></i>Fee History</span>
                <a href="../fees/add.php?student_id=<?= $id ?>" class="btn btn-sm btn-success">
                    <i class="fas fa-plus me-1"></i>Add Payment
                </a>
            </div>
            <div class="card-body p-0">
                <?php if (mysqli_num_rows($fee_history) === 0): ?>
                    <div class="text-center text-muted py-4">
                        <i class="fas fa-receipt fa-2x mb-2 d-block opacity-25"></i>
                        No fee records yet.
                    </div>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Month</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th>Payment Date</th>
                                <th>Remarks</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($fee = mysqli_fetch_assoc($fee_history)): ?>
                            <tr>
                                <td class="fw-semibold"><?= htmlspecialchars($fee['month']) ?></td>
                                <td>PKR <?= number_format($fee['amount'], 0) ?></td>
                                <td>
                                    <?php if ($fee['status'] === 'Paid'): ?>
                                        <span class="badge bg-success">
                                            <i class="fas fa-check me-1"></i>Paid
                                        </span>
                                    <?php else: ?>
                                        <span class="badge bg-danger">
                                            <i class="fas fa-times me-1"></i>Unpaid
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-muted small">
                                    <?= $fee['payment_date']
                                        ? date('d M Y', strtotime($fee['payment_date']))
                                        : '—' ?>
                                </td>
                                <td class="text-muted small">
                                    <?= htmlspecialchars($fee['remarks'] ?? '—') ?>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        </div>

    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>