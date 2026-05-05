<?php
// ============================================================
//  modules/expenses/add.php
//  Add a new expense record
// ============================================================

session_start();
require_once '../../config/db.php';

// ── Common expense titles for quick-fill ─────────────────────
$quick_titles = [
    'Electricity Bill', 'Water Bill', 'Gas Bill',
    'Internet Bill',    'Generator Fuel', 'Stationery Purchase',
    'Cleaning Supplies', 'Maintenance / Repair', 'Printing & Photocopying',
    'Staff Salary', 'Rent', 'Other',
];

$errors = [];
$input  = [
    'title'       => '',
    'amount'      => '',
    'date'        => date('Y-m-d'),
    'description' => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // ── Sanitize ─────────────────────────────────────────────
    $input['title']       = trim($_POST['title']       ?? '');
    $input['amount']      = trim($_POST['amount']      ?? '');
    $input['date']        = trim($_POST['date']        ?? '');
    $input['description'] = trim($_POST['description'] ?? '');

    // ── Validate ─────────────────────────────────────────────
    if (empty($input['title'])) {
        $errors[] = "Expense title is required.";
    } elseif (strlen($input['title']) > 200) {
        $errors[] = "Title cannot exceed 200 characters.";
    }
    if (empty($input['amount']) || !is_numeric($input['amount'])
        || (float)$input['amount'] <= 0) {
        $errors[] = "A valid amount greater than zero is required.";
    }
    if (empty($input['date'])) {
        $errors[] = "Date is required.";
    } elseif (strtotime($input['date']) > strtotime(date('Y-m-d'))) {
        $errors[] = "Date cannot be in the future.";
    }

    // ── Insert ───────────────────────────────────────────────
    if (empty($errors)) {
        $amount      = (float)$input['amount'];
        $description = !empty($input['description']) ? $input['description'] : null;

        $stmt = mysqli_prepare($conn,
            "INSERT INTO expenses (title, amount, date, description) VALUES (?, ?, ?, ?)"
        );
        mysqli_stmt_bind_param($stmt, "sdss",
            $input['title'],
            $amount,
            $input['date'],
            $description
        );

        if (mysqli_stmt_execute($stmt)) {
            $_SESSION['success'] = "Expense '<strong>" .
                htmlspecialchars($input['title']) .
                "</strong>' of PKR " .
                number_format($amount, 0) . " recorded successfully.";
            header("Location: index.php");
            exit();
        } else {
            $errors[] = "Database error: " . mysqli_stmt_error($stmt);
        }
        mysqli_stmt_close($stmt);
    }
}

$page_title = "Add Expense";
require_once '../../includes/header.php';
require_once '../../includes/sidebar.php';
?>

<!-- ── PAGE HEADER ────────────────────────────────────────── -->
<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h4 class="fw-bold mb-0">Add Expense</h4>
        <small class="text-muted">Record a new school expense</small>
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

<div class="row g-4">

    <!-- ── FORM ──────────────────────────────────────────── -->
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <i class="fas fa-receipt me-2 text-danger"></i>Expense Details
            </div>
            <div class="card-body">
                <form action="add.php" method="POST" id="expenseForm">
                    <div class="row g-3">

                        <!-- Title -->
                        <div class="col-12">
                            <label class="form-label fw-semibold">
                                Title <span class="text-danger">*</span>
                            </label>
                            <input
                                type="text"
                                name="title"
                                id="titleInput"
                                class="form-control"
                                placeholder="e.g. Electricity Bill"
                                value="<?= htmlspecialchars($input['title']) ?>"
                                maxlength="200"
                                required
                            >
                            <div class="form-text">
                                <span id="titleCount">0</span>/200 characters
                            </div>
                        </div>

                        <!-- Amount -->
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">
                                Amount (PKR) <span class="text-danger">*</span>
                            </label>
                            <div class="input-group">
                                <span class="input-group-text">PKR</span>
                                <input
                                    type="number"
                                    name="amount"
                                    class="form-control"
                                    placeholder="e.g. 4500"
                                    min="1"
                                    step="0.01"
                                    value="<?= htmlspecialchars($input['amount']) ?>"
                                    required
                                >
                            </div>
                        </div>

                        <!-- Date -->
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">
                                Date <span class="text-danger">*</span>
                            </label>
                            <input
                                type="date"
                                name="date"
                                class="form-control"
                                value="<?= htmlspecialchars($input['date']) ?>"
                                max="<?= date('Y-m-d') ?>"
                                required
                            >
                        </div>

                        <!-- Description -->
                        <div class="col-12">
                            <label class="form-label fw-semibold">
                                Description
                                <span class="text-muted">(Optional)</span>
                            </label>
                            <textarea
                                name="description"
                                class="form-control"
                                rows="3"
                                placeholder="Additional details about this expense..."
                            ><?= htmlspecialchars($input['description']) ?></textarea>
                        </div>

                    </div><!-- /.row -->

                    <div class="d-flex gap-2 mt-4">
                        <button type="submit" class="btn btn-primary px-4">
                            <i class="fas fa-save me-2"></i>Save Expense
                        </button>
                        <a href="index.php" class="btn btn-outline-secondary">
                            Cancel
                        </a>
                    </div>

                </form>
            </div>
        </div>
    </div>

    <!-- ── QUICK TITLES SIDEBAR ───────────────────────────── -->
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <i class="fas fa-bolt me-2 text-warning"></i>Quick Fill Titles
            </div>
            <div class="card-body p-2">
                <p class="text-muted small px-2 mb-2">
                    Click a title to auto-fill the form:
                </p>
                <div class="d-flex flex-wrap gap-2 p-2">
                    <?php foreach ($quick_titles as $qt): ?>
                    <button
                        type="button"
                        class="btn btn-sm btn-outline-secondary quick-title"
                        data-title="<?= htmlspecialchars($qt) ?>"
                    >
                        <?= htmlspecialchars($qt) ?>
                    </button>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- This month so far -->
        <div class="card mt-3">
            <div class="card-header">
                <i class="fas fa-chart-pie me-2 text-danger"></i>
                <?= date('F Y') ?> So Far
            </div>
            <div class="card-body">
                <?php
                $month_rows = mysqli_query($conn, "
                    SELECT title, amount
                    FROM expenses
                    WHERE DATE_FORMAT(date,'%Y-%m') = DATE_FORMAT(CURDATE(),'%Y-%m')
                    ORDER BY amount DESC
                    LIMIT 5
                ");
                $month_sum = mysqli_fetch_assoc(mysqli_query($conn, "
                    SELECT COALESCE(SUM(amount),0) AS total
                    FROM expenses
                    WHERE DATE_FORMAT(date,'%Y-%m') = DATE_FORMAT(CURDATE(),'%Y-%m')
                "))['total'];
                ?>
                <div class="fw-bold text-danger mb-2">
                    PKR <?= number_format($month_sum, 0) ?>
                    <small class="text-muted fw-normal">total this month</small>
                </div>
                <?php if (mysqli_num_rows($month_rows) > 0): ?>
                <ul class="list-unstyled mb-0 small">
                    <?php while ($mr = mysqli_fetch_assoc($month_rows)): ?>
                    <li class="d-flex justify-content-between border-bottom py-1">
                        <span class="text-muted">
                            <?= htmlspecialchars($mr['title']) ?>
                        </span>
                        <span class="fw-semibold">
                            PKR <?= number_format($mr['amount'], 0) ?>
                        </span>
                    </li>
                    <?php endwhile; ?>
                </ul>
                <?php else: ?>
                    <p class="text-muted small mb-0">No expenses this month yet.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

</div>

<script>
// ── Title character counter ──────────────────────────────────
const titleInput = document.getElementById('titleInput');
const titleCount = document.getElementById('titleCount');
titleInput.addEventListener('input', () => {
    titleCount.textContent = titleInput.value.length;
});
titleCount.textContent = titleInput.value.length;

// ── Quick fill buttons ───────────────────────────────────────
document.querySelectorAll('.quick-title').forEach(btn => {
    btn.addEventListener('click', () => {
        titleInput.value = btn.dataset.title;
        titleCount.textContent = titleInput.value.length;
        titleInput.focus();
        // Highlight active
        document.querySelectorAll('.quick-title')
            .forEach(b => b.classList.remove('btn-primary','text-white'));
        btn.classList.add('btn-primary', 'text-white');
    });
});
</script>

<?php require_once '../../includes/footer.php'; ?>