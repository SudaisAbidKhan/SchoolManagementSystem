<?php
// ============================================================
//  modules/expenses/index.php
//  List all expenses with search, filter, summary
// ============================================================

$page_title = "Expenses";
require_once '../../includes/header.php';
require_once '../../includes/sidebar.php';
require_once '../../config/db.php';

// ── Flash messages ───────────────────────────────────────────
$success = $_SESSION['success'] ?? ''; unset($_SESSION['success']);
$error   = $_SESSION['error']   ?? ''; unset($_SESSION['error']);

// ── Filters ──────────────────────────────────────────────────
$search     = trim($_GET['search']     ?? '');
$month_year = trim($_GET['month_year'] ?? '');  // format: YYYY-MM

// ── Build WHERE ──────────────────────────────────────────────
$where  = "WHERE 1=1";
$params = [];
$types  = "";

if (!empty($search)) {
    $where   .= " AND (title LIKE ? OR description LIKE ?)";
    $like     = "%{$search}%";
    $params[] = $like;
    $params[] = $like;
    $types   .= "ss";
}
if (!empty($month_year)) {
    $where   .= " AND DATE_FORMAT(date, '%Y-%m') = ?";
    $params[] = $month_year;
    $types   .= "s";
}

// ── Fetch expenses ───────────────────────────────────────────
$sql  = "SELECT * FROM expenses {$where} ORDER BY date DESC, id DESC";
$stmt = mysqli_prepare($conn, $sql);
if (!empty($params)) {
    mysqli_stmt_bind_param($stmt, $types, ...$params);
}
mysqli_stmt_execute($stmt);
$expenses = mysqli_stmt_get_result($stmt);
mysqli_stmt_close($stmt);

// ── Global summary ───────────────────────────────────────────
$global = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT
        COUNT(*)                  AS total_records,
        COALESCE(SUM(amount), 0)  AS total_amount,
        COALESCE(SUM(CASE WHEN DATE_FORMAT(date,'%Y-%m') = DATE_FORMAT(CURDATE(),'%Y-%m')
                          THEN amount ELSE 0 END), 0) AS this_month,
        COALESCE(SUM(CASE WHEN DATE_FORMAT(date,'%Y-%m') = DATE_FORMAT(
                          DATE_SUB(CURDATE(), INTERVAL 1 MONTH),'%Y-%m')
                          THEN amount ELSE 0 END), 0) AS last_month
    FROM expenses
"));

// ── Distinct months for filter dropdown ──────────────────────
$distinct_months = mysqli_query($conn, "
    SELECT DISTINCT DATE_FORMAT(date, '%Y-%m') AS ym,
                    DATE_FORMAT(date, '%M %Y') AS label
    FROM expenses
    ORDER BY ym DESC
");
?>

<!-- ── PAGE HEADER ────────────────────────────────────────── -->
<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h4 class="fw-bold mb-0">Expenses</h4>
        <small class="text-muted">Track all school operational expenses</small>
    </div>
    <div class="d-flex gap-2">
        <a href="report.php" class="btn btn-outline-primary">
            <i class="fas fa-chart-bar me-2"></i>Monthly Report
        </a>
        <a href="add.php" class="btn btn-primary">
            <i class="fas fa-plus me-2"></i>Add Expense
        </a>
    </div>
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
                <div class="fs-3 fw-bold text-primary">
                    <?= $global['total_records'] ?>
                </div>
                <div class="text-muted small">Total Records</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm text-center">
            <div class="card-body py-3">
                <div class="fs-6 fw-bold text-danger">
                    PKR <?= number_format($global['total_amount'], 0) ?>
                </div>
                <div class="text-muted small">All Time Total</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm text-center">
            <div class="card-body py-3">
                <div class="fs-6 fw-bold text-warning">
                    PKR <?= number_format($global['this_month'], 0) ?>
                </div>
                <div class="text-muted small">This Month</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm text-center">
            <div class="card-body py-3">
                <div class="fs-6 fw-bold text-secondary">
                    PKR <?= number_format($global['last_month'], 0) ?>
                </div>
                <div class="text-muted small">Last Month</div>
            </div>
        </div>
    </div>
</div>

<!-- ── SEARCH & FILTER ────────────────────────────────────── -->
<div class="card mb-3">
    <div class="card-body py-3">
        <form method="GET" action="index.php" class="row g-2 align-items-end">

            <!-- Search -->
            <div class="col-md-5">
                <label class="form-label small fw-semibold mb-1">Search</label>
                <div class="input-group">
                    <span class="input-group-text">
                        <i class="fas fa-search"></i>
                    </span>
                    <input
                        type="text"
                        name="search"
                        class="form-control"
                        placeholder="Title or description..."
                        value="<?= htmlspecialchars($search) ?>"
                    >
                </div>
            </div>

            <!-- Month -->
            <div class="col-md-3">
                <label class="form-label small fw-semibold mb-1">Month</label>
                <select name="month_year" class="form-select">
                    <option value="">All Months</option>
                    <?php while ($m = mysqli_fetch_assoc($distinct_months)): ?>
                    <option value="<?= $m['ym'] ?>"
                        <?= $month_year === $m['ym'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($m['label']) ?>
                    </option>
                    <?php endwhile; ?>
                </select>
            </div>

            <!-- Buttons -->
            <div class="col-md-4 d-flex gap-2">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="fas fa-filter me-1"></i>Filter
                </button>
                <a href="index.php" class="btn btn-outline-secondary">
                    <i class="fas fa-times"></i>
                </a>
                <?php if (!empty($month_year)): ?>
                <a href="report.php?month_year=<?= urlencode($month_year) ?>"
                   class="btn btn-outline-info" title="View Report for This Month">
                    <i class="fas fa-chart-bar"></i>
                </a>
                <?php endif; ?>
            </div>

        </form>
    </div>
</div>

<!-- ── EXPENSES TABLE ─────────────────────────────────────── -->
<div class="card">
    <div class="card-header d-flex align-items-center justify-content-between">
        <span>
            <i class="fas fa-receipt me-2 text-danger"></i>Expense Records
        </span>
        <span class="badge bg-primary"><?= mysqli_num_rows($expenses) ?> Records</span>
    </div>
    <div class="card-body p-0">

        <?php if (mysqli_num_rows($expenses) === 0): ?>
            <div class="text-center text-muted py-5">
                <i class="fas fa-receipt fa-3x mb-3 d-block opacity-25"></i>
                <?= !empty($search) || !empty($month_year)
                    ? 'No expenses match your search.'
                    : 'No expenses recorded yet. <a href="add.php">Add the first one</a>.' ?>
            </div>

        <?php else:
            // ── Group by month for visual separation ─────────
            $rows        = [];
            $month_totals = [];
            while ($row = mysqli_fetch_assoc($expenses)) {
                $key = date('F Y', strtotime($row['date']));
                $rows[$key][]          = $row;
                $month_totals[$key]    = ($month_totals[$key] ?? 0) + $row['amount'];
            }
        ?>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Title</th>
                        <th>Description</th>
                        <th>Date</th>
                        <th>Amount</th>
                        <th class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $i = 1;
                    foreach ($rows as $month_label => $month_rows):
                    ?>
                    <!-- Month group header -->
                    <tr class="table-light">
                        <td colspan="4" class="fw-bold text-primary py-2">
                            <i class="fas fa-calendar-alt me-2"></i><?= $month_label ?>
                        </td>
                        <td class="fw-bold text-danger">
                            PKR <?= number_format($month_totals[$month_label], 0) ?>
                        </td>
                        <td></td>
                    </tr>

                    <?php foreach ($month_rows as $row): ?>
                    <tr>
                        <td class="text-muted small"><?= $i++ ?></td>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <div class="exp-icon">
                                    <i class="fas fa-receipt"></i>
                                </div>
                                <span class="fw-semibold">
                                    <?= htmlspecialchars($row['title']) ?>
                                </span>
                            </div>
                        </td>
                        <td class="text-muted small" style="max-width:220px;">
                            <?php if (!empty($row['description'])): ?>
                                <span title="<?= htmlspecialchars($row['description']) ?>">
                                    <?= htmlspecialchars(
                                        strlen($row['description']) > 60
                                            ? substr($row['description'], 0, 60) . '…'
                                            : $row['description']
                                    ) ?>
                                </span>
                            <?php else: ?>
                                <span class="text-muted">—</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-muted small">
                            <?= date('d M Y', strtotime($row['date'])) ?>
                        </td>
                        <td class="fw-semibold text-danger">
                            PKR <?= number_format($row['amount'], 0) ?>
                        </td>
                        <td class="text-center">
                            <a href="edit.php?id=<?= $row['id'] ?>"
                               class="btn btn-sm btn-outline-primary me-1"
                               title="Edit">
                                <i class="fas fa-edit"></i>
                            </a>
                            <button
                                class="btn btn-sm btn-outline-danger"
                                title="Delete"
                                onclick="confirmDelete(
                                    <?= $row['id'] ?>,
                                    '<?= htmlspecialchars($row['title'], ENT_QUOTES) ?>',
                                    '<?= number_format($row['amount'], 0) ?>'
                                )"
                            >
                                <i class="fas fa-trash"></i>
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endforeach; ?>
                </tbody>

                <!-- Grand total footer -->
                <?php
                $filtered_total = array_sum($month_totals);
                ?>
                <tfoot>
                    <tr class="table-dark">
                        <td colspan="4" class="fw-bold text-end">
                            <?= !empty($search) || !empty($month_year)
                                ? 'Filtered Total:'
                                : 'Grand Total:' ?>
                        </td>
                        <td class="fw-bold">
                            PKR <?= number_format($filtered_total, 0) ?>
                        </td>
                        <td></td>
                    </tr>
                </tfoot>

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
                    <i class="fas fa-trash me-2"></i>Delete Expense
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Delete expense <strong id="expTitle"></strong>
                   worth <strong id="expAmount"></strong>?</p>
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

<style>
.exp-icon {
    width: 32px; height: 32px;
    border-radius: 8px;
    background: #dc354522;
    color: #dc3545;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.8rem;
    flex-shrink: 0;
}
</style>

<script>
function confirmDelete(id, title, amount) {
    document.getElementById('expTitle').textContent  = title;
    document.getElementById('expAmount').textContent = 'PKR ' + amount;
    document.getElementById('deleteBtn').href = 'delete.php?id=' + id;
    new bootstrap.Modal(document.getElementById('deleteModal')).show();
}
</script>

<?php require_once '../../includes/footer.php'; ?>