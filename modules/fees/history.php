<?php
// ============================================================
//  modules/fees/history.php
//  Full fee history for a specific student
// ============================================================

$page_title = "Fee History";
require_once '../../includes/header.php';
require_once '../../includes/sidebar.php';
require_once '../../config/db.php';

// ── Validate student ID ──────────────────────────────────────
$student_id = (int)($_GET['student_id'] ?? 0);
if ($student_id <= 0) {
    $_SESSION['error'] = "Invalid student ID.";
    header("Location: index.php");
    exit();
}

// ── Fetch student ────────────────────────────────────────────
$stmt = mysqli_prepare($conn, "
    SELECT s.*, c.name AS class_name, c.section AS class_section, c.fee AS class_fee
    FROM students s
    JOIN classes c ON s.class_id = c.id
    WHERE s.id = ? LIMIT 1
");
mysqli_stmt_bind_param($stmt, "i", $student_id);
mysqli_stmt_execute($stmt);
$student = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);

if (!$student) {
    $_SESSION['error'] = "Student not found.";
    header("Location: index.php");
    exit();
}

// ── Fee summary ──────────────────────────────────────────────
$summary = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT
        COUNT(*)                                                      AS total,
        SUM(CASE WHEN status='Paid'   THEN 1    ELSE 0   END)        AS paid_count,
        SUM(CASE WHEN status='Unpaid' THEN 1    ELSE 0   END)        AS unpaid_count,
        COALESCE(SUM(CASE WHEN status='Paid'   THEN amount ELSE 0 END), 0) AS total_paid,
        COALESCE(SUM(CASE WHEN status='Unpaid' THEN amount ELSE 0 END), 0) AS total_due
    FROM fees WHERE student_id = {$student_id}
"));

// ── Status filter ────────────────────────────────────────────
$filter_status = trim($_GET['status'] ?? '');
$status_where  = '';
if (in_array($filter_status, ['Paid', 'Unpaid'])) {
    $status_where = "AND status = '" . mysqli_real_escape_string($conn, $filter_status) . "'";
}

// ── Fee records ──────────────────────────────────────────────
$fees = mysqli_query($conn, "
    SELECT * FROM fees
    WHERE student_id = {$student_id} {$status_where}
    ORDER BY id DESC
");
?>

<!-- ── PAGE HEADER ────────────────────────────────────────── -->
<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h4 class="fw-bold mb-0">Fee History</h4>
        <small class="text-muted">
            All fee records for
            <strong><?= htmlspecialchars($student['name']) ?></strong>
        </small>
    </div>
    <div class="d-flex gap-2">
        <a href="add.php?student_id=<?= $student_id ?>"
           class="btn btn-success btn-sm">
            <i class="fas fa-plus me-1"></i>Add Payment
        </a>
        <a href="index.php" class="btn btn-outline-secondary btn-sm">
            <i class="fas fa-arrow-left me-1"></i>Back
        </a>
    </div>
</div>

<!-- ── STUDENT PROFILE CARD ───────────────────────────────── -->
<div class="card mb-4">
    <div class="card-body">
        <div class="row align-items-center g-3">
            <div class="col-auto">
                <div style="width:56px;height:56px;border-radius:50%;
                            background:#0d6efd22;color:#0d6efd;
                            font-weight:700;font-size:1.4rem;
                            display:flex;align-items:center;justify-content:center;">
                    <?= strtoupper(substr($student['name'], 0, 1)) ?>
                </div>
            </div>
            <div class="col">
                <h5 class="fw-bold mb-0"><?= htmlspecialchars($student['name']) ?></h5>
                <div class="text-muted small">
                    Father: <?= htmlspecialchars($student['father_name']) ?>
                    &nbsp;|&nbsp;
                    Class: <?= htmlspecialchars($student['class_name']) ?>
                    <?= $student['class_section']
                        ? '- ' . htmlspecialchars($student['class_section']) : '' ?>
                    &nbsp;|&nbsp;
                    Monthly Fee: <strong class="text-success">
                        PKR <?= number_format($student['class_fee'], 0) ?>
                    </strong>
                </div>
            </div>
            <div class="col-auto">
                <a href="../students/view.php?id=<?= $student_id ?>"
                   class="btn btn-sm btn-outline-primary">
                    <i class="fas fa-user me-1"></i>Full Profile
                </a>
            </div>
        </div>
    </div>
</div>

<!-- ── SUMMARY CARDS ──────────────────────────────────────── -->
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm text-center">
            <div class="card-body py-3">
                <div class="fs-3 fw-bold text-primary"><?= $summary['total'] ?></div>
                <div class="text-muted small">Total Months</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm text-center">
            <div class="card-body py-3">
                <div class="fs-3 fw-bold text-success"><?= $summary['paid_count'] ?></div>
                <div class="text-muted small">Paid Months</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm text-center">
            <div class="card-body py-3">
                <div class="fs-3 fw-bold text-danger"><?= $summary['unpaid_count'] ?></div>
                <div class="text-muted small">Unpaid Months</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm text-center">
            <div class="card-body py-3">
                <div class="fs-6 fw-bold text-success">
                    PKR <?= number_format($summary['total_paid'], 0) ?>
                </div>
                <div class="text-muted small">Total Paid</div>
            </div>
        </div>
    </div>
</div>

<!-- ── FILTER + TABLE ─────────────────────────────────────── -->
<div class="card">
    <div class="card-header d-flex align-items-center justify-content-between">
        <span>
            <i class="fas fa-history me-2 text-primary"></i>
            Payment Records
        </span>
        <div class="d-flex gap-2">
            <a href="?student_id=<?= $student_id ?>"
               class="btn btn-sm <?= $filter_status === '' ? 'btn-primary' : 'btn-outline-primary' ?>">
                All
            </a>
            <a href="?student_id=<?= $student_id ?>&status=Paid"
               class="btn btn-sm <?= $filter_status === 'Paid' ? 'btn-success' : 'btn-outline-success' ?>">
                Paid
            </a>
            <a href="?student_id=<?= $student_id ?>&status=Unpaid"
               class="btn btn-sm <?= $filter_status === 'Unpaid' ? 'btn-danger' : 'btn-outline-danger' ?>">
                Unpaid
            </a>
        </div>
    </div>
    <div class="card-body p-0">
        <?php if (mysqli_num_rows($fees) === 0): ?>
            <div class="text-center text-muted py-5">
                <i class="fas fa-receipt fa-3x mb-3 d-block opacity-25"></i>
                No fee records found.
            </div>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Month</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th>Payment Date</th>
                        <th>Remarks</th>
                        <th class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $i = 1; while ($row = mysqli_fetch_assoc($fees)): ?>
                    <tr class="<?= $row['status'] === 'Unpaid' ? 'table-danger bg-opacity-25' : '' ?>">
                        <td class="text-muted small"><?= $i++ ?></td>
                        <td class="fw-semibold"><?= htmlspecialchars($row['month']) ?></td>
                        <td>PKR <?= number_format($row['amount'], 0) ?></td>
                        <td>
                            <?php if ($row['status'] === 'Paid'): ?>
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
                            <?= $row['payment_date']
                                ? date('d M Y', strtotime($row['payment_date']))
                                : '—' ?>
                        </td>
                        <td class="text-muted small">
                            <?= htmlspecialchars($row['remarks'] ?? '—') ?>
                        </td>
                        <td class="text-center">
                            <a href="receipt.php?id=<?= $row['id'] ?>"
                               class="btn btn-sm btn-outline-secondary me-1"
                               title="Print Receipt" target="_blank">
                                <i class="fas fa-print"></i>
                            </a>
                            <a href="edit.php?id=<?= $row['id'] ?>"
                               class="btn btn-sm btn-outline-primary me-1"
                               title="Edit">
                                <i class="fas fa-edit"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>

        <!-- Outstanding due banner -->
        <?php if ($summary['total_due'] > 0): ?>
        <div class="p-3 border-top bg-danger bg-opacity-10">
            <i class="fas fa-exclamation-triangle text-danger me-2"></i>
            <strong class="text-danger">
                Outstanding balance:
                PKR <?= number_format($summary['total_due'], 0) ?>
            </strong>
            across <?= $summary['unpaid_count'] ?> unpaid month(s).
        </div>
        <?php endif; ?>

        <?php endif; ?>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>