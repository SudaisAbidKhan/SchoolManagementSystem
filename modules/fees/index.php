<?php
// ============================================================
//  modules/fees/index.php
//  List all fee records with search, filter, summary
// ============================================================

$page_title = "Fees";
require_once '../../includes/header.php';
require_once '../../includes/sidebar.php';
require_once '../../config/db.php';

// ── Flash messages ───────────────────────────────────────────
$success = $_SESSION['success'] ?? ''; unset($_SESSION['success']);
$error   = $_SESSION['error']   ?? ''; unset($_SESSION['error']);

// ── Filters ──────────────────────────────────────────────────
$search   = trim($_GET['search']   ?? '');
$status   = trim($_GET['status']   ?? '');
$class_id = (int)($_GET['class_id'] ?? 0);
$month    = trim($_GET['month']    ?? '');

// ── Build WHERE ──────────────────────────────────────────────
$where  = "WHERE 1=1";
$params = [];
$types  = "";

if (!empty($search)) {
    $where   .= " AND (s.name LIKE ? OR s.father_name LIKE ?)";
    $like     = "%{$search}%";
    $params[] = $like;
    $params[] = $like;
    $types   .= "ss";
}
if (!empty($status)) {
    $where   .= " AND f.status = ?";
    $params[] = $status;
    $types   .= "s";
}
if ($class_id > 0) {
    $where   .= " AND s.class_id = ?";
    $params[] = $class_id;
    $types   .= "i";
}
if (!empty($month)) {
    $where   .= " AND f.month = ?";
    $params[] = $month;
    $types   .= "s";
}

// ── Fetch fee records ────────────────────────────────────────
$sql = "
    SELECT
        f.id, f.amount, f.month, f.status,
        f.payment_date, f.remarks,
        s.id   AS student_id,
        s.name AS student_name,
        c.name    AS class_name,
        c.section AS class_section
    FROM fees f
    JOIN students s ON f.student_id = s.id
    JOIN classes  c ON s.class_id   = c.id
    {$where}
    ORDER BY f.id DESC
";
$stmt = mysqli_prepare($conn, $sql);
if (!empty($params)) {
    mysqli_stmt_bind_param($stmt, $types, ...$params);
}
mysqli_stmt_execute($stmt);
$fees = mysqli_stmt_get_result($stmt);
mysqli_stmt_close($stmt);

// ── Summary stats ────────────────────────────────────────────
$summary = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT
        COUNT(*)                                                AS total,
        SUM(CASE WHEN status='Paid'   THEN 1    ELSE 0   END) AS paid_count,
        SUM(CASE WHEN status='Unpaid' THEN 1    ELSE 0   END) AS unpaid_count,
        COALESCE(SUM(CASE WHEN status='Paid'   THEN amount ELSE 0 END), 0) AS total_collected,
        COALESCE(SUM(CASE WHEN status='Unpaid' THEN amount ELSE 0 END), 0) AS total_due
    FROM fees
"));

// ── Distinct months for filter ───────────────────────────────
$months = mysqli_query($conn,
    "SELECT DISTINCT month FROM fees ORDER BY id DESC"
);

// ── All classes for filter ───────────────────────────────────
$all_classes = mysqli_query($conn,
    "SELECT id, name, section FROM classes ORDER BY name ASC, section ASC"
);
?>

<!-- ── PAGE HEADER ────────────────────────────────────────── -->
<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h4 class="fw-bold mb-0">Fee Management</h4>
        <small class="text-muted">Track and manage all student fee records</small>
    </div>
    <a href="add.php" class="btn btn-primary">
        <i class="fas fa-plus me-2"></i>Record Payment
    </a>
</div>

<!-- ── FLASH MESSAGES ────────────────────────────────────── -->
<?php if ($success): ?>
    <div class="alert alert-success alert-dismissible fade show">
        <i class="fas fa-check-circle me-2"></i><?= $success ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>
<?php if ($error): ?>
    <div class="alert alert-danger alert-dismissible fade show">
        <i class="fas fa-exclamation-circle me-2"></i><?= htmlspecialchars($error) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<!-- ── SUMMARY CARDS ──────────────────────────────────────── -->
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm text-center">
            <div class="card-body py-3">
                <div class="fs-3 fw-bold text-primary"><?= $summary['total'] ?></div>
                <div class="text-muted small">Total Records</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm text-center">
            <div class="card-body py-3">
                <div class="fs-3 fw-bold text-success"><?= $summary['paid_count'] ?></div>
                <div class="text-muted small">Paid</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm text-center">
            <div class="card-body py-3">
                <div class="fs-3 fw-bold text-danger"><?= $summary['unpaid_count'] ?></div>
                <div class="text-muted small">Unpaid</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm text-center">
            <div class="card-body py-3">
                <div class="fs-6 fw-bold text-danger">
                    PKR <?= number_format($summary['total_due'], 0) ?>
                </div>
                <div class="text-muted small">Total Outstanding</div>
            </div>
        </div>
    </div>
</div>

<!-- ── SEARCH & FILTERS ───────────────────────────────────── -->
<div class="card mb-3">
    <div class="card-body py-3">
        <form method="GET" action="index.php" class="row g-2 align-items-end">

            <!-- Search -->
            <div class="col-md-3">
                <label class="form-label small fw-semibold mb-1">Search Student</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-search"></i></span>
                    <input type="text" name="search" class="form-control"
                        placeholder="Name or father name..."
                        value="<?= htmlspecialchars($search) ?>">
                </div>
            </div>

            <!-- Class -->
            <div class="col-md-3">
                <label class="form-label small fw-semibold mb-1">Class</label>
                <select name="class_id" class="form-select">
                    <option value="0">All Classes</option>
                    <?php while ($cls = mysqli_fetch_assoc($all_classes)): ?>
                    <option value="<?= $cls['id'] ?>"
                        <?= $class_id === (int)$cls['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($cls['name']) ?>
                        <?= $cls['section'] ? '- ' . htmlspecialchars($cls['section']) : '' ?>
                    </option>
                    <?php endwhile; ?>
                </select>
            </div>

            <!-- Month -->
            <div class="col-md-2">
                <label class="form-label small fw-semibold mb-1">Month</label>
                <select name="month" class="form-select">
                    <option value="">All Months</option>
                    <?php while ($m = mysqli_fetch_assoc($months)): ?>
                    <option value="<?= htmlspecialchars($m['month']) ?>"
                        <?= $month === $m['month'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($m['month']) ?>
                    </option>
                    <?php endwhile; ?>
                </select>
            </div>

            <!-- Status -->
            <div class="col-md-2">
                <label class="form-label small fw-semibold mb-1">Status</label>
                <select name="status" class="form-select">
                    <option value="">All</option>
                    <option value="Paid"   <?= $status === 'Paid'   ? 'selected' : '' ?>>Paid</option>
                    <option value="Unpaid" <?= $status === 'Unpaid' ? 'selected' : '' ?>>Unpaid</option>
                </select>
            </div>

            <!-- Buttons -->
            <div class="col-md-2 d-flex gap-2">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="fas fa-filter me-1"></i>Filter
                </button>
                <a href="index.php" class="btn btn-outline-secondary">
                    <i class="fas fa-times"></i>
                </a>
            </div>

        </form>
    </div>
</div>

<!-- ── FEES TABLE ─────────────────────────────────────────── -->
<div class="card">
    <div class="card-header d-flex align-items-center justify-content-between">
        <span><i class="fas fa-money-bill-wave me-2 text-success"></i>Fee Records</span>
        <span class="badge bg-primary"><?= mysqli_num_rows($fees) ?> Records</span>
    </div>
    <div class="card-body p-0">
        <?php if (mysqli_num_rows($fees) === 0): ?>
            <div class="text-center text-muted py-5">
                <i class="fas fa-receipt fa-3x mb-3 d-block opacity-25"></i>
                No fee records found.
                <a href="add.php">Record the first payment</a>.
            </div>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Student</th>
                        <th>Class</th>
                        <th>Month</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th>Payment Date</th>
                        <th class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $i = 1; while ($row = mysqli_fetch_assoc($fees)): ?>
                    <tr>
                        <td class="text-muted small"><?= $i++ ?></td>
                        <td>
                            <a href="../students/view.php?id=<?= $row['student_id'] ?>"
                               class="fw-semibold text-decoration-none">
                                <?= htmlspecialchars($row['student_name']) ?>
                            </a>
                        </td>
                        <td>
                            <span class="badge bg-primary bg-opacity-10 text-primary">
                                <?= htmlspecialchars($row['class_name']) ?>
                                <?= $row['class_section']
                                    ? '- ' . htmlspecialchars($row['class_section'])
                                    : '' ?>
                            </span>
                        </td>
                        <td class="text-muted"><?= htmlspecialchars($row['month']) ?></td>
                        <td class="fw-semibold">
                            PKR <?= number_format($row['amount'], 0) ?>
                        </td>
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
                        <td class="text-center">
                            <a href="receipt.php?id=<?= $row['id'] ?>"
                               class="btn btn-sm btn-outline-secondary me-1"
                               title="Print Receipt" target="_blank">
                                <i class="fas fa-print"></i>
                            </a>
                            <a href="edit.php?id=<?= $row['id'] ?>"
                               class="btn btn-sm btn-outline-primary me-1" title="Edit">
                                <i class="fas fa-edit"></i>
                            </a>
                            <button
                                class="btn btn-sm btn-outline-danger" title="Delete"
                                onclick="confirmDelete(
                                    <?= $row['id'] ?>,
                                    '<?= htmlspecialchars($row['student_name'], ENT_QUOTES) ?>',
                                    '<?= htmlspecialchars($row['month'], ENT_QUOTES) ?>'
                                )"
                            >
                                <i class="fas fa-trash"></i>
                            </button>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- ── DELETE MODAL ───────────────────────────────────────── -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-0">
                <h5 class="modal-title text-danger">
                    <i class="fas fa-trash me-2"></i>Delete Fee Record
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Delete fee record for <strong id="feeName"></strong>
                   — <strong id="feeMonth"></strong>?</p>
                <p class="text-muted small mb-0">This action cannot be undone.</p>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-secondary"
                        data-bs-dismiss="modal">Cancel</button>
                <a href="#" id="deleteBtn" class="btn btn-danger">
                    <i class="fas fa-trash me-2"></i>Delete
                </a>
            </div>
        </div>
    </div>
</div>

<script>
function confirmDelete(id, name, month) {
    document.getElementById('feeName').textContent  = name;
    document.getElementById('feeMonth').textContent = month;
    document.getElementById('deleteBtn').href = 'delete.php?id=' + id;
    new bootstrap.Modal(document.getElementById('deleteModal')).show();
}
</script>

<?php require_once '../../includes/footer.php'; ?>