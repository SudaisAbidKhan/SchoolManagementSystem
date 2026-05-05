<?php
// ============================================================
//  modules/expenses/edit.php
//  Edit an existing expense record
// ============================================================

session_start();
require_once '../../config/db.php';

// ── Validate ID ──────────────────────────────────────────────
$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    $_SESSION['error'] = "Invalid expense ID.";
    header("Location: index.php");
    exit();
}

// ── Fetch record ─────────────────────────────────────────────
$stmt = mysqli_prepare($conn,
    "SELECT * FROM expenses WHERE id = ? LIMIT 1"
);
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$expense = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);

if (!$expense) {
    $_SESSION['error'] = "Expense record not found.";
    header("Location: index.php");
    exit();
}

$errors = [];
$input  = [
    'title'       => $expense['title'],
    'amount'      => $expense['amount'],
    'date'        => $expense['date'],
    'description' => $expense['description'] ?? '',
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

    // ── Update ───────────────────────────────────────────────
    if (empty($errors)) {
        $amount      = (float)$input['amount'];
        $description = !empty($input['description']) ? $input['description'] : null;

        $stmt = mysqli_prepare($conn, "
            UPDATE expenses
            SET title = ?, amount = ?, date = ?, description = ?
            WHERE id = ?
        ");
        mysqli_stmt_bind_param($stmt, "sdssi",
            $input['title'],
            $amount,
            $input['date'],
            $description,
            $id
        );

        if (mysqli_stmt_execute($stmt)) {
            $_SESSION['success'] = "Expense '<strong>" .
                htmlspecialchars($input['title']) .
                "</strong>' updated successfully.";
            header("Location: index.php");
            exit();
        } else {
            $errors[] = "Database error: " . mysqli_stmt_error($stmt);
        }
        mysqli_stmt_close($stmt);
    }
}

$page_title = "Edit Expense";
require_once '../../includes/header.php';
require_once '../../includes/sidebar.php';
?>

<!-- ── PAGE HEADER ────────────────────────────────────────── -->
<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h4 class="fw-bold mb-0">Edit Expense</h4>
        <small class="text-muted">Update expense details</small>
    </div>
    <a href="index.php" class="btn btn-outline-secondary">
        <i class="fas fa-arrow-left me-2"></i>Back
    </a>
</div>

<!-- ── ORIGINAL RECORD INFO ───────────────────────────────── -->
<div class="alert alert-light border mb-4">
    <div class="row g-2 small text-muted">
        <div class="col-auto">
            <i class="fas fa-receipt me-1 text-danger"></i>
            <strong>Original Title:</strong>
            <?= htmlspecialchars($expense['title']) ?>
        </div>
        <div class="col-auto">|
            <i class="fas fa-money-bill ms-1 me-1"></i>
            <strong>Original Amount:</strong>
            PKR <?= number_format($expense['amount'], 0) ?>
        </div>
        <div class="col-auto">|
            <i class="fas fa-calendar ms-1 me-1"></i>
            <strong>Date:</strong>
            <?= date('d M Y', strtotime($expense['date'])) ?>
        </div>
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
<div class="card" style="max-width: 680px;">
    <div class="card-header">
        <i class="fas fa-edit me-2 text-primary"></i>Edit Expense Details
    </div>
    <div class="card-body">
        <form action="edit.php?id=<?= $id ?>" method="POST">
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
                        maxlength="200"
                        value="<?= htmlspecialchars($input['title']) ?>"
                        required
                    >
                    <div class="form-text">
                        <span id="titleCount">
                            <?= strlen($input['title']) ?>
                        </span>/200 characters
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
                        Description <span class="text-muted">(Optional)</span>
                    </label>
                    <textarea
                        name="description"
                        class="form-control"
                        rows="3"
                        placeholder="Additional details..."
                    ><?= htmlspecialchars($input['description']) ?></textarea>
                </div>

            </div><!-- /.row -->

            <div class="d-flex gap-2 mt-4">
                <button type="submit" class="btn btn-primary px-4">
                    <i class="fas fa-save me-2"></i>Update Expense
                </button>
                <a href="index.php" class="btn btn-outline-secondary">Cancel</a>
            </div>

        </form>
    </div>
</div>

<script>
const titleInput = document.getElementById('titleInput');
const titleCount = document.getElementById('titleCount');
titleInput.addEventListener('input', () => {
    titleCount.textContent = titleInput.value.length;
});
</script>

<?php require_once '../../includes/footer.php'; ?>