<?php
// ============================================================
//  modules/fees/edit.php
//  Edit an existing fee record
// ============================================================

session_start();
require_once '../../config/db.php';

// ── Validate ID ──────────────────────────────────────────────
$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    $_SESSION['error'] = "Invalid fee record ID.";
    header("Location: index.php");
    exit();
}

// ── Fetch fee record ─────────────────────────────────────────
$stmt = mysqli_prepare($conn, "
    SELECT f.*, s.name AS student_name, s.father_name,
           c.name AS class_name, c.section AS class_section, c.fee AS class_fee
    FROM fees f
    JOIN students s ON f.student_id = s.id
    JOIN classes  c ON s.class_id   = c.id
    WHERE f.id = ? LIMIT 1
");
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$fee = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);

if (!$fee) {
    $_SESSION['error'] = "Fee record not found.";
    header("Location: index.php");
    exit();
}

// ── Month options ────────────────────────────────────────────
$month_options = [];
for ($i = 0; $i < 12; $i++) {
    $month_options[] = date('F Y', strtotime("-$i months"));
}
// Include existing month if not in last 12
if (!in_array($fee['month'], $month_options)) {
    $month_options[] = $fee['month'];
}

$errors = [];
$input  = [
    'amount'       => $fee['amount'],
    'month'        => $fee['month'],
    'status'       => $fee['status'],
    'payment_date' => $fee['payment_date'] ?? '',
    'remarks'      => $fee['remarks']      ?? '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // ── Sanitize ─────────────────────────────────────────────
    $input['amount']       = trim($_POST['amount']       ?? '');
    $input['month']        = trim($_POST['month']        ?? '');
    $input['status']       = trim($_POST['status']       ?? '');
    $input['payment_date'] = trim($_POST['payment_date'] ?? '');
    $input['remarks']      = trim($_POST['remarks']      ?? '');

    // ── Validate ─────────────────────────────────────────────
    if (empty($input['amount']) || !is_numeric($input['amount'])
        || (float)$input['amount'] <= 0) {
        $errors[] = "A valid fee amount is required.";
    }
    if (empty($input['month'])) {
        $errors[] = "Please select a month.";
    }
    if (!in_array($input['status'], ['Paid', 'Unpaid'])) {
        $errors[] = "Invalid payment status.";
    }
    if ($input['status'] === 'Paid' && empty($input['payment_date'])) {
        $errors[] = "Payment date is required for paid fees.";
    }

    // ── Duplicate check (exclude self) ───────────────────────
    if (empty($errors)) {
        $dup = mysqli_prepare($conn,
            "SELECT id FROM fees
             WHERE student_id = ? AND month = ? AND id != ?
             LIMIT 1"
        );
        mysqli_stmt_bind_param($dup, "isi",
            $fee['student_id'], $input['month'], $id
        );
        mysqli_stmt_execute($dup);
        mysqli_stmt_store_result($dup);
        if (mysqli_stmt_num_rows($dup) > 0) {
            $errors[] = "A fee record for this student and month already exists.";
        }
        mysqli_stmt_close($dup);
    }

    // ── Update ───────────────────────────────────────────────
    if (empty($errors)) {
        $payment_date = $input['status'] === 'Paid'
            ? $input['payment_date'] : null;
        $remarks = !empty($input['remarks']) ? $input['remarks'] : null;
        $amount  = (float)$input['amount'];

        $stmt = mysqli_prepare($conn, "
            UPDATE fees
            SET amount=?, month=?, status=?, payment_date=?, remarks=?
            WHERE id=?
        ");
        mysqli_stmt_bind_param($stmt, "dssssi",
            $amount,
            $input['month'],
            $input['status'],
            $payment_date,
            $remarks,
            $id
        );

        if (mysqli_stmt_execute($stmt)) {
            $_SESSION['success'] = "Fee record updated successfully.";
            header("Location: index.php");
            exit();
        } else {
            $errors[] = "Database error: " . mysqli_stmt_error($stmt);
        }
        mysqli_stmt_close($stmt);
    }
}

$page_title = "Edit Fee Record";
require_once '../../includes/header.php';
require_once '../../includes/sidebar.php';
?>

<!-- ── PAGE HEADER ────────────────────────────────────────── -->
<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h4 class="fw-bold mb-0">Edit Fee Record</h4>
        <small class="text-muted">Update payment details</small>
    </div>
    <div class="d-flex gap-2">
        <a href="receipt.php?id=<?= $id ?>"
           class="btn btn-outline-secondary" target="_blank">
            <i class="fas fa-print me-2"></i>Receipt
        </a>
        <a href="index.php" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left me-2"></i>Back
        </a>
    </div>
</div>

<!-- ── STUDENT INFO BOX ───────────────────────────────────── -->
<div class="alert alert-light border mb-4">
    <div class="row g-2 align-items-center">
        <div class="col-auto">
            <div style="width:42px;height:42px;border-radius:50%;
                        background:#0d6efd22;color:#0d6efd;
                        font-weight:700;font-size:1.1rem;
                        display:flex;align-items:center;justify-content:center;">
                <?= strtoupper(substr($fee['student_name'], 0, 1)) ?>
            </div>
        </div>
        <div class="col">
            <div class="fw-semibold"><?= htmlspecialchars($fee['student_name']) ?></div>
            <small class="text-muted">
                <?= htmlspecialchars($fee['father_name']) ?> &mdash;
                <?= htmlspecialchars($fee['class_name']) ?>
                <?= $fee['class_section']
                    ? '- ' . htmlspecialchars($fee['class_section']) : '' ?>
                &mdash; Class Fee: PKR <?= number_format($fee['class_fee'], 0) ?>
            </small>
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
<div class="card" style="max-width: 620px;">
    <div class="card-header">
        <i class="fas fa-edit me-2 text-primary"></i>Edit Payment Details
    </div>
    <div class="card-body">
        <form action="edit.php?id=<?= $id ?>" method="POST">
            <div class="row g-3">

                <!-- Month -->
                <div class="col-md-6">
                    <label class="form-label fw-semibold">
                        Month <span class="text-danger">*</span>
                    </label>
                    <select name="month" class="form-select" required>
                        <?php foreach ($month_options as $mo): ?>
                        <option value="<?= $mo ?>"
                            <?= $input['month'] === $mo ? 'selected' : '' ?>>
                            <?= $mo ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
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

                <!-- Status -->
                <div class="col-md-6">
                    <label class="form-label fw-semibold">
                        Status <span class="text-danger">*</span>
                    </label>
                    <select name="status" id="statusSelect"
                            class="form-select" required>
                        <option value="Paid"
                            <?= $input['status'] === 'Paid' ? 'selected' : '' ?>>
                            Paid
                        </option>
                        <option value="Unpaid"
                            <?= $input['status'] === 'Unpaid' ? 'selected' : '' ?>>
                            Unpaid
                        </option>
                    </select>
                </div>

                <!-- Payment Date -->
                <div class="col-md-6" id="paymentDateWrap">
                    <label class="form-label fw-semibold">
                        Payment Date <span class="text-danger">*</span>
                    </label>
                    <input
                        type="date"
                        name="payment_date"
                        id="paymentDate"
                        class="form-control"
                        value="<?= htmlspecialchars($input['payment_date']) ?>"
                        max="<?= date('Y-m-d') ?>"
                    >
                </div>

                <!-- Remarks -->
                <div class="col-12">
                    <label class="form-label fw-semibold">Remarks</label>
                    <input
                        type="text"
                        name="remarks"
                        class="form-control"
                        placeholder="Optional note"
                        value="<?= htmlspecialchars($input['remarks']) ?>"
                    >
                </div>

            </div>

            <div class="d-flex gap-2 mt-4">
                <button type="submit" class="btn btn-primary px-4">
                    <i class="fas fa-save me-2"></i>Update Record
                </button>
                <a href="index.php" class="btn btn-outline-secondary">Cancel</a>
            </div>

        </form>
    </div>
</div>

<script>
const statusSelect     = document.getElementById('statusSelect');
const paymentDateWrap  = document.getElementById('paymentDateWrap');
const paymentDateInput = document.getElementById('paymentDate');

function togglePaymentDate() {
    if (statusSelect.value === 'Unpaid') {
        paymentDateWrap.style.opacity = '0.4';
        paymentDateInput.removeAttribute('required');
        paymentDateInput.value = '';
    } else {
        paymentDateWrap.style.opacity = '1';
        paymentDateInput.setAttribute('required', 'required');
        if (!paymentDateInput.value) {
            paymentDateInput.value = '<?= date('Y-m-d') ?>';
        }
    }
}
statusSelect.addEventListener('change', togglePaymentDate);
togglePaymentDate();
</script>

<?php require_once '../../includes/footer.php'; ?>